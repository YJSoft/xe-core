<?php

class PackageStorage
{
	public $base;
	public $directory;
	private $site;
	const HEADER = "<?php exit; __halt_compiler();\n";

	public function __construct($site, $base = null)
	{
		$this->site = realpath($site);
		if($base === null)
		{
			$config = $this->site . '/files/config/package-storage.php';
			if(file_exists($config))
			{
				$value = @file_get_contents($config);
				if(strpos((string)$value, self::HEADER) !== 0) throw new RuntimeException('pkg_storage_config_error');
				$base = substr($value, strlen(self::HEADER));
			}
			else
			{
				$base = defined('XE_PACKAGE_STORAGE') ? XE_PACKAGE_STORAGE : dirname($this->site);
			}
		}
		// Preserve filesystem roots while accepting a trailing slash on a directory.
		if(!is_string($base)) throw new RuntimeException('pkg_storage_config_error');
		if($base !== '' && dirname($base) !== $base)
			$base = rtrim($base, DIRECTORY_SEPARATOR);
		$this->base = $base;
		$this->directory = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.xe-packages-' . substr(hash('sha256', $this->site), 0, 16);
	}

	public function check($target, $id = '')
	{
		if($this->base === '' || strpos($this->base, "\0") !== false
			|| @realpath($this->base) !== $this->base || !is_dir($this->base))
			return array('error' => 'pkg_storage_base', 'path' => $this->base);

		$roots = array($this->site);
		if(!empty($_SERVER['DOCUMENT_ROOT']) && ($root = realpath($_SERVER['DOCUMENT_ROOT']))) $roots[] = $root;
		foreach($roots as $root)
		{
			if(strpos($this->directory . DIRECTORY_SEPARATOR, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0)
				return array('error' => 'pkg_storage_public', 'path' => $root);
		}

		$dirs = array($this->directory);
		if($id !== '') $dirs[] = $this->directory . DIRECTORY_SEPARATOR . $id;
		$existing = $this->base;
		foreach($dirs as $dir)
		{
			if(is_link($dir) || (file_exists($dir) && !is_dir($dir)))
				return array('error' => 'pkg_storage_directory', 'path' => $dir);
			if(!is_dir($dir)) break;
			if((fileperms($dir) & 0077) || !is_writable($dir) || !is_readable($dir)
				|| (DIRECTORY_SEPARATOR === '/' && !is_executable($dir)))
				return array('error' => 'pkg_storage_private', 'path' => $dir);
			$existing = $dir;
		}
		if(!is_writable($existing) || (DIRECTORY_SEPARATOR === '/' && !is_executable($existing)))
			return array('error' => 'pkg_storage_create', 'path' => $this->directory);
		if(realpath($target) !== $target || !is_dir($target))
			return array('error' => 'pkg_storage_target_path', 'path' => $target);
		if(!is_writable($target) || (DIRECTORY_SEPARATOR === '/' && !is_executable($target)))
			return array('error' => 'pkg_storage_target_write', 'path' => $target);
		$dest = stat($target);
		$source = stat($existing);
		if(!$source || !$dest || $source['dev'] !== $dest['dev'])
			return array('error' => 'pkg_storage_device', 'path' => $target);
		return null;
	}

	// A settings change cannot race the first installation at the previous location.
	public static function lock($site, $mode)
	{
		$lock = @fopen($site . '/files/config/package-storage.lock', 'c');
		if(!$lock) throw new RuntimeException('pkg_storage_config_error');
		if(!flock($lock, $mode | LOCK_NB))
		{
			fclose($lock);
			throw new RuntimeException('pkg_storage_busy');
		}
		return $lock;
	}

	public function save($target)
	{
		$lock = self::lock($this->site, LOCK_EX);
		try
		{
			$issue = $this->check($target);
			if($issue) throw new RuntimeException($issue['error']);
			$previous = new self($this->site);
			if($previous->directory !== $this->directory && is_dir($previous->directory))
			{
				$entries = @scandir($previous->directory);
				if($entries === false || array_diff($entries, array('.', '..')))
					throw new RuntimeException('pkg_storage_has_records');
			}
			$config = $this->site . '/files/config/package-storage.php';
			$data = self::HEADER . $this->base;
			// The temporary file also has a PHP extension so it cannot serve the path as text.
			$temp = $config . '.tmp.php';
			if(file_put_contents($temp, $data) !== strlen($data) || !rename($temp, $config))
				throw new RuntimeException('pkg_storage_config_error');
		}
		catch(Exception $e)
		{
			fclose($lock);
			throw $e;
		}
		fclose($lock);
	}
}
