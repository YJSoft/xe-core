<?php
require_once dirname(__FILE__) . '/PackageArchive.php';
require_once dirname(__FILE__) . '/PackageRegistry.php';
require_once dirname(__FILE__) . '/PackageStorage.php';

class PackageManager
{
	private $site;
	private $parentDirectory;
	private $store;
	private $storage;
	private $work;
	private $target;
	private $package;
	public $failedDependency;
	public $installedDependencies = 0;

	public function __construct($site, $id)
	{
		$catalog = PackageRegistry::catalog($site);
		if(!is_string($id) || !isset($catalog[$id])) throw new RuntimeException('msg_invalid_request');
		$this->package = $catalog[$id];
		$this->site = $site = realpath($site);
		$this->target = $site . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, PackageRegistry::installPath($this->package));
		$this->parentDirectory = dirname($this->target);
		$this->storage = new PackageStorage($site);
		$this->store = $this->storage->directory;
		$this->work = $this->store . DIRECTORY_SEPARATOR . $id;
	}

	private function readRecord($name)
	{
		$path = $this->work . '/' . $name . '.json';
		if(!file_exists($path)) return null;
		$value = json_decode(file_get_contents($path), true);
		if(!is_array($value)) throw new RuntimeException('pkg_storage_record');
		return $value;
	}

	private function saveRecord($name, $value)
	{
		$path = $this->work . '/' . $name . '.json';
		$data = json_encode($value);
		if($data === false || file_put_contents($path . '.tmp', $data) !== strlen($data) || !rename($path . '.tmp', $path))
			throw new RuntimeException('pkg_storage_error');
	}

	private function installed(&$inventory = null)
	{
		if(!is_link($this->target) && !file_exists($this->target)) return null;
		return PackageArchive::fingerprint($this->target, $inventory);
	}

	private function known($hash, $state)
	{
		$p = $this->package;
		if(isset($state['active']['tree']) && $state['active']['tree'] === $hash) return $state['active'];
		if($hash === $p['tree']) return array('version' => $p['version'], 'tree' => $hash);
		if(is_string($hash) && isset($p['upgrade_from'][$hash])) return array('version' => $p['upgrade_from'][$hash], 'tree' => $hash);
		return null;
	}

	public function inspect()
	{
		$p = $this->package;
		$issue = $this->storage->check($this->parentDirectory, $p['id']);
		$available = !$issue;
		$state = $available ? $this->readRecord('state') : null;
		$pending = $available && file_exists($this->work . '/transaction.json');
		$inventory = array();
		$current = $this->installed($inventory);
		$known = $this->known($current, $state);
		$active = isset($state['active']) ? $state['active'] : null;
		$changed = $active && $active['tree'] !== $current;
		$differences = array();
		if($changed && isset($state['files']) && is_string($current))
		{
			$differences = array_keys(array_diff_assoc($state['files'], $inventory) + array_diff_assoc($inventory, $state['files']));
			sort($differences, SORT_STRING);
		}
		$status = $current === null ? 'absent' : 'manual';
		if($known) $status = $active ? 'managed' : 'recognized';
		if($changed || ($current !== null && !$known)) $status = 'modified';
		if($pending) $status = 'pending';
		$compatible = version_compare(PHP_VERSION, $p['php'], '>=') && version_compare(__XE_VERSION__, $p['core'], '>=');
		$download = $compatible && $p['online'] && !empty($p['available']) && function_exists('curl_init') && class_exists('ZipArchive');
		$actions = array();
		if($available && $pending) $actions[] = 'recover';
		elseif($available && $status !== 'modified')
		{
			if($download && $current !== $p['tree'] && (!$known || version_compare($p['version'], $known['version'], '>='))) $actions[] = $current === null ? 'install' : 'update';
			if(!$active && $p['online'] && !empty($p['available']) && $current === $p['tree']) $actions[] = 'adopt';
			if($active && $known) $actions[] = 'remove';
			// Only offer explicit restore to the currently approved release.
			if($p['online'] && isset($state['previous']['tree'], $p['approved'][$state['previous']['tree']])
				&& $current !== $state['previous']['tree']) $actions[] = 'restore';
		}
		return array('status' => $status, 'version' => $known ? $known['version'] : '',
			'approved_install' => !$pending && !$changed && $known && isset($p['approved'])
				&& (isset($p['approved'][$current]) || (isset($p['upgrade_from'][$current]) && in_array($p['upgrade_from'][$current], $p['approved'], true))),
			'actions' => $actions, 'storage' => $available, 'storage_issue' => $issue, 'download' => $download,
			'differences' => $differences, 'history' => isset($state['history']) ? array_reverse($state['history']) : array());
	}

	private function move($from, $to)
	{
		if(file_exists($to) || is_link($to) || !@rename($from, $to)) throw new RuntimeException('pkg_storage_error');
		clearstatcache(true);
	}

	private function discard($path)
	{
		if(is_link($path) || is_file($path))
		{
			if(!unlink($path)) throw new RuntimeException('pkg_storage_error');
		}
		elseif(is_dir($path))
		{
			foreach(new FilesystemIterator($path) as $file) $this->discard($file->getPathname());
			if(!rmdir($path)) throw new RuntimeException('pkg_storage_error');
		}
	}

	private function invalidateFiles($tree)
	{
		if(!is_dir($tree)) return;
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tree, FilesystemIterator::SKIP_DOTS));
		foreach($files as $file)
		{
			$relative = substr($file->getPathname(), strlen($tree));
			if(substr($relative, -5) === '.html')
			{
				$template = PackageRegistry::installPath($this->package) . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
				$compiled = $this->site . '/files/cache/template_compiled/' . md5($template . __XE_VERSION__) . '.compiled.php';
				if(is_file($compiled))
				{
					if(function_exists('opcache_invalidate')) @opcache_invalidate($compiled, true);
					unlink($compiled);
				}
			}
			elseif(function_exists('opcache_invalidate') && substr($relative, -4) === '.php')
			{
				@opcache_invalidate($this->target . $relative, true);
			}
		}
	}

	private function finish($tx)
	{
		$files = array();
		$expected = $tx['state']['active'];
		if($this->installed($files) !== ($expected ? $expected['tree'] : null)) throw new RuntimeException('pkg_recovery_error');
		$tx['state']['files'] = $files;
		$this->invalidateFiles($this->target);
		$pattern = $this->site . '/files/cache/module_info/' . $this->package['id'] . '.*.php';
		foreach($this->package['type'] === 'module' ? (array)glob($pattern) : array() as $metadata)
		{
			if($metadata && is_file($metadata))
			{
				if(function_exists('opcache_invalidate')) @opcache_invalidate($metadata, true);
				unlink($metadata);
			}
		}
		$this->saveRecord('state', $tx['state']);
		if(!unlink($this->work . '/transaction.json')) throw new RuntimeException('pkg_storage_error');
	}

	private function recover()
	{
		$tx = $this->readRecord('transaction');
		if(!$tx) return;
		$current = $this->installed();
		$new = $tx['state']['active'];
		$old = $tx['state']['previous'];
		$backup = $this->work . '/' . $tx['backup'];
		if($current === ($new ? $new['tree'] : null) && (!$old || is_dir($backup)))
		{
			$this->finish($tx);
			return;
		}
		if($current === null && $old && PackageArchive::fingerprint($backup) === $old['tree'])
		{
			$this->move($backup, $this->target);
		}
		elseif($current !== ($old ? $old['tree'] : null) || is_dir($backup))
		{
			throw new RuntimeException('pkg_recovery_error');
		}
		if(!unlink($this->work . '/transaction.json')) throw new RuntimeException('pkg_storage_error');
	}

	private function cleanWork()
	{
		$state = $this->readRecord('state');
		foreach(new FilesystemIterator($this->work) as $entry)
		{
			$name = $entry->getFilename();
			if($name === 'stage' || $name === 'download.zip' || (preg_match('/^backup-[0-9a-f.]+$/D', $name)
				&& (!isset($state['backup']) || $name !== $state['backup']))) $this->discard($entry->getPathname());
		}
	}

	public function dependencyPlan()
	{
		$catalog = PackageRegistry::catalog($this->site);
		$plan = array();
		foreach(PackageRegistry::dependencies($catalog, $this->package['id']) as $id)
		{
			$manager = new self($this->site, $id);
			$info = $manager->inspect();
			$step = 'blocked';
			if($info['approved_install']) $step = 'ready';
			elseif(in_array('install', $info['actions'], true)) $step = 'install';
			elseif(in_array('update', $info['actions'], true)) $step = 'update';
			$plan[$id] = array('title' => $catalog[$id]['title'], 'step' => $step);
		}
		return $plan;
	}

	public function apply($action)
	{
		if(!in_array($action, array('install', 'update', 'restore', 'adopt', 'dependencies'), true)) return $this->applyFiles($action);
		$id = $this->package['id'];
		$catalog = PackageRegistry::catalog($this->site, true);
		if(empty($catalog[$id]['online'])) throw new RuntimeException('pkg_registry_error');
		if($catalog[$id]['revision'] !== $this->package['revision']) throw new RuntimeException('pkg_conflict');
		$this->package = $catalog[$id];
		$info = $this->inspect();
		if($action === 'dependencies')
		{
			if(!$info['approved_install'] || empty($this->package['requires'])) throw new RuntimeException('pkg_conflict');
		}
		elseif(!in_array($action, $info['actions'], true)) throw new RuntimeException('pkg_conflict');

		$plan = $this->dependencyPlan();
		foreach($plan as $dep => $item)
		{
			if($item['step'] === 'blocked')
			{
				$this->failedDependency = $dep;
				throw new RuntimeException('pkg_dependency_blocked');
			}
		}
		foreach($plan as $dep => $item)
		{
			if($item['step'] === 'ready') continue;
			$this->failedDependency = $dep;
			$manager = new self($this->site, $dep);
			$manager->applyFiles($item['step']);
			$this->installedDependencies++;
			$this->failedDependency = null;
		}
		if($action !== 'dependencies') $this->applyFiles($action);
	}

	private function applyFiles($action)
	{
		if(!in_array($action, array('install', 'update', 'adopt', 'remove', 'restore', 'recover'), true)) throw new RuntimeException('msg_invalid_request');
		$issue = $this->storage->check($this->parentDirectory, $this->package['id']);
		if($issue) throw new RuntimeException($issue['error']);
		foreach(array($this->store, $this->work) as $dir)
		{
			if(!is_dir($dir) && !@mkdir($dir, 0700) && !is_dir($dir)) throw new RuntimeException('pkg_storage_mkdir');
		}
		$issue = $this->storage->check($this->parentDirectory, $this->package['id']);
		if($issue) throw new RuntimeException($issue['error']);
		$lock = @fopen($this->work . '/lock', 'c');
		if(!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('pkg_busy');
		try
		{
			$this->recover();
			$this->cleanWork();
			if($action !== 'recover') $this->change($action);
			$this->cleanWork();
		}
		catch(Exception $error)
		{
			// A terminated process leaves the same journal for the next administrator request.
			if($error->getMessage() !== 'pkg_recovery_error')
			{
				$this->recover();
				$this->cleanWork();
			}
			fclose($lock);
			throw $error;
		}
		fclose($lock);
	}

	private function change($action)
	{
		$info = $this->inspect();
		if(!in_array($action, $info['actions'], true)) throw new RuntimeException('pkg_conflict');
		$p = $this->package;
		$state = $this->readRecord('state');
		$current = $this->installed();
		$old = $this->known($current, $state);
		$new = array('version' => $p['version'], 'tree' => $p['tree']);
		$history = isset($state['history']) ? $state['history'] : array();
		$history[] = array('time' => gmdate('Y-m-d H:i:s'), 'action' => $action, 'from' => $old ? $old['version'] : '',
			'to' => $action === 'remove' ? '' : $new['version']);
		$backup = 'backup-' . uniqid('', true);
		$next = array('active' => $action === 'remove' ? null : $new, 'previous' => $old, 'backup' => $backup,
			'history' => array_slice($history, -25));
		if($action === 'adopt')
		{
			if($this->installed($files) !== $p['tree']) throw new RuntimeException('pkg_conflict');
			$next['files'] = $files;
			$next['previous'] = $next['backup'] = null;
			$this->saveRecord('state', $next);
			return;
		}

		$source = $this->work . '/stage';
		if($action === 'restore')
		{
			$source = $this->work . '/' . $state['backup'];
			$new = $state['previous'];
			$next['active'] = $new;
			$last = count($next['history']) - 1;
			$next['history'][$last]['to'] = $new['version'];
			if(PackageArchive::fingerprint($source) !== $new['tree']) throw new RuntimeException('pkg_recovery_error');
		}
		elseif($action !== 'remove')
		{
			$free = disk_free_space($this->work);
			if($free !== false && $free < PackageArchive::MAX_BYTES + $p['size']) throw new RuntimeException('pkg_storage_space');
			if(!mkdir($source, 0755)) throw new RuntimeException('pkg_storage_error');
			$archive = $this->work . '/download.zip';
			PackageArchive::download($p, $archive);
			PackageArchive::extract($p, $archive, $source);
		}
		// Recheck after the download, immediately before moving the live directory.
		if($this->installed() !== $current || ($current !== null && !$old)) throw new RuntimeException('pkg_conflict');
		$tx = array('backup' => $backup, 'state' => $next);
		$this->saveRecord('transaction', $tx);
		if($old)
		{
			$this->invalidateFiles($this->target);
			$this->move($this->target, $this->work . '/' . $backup);
		}
		if($action !== 'remove') $this->move($source, $this->target);
		$this->finish($tx);
	}
}
