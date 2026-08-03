<?php
$_SERVER['SCRIPT_NAME'] = '/xe/index.php';
if(!isset($GLOBALS['lang']) || !is_object($GLOBALS['lang']))
{
	$GLOBALS['lang'] = new stdClass();
}
