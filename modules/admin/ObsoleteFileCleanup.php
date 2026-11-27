<?php
/**
 * Adapted from the Rhymix admin file cleanup by Kijin Sung and contributors.
 * GPL-2.0-or-later. See COPYRIGHT for the upstream revision.
 */
class ObsoleteFileCleanup
{
	private $root;

	public function __construct($root)
	{
		$this->root = realpath($root);
	}

	/** Return paths relative to the installation root and their cleanup status. */
	public function checkFiles()
	{
		return $this->process(false);
	}

	/** Recheck each file on submission. Return only paths requiring attention. */
	public function deleteFiles()
	{
		return $this->process(true);
	}

	private function process($delete)
	{
		$result = array();
		if($this->root === false) return $result;
		$list = require dirname(__FILE__) . '/conf/obsolete_files.php';
		clearstatcache(true);
		foreach($list as $name => $entries)
		{
			$this->processEntry($name, $entries, $delete, $result);
		}
		ksort($result);
		return $result;
	}

	private function processEntry($name, $entries, $delete, &$result)
	{
		$path = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
		if(!is_link($path) && !file_exists($path)) return;
		if(is_link($path) || realpath($path) !== $path)
		{
			$result[$name] = 'review';
			return;
		}

		if(is_array($entries))
		{
			if(!is_dir($path) || !is_readable($path))
			{
				$result[$name] = 'review';
				return;
			}
			foreach($entries as $child => $contents)
			{
				$this->processEntry($name . '/' . $child, $contents, $delete, $result);
			}
			// rmdir only accepts an empty directory. Keep unlisted files, including dotfiles.
			$children = @scandir($path);
			if($children === false) $result[$name] = 'review';
			elseif(count($children) === 2)
			{
				if(!$delete) $result[$name . '/'] = 'ready';
				elseif(!@rmdir($path) && is_dir($path)) $result[$name . '/'] = 'failed';
			}
			return;
		}

		if(!is_file($path) || !in_array(@hash_file('sha256', $path), explode(' ', $entries), true))
		{
			$result[$name] = 'review';
		}
		elseif(!$delete)
		{
			$result[$name] = 'ready';
		}
		else
		{
			FileHandler::invalidateOpcache($path);
			if(!@unlink($path) && file_exists($path)) $result[$name] = 'failed';
		}
	}
}
