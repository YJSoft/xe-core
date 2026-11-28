<?php
require_once dirname(__FILE__) . '/PackageManager.php';

class packageinstaller extends ModuleObject
{
	protected function packageInfo($package)
	{
		$manager = new PackageManager(_XE_PATH_, $package['id']);
		$package['local'] = $manager->inspect();
		$package['directory'] = PackageRegistry::$directories[$package['type']];
		$package['description'] = isset($package['description']) && is_string($package['description']) ? $package['description'] : '';
		$package['icon'] = isset($package['icon']) && in_array($package['icon'], array('bell', 'envelope'), true) ? $package['icon'] : 'cube';
		$actions = $package['local']['actions'];
		$package['badge'] = in_array('update', $actions, true) ? 'updatable' : $package['local']['status'];
		if($package['badge'] === 'absent' && in_array('install', $actions, true)) $package['badge'] = 'installable';
		return $package;
	}
}
