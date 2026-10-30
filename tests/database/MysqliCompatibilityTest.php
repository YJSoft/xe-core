<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class MysqliCompatibilityTest extends CompatibilityTestCase
{
	private $connections;
	private $reportMode;
	private $dbSettings;
	private $installDatabases;

	protected function _before()
	{
		parent::_before();
		$this->connections = array();
		$this->installDatabases = array();
		$driver = new mysqli_driver();
		$this->reportMode = $driver->report_mode;
		$config = $this->getModule('Db')->_getConfig();
		preg_match('/(?:^|;)host=([^;]+)/', substr($config['dsn'], 6), $host);
		preg_match('/(?:^|;)dbname=([^;]+)/', substr($config['dsn'], 6), $database);
		preg_match('/(?:^|;)port=([^;]+)/', substr($config['dsn'], 6), $port);
		$this->dbSettings = array('db_hostname' => $host[1], 'db_port' => isset($port[1]) ? (int)$port[1] : 3306, 'db_database' => $database[1], 'db_userid' => $config['user'], 'db_password' => $config['password'], 'db_table_prefix' => 'xe_');
	}

	protected function _after()
	{
		foreach($this->installDatabases as $db) $db->close();
		foreach($this->connections as $connection) mysqli_close($connection);
		mysqli_report($this->reportMode);
		parent::_after();
	}

	private function connect($class, $prepared = true)
	{
		require_once _XE_PATH_ . 'classes/db/' . $class . '.class.php';
		Context::setDBInfo((object)array('master_db' => $this->dbSettings, 'slave_db' => array($this->dbSettings), 'use_prepared_statements' => $prepared ? 'Y' : 'N'));
		$db = new $class(false);
		// Force strict reporting even on PHP versions whose default is OFF.
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$connection = $db->__connect($this->dbSettings);
		$this->assertTrue($connection !== false && $connection !== null, $db->errstr);
		$this->connections[] = $connection;
		$db->master_db['resource'] = $connection;
		$db->master_db['is_connected'] = true;
		$db->param = array();
		return $db;
	}

	public static function drivers()
	{
		return array(array('DBMysqli'), array('DBMysqli_innodb'));
	}

	public function testInstallerPrefersPdoAndSavesInnodbUsingFreshConnectionSettings()
	{
		$controller = new installController();
		$controller->db_tmp_config_file = $this->fixtureDir . 'tmpDB.config.php';
		Context::getInstance()->request_method = 'JSON';
		foreach(array('first_', 'second_') as $prefix)
		{
			$settings = $this->dbSettings;
			$settings['db_type'] = 'mysql';
			$settings['db_table_prefix'] = $prefix;
			foreach($settings as $key => $value) Context::set($key, $value);
			$this->assertNull($controller->_procDBSetting());
			$db = DB::getInstance();
			$this->installDatabases[] = $db;
			$this->assertInstanceOf('DBPdo_mysql_innodb', $db);
			$this->assertSame($prefix, $db->prefix);
			include $controller->db_tmp_config_file;
			$this->assertSame('pdo_mysql_innodb', $db_info->master_db['db_type']);
			$this->assertSame('pdo_mysql_innodb', $db_info->slave_db[0]['db_type']);
		}
		// A failed retry must not reuse the preceding successful connection or config.
		$saved = FileHandler::readFile($controller->db_tmp_config_file);
		Context::set('db_database', $this->fixtureName . '_missing');
		$output = $controller->_procDBSetting();
		$this->assertFalse($output->toBool());
		$this->assertSame($saved, FileHandler::readFile($controller->db_tmp_config_file));
	}

	public function testExistingMyisamConfigurationIsNotChangedByInstallerChoices()
	{
		$db = $this->connect('DBMysqli', false);
		$info = Context::getDBInfo();
		$info->master_db['db_type'] = 'mysqli';
		$info->slave_db[0]['db_type'] = 'mysqli';
		$GLOBALS['__DB__']['mysqli'] = $db;
		getModel('install')->getInstallDBList();
		$this->assertSame($db, DB::getInstance());
		$this->assertSame('mysqli', Context::getDBType());
		$this->assertSame('DBMysqli', get_class(DB::getInstance()));
	}

	private function executeFixtureSql($connection, $sql)
	{
		$result = mysqli_query($connection, $sql);
		$this->assertTrue($result !== false, sprintf('Fixture SQL failed (%d): %s; SQL: %s', mysqli_errno($connection), mysqli_error($connection), substr($sql, 0, 200)));
		return $result;
	}

	/** @dataProvider drivers */
	public function testPreparedResultBindsNamedColumnsAndCopiesEachRow($class)
	{
		$db = $this->connect($class);
		$result = $db->_query("SELECT 1 AS id, 'first' AS label UNION ALL SELECT 2, 'second'");
		$rows = $db->_fetch($result);
		$this->assertCount(2, $rows);
		$this->assertSame('1', (string)$rows[0]->id);
		$this->assertSame('first', $rows[0]->label);
		$this->assertSame('2', (string)$rows[1]->id);
		$this->assertSame('second', $rows[1]->label);
		// Fetch must close the previous statement before the next query.
		$next = $db->_fetch($db->_query('SELECT 3 AS id'));
		$this->assertSame('3', (string)$next->id);
	}

	/** @dataProvider drivers */
	public function testPreparedResultHandlesDuplicateColumnNamesAndNull($class)
	{
		$db = $this->connect($class);
		$row = $db->_fetch($db->_query('SELECT NULL AS duplicate, 2 AS duplicate'));
		$this->assertNull($row->duplicate);
		$this->assertSame('2', (string)$row->repeat_duplicate);
	}

	/** @dataProvider drivers */
	public function testPreparedResultHandlesEmptyAndIndexedResults($class)
	{
		$db = $this->connect($class);
		$this->assertSame(array(), $db->_fetch($db->_query('SELECT 1 AS id FROM DUAL WHERE 1 = 0')));
		$rows = $db->_fetch($db->_query('SELECT 1 AS id UNION ALL SELECT 2'), 10);
		$this->assertSame(array(10, 9), array_keys($rows));
		$this->assertSame('1', (string)$rows[10]->id);
		$this->assertSame('2', (string)$rows[9]->id);
	}

	/** @dataProvider drivers */
	public function testLongTextIsNotTruncatedDuringPreparedFetch($class)
	{
		$db = $this->connect($class);
		$connection = $db->master_db['resource'];
		$table = $this->fixtureName;
		// Temporary tables disappear when this test's connection closes.
		// Match XE's UTF-8 tables even when the database default is latin1.
		$this->executeFixtureSql($connection, 'CREATE TEMPORARY TABLE `' . $table . '` (body LONGTEXT) CHARACTER SET utf8 COLLATE utf8_general_ci');
		$body = str_repeat('한글 <content> ', 5000);
		$this->executeFixtureSql($connection, 'INSERT INTO `' . $table . '` VALUES (\'' . mysqli_real_escape_string($connection, $body) . '\')');
		$row = $db->_fetch($db->_query('SELECT body FROM `' . $table . '`'));
		$this->assertSame($body, $row->body);
	}

	/** @dataProvider drivers */
	public function testFailedSchemaLookupsClearErrorBeforeNextQuery($class)
	{
		$db = $this->connect($class, false);
		$this->assertFalse($db->isColumnExists($this->fixtureName, 'id'));
		$this->assertFalse($db->isError());
		$this->assertFalse($db->isIndexExists($this->fixtureName, 'PRIMARY'));
		$this->assertFalse($db->isError());
		$row = $db->_fetch($db->_query('SELECT 7 AS id'));
		$this->assertSame('7', (string)$row->id);
	}

	/** @dataProvider drivers */
	public function testInvalidSqlReturnsAnErrorInsteadOfThrowingMysqliException($class)
	{
		$db = $this->connect($class, false);
		$this->assertFalse($db->_query('SELECT missing_column FROM `' . $this->fixtureName . '`'));
		$this->assertTrue($db->isError());
		$this->assertTrue($db->errno > 0);
		$this->assertSame('9', (string)$db->_fetch($db->_query('SELECT 9 AS id'))->id);
		$this->assertFalse($db->isError());
	}
}
