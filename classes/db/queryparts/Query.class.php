<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts
 * @version 0.1
 */
class Query extends BaseObject
{

	/**
	 * Query id, defined in query xml file
	 * @var string
	 */
	public $queryID;

	/**
	 * DML type, ex) INSERT, DELETE, UPDATE, SELECT
	 * @var string
	 */
	public $action;

	/**
	 * priority level ex)LOW_PRIORITY, HIGHT_PRIORITY
	 * @var string
	 */
	public $priority;

	/**
	 * column list
	 * @var string|array
	 */
	public $columns;

	/**
	 * table list
	 * @var string|array
	 */
	public $tables;

	/**
	 * condition list
	 * @var string|array
	 */
	public $conditions;

	/**
	 * group list
	 * @var string|array
	 */
	public $groups;

	/**
	 * order list
	 * @var array
	 */
	public $orderby;

	/**
	 * limit count
	 * @var int
	 */
	public $limit;

	/**
	 * argument list
	 * @var array
	 */
	public $arguments = NULL;

	/**
	 * column list
	 * @var array
	 */
	public $columnList = NULL;

	/**
	 * order by text
	 * @var string
	 */
	public $_orderByString;

	/**
	 * constructor
	 * @param string $queryID
	 * @param string $action
	 * @param string|array $columns
	 * @param string|array $tables
	 * @param string|array $conditions
	 * @param string|array $groups
	 * @param string|array $orderby
	 * @param int $limit
	 * @param string $priority
	 * @return void
	 */
	public function __construct($queryID = NULL
	, $action = NULL
	, $columns = NULL
	, $tables = NULL
	, $conditions = NULL
	, $groups = NULL
	, $orderby = NULL
	, $limit = NULL
	, $priority = NULL)
	{
		$this->queryID = $queryID;
		$this->action = $action;
		$this->priority = $priority;

		if(!isset($tables))
		{
			return;
		}

		$this->columns = $this->setColumns($columns);
		$this->tables = $this->setTables($tables);
		$this->conditions = $this->setConditions($conditions);
		$this->groups = $this->setGroups($groups);
		$this->orderby = $this->setOrder($orderby);
		$this->limit = $this->setLimit($limit);
	}

	public function show()
	{
		return TRUE;
	}

	public function setQueryId($queryID)
	{
		$this->queryID = $queryID;
	}

	public function setAction($action)
	{
		$this->action = $action;
	}

	public function setPriority($priority)
	{
		$this->priority = $priority;
	}

	public function setColumnList($columnList)
	{
		$this->columnList = $columnList;
		if(count($this->columnList) > 0)
		{
			$selectColumns = array();
			$dbParser = DB::getParser();

			foreach($this->columnList as $columnName)
			{
				$columnName = $dbParser->escapeColumn($columnName);
				$selectColumns[] = new SelectExpression($columnName);
			}
			unset($this->columns);
			$this->columns = $selectColumns;
		}
	}

	public function setColumns($columns)
	{
		if(!isset($columns) || count($columns) === 0)
		{
			$this->columns = array(new StarExpression());
			return;
		}

		if(!is_array($columns))
		{
			$columns = array($columns);
		}

		$this->columns = $columns;
	}

	public function setTables($tables)
	{
		if(!isset($tables) || count($tables) === 0)
		{
			$this->setError(TRUE);
			$this->setMessage("You must provide at least one table for the query.");
			return;
		}

		if(!is_array($tables))
		{
			$tables = array($tables);
		}

		$this->tables = $tables;
	}

	public function setSubquery($subquery)
	{
		$this->subquery = $subquery;
	}

	public function setConditions($conditions)
	{
		$this->conditions = array();
		if(!isset($conditions) || count($conditions) === 0)
		{
			return;
		}
		if(!is_array($conditions))
		{
			$conditions = array($conditions);
		}

		foreach($conditions as $conditionGroup)
		{
			if($conditionGroup->show())
			{
				$this->conditions[] = $conditionGroup;
			}
		}
	}

	public function setGroups($groups)
	{
		if(!isset($groups) || count($groups) === 0)
		{
			return;
		}
		if(!is_array($groups))
		{
			$groups = array($groups);
		}

		$this->groups = $groups;
	}

	public function setOrder($order)
	{
		if(!isset($order) || count($order) === 0)
		{
			return;
		}
		if(!is_array($order))
		{
			$order = array($order);
		}

		$this->orderby = $order;
	}

