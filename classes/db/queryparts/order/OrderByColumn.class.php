<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts/order
 * @version 0.1
 */
class OrderByColumn
{

	/**
	 * column name
	 * @var string
	 */
	public $column_name;

	/**
	 * sort order
	 * @var string
	 */
	public $sort_order;

	/**
	 * constructor
	 * @param string $column_name
	 * @param string $sort_order
	 * @return void
	 */
	public function __construct($column_name, $sort_order)
	{
		$this->column_name = $column_name;
		$this->sort_order = $sort_order;
	}

	public function toString()
	{
		$result = $this->getColumnName();
		$result .= ' ';
		$result .= is_a($this->sort_order, 'Argument') ? $this->sort_order->getValue() : $this->sort_order;
		return $result;
	}

	public function getColumnName()
	{
		return is_a($this->column_name, 'Argument') ? $this->column_name->getValue() : $this->column_name;
	}

	public function getPureColumnName()
	{
		return is_a($this->column_name, 'Argument') ? $this->column_name->getPureValue() : $this->column_name;
	}

	public function getPureSortOrder()
	{
		return is_a($this->sort_order, 'Argument') ? $this->sort_order->getPureValue() : $this->sort_order;
	}

	public function getArguments()
	{
		$args = array();
		if(is_a($this->column_name, 'Argument'))
		{
			$args[] = $this->column_name;
		}
		if(is_a($this->sort_order, 'Argument'))
		{
			$args[] = $this->sort_order;
		}
	}

}
/* End of file OrderByColumn.class.php */
/* Location: ./classes/db/order/OrderByColumn.class.php */
