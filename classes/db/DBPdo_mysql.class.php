<?php
/** MySQL transport for XE's existing SQL generator and result contract. */
require_once dirname(__FILE__) . '/DBMysql.class.php';

class DBPdo_mysql extends DBMysql
{
	private $pdoParameters = array();
	private $pdoColumns = array();

	public static function create()
	{
		return new DBPdo_mysql;
	}

	public static function isSupported()
	{
		return version_compare(PHP_VERSION, '5.3.6', '>=') && class_exists('PDO', false)
			&& in_array('mysql', PDO::getAvailableDrivers(), true);
	}

	function __connect($connection)
	{
		if(!self::isSupported())
		{
			$this->setError(-1, 'PDO MySQL requires PHP 5.3.6 or newer and the pdo_mysql extension.');
			return false;
		}
		foreach(array('db_hostname', 'db_database') as $key)
		{
			if(strpbrk($connection[$key], ";\r\n\0") !== false)
			{
				$this->setError(-1, 'Invalid MySQL connection settings.');
				return false;
			}
		}
		$dsn = 'mysql:host=' . $connection['db_hostname'] . ';dbname=' . $connection['db_database'] . ';charset=utf8';
		if(!empty($connection['db_port'])) $dsn .= ';port=' . (int)$connection['db_port'];
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_PERSISTENT => false,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
		);
		if(defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) $options[constant('PDO::MYSQL_ATTR_MULTI_STATEMENTS')] = false;
		try
		{
			return new PDO($dsn, $connection['db_userid'], $connection['db_password'], $options);
		}
		catch(PDOException $e)
		{
			$this->setPdoError($e);
			return false;
		}
	}

	function _afterConnect($connection) {}
	function _close($connection) {}

	function close($type = 'master', $indx = 0)
	{
		parent::close($type, $indx);
		if($type === 'master') $this->master_db['resource'] = null;
		else $this->slave_db[$indx]['resource'] = null;
		$this->pdoColumns = array();
	}

	private function setPdoError($exception)
	{
		$info = $exception->errorInfo;
		$this->setError(isset($info[1]) && $info[1] ? (int)$info[1] : -1,
			isset($info[2]) ? $info[2] : $exception->getMessage());
	}

	function addQuotes($string)
	{
		if(version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}
		if(is_numeric($string)) return $string;
		try
		{
			$connection = $this->_getConnection('master');
			if(!$connection) return '';
			return substr($connection->quote((string)$string), 1, -1);
		}
		catch(PDOException $e)
		{
			$this->setPdoError($e);
			return '';
		}
	}

	function __query($query, $connection)
	{
		// Consume parameters once, so later SHOW/DDL/direct queries cannot reuse them.
		$parameters = $this->pdoParameters;
		$this->pdoParameters = array();
		if(!$connection)
		{
			if(!$this->isError()) $this->setError(-1, 'MySQL is not connected.');
			return false;
		}
		try
		{
			if(count($parameters))
			{
				$result = $connection->prepare($query);
				foreach($parameters as $index => $value)
				{
					$type = $value === null ? PDO::PARAM_NULL : (is_bool($value) ? PDO::PARAM_BOOL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
					$result->bindValue($index + 1, $value, $type);
				}
				$result->execute();
			}
			else $result = $connection->query($query);
			$this->pdoColumns[spl_object_hash($result)] = null;
			return $result;
		}
		catch(PDOException $e)
		{
			$this->setPdoError($e);
			return false;
		}
	}

	/** Collect value arguments in SQL clause order, including nested SELECTs. */
	private function queryParameters($query)
	{
		if(isset($query->subquery)) return $this->queryParameters($query->subquery);
		$parts = $query->action === 'select' ? array('columns', 'tables', 'conditions') : array('tables', 'columns', 'conditions');
		$result = array();
		foreach($parts as $part)
		{
			if(empty($query->$part)) continue;
			foreach($query->$part as $item)
			{
				if(method_exists($item, 'show') && !$item->show()) continue;
				if($item instanceof Query) $values = $this->queryParameters($item);
				else $values = method_exists($item, 'getArguments') ? $this->argumentValues($item->getArguments()) : array();
				$result = array_merge($result, $values);
			}
		}
		return $result;
	}

	private function argumentValues($arguments)
	{
		$result = array();
		foreach((array)$arguments as $argument)
		{
			if($argument instanceof Query) $result = array_merge($result, $this->queryParameters($argument));
			else if(is_array($argument)) $result = array_merge($result, $this->argumentValues($argument));
			else if(!$argument->isColumnName())
			{
				$value = $argument->getUnescapedValue();
				$result = array_merge($result, is_array($value) ? array_values($value) : array($value));
			}
		}
		return $result;
	}

	function _executeInsertAct($queryObject, $with_values = false)
	{
		$with_values = $this->use_prepared_statements !== 'Y';
		$this->pdoParameters = $with_values ? array() : $this->queryParameters($queryObject);
		$result = parent::_executeInsertAct($queryObject, $with_values);
		$this->pdoParameters = array();
		return $result;
	}

	function _executeUpdateAct($queryObject, $with_values = false)
	{
		$with_values = $this->use_prepared_statements !== 'Y';
		$this->pdoParameters = $with_values ? array() : $this->queryParameters($queryObject);
		$result = parent::_executeUpdateAct($queryObject, $with_values);
		$this->pdoParameters = array();
		return $result;
	}

	function _executeDeleteAct($queryObject, $with_values = false)
	{
		$with_values = $this->use_prepared_statements !== 'Y';
		$this->pdoParameters = $with_values ? array() : $this->queryParameters($queryObject);
		$result = parent::_executeDeleteAct($queryObject, $with_values);
		$this->pdoParameters = array();
		return $result;
	}

	function _executeSelectAct($queryObject, $connection = null, $with_values = false)
	{
		$with_values = $this->use_prepared_statements !== 'Y';
		$this->pdoParameters = $with_values ? array() : $this->queryParameters($queryObject);
		$result = parent::_executeSelectAct($queryObject, $connection, $with_values);
		$this->pdoParameters = array();
		return $result;
	}

	function queryPageLimit($queryObject, $result, $connection, $with_values = true)
	{
		$count = clone $queryObject;
		$count->orderby = array();
		$count->_orderByString = null;
		$count->limit = null;
		$grouped = $count->getGroupByString() !== '' || stripos($count->getSelectString($with_values), 'distinct') !== false;
		if(!$grouped) $count->columns = array(new SelectExpression('count(*)', 'count'));
		$sql = $this->getSelectSql($count, $with_values);
		if($grouped) $sql = 'SELECT count(*) AS count FROM (' . $sql . ') AS xe_count';
		$this->pdoParameters = $with_values ? array() : $this->queryParameters($count);
		$row = $this->_fetch($this->_query($sql, $connection));
		if($this->isError()) return $this->queryError($queryObject);
		$total_count = (int)$row->count;
		$limit = $queryObject->getLimit();
		$list_count = (int)$limit->list_count->getValue();
		$page_count = (int)$limit->page_count->getValue();
		$page = max(1, (int)$limit->page->getValue());
		if(!$list_count) $list_count = 20;
		if(!$page_count) $page_count = 10;
		$total_page = $total_count ? (int)ceil($total_count / $list_count) : 1;
		$output = new BaseObject();
		$output->total_count = $total_count;
		$output->total_page = $total_page;
		$output->page = $page;
		$output->page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
		$output->data = array();
		if($page <= $total_page)
		{
			$this->pdoParameters = $with_values ? array() : $this->queryParameters($queryObject);
			$sql = $this->getSelectPageSql($queryObject, $with_values, ($page - 1) * $list_count, $list_count);
			$output->data = $this->_fetch($this->_query($sql, $connection), $total_count - ($page - 1) * $list_count);
		}
		return $output;
	}

	function db_fetch_object(&$result)
	{
		if(!($result instanceof PDOStatement) || !$result->columnCount()) return false;
		try
		{
			$key = spl_object_hash($result);
			if(!isset($this->pdoColumns[$key]))
			{
				$columns = array();
				$seen = array();
				for($i = 0; $i < $result->columnCount(); $i++)
				{
					$meta = $result->getColumnMeta($i);
					$name = $meta['name'];
					$column = $name;
					while(isset($seen[$column])) $column = 'repeat_' . $column;
					$seen[$column] = true;
					$columns[] = $column;
				}
				$this->pdoColumns[$key] = $columns;
			}
			$values = $result->fetch(PDO::FETCH_NUM);
			if($values === false) return false;
			$row = new stdClass();
			foreach($this->pdoColumns[$key] as $index => $name) $row->$name = $values[$index];
			return $row;
		}
		catch(PDOException $e)
		{
			$this->setPdoError($e);
			return false;
		}
	}

	function db_free_result(&$result)
	{
		if(!($result instanceof PDOStatement)) return false;
		unset($this->pdoColumns[spl_object_hash($result)]);
		try { return $result->closeCursor(); }
		catch(PDOException $e) { $this->setPdoError($e); return false; }
	}

	function _fetch($result, $arrayIndexEndValue = null)
	{
		$rows = array();
		if(!$this->isError() && $result instanceof PDOStatement)
		{
			while(($row = $this->db_fetch_object($result)) !== false)
			{
				if($arrayIndexEndValue) $rows[$arrayIndexEndValue--] = $row;
				else $rows[] = $row;
			}
			$this->db_free_result($result);
		}
		return count($rows) === 1 && !isset($arrayIndexEndValue) ? reset($rows) : $rows;
	}

	function db_insert_id()
	{
		try { return $this->_getConnection('master')->lastInsertId(); }
		catch(PDOException $e) { $this->setPdoError($e); return false; }
	}

	function isValidOldPassword($password, $saved_password)
	{
		return mysql_pre4_hash_password($password) === $saved_password;
	}
}