	public function getOrder()
	{
		return $this->orderby;
	}

	public function setLimit($limit = NULL)
	{
		if(!isset($limit))
		{
			return;
		}
		$this->limit = $limit;
	}

	// START Fluent interface
	/**
	 * seleect set
	 * @param string|array $columns
	 * @return Query return Query instance
	 */
	public function select($columns = NULL)
	{
		$this->action = 'select';
		$this->setColumns($columns);
		return $this;
	}

	/**
	 * from set
	 * @param string|array $tables
	 * @return Query return Query instance
	 */
	public function from($tables)
	{
		$this->setTables($tables);
		return $this;
	}

	/**
	 * where set
	 * @param string|array $conditions
	 * @return Query return Query instance
	 */
	public function where($conditions)
	{
		$this->setConditions($conditions);
		return $this;
	}

	/**
	 * groupBy set
	 * @param string|array $groups
	 * @return Query return Query instance
	 */
	public function groupBy($groups)
	{
		$this->setGroups($groups);
		return $this;
	}

	/**
	 * orderBy set
	 * @param string|array $order
	 * @return Query return Query instance
	 */
	public function orderBy($order)
	{
		$this->setOrder($order);
		return $this;
	}

	/**
	 * limit set
	 * @param int $limit
	 * @return Query return Query instance
	 */
	public function limit($limit)
	{
		$this->setLimit($limit);
		return $this;
	}

	// END Fluent interface

	public function getAction()
	{
		return $this->action;
	}

	public function getPriority()
	{
		return $this->priority ? 'LOW_PRIORITY' : '';
	}

	/**
	 * Check if current query uses the click count attribute
	 * For CUBRID, this statement uses the click count feature.
	 * For the other databases, using this attribute causes a query
	 * to produce both a select and an update
	 */
	public function usesClickCount()
	{
		return count($this->getClickCountColumns()) > 0;
	}

	public function getClickCountColumns()
	{
		$click_count_columns = array();
		foreach($this->columns as $column)
		{
			if($column->show() && is_a($column, 'ClickCountExpression'))
			{
				$click_count_columns[] = $column;
			}
		}
		return $click_count_columns;
	}

	/**
	 * Return select sql
	 * @param boolean $with_values
	 * @return string
	 */
	public function getSelectString($with_values = TRUE)
	{
		foreach($this->columns as $column)
		{
			if($column->show())
			{
				if($column->isSubquery())
				{
					$select[] = $column->toString($with_values) . ' as ' . $column->getAlias();
				}
				else
				{
					$select[] = $column->getExpression($with_values);
				}
			}
		}
		return trim(implode($select, ', '));
	}

	/**
	 * Return update sql
	 * @param boolean $with_values
	 * @return string
	 */
	public function getUpdateString($with_values = TRUE)
	{
		foreach($this->columns as $column)
		{
			if($column->show())
			{
				$update[] = $column->getExpression($with_values);
			}
		}

		if(!$update) return;
		return trim(implode($update, ', '));
	}

	/**
	 * Return insert sql
	 * @param boolean $with_values
	 * @return string
	 */
	public function getInsertString($with_values = TRUE)
	{
		$columnsList = '';
		// means we have insert-select
		if($this->subquery)
		{
			foreach($this->columns as $column)
			{
				$columnsList .= $column->getColumnName() . ', ';
			}
			$columnsList = substr($columnsList, 0, -2);
			$selectStatement = $this->subquery->toString($with_values);
			$selectStatement = substr($selectStatement, 1, -1);
			return "($columnsList) \n $selectStatement";
		}

		$valuesList = '';
		foreach($this->columns as $column)
		{
			if($column->show())
			{
				$columnsList .= $column->getColumnName() . ', ';
				$valuesList .= $column->getValue($with_values) . ', ';
			}
		}
		$columnsList = substr($columnsList, 0, -2);
		$valuesList = substr($valuesList, 0, -2);

		return "($columnsList) \n VALUES ($valuesList)";
	}

	public function getTables()
	{
		return $this->tables;
	}

