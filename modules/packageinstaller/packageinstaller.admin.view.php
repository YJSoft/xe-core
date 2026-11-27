<?php
require_once dirname(__FILE__) . '/PackageManager.php';

class packageinstallerAdminView extends packageinstaller
{
	function dispPackageinstallerAdminIndex()
	{
		$user = getModel('member')->getLoggedInfo();
		if(empty($user->is_admin) || $user->is_admin !== 'Y') return $this->stop('msg_is_not_administrator');
		if(empty($_SESSION['packageinstaller_token'])) $_SESSION['packageinstaller_token'] = Security::getRandomToken();

		$query = Context::get('pkg_search');
		$query = is_string($query) ? trim($query) : '';
		$type = Context::get('pkg_type');
		$type = is_string($type) && isset(PackageRegistry::$directories[$type]) ? $type : '';
		$rows = array();
		foreach(PackageRegistry::catalog(_XE_PATH_) as $id => $package)
		{
			if($type !== '' && $package['type'] !== $type) continue;
			if($query !== '' && stripos(implode(' ', array($id, $package['title'], $package['author'], $package['repo'])), $query) === false) continue;
			$manager = new PackageManager(_XE_PATH_, $id);
			try { $package['local'] = $manager->inspect(); }
			catch(RuntimeException $e) { return $this->stop($e->getMessage()); }
			$package['directory'] = PackageRegistry::$directories[$package['type']];
			$rows[] = $package;
		}
		Context::set('pkg_rows', $rows);
		Context::set('pkg_types', array_keys(PackageRegistry::$directories));
		Context::set('pkg_type', $type);
		Context::set('pkg_search', $query);
		Context::set('pkg_token', $_SESSION['packageinstaller_token']);
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('index');
	}
}
