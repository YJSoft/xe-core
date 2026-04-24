<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * adminlogging class
 * Base class of adminlogging module
 *
 * @author XEHub (developers@xpressengine.com)
 * @package /modules/adminlogging
 * @version 0.1
 */
class adminlogging extends ModuleObject
{

	/**
	 * Install adminlogging module
	 * @return BaseObject
	 */
	public function moduleInstall()
	{
		return new BaseObject();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	public function checkUpdate()
	{
		return FALSE;
	}

	/**
	 * Update module
	 * @return BaseObject
	 */
	public function moduleUpdate()
	{
		return new BaseObject();
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	public function recompileCache()
	{
		
	}

}
/* End of file adminlogging.class.php */
/* Location: ./modules/adminlogging/adminlogging.class.php */