	/**
	 * from table_a
	 * from table_a inner join table_b on x=y
	 * from (select * from table a) as x
	 * from (select * from table t) as x inner join table y on y.x
	 * @param boolean $with_values
	 * @return string
	 */
	public function getFromString($with_values = TRUE)
	{
		$from = '';
		$simple_table_count = 0;
		foreach($this->tables as $table)
		{
			if($table->isJoinTable() || !$simple_table_count)
			{
				$from .= $table->toString($with_values) . ' ';
			}
			else
			{
				$from .= ', ' . $table->toString($with_values) . ' ';
			}

			if(is_a($table, 'Subquery'))
			{
				$from .= $table->getAlias() ? ' as ' . $table->getAlias() . ' ' : ' ';
			}

			$simple_table_count++;
		}
		if(trim($from) == '')
		{
			return '';
		}
		return $from;
	}

	/**
	 * Return where sql
	 * @param boolean $with_values
	 * @param boolean $with_optimization
	 * @return string
	 */
	public function getWhereString($with_values = TRUE, $with_optimization = TRUE)
	{
		$where = '';
		$condition_count = 0;

		foreach($this->conditions as $conditionGroup)
		{
			if($condition_count === 0)
			{
				$conditionGroup->setPipe("");
			}
			$condition_string = $conditionGroup->toString($with_values);
			$where .= $condition_string;
			$condition_count++;
		}

		if($with_optimization &&
				(strstr($this->getOrderByString(), 'list_order') || strstr($this->getOrderByString(), 'update_order')))
		{

			if($condition_count !== 0)
			{
				$where = '(' . $where . ') ';
			}

			foreach($this->orderby as $order)
			{
				$colName = $order->getColumnName();
				if(strstr($colName, 'list_order') || strstr($colName, 'update_order'))
				{
					$opt_condition = new ConditionWithoutArgument($colName, 2100000000, 'less', 'and');
					if($condition_count === 0)
					{
						$opt_condition->setPipe("");
					}
					$where .= $opt_condition->toString($with_values) . ' ';
					$condition_count++;
				}
			}
		}

		return trim($where);
	}

	/**
	 * Return groupby sql
	 * @return string
	 */
	public function getGroupByString()
	{
		$groupBy = '';
		if($this->groups)
		{
			if($this->groups[0] !== "")
			{
				$groupBy = implode(', ', $this->groups);
			}
		}
		return $groupBy;
	}

	/**
	 * Return orderby sql
	 * @return string
	 */
	public function getOrderByString()
	{
		if(!$this->_orderByString)
		{
			if(count($this->orderby) === 0)
			{
				return '';
			}
			$orderBy = '';
			foreach($this->orderby as $order)
			{
				$orderBy .= $order->toString() . ', ';
			}
			$orderBy = substr($orderBy, 0, -2);
			$this->_orderByString = $orderBy;
		}
		return $this->_orderByString;
	}

	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Return limit sql
	 * @return string
	 */
	public function getLimitString()
	{
		$limit = '';
		if(count($this->limit) > 0)
		{
			$limit = '';
			$limit .= $this->limit->toString();
		}
		return $limit;
	}

	public function getFirstTableName()
	{
		return $this->tables[0]->getName();
	}

	/**
	 * Return argument list
	 * @return array
	 */
	public function getArguments()
	{
		if(!isset($this->arguments))
		{
			$this->arguments = array();

			// Join table arguments
			if(count($this->tables) > 0)
			{
				foreach($this->tables as $table)
				{
					if($table->isJoinTable() || is_a($table, 'Subquery'))
					{
						$args = $table->getArguments();
						if($args)
						{
							$this->arguments = array_merge($this->arguments, $args);
						}
					}
				}
			}

			// Column arguments
			// The if is for delete statements, all others must have columns
			if(count($this->columns) > 0)
			{
				foreach($this->columns as $column)
				{
					if($column->show())
					{
						$args = $column->getArguments();
						if($args)
						{
							$this->arguments = array_merge($this->arguments, $args);
						}
					}
				}
			}

			// Condition arguments
			if(count($this->conditions) > 0)
			{
				foreach($this->conditions as $conditionGroup)
				{
					$args = $conditionGroup->getArguments();
					if(count($args) > 0)
					{
						$this->arguments = array_merge($this->arguments, $args);
					}
				}
			}

			// Navigation arguments
			if(count($this->orderby) > 0)
			{
				foreach($this->orderby as $order)
				{
					$args = $order->getArguments();
					if(count($args) > 0)
					{
						$this->arguments = array_merge($this->arguments, $args);
					}
				}
			}
		}
		return $this->arguments;
	}

}
/* End of file Query.class.php */
/* Location: ./classes/db/queryparts/Query.class.php */
