<?php
/** Shared real-database checks, executable without Codeception on PHP 5.3. */
class PdoMysqlContract
{
	private static function argument($value, $type = 'varchar')
	{
		$argument = new Argument('value', $value);
		$argument->setColumnType($type);
		return $argument;
	}

	private static function query($action, $table, $columns = array(), $conditions = array())
	{
		$query = new Query();
		$query->setAction($action);
		$query->setTables(array(new Table('`' . $table . '`', 't')));
		$query->setColumns($columns);
		$query->setConditions($conditions);
		return $query;
	}

	private static function sql($db, $sql, $check)
	{
		$result = $db->_query($sql);
		$check($result !== false, 'SQL succeeds: ' . substr($sql, 0, 60) . '; ' . $db->errstr);
		return $result;
	}

	public static function run($settings, $driver, $prepared, $check)
	{
		$type = $driver === 'DBPdo_mysql' ? 'pdo_mysql' : 'pdo_mysql_innodb';
		$settings['db_type'] = $type;
		Context::setDBInfo((object)array('master_db' => $settings, 'slave_db' => array($settings), 'use_prepared_statements' => $prepared ? 'Y' : 'N'));
		unset($GLOBALS['__DB__'][$type]);
		$db = DB::getInstance();
		$check($db instanceof $driver && $db->isConnected(), 'Connect ' . $type . ': ' . $db->errstr);
		try
		{
			$handle = $db->master_db['resource'];
			$check($handle instanceof PDO, 'XE stores a PDO connection');
			$row = $db->_fetch(self::sql($db, 'SELECT @@character_set_client AS charset', $check));
			$check(in_array($row->charset, array('utf8', 'utf8mb3')), 'Connection charset remains UTF-8');
			$table = 'pdo_' . str_replace('.', '_', uniqid('', true));
			self::sql($db, 'CREATE TEMPORARY TABLE `' . $table . '` (id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, body LONGTEXT, nullable VARCHAR(10) NULL) ENGINE=' . $db->getTableEngine() . ' CHARACTER SET utf8', $check);
			$payload = str_repeat("한글 ' \\ ? ", 6000);
			foreach(array($payload, 'second') as $body)
			{
				$query = self::query('insert', $table, array(new InsertExpression('body', self::argument($body))));
				$check($db->_executeInsertAct($query) !== false, 'Bound insert: ' . $db->errstr);
			}
			$check((string)$db->db_insert_id() === '2', 'Insert ID uses the same connection');
			$in = new ConditionArgument('ids', array(1, 2), 'in');
			$in->setColumnType('number');
			$in->createConditionValue();
			$conditions = array(new ConditionGroup(array(new ConditionWithArgument('id', $in, 'in'))));
			$query = self::query('select', $table, array(), $conditions);
			$query->setOrder(array(new OrderByColumn('id', 'asc')));
			$rows = $db->_executeSelectAct($query)->data;
			$check(is_array($rows) && count($rows) === 2, 'IN array produces two rows');
			$check($rows[0]->body === $payload && $rows[1]->body === 'second', 'LONGTEXT, quotes and independent rows survive');
			$check($rows[0]->nullable === null, 'NULL survives fetch');
			$query->setLimit(new Limit(self::argument(1, 'number'), self::argument(2, 'number'), self::argument(10, 'number')));
			$page = $db->_executeSelectAct($query);
			$check($page->total_count === 2 && $page->total_page === 2 && $page->page === 2, 'Page count and offset');
			$check(isset($page->data[1]) && $page->data[1]->body === 'second', 'Single paged row remains an indexed array');
			$condition = array(new ConditionGroup(array(new ConditionWithArgument('id', self::argument(2, 'number'), 'equal'))));
			$update = self::query('update', $table, array(new UpdateExpression('body', self::argument('updated'))), $condition);
			$check($db->_executeUpdateAct($update) !== false, 'Bound update: ' . $db->errstr);
			$row = $db->_fetch(self::sql($db, 'SELECT body FROM `' . $table . '` WHERE id=2', $check));
			$check(is_object($row) && $row->body === 'updated', 'Single result is an object');
			$delete = self::query('delete', $table, array(), $condition);
			$check($db->_executeDeleteAct($delete) !== false, 'Bound delete: ' . $db->errstr);
			$row = $db->_fetch(self::sql($db, 'SELECT count(*) AS count FROM `' . $table . '`', $check));
			$check((int)$row->count === 1, 'Only the requested row was deleted');
			$row = $db->_fetch(self::sql($db, 'SELECT NULL AS duplicate, 2 AS duplicate, 3 AS duplicate', $check));
			$check($row->duplicate === null && (string)$row->repeat_duplicate === '2' && (string)$row->repeat_repeat_duplicate === '3', 'Duplicate names retain every column');
			$check($db->_fetch(self::sql($db, 'SELECT 1 FROM DUAL WHERE 0', $check)) === array(), 'Empty result is an array');

			// Projection parameters precede derived-table parameters; COUNT omits projection values.
			$derived = new Subquery('d', array(new PdoContractProjection(21, 'value')), array(new Table('(SELECT 1) seed')), array(), array(), array(), null);
			$select = new Query();
			$select->setAction('select');
			$select->setColumns(array(new PdoContractProjection(11, 'selected'), new SelectExpression('d.value')));
			$select->setTables(array($derived));
			$select->setConditions(array(new ConditionGroup(array(new ConditionWithArgument('d.value', self::argument(21, 'number'), 'equal')))));
			$select->setLimit(new Limit(self::argument(1, 'number'), self::argument(1, 'number'), self::argument(10, 'number')));
			$page = $db->_executeSelectAct($select);
			$check(!$db->isError(), 'Derived SELECT and COUNT: ' . $db->errstr);
			$check($page->total_count === 1 && (string)$page->data[1]->selected === '11' && (string)$page->data[1]->value === '21', 'Parameter order is preserved in nested SELECTs');

			self::sql($db, 'DELETE FROM `' . $table . '`', $check);
			$db->begin();
			self::sql($db, "INSERT INTO `" . $table . "` (body) VALUES ('outer')", $check);
			$db->begin();
			self::sql($db, "INSERT INTO `" . $table . "` (body) VALUES ('inner')", $check);
			$db->rollback();
			$db->commit();
			$row = $db->_fetch(self::sql($db, 'SELECT count(*) AS count FROM `' . $table . '`', $check));
			$check((int)$row->count === ($type === 'pdo_mysql_innodb' ? 1 : 2), 'Nested rollback respects the table engine');
			$check(!$db->transaction_started, 'Transaction nesting returns to zero');
			$db->begin();
			self::sql($db, "INSERT INTO `" . $table . "` (body) VALUES ('rollback')", $check);
			$db->rollback();
			$row = $db->_fetch(self::sql($db, 'SELECT count(*) AS count FROM `' . $table . '`', $check));
			$check((int)$row->count === ($type === 'pdo_mysql_innodb' ? 1 : 3), 'Outer rollback respects the table engine');
			$check($db->_query('SELECT invalid_column FROM `' . $table . '`') === false && $db->errno > 0, 'SQL exception becomes XE error state');
			$row = $db->_fetch(self::sql($db, 'SELECT 9 AS id', $check));
			$check((string)$row->id === '9' && !$db->isError(), 'A later successful query clears the error');
			$check(!$db->isColumnExists($table . '_missing', 'id') && !$db->isError(), 'Expected column lookup failure clears its error');
			$check(!$db->isIndexExists($table . '_missing', 'PRIMARY') && !$db->isError(), 'Expected index lookup failure clears its error');
			$password = new Password();
			$check($password->checkPassword('password', '5d2e19393cc5ef67'), 'Legacy MySQL hash works without OLD_PASSWORD SQL');
			$check($password->checkPassword("pass \tword", '5d2e19393cc5ef67'), 'Legacy hash ignores spaces and tabs');
			$check(!$password->checkPassword('incorrect', '5d2e19393cc5ef67'), 'Legacy hash rejects incorrect passwords');
			$db->close();
			$check(!$db->isConnected() && $db->master_db['resource'] === null, 'Closing clears connection state and PDO reference');
			$row = $db->_fetch(self::sql($db, 'SELECT 10 AS id', $check));
			$check((string)$row->id === '10', 'The driver reconnects after close');
			$db->close();
		}
		catch(Exception $e)
		{
			$db->close();
			throw $e;
		}
	}
}

/** A Query expression with a bound projection, to exercise SQL clause order. */
class PdoContractProjection extends SelectExpression
{
	private $value;

	function __construct($value, $alias)
	{
		parent::__construct('', $alias);
		$this->value = new Argument('projection', $value);
		$this->value->setColumnType('number');
	}

	function getExpression($with_values = true)
	{
		return ($with_values ? $this->value->getValue() : '?') . ' AS ' . $this->column_alias;
	}

	function getArguments() { return array($this->value); }
}
