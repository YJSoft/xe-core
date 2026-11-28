<?php

class packageinstallerAdminController extends packageinstaller
{
	function procPackageinstallerAdminApply()
	{
		$user = getModel('member')->getLoggedInfo();
		if(empty($user->is_admin) || $user->is_admin !== 'Y') return new BaseObject(-1, 'msg_is_not_administrator');
		if(!Security::checkCSRFRequest() || empty($_SESSION['packageinstaller_token'])
			|| Context::get('pkg_token') !== $_SESSION['packageinstaller_token']) return new BaseObject(-1, 'msg_invalid_request');
		$id = Context::get('pkg_id');
		$catalog = PackageRegistry::catalog(_XE_PATH_);
		if(!is_string($id) || !isset($catalog[$id]) || Context::get('pkg_revision') !== $catalog[$id]['revision'])
			return new BaseObject(-1, 'msg_invalid_request');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminPackageDetail', 'pkg_id', $id));
		try
		{
			$manager = new PackageManager(_XE_PATH_, $id);
			$manager->apply(Context::get('pkg_action'));
			$this->setMessage('pkg_done');
		}
		catch(RuntimeException $e)
		{
			$this->setError(-1);
			$this->setMessage($e->getMessage());
		}

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
