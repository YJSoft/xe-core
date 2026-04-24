<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts/limit
 * @version 0.1
 */
class Limit
{

	/**
	 * start number
	 * @var int
	 */
	public $start;

	/**
	 * list count
	 * @var int
	 */
	public $list_count;

	/**
	 * page count
	 * @var int
	 */
	public $page_count;

	/**
	 * current page
	 * @var int
	 */
	public $page;

	/**
	 * constructor
	 * @param int $list_count
	 * @param int $page
	 * @param int $page_count
	 * @param int $offset
	 * @return void
	 */
	public function __construct($list_count, $page = NULL, $page_count = NULL, $offset = NULL)
	{
		$this->list_count = $list_count;
		if($page)
		{
			$list_count_value = $list_count->getValue();
			$page_value = $page->getValue();
			$this->start = ($page_value - 1) * $list_count_value;
			$this->page_count = $page_count;
			$this->page = $page;
		}
		elseif($offset)
		{
			$this->start = $offset->getValue();
		}
	}

	/**
	 * In case you choose to use query limit in other cases than page select
	 * @return boolean
	 */
	public function isPageHandler()
	{
		if($this->page)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getOffset()
	{
		return $this->start;
	}

	public function getLimit()
	{
		return $this->list_count->getValue();
	}

	public function toString()
	{
		if($this->page || $this->start)
		{
			return $this->start . ' , ' . $this->list_count->getValue();
		}
		else
		{
			return $this->list_count->getValue();
		}
	}

}
/* End of file Limit.class.php */
/* Location: ./classes/db/limit/Limit.class.php */
