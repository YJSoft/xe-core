<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * StarExpression
 * Represents the * in 'select * from ...' statements 
 *
 * @author Corina
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class StarExpression extends SelectExpression
{

	/**
	 * constructor, set the column to asterisk
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct("*");
	}

	public function getArgument()
	{
		return null;
	}

	public function getArguments()
	{
		// StarExpression has no arguments
		return array();
	}

}
/* End of file StarExpression.class.php */
/* Location: ./classes/db/queryparts/expression/StarExpression.class.php */
