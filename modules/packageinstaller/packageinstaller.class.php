<?php
require_once dirname(__FILE__) . '/PackageManager.php';

class packageinstaller extends ModuleObject
{
	protected function packageInfo($package)
	{
		$manager = new PackageManager(_XE_PATH_, $package['id']);
		$package['local'] = $manager->inspect();
		$package['install_path'] = PackageRegistry::installPath($package);
		$package['thumbnail_url'] = isset($package['thumbnail']) && is_string($package['thumbnail'])
			&& preg_match('/^[a-z][a-z0-9_-]{0,63}\.png$/D', $package['thumbnail']) ? PackageRegistry::BASE . 'thumbnails/' . $package['thumbnail'] : '';
		$package['source_url'] = 'https://github.com/' . $package['repo'] . '/tree/' . $package['revision']
			. (isset($package['source_path']) ? '/' . $package['install_path'] : '');
		$package['download_url'] = $package['online'] && !empty($package['available']) ? PackageRegistry::BASE . 'files/' . $package['sha256'] . '.zip' : '';
		$package['description'] = isset($package['description']) && is_string($package['description']) ? $package['description'] : '';
		$package['icon'] = isset($package['icon']) && in_array($package['icon'], array('bell', 'envelope'), true) ? $package['icon'] : 'cube';
		$package['dependencies'] = $manager->dependencyPlan();
		$needed = $blocked = false;
		foreach($package['dependencies'] as $dependency)
		{
			if($dependency['step'] !== 'ready') $needed = true;
			if($dependency['step'] === 'blocked') $blocked = true;
		}
		$actions = $package['local']['actions'];
		if($blocked) $actions = array_values(array_diff($actions, array('install', 'update', 'restore', 'adopt')));
		elseif($needed && $package['local']['approved_install'] && $package['online']) $actions[] = 'dependencies';
		$package['local']['actions'] = $actions;
		$package['badge'] = in_array('update', $actions, true) ? 'updatable' : $package['local']['status'];
		if($package['badge'] === 'absent' && in_array('install', $actions, true)) $package['badge'] = 'installable';
		return $package;
	}
}
