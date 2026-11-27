<?php
require_once dirname(__FILE__) . '/PackageManager.php';

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
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPackageinstallerAdminIndex'));
		try
		{
			$manager = new PackageManager(_XE_PATH_, $id);
			$manager->apply(Context::get('pkg_action'));
		}
		catch(RuntimeException $e)
		{
			$this->setError(-1);
			$this->setMessage($e->getMessage());
			return;
		}
		$this->setMessage('pkg_done');
	}
}
