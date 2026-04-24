<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts
 * @version 0.1
 */
class Subquery extends Query
{

	/**
	 * table alias
	 * @var string
	 */
	public $alias;

	/**
	 * join type
	 * @var string
	 */
	public $join_type;

	/**
	 * constructor
	 * @param string $alias
	 * @param string|array $columns
	 * @param string|array $tables
	 * @param string|array $conditions
	 * @param string|array $groups
	 * @param string|array $orderby
	 * @param int $limit
	 * @param string $join_type
	 * @return void
	 */
	public function __construct($alias, $columns, $tables, $conditions, $groups, $orderby, $limit, $join_type = null)
	{
		$this->alias = $alias;

		$this->queryID = null;
		$this->action = "select";

		$this->columns = $columns;
		$this->tables = $tables;
		$this->conditions = $conditions;
		$this->groups = $groups;
		$this->orderby = $orderby;
		$this->limit = $limit;
		$this->join_type = $join_type;
	}

	public function getAlias()
	{
		return $this->alias;
	}

	public function isJoinTable()
	{
		if($this->join_type)
		{
			return true;
		}
		return false;
	}

	public function toString($with_values = true)
	{
		$oDB = DB::getInstance();

		return '(' . $oDB->getSelectSql($this, $with_values) . ')';
	}

	public function isSubquery()
	{
		return true;
	}

}
/* End of file Subquery.class.php */
/* Location: ./classes/db/queryparts/Subquery.class.php */
