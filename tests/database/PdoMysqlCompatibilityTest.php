<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'tests/_support/PdoMysqlContract.php';

/** @group compatibility */
class PdoMysqlCompatibilityTest extends CompatibilityTestCase
{
	public static function drivers()
	{
		return array(array('DBPdo_mysql', false), array('DBPdo_mysql', true), array('DBPdo_mysql_innodb', false), array('DBPdo_mysql_innodb', true));
	}

	/** @dataProvider drivers */
	public function testRealPdoDriverContract($driver, $prepared)
	{
		$config = $this->getModule('Db')->_getConfig();
		preg_match('/(?:^|;)host=([^;]+)/', substr($config['dsn'], 6), $host);
		preg_match('/(?:^|;)dbname=([^;]+)/', substr($config['dsn'], 6), $database);
		preg_match('/(?:^|;)port=([^;]+)/', substr($config['dsn'], 6), $port);
		$settings = array('db_hostname' => $host[1], 'db_port' => isset($port[1]) ? (int)$port[1] : 3306, 'db_database' => $database[1], 'db_userid' => $config['user'], 'db_password' => $config['password'], 'db_table_prefix' => 'xe_');
		$self = $this;
		PdoMysqlContract::run($settings, $driver, $prepared, function($condition, $message) use ($self) { $self->assertTrue($condition, $message); });
	}
}
