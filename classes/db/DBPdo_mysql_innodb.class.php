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
		return $transactionLevel ? $this->pdoTransaction('SAVEPOINT SP' . $transactionLevel)
			: $this->pdoTransaction('START TRANSACTION', 'beginTransaction');
	}

	function _rollback($transactionLevel = 0)
	{
		return $transactionLevel > 1 ? $this->pdoTransaction('ROLLBACK TO SAVEPOINT SP' . ($transactionLevel - 1))
			: $this->pdoTransaction('ROLLBACK', 'rollBack');
	}

	function _commit()
	{
		return $this->pdoTransaction('COMMIT', 'commit');
	}
}
