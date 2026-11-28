<?php

class packageinstallerAdminView extends packageinstaller
{
	function init()
	{
		$user = getModel('member')->getLoggedInfo();
		if(empty($user->is_admin) || $user->is_admin !== 'Y') return $this->stop('msg_is_not_administrator');
		$this->setTemplatePath($this->module_path . 'tpl');
		$type = Context::get('type');
		if($type === null) $type = Context::get('pkg_type');
		$tabs = array_merge(array('all', 'updates'), array_keys(PackageRegistry::$directories));
		Context::set('pkg_tabs', $tabs);
		Context::set('pkg_type', in_array($type, $tabs, true) ? $type : 'all');
		$query = Context::get('search_keyword');
		if($query === null) $query = Context::get('pkg_search');
		Context::set('pkg_search', is_string($query) ? trim($query) : '');
	}

	private function packageToken()
	{
		if(empty($_SESSION['packageinstaller_token'])) $_SESSION['packageinstaller_token'] = Security::getRandomToken();
		return $_SESSION['packageinstaller_token'];
	}

	function dispPackageinstallerAdminStorage()
	{
		try { $storage = new PackageStorage(_XE_PATH_); }
		catch(RuntimeException $e) { return $this->stop($e->getMessage()); }
		$attempt = isset($_SESSION['packageinstaller_storage_attempt']) ? $_SESSION['packageinstaller_storage_attempt'] : null;
		unset($_SESSION['packageinstaller_storage_attempt']);
		Context::set('pkg_attempt', $attempt);
		$target = realpath(_XE_PATH_) . DIRECTORY_SEPARATOR . 'modules';
		Context::set('pkg_storage', $storage);
		Context::set('pkg_storage_issue', $storage->check($target));
		$root = empty($_SERVER['DOCUMENT_ROOT']) ? false : realpath($_SERVER['DOCUMENT_ROOT']);
		Context::set('pkg_example', dirname($root ? $root : realpath(_XE_PATH_)) . DIRECTORY_SEPARATOR . 'xe-private');
		Context::set('pkg_token', $this->packageToken());
		$this->setTemplateFile('storage');
	}

	function dispAutoinstallAdminIndex()
	{
		$query = Context::get('pkg_search');
		$type = Context::get('pkg_type');
		$rows = array();
		foreach(PackageRegistry::catalog(_XE_PATH_) as $package)
		{
			if($type !== 'all' && $type !== 'updates' && $package['type'] !== $type) continue;
			if($query !== '' && stripos(implode(' ', array($package['id'], $package['title'], $package['author'], $package['repo'])), $query) === false) continue;
			try { $package = $this->packageInfo($package); }
			catch(RuntimeException $e) { return $this->stop($e->getMessage()); }
			if($type === 'updates' && $package['badge'] !== 'updatable') continue;
			$rows[] = $package;
		}
		Context::set('pkg_rows', $rows);
		Context::setBrowserTitle(Context::getLang('pkg_title'));
		$this->setTemplateFile('index');
	}

	function dispPackageinstallerAdminIndex()
	{
		return $this->dispAutoinstallAdminIndex();
	}

	function dispAutoinstallAdminPackageDetail()
	{
		$id = Context::get('pkg_id');
		$catalog = PackageRegistry::catalog(_XE_PATH_);
		$this->setTemplateFile('package_detail');
		Context::set('pkg', null);
		if(!is_string($id) || !isset($catalog[$id])) return;
		try { $package = $this->packageInfo($catalog[$id]); }
		catch(RuntimeException $e) { return $this->stop($e->getMessage()); }
		Context::set('pkg', $package);
		Context::set('pkg_token', $this->packageToken());
		Context::setBrowserTitle($package['title'] . ' - ' . Context::getLang('pkg_title'));
	}

	function dispAutoinstallAdminInstall()
	{
		return $this->dispAutoinstallAdminPackageDetail();
	}
}
