<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'classes/db/DBMysql.class.php';
require_once _XE_PATH_ . 'classes/db/DBMysqli_innodb.class.php';
require_once _XE_PATH_ . 'classes/db/DBCubrid.class.php';
require_once _XE_PATH_ . 'classes/db/DBMssql.class.php';

/** @group compatibility */
class QueryCompatibilityTest extends CompatibilityTestCase
{
	public function testQueryWithNullableColumnOrderLimitAndArgumentLists()
	{
		$query = new Query();
		$query->setColumnList(null);
		$this->assertSame('', $query->getOrderByString());
		$this->assertSame('', $query->getLimitString());
		$this->assertSame(array(), $query->getArguments());
		$query->columns = array();
		$this->assertSame('', $query->getSelectString());
	}

	public function testSelectAndUpdateExpressionsUseSupportedImplodeArgumentOrder()
	{
		$query = new Query();
		$query->columns = array(new SelectExpression('id'), new SelectExpression('title'));
		$this->assertSame('id, title', $query->getSelectString());
		$query->columns = array(new UpdateExpressionWithoutArgument('id', '1'), new UpdateExpressionWithoutArgument('title', "'title'"));
		$this->assertSame("id = 1, title = 'title'", $query->getUpdateString());
	}

	public function testInConditionWithArrayValuesUsesSupportedImplodeArgumentOrder()
	{
		$condition = new ConditionWithoutArgument('id', array(1, 2, 3), 'in');
		$this->assertTrue(strpos($condition->toString(), '(1,2,3)') !== false);
	}

	public static function schemaDrivers()
	{
		return array(array('CompatibilityMysqlSchema'), array('CompatibilityInnodbSchema'), array('CompatibilityCubridSchema'), array('CompatibilityMssqlSchema'));
	}

	/** @dataProvider schemaDrivers */
	public function testSchemaWithoutPrimaryUniqueOrIndexLists($class)
	{
		$db = new $class();
		$db->createTableByXml('<table name="compat_schema"><column name="title" type="varchar" size="80" /></table>');
		$sql = implode("\n", $db->statements);
		$this->assertTrue(strpos($sql, 'compat_schema') !== false);
		$this->assertTrue(strpos($sql, 'title') !== false);
		$this->assertFalse(stripos($sql, 'primary key'));
		$this->assertFalse(stripos($sql, 'create index'));
	}
}

// Only SQL I/O is replaced: the schema parsers and builders are the real drivers.
class CompatibilityMysqlSchema extends DBMysql
{
	public $statements = array();
	public function __construct() {}
	public function isTableExists($table_name) { return false; }
	public function addQuotes($value) { return $value; }
	public function _query($query, $connection = null) { $this->statements[] = $query; return true; }
}
class CompatibilityInnodbSchema extends DBMysqli_innodb
{
	public $statements = array();
	public function __construct() {}
	public function isTableExists($table_name) { return false; }
	public function addQuotes($value) { return $value; }
	public function _query($query, $connection = null) { $this->statements[] = $query; return true; }
}
class CompatibilityCubridSchema extends DBCubrid
{
	public $statements = array();
	public function __construct() {}
	public function isTableExists($table_name) { return false; }
	public function addQuotes($value) { return $value; }
	public function _query($query, $connection = null) { $this->statements[] = $query; return true; }
}
class CompatibilityMssqlSchema extends DBMssql
{
	public $statements = array();
	public function __construct() {}
	public function isTableExists($table_name) { return false; }
	public function addQuotes($value) { return $value; }
	public function _query($query, $connection = null) { $this->statements[] = $query; return true; }
}
