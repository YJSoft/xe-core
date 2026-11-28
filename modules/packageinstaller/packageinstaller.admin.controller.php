<?php

class packageinstallerAdminController extends packageinstaller
{
	private function checkAdminRequest()
	{
		$user = getModel('member')->getLoggedInfo();
		if(empty($user->is_admin) || $user->is_admin !== 'Y') return new BaseObject(-1, 'msg_is_not_administrator');
		if(!Security::checkCSRFRequest() || empty($_SESSION['packageinstaller_token'])
			|| Context::get('pkg_token') !== $_SESSION['packageinstaller_token']) return new BaseObject(-1, 'msg_invalid_request');
	}

	function procPackageinstallerAdminSaveStorage()
	{
		if($error = $this->checkAdminRequest()) return $error;
		$base = Context::get('pkg_storage_base');
		if(!is_string($base)) return new BaseObject(-1, 'msg_invalid_request');
		$storage = new PackageStorage(_XE_PATH_, trim($base));
		try
		{
			$storage->save(realpath(_XE_PATH_) . DIRECTORY_SEPARATOR . 'modules');
		}
		catch(RuntimeException $e)
		{
			$issue = $storage->check(realpath(_XE_PATH_) . DIRECTORY_SEPARATOR . 'modules');
			$_SESSION['packageinstaller_storage_attempt'] = array('base' => $storage->base, 'path' => $issue ? $issue['path'] : '');
			return new BaseObject(-1, $e->getMessage());
		}
		unset($_SESSION['packageinstaller_storage_attempt']);
		$this->setMessage('pkg_storage_saved');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPackageinstallerAdminStorage'));
	}

	function procPackageinstallerAdminApply()
	{
		if($error = $this->checkAdminRequest()) return $error;
		$id = Context::get('pkg_id');
		$catalog = PackageRegistry::catalog(_XE_PATH_);
		if(!is_string($id) || !isset($catalog[$id]) || Context::get('pkg_revision') !== $catalog[$id]['revision'])
			return new BaseObject(-1, 'msg_invalid_request');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminPackageDetail', 'pkg_id', $id));
		try
		{
			$lock = PackageStorage::lock(_XE_PATH_, LOCK_SH);
			$manager = new PackageManager(_XE_PATH_, $id);
			$manager->apply(Context::get('pkg_action'));
			$this->setMessage('pkg_done');
		}
		catch(RuntimeException $e)
		{
			$this->setError(-1);
			$this->setMessage($e->getMessage());
		}

		if(isset($lock)) fclose($lock);

		if(Context::getRequestMethod() === 'JSON')
		{
			try
			{
				$catalog = PackageRegistry::catalog(_XE_PATH_);
				if(!isset($catalog[$id])) return;
				Context::set('pkg', $this->packageInfo($catalog[$id]));
				Context::set('pkg_token', $_SESSION['packageinstaller_token']);
				$template = TemplateHandler::getInstance();
				$this->add('panel', $template->compile($this->module_path . 'tpl', '_package_state'));
			}
			catch(RuntimeException $e)
			{
				// Keep the action outcome. The client can request the current state again.
			}
		}
	}
}
