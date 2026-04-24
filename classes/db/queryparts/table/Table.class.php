<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class Table
{

	/**
	 * table name
	 * @var string
	 */
	public $name;

	/**
	 * table alias
	 * @var string
	 */
	public $alias;

	/**
	 * constructor
	 * @param string $name
	 * @param string $alias
	 * @return void
	 */
	public function __construct($name, $alias = NULL)
	{
		$this->name = $name;
		$this->alias = $alias;
	}

	public function toString()
	{
		//return $this->name;
		return sprintf("%s%s", $this->name, $this->alias ? ' as ' . $this->alias : '');
	}

	public function getName()
	{
		return $this->name;
	}

	public function getAlias()
	{
		return $this->alias;
	}

	public function isJoinTable()
	{
		return false;
	}

}
/* End of file Table.class.php */
/* Location: ./classes/db/queryparts/table/Table.class.php */
