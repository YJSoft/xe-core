<?php
require_once dirname(__FILE__) . '/DBPdo_mysql.class.php';

class DBPdo_mysql_innodb extends DBPdo_mysql
{
	public static function create()
	{
		return new DBPdo_mysql_innodb;
	}

	function getTableEngine()
	{
		return 'INNODB';
	}

	function _begin($transactionLevel = 0)
	{
		return $this->_query($transactionLevel ? 'SAVEPOINT SP' . $transactionLevel : 'START TRANSACTION') !== false;
	}

	function _rollback($transactionLevel = 0)
	{
		return $this->_query($transactionLevel > 1 ? 'ROLLBACK TO SAVEPOINT SP' . ($transactionLevel - 1) : 'ROLLBACK') !== false;
	}

	function _commit()
	{
		return $this->_query('COMMIT') !== false;
	}
}
