<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * @author XEHub (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class IndexHint
{

	/**
	 * index name
	 * @var string
	 */
	public $index_name;

	/**
	 * index hint type, ex) IGNORE, FORCE, USE...
	 * @var string
	 */
	public $index_hint_type;

	/**
	 * constructor
	 * @param string $index_name
	 * @param string $index_hint_type
	 * @return void
	 */
	public function __construct($index_name, $index_hint_type)
	{
		$this->index_name = $index_name;
		$this->index_hint_type = $index_hint_type;
	}

	public function getIndexName()
	{
		return $this->index_name;
	}

	public function getIndexHintType()
	{
		return $this->index_hint_type;
	}

}
/* End of file IndexHint.class.php */
/* Location: ./classes/db/queryparts/table/IndexHint.class.php */
