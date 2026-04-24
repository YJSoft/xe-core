<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * QueryTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/query
 * @version 0.1
 */
class QueryTag
{

	/**
	 * Action for example, 'select', 'insert', 'delete'...
	 * @var string
	 */
	public $action;

	/**
	 * Query id
	 * @var string
	 */
	public $query_id;

	/**
	 * Priority
	 * @var string
	 */
	public $priority;

	/**
	 * column type list
	 * @var array
	 */
	public $column_type;

	/**
	 * Query stdClass object
	 * @var object
	 */
	public $query;

	/**
	 * Columns in xml tags
	 * @var object
	 */
	public $columns;

	/**
	 * Tables in xml tags
	 * @var object
	 */
	public $tables;

	/**
	 * Subquery in xml tags
	 * @var object
	 */
	public $subquery;

	/**
	 * Conditions in xml tags
	 * @var object
	 */
	public $conditions;

	/**
	 * Groups in xml tags
	 * @var object
	 */
	public $groups;

	/**
	 * Navigation in xml tags
	 * @var object
	 */
	public $navigation;

	/**
	 * Arguments in xml tags
	 * @var object
	 */
	public $arguments;

	/**
	 * PreBuff
	 * @var string
	 */
	public $preBuff;

	/**
	 * Buff
	 * @var string
	 */
	public $buff;

	/**
	 * Subquery status
	 * @var bool
	 */
	public $isSubQuery;

	/**
	 * Join type
	 * @var string
	 */
	public $join_type;

	/**
	 * alias
	 * @var string
	 */
	public $alias;

	/**
	 * constructor
	 * @param object $query
	 * @param bool $isSubQuery
	 * @return void
	 */
	public function __construct($query, $isSubQuery = FALSE)
	{
		$this->action = $query->attrs->action;
		$this->query_id = $query->attrs->id;
		$this->priority = $query->attrs->priority;
		$this->query = $query;
		$this->isSubQuery = $isSubQuery;
		if($this->isSubQuery)
		{
			$this->action = 'select';
		}
		if($query->attrs->alias)
		{
			$dbParser = DB::getParser();
			$this->alias = $dbParser->escape($query->attrs->alias);
		}
		$this->join_type = $query->attrs->join_type;

		$this->getColumns();
		$tables = $this->getTables();
		$this->setTableColumnTypes($tables);
		$this->getSubquery(); // Used for insert-select
		$this->getConditions();
		$this->getGroups();
		$this->getNavigation();

		$this->getPrebuff();
		$this->getBuff();
	}

	public function show()
	{
		return TRUE;
	}

	public function getQueryId()
	{
		return $this->query->attrs->query_id ? $this->query->attrs->query_id : $this->query->attrs->id;
	}

	public function getPriority()
	{
		return $this->query->attrs->priority;
	}

	public function getAction()
	{
		return $this->query->attrs->action;
	}

	public function setTableColumnTypes($tables)
	{
		$query_id = $this->getQueryId();
		if(!isset($this->column_type[$query_id]))
		{
			$table_tags = $tables->getTables();
			$column_type = array();
			foreach($table_tags as $table_tag)
			{
				if(is_a($table_tag, 'TableTag'))
				{
					$table_name = $table_tag->getTableName();
					$table_alias = $table_tag->getTableAlias();
					$tag_column_type = QueryParser::getTableInfo($query_id, $table_name);
					$column_type[$table_alias] = $tag_column_type;
				}
			}
			$this->column_type[$query_id] = $column_type;
		}
	}

	public function getColumns()
	{
		if($this->action == 'select')
		{
			return $this->columns = new SelectColumnsTag($this->query->columns);
		}
		else if($this->action == 'insert' || $this->action == 'insert-select')
		{
			return $this->columns = new InsertColumnsTag($this->query->columns->column);
		}
		else if($this->action == 'update')
		{
			return $this->columns = new UpdateColumnsTag($this->query->columns->column);
		}
		else if($this->action == 'delete')
		{
			return $this->columns = null;
		}
	}

