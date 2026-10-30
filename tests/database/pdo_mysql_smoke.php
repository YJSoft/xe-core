<?php
// No Composer or test framework dependency: CI also executes this on PHP 5.3.
define('__XE__', true);
require dirname(dirname(__DIR__)) . '/config/config.inc.php';
require _XE_PATH_ . 'tests/_support/PdoMysqlContract.php';
$context = Context::getInstance();
$context->context = new stdClass();
$context->get_vars = new stdClass();
$context->lang_type = 'en';
$context->request_method = 'JSON';
$GLOBALS['lang'] = new stdClass();
$settings = array(
	'db_hostname' => getenv('XE_TEST_DB_HOST') ? getenv('XE_TEST_DB_HOST') : '127.0.0.1',
	'db_port' => getenv('XE_TEST_DB_PORT') ? (int)getenv('XE_TEST_DB_PORT') : 3306,
	'db_database' => getenv('XE_TEST_DB_NAME') ? getenv('XE_TEST_DB_NAME') : 'xe_test',
	'db_userid' => getenv('XE_TEST_DB_USER') ? getenv('XE_TEST_DB_USER') : 'root',
	'db_password' => getenv('XE_TEST_DB_PASSWORD') !== false ? getenv('XE_TEST_DB_PASSWORD') : 'root',
	'db_table_prefix' => 'xe_'
);
$checks = 0;
$assert = function($condition, $message) use (&$checks) {
	if(!$condition) throw new Exception($message);
	$checks++;
};
PdoMysqlContract::installation($settings, sys_get_temp_dir() . '/xe_pdo_' . uniqid() . '.php', $assert);
foreach(array('DBPdo_mysql', 'DBPdo_mysql_innodb') as $driver)
{
	foreach(array(false, true) as $prepared) PdoMysqlContract::run($settings, $driver, $prepared, $assert);
}
echo 'PDO MySQL: ' . $checks . ' checks passed on PHP ' . PHP_VERSION . "\n";
