<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * @class  page
 * @author XEHub (developers@xpressengine.com)
 * @brief high class of the module page
 */
class page extends ModuleObject
{
	/** Resolve the path that may be saved or rendered. Empty settings are allowed. */
	protected static function resolveExternalPath($path)
	{
		if($path === '') return '';
		if(!is_string($path) || trim($path) !== $path || preg_match('/[\x00-\x1f\x7f]/', $path)) return false;

		$normalized = str_replace('\\', '/', $path);
		// Allow installations that share pages above the XE root.
		$relative = preg_replace('!^(?:\.\./)+!', '', $normalized);
		if(preg_match('!(?:^|/)\.\.(?:/|$)!', $relative)) return false;

		$restricted = '!(?:^|/)files/(?:attach|cache|config|debug|env|member_extra_info|ruleset|site_design|thumbnails)/!i';
		if(preg_match($restricted, $normalized)) return false;
		$is_windows_path = DIRECTORY_SEPARATOR === '\\' && preg_match('!^[a-z]:/!i', $normalized);
		if(!$is_windows_path && preg_match('!^[a-z][a-z0-9+.-]*:!i', $normalized))
		{
			if($normalized !== $path || !preg_match('!^https?://!i', $path)) return false;
			$url = @parse_url($path);
			return !empty($url['host']) ? $path : false;
		}

		$resolved = realpath(FileHandler::getRealPath($path));
		if($resolved === false || !is_file($resolved) || preg_match($restricted, str_replace('\\', '/', $resolved))) return false;
		return $resolved;
	}

	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// page generated from the cache directory to use
		FileHandler::makeDir('./files/cache/page');

		return new BaseObject();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$version_update_id = implode('.', array(__CLASS__, __XE_VERSION__, 'updated'));
		if($oModuleModel->needUpdate($version_update_id))
		{
			$output = executeQuery('page.pageTypeOpageCheck');
			if($output->toBool() && $output->data) return true;

			$output = executeQuery('page.pageTypeNullCheck');
			if($output->toBool() && $output->data) return true;

			$oModuleController->insertUpdatedLog($version_update_id);
		}

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$version_update_id = implode('.', array(__CLASS__, __XE_VERSION__, 'updated'));
		if($oModuleModel->needUpdate($version_update_id))
		{
			$args = new stdClass;
			// opage module instance update
			$output = executeQueryArray('page.pageTypeOpageCheck');
			if($output->toBool() && count($output->data) > 0)
			{
				foreach($output->data as $val)
				{
					$args->module_srl = $val->module_srl;
					$args->name = 'page_type';
					$args->value= 'OUTSIDE';
					$in_out = executeQuery('page.insertPageType', $args);
				}
				$output = executeQuery('page.updateAllOpage');
				if(!$output->toBool()) return $output;
			}

			// old page module instance update
			$output = executeQueryArray('page.pageTypeNullCheck');
			$skin_update_srls = array();
			if($output->toBool() && $output->data)
			{
				foreach($output->data as $val)
				{
					$args->module_srl = $val->module_srl;
					$args->name = 'page_type';
					$args->value= 'WIDGET';
					$in_out = executeQuery('page.insertPageType', $args);

					$skin_update_srls[] = $val->module_srl;
				}
			}

			if(count($skin_update_srls)>0)
			{
				$skin_args = new stdClass;
				$skin_args->module_srls = implode(',',$skin_update_srls);
				$skin_args->is_skin_fix = "Y";
				$ouput = executeQuery('page.updateSkinFix', $skin_args);
			}

			$oModuleController->insertUpdatedLog($version_update_id);
		}

		return new BaseObject(0,'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file page.class.php */
/* Location: ./modules/page/page.class.php */