	public function getPrebuff()
	{
		if($this->isSubQuery)
		{
			return;
		}
		// TODO Check if this work with arguments in join clause
		$arguments = $this->getArguments();

		$prebuff = '';
		foreach($arguments as $argument)
		{
			if(isset($argument))
			{
				$arg_name = $argument->getArgumentName();
				if($arg_name)
				{
					unset($column_type);
					$prebuff .= $argument->toString();

					$table_alias = $argument->getTableName();
					if(isset($table_alias))
					{
						if(isset($this->column_type[$this->getQueryId()][$table_alias][$argument->getColumnName()]))
						{
							$column_type = $this->column_type[$this->getQueryId()][$table_alias][$argument->getColumnName()];
						}
					}
					else
					{
						$current_tables = $this->column_type[$this->getQueryId()];
						$column_name = $argument->getColumnName();
						foreach($current_tables as $current_table)
						{
							if(isset($current_table[$column_name]))
							{
								$column_type = $current_table[$column_name];
							}
						}
					}

					if(isset($column_type))
					{
						$prebuff .= sprintf('if(${\'%s_argument\'} !== null) ${\'%s_argument\'}->setColumnType(\'%s\');' . "\n"
								, $arg_name
								, $arg_name
								, $column_type);
					}
				}
			}
		}
		$prebuff .= "\n";

		return $this->preBuff = $prebuff;
	}

	public function getBuff()
	{
		$buff = '';
		if($this->isSubQuery)
		{
			$buff = 'new Subquery(';
			$buff .= "'" . $this->alias . '\', ';
			$buff .= ($this->columns ? $this->columns->toString() : 'null' ) . ', ' . PHP_EOL;
			$buff .= $this->tables->toString() . ',' . PHP_EOL;
			$buff .= $this->conditions->toString() . ',' . PHP_EOL;
			$buff .= $this->groups->toString() . ',' . PHP_EOL;
			$buff .= $this->navigation->getOrderByString() . ',' . PHP_EOL;
			$limit = $this->navigation->getLimitString();
			$buff .= $limit ? $limit : 'null' . PHP_EOL;
			$buff .= $this->join_type ? "'" . $this->join_type . "'" : '';
			$buff .= ')';

			$this->buff = $buff;
			return $this->buff;
		}

		$buff .= '$query = new Query();' . PHP_EOL;
		$buff .= sprintf('$query->setQueryId("%s");%s', $this->query_id, "\n");
		$buff .= sprintf('$query->setAction("%s");%s', $this->action, "\n");
		$buff .= sprintf('$query->setPriority("%s");%s', $this->priority, "\n");
		$buff .= $this->preBuff;
		if($this->columns)
		{
			$buff .= '$query->setColumns(' . $this->columns->toString() . ');' . PHP_EOL;
		}

		$buff .= '$query->setTables(' . $this->tables->toString() . ');' . PHP_EOL;
		if($this->action == 'insert-select')
		{
			$buff .= '$query->setSubquery(' . $this->subquery->toString() . ');' . PHP_EOL;
		}
		$buff .= '$query->setConditions(' . $this->conditions->toString() . ');' . PHP_EOL;
		$buff .= '$query->setGroups(' . $this->groups->toString() . ');' . PHP_EOL;
		$buff .= '$query->setOrder(' . $this->navigation->getOrderByString() . ');' . PHP_EOL;
		$buff .= '$query->setLimit(' . $this->navigation->getLimitString() . ');' . PHP_EOL;

		$this->buff = $buff;
		return $this->buff;
	}

	public function getTables()
	{
		if($this->query->index_hint && ($this->query->index_hint->attrs->for == 'ALL' || Context::getDBType() == strtolower($this->query->index_hint->attrs->for)))
		{
			return $this->tables = new TablesTag($this->query->tables, $this->query->index_hint);
		}
		else
		{
			return $this->tables = new TablesTag($this->query->tables);
		}
	}

	public function getSubquery()
	{
		if($this->query->query)
		{
			$this->subquery = new QueryTag($this->query->query, true);
		}
	}

	public function getConditions()
	{
		return $this->conditions = new ConditionsTag($this->query->conditions);
	}

	public function getGroups()
	{
		if($this->query->groups)
		{
			return $this->groups = new GroupsTag($this->query->groups->group);
		}
		else
		{
			return $this->groups = new GroupsTag(NULL);
		}
	}

	public function getNavigation()
	{
		return $this->navigation = new NavigationTag($this->query->navigation);
	}

	public function toString()
	{
		return $this->buff;
	}

	public function getTableString()
	{
		return $this->buff;
	}

	public function getConditionString()
	{
		return $this->buff;
	}

	public function getExpressionString()
	{
		return $this->buff;
	}

	public function getArguments()
	{
		$arguments = array();
		if($this->columns)
		{
			$arguments = array_merge($arguments, $this->columns->getArguments());
		}
		if($this->action == 'insert-select')
		{
			$arguments = array_merge($arguments, $this->subquery->getArguments());
		}
		$arguments = array_merge($arguments, $this->tables->getArguments());
		$arguments = array_merge($arguments, $this->conditions->getArguments());
		$arguments = array_merge($arguments, $this->navigation->getArguments());
		return $arguments;
	}

}
/* End of file QueryTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/navigation/QueryTag.class.php */
