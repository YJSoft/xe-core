<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * @file  index.php
 * @author XEHub (developers@xpressengine.com)
 * @brief Start page
 *
 * @mainpage XpressEngine
 * @section intro introduction
 *
 * "XpressEngine (XE)" is an opensource and being developed in the opensource project.\n
 * For more information, please see the link below.
 * - Official website: http://www.xpressengine.com
 * - Offcial Repository: https://github.com/xpressengine/xe-core
 * \n
 * YJSoft XE Fork is free software distributed under the GNU General Public
 * License, version 2 or (at your option) any later version.
 * See LICENSE for the full terms and warranty disclaimer.
 *
 **/

/**
 * @brief Declare constants for generic use and for checking to avoid a direct call from the Web
 **/
define('__XE__',   TRUE);
/**
 * @brief Include the necessary configuration files
 **/
require dirname(__FILE__) . '/config/config.inc.php';

/**
 * @brief Initialize by creating Context object
 * Set all Request Argument/Environment variables
 **/
$oContext = Context::getInstance();
$oContext->init();

/**
 * @brief If default_url is set and it is different from the current url, attempt to redirect for SSO authentication and then process the module
 **/
if($oContext->checkSSO())
{
	try
	{
		$oModuleHandler = new ModuleHandler();
		if($oModuleHandler->init())
		{
			$oModuleHandler->displayContent($oModuleHandler->procModule());
		}
	}
	catch(Exception $e)
	{
		htmlHeader();
		echo Context::getLang($e->getMessage());
		htmlFooter();
	}
}

$oContext->close();

/* End of file index.php */
/* Location: ./index.php */
