<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * UpdateExpression
 *
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class UpdateExpressionWithoutArgument extends UpdateExpression
{

	/**
	 * argument
	 * @var object
	 */
	public $argument;

	/**
	 * constructor
	 * @param string $column_name
	 * @param object $argument
	 * @return void
	 */
	public function __construct($column_name, $argument)
	{
		Expression::__construct($column_name);
		$this->argument = $argument;
	}

	public function getExpression($with_value = true)
	{
		return "$this->column_name = $this->argument";
	}

	public function getValue()
	{
		// TODO Escape value according to column type instead of variable type
		$value = $this->argument;
		if(!is_numeric($value))
		{
			return "'" . $value . "'";
		}
		return $value;
	}

	public function show()
	{
		if(!$this->argument)
		{
			return false;
		}
		$value = $this->argument;
		if(!isset($value))
		{
			return false;
		}
		return true;
	}

	public function getArgument()
	{
		return null;
	}

	public function getArguments()
	{
		return array();
	}

}
/* End of file UpdateExpressionWithoutArgument.class.php */
/* Location: ./classes/db/queryparts/expression/UpdateExpressionWithoutArgument.class.php */
