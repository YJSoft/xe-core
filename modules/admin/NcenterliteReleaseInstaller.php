<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

class NcenterliteReleaseInstaller
{
	const ZIP_URL = 'https://github.com/YJSoft/xe-module-ncenterlite/archive/refs/tags/3.0.10.zip';
	const MODULE_PATH = './modules/ncenterlite';

	private $download_path;

	protected function installModule()
	{
		$model = getModel('module');
		if($model->checkNeedInstall('ncenterlite'))
		{
			getController('install')->installModule('ncenterlite', ModuleHandler::getModulePath('ncenterlite'));
		}
		if($model->checkNeedUpdate('ncenterlite'))
		{
			$module = getModule('ncenterlite', 'class');
			if(method_exists($module, 'moduleUpdate')) $module->moduleUpdate();
		}
	}

	/** Read the installed manifest, including the older XE 0.1 module format. */
	public static function getAffectedVersion()
	{
		$dir = FileHandler::getRealPath(self::MODULE_PATH);
		if(!is_file($dir . '/ncenterlite.class.php')) return false;
		$info = XeXmlParser::loadXmlFile($dir . '/conf/info.xml');
		if(!isset($info->module->attrs->version)) return false;
		$version = $info->module->attrs->version;
		if($version === '0.2')
		{
			$version = isset($info->module->version->body) ? trim($info->module->version->body) : '';
		}
		return preg_match('/\A[0-9][0-9a-z.+_-]*\z/i', $version) && version_compare($version, '3.0.9', '<=') ? $version : false;
	}

	public static function canInstall()
	{
		$dir = FileHandler::getRealPath(self::MODULE_PATH);
		foreach(array('', '/conf', '/conf/info.xml', '/ncenterlite.model.php', '/ncenterlite.admin.model.php', '/package.json') as $suffix)
		{
			$path = $dir . $suffix;
			if(is_link($path) || (file_exists($path) && !is_writable($path))) return false;
		}
		return class_exists('ZipArchive') && function_exists('curl_init') && defined('CURLOPT_REDIR_PROTOCOLS')
			&& is_writable(_XE_PATH_ . 'files/cache') && is_writable(sys_get_temp_dir());
	}

	public function install()
	{
		if(self::getAffectedVersion() === false) return new BaseObject();
		if(!self::canInstall()) return new BaseObject(-1, 'ncenterlite_patch_failed');
		$lock = @fopen(_XE_PATH_ . 'files/cache/ncenterlite-release.lock', 'c');
		if(!$lock) return new BaseObject(-1, 'ncenterlite_patch_failed');
		$output = new BaseObject(-1, 'ncenterlite_patch_failed');
		if(!flock($lock, LOCK_EX | LOCK_NB))
		{
			fclose($lock);
			return $output;
		}
		if(self::getAffectedVersion() === false)
		{
			fclose($lock);
			return new BaseObject();
		}
		$archive = tempnam(sys_get_temp_dir(), 'xe-ncenterlite-');
		if($archive !== false)
		{
			$work = $archive . '.d';
			if(@mkdir($work, 0700))
			{
				if($this->downloadRelease($archive) && $this->applyArchive($archive, $work))
				{
					$output = new BaseObject(0, 'ncenterlite_patch_installed');
				}
				FileHandler::removeDir($work);
			}
			unlink($archive);
		}
		if($output->toBool()) $this->installModule();
		fclose($lock);
		return $output;
	}

	protected function downloadRelease($archive)
	{
		$file = fopen($archive, 'wb');
		if(!$file) return false;
		$curl = curl_init(self::ZIP_URL);
		$options = array(CURLOPT_FILE => $file, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30, CURLOPT_FAILONERROR => true,
			CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2);
		if(defined('CURLOPT_REDIR_PROTOCOLS_STR'))
		{
			$options[CURLOPT_REDIR_PROTOCOLS_STR] = 'https';
		}
		else
		{
			$options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
		}
		$ok = curl_setopt_array($curl, $options) && curl_exec($curl) && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200;
		curl_close($curl);
		fclose($file);
		return $ok;
	}

	protected function applyArchive($archive, $work)
	{
		$zip = new ZipArchive();
		if($zip->open($archive, ZipArchive::CHECKCONS) !== true) return false;
		$this->download_path = $work . '/release';
		$files = $this->stageRelease($zip);
		$zip->close();
		if($files === false) return false;

		$root = str_replace('\\', '/', realpath(FileHandler::getRealPath(self::MODULE_PATH)));
		$backup = $work . '/backup';
		$changed = array();
		$new_dirs = array();
		foreach($files as $file)
		{
			$target = $root . '/' . $file;
			$parent = dirname($target);
			while(!file_exists($parent) && !is_link($parent))
			{
				$new_dirs[$parent] = true;
				$parent = dirname($parent);
			}
			if(str_replace('\\', '/', (string)realpath($parent)) !== $parent || !is_dir($parent) || is_link($target)) return false;
			if(file_exists($target))
			{
				if(!is_file($target) || !is_readable($target)) return false;
				if(hash_file('sha256', $target) === hash_file('sha256', $this->download_path . '/' . $file)) continue;
				if(!is_writable($target) || !FileHandler::makeDir(dirname($backup . '/' . $file))
					|| !@copy($target, $backup . '/' . $file)) return false;
			}
			if(!is_writable($parent)) return false;
			$changed[] = $file;
		}

		// Publish the version manifest last and restore completed copies on failure.
		$copied = array();
		foreach($changed as $file)
		{
			$target = $root . '/' . $file;
			if(!$this->copyInstallFile($this->download_path . '/' . $file, $target)) break;
			$copied[] = $target;
		}
		FileHandler::clearStatCache($copied, true);
		FileHandler::invalidateOpcache($copied);
		if(count($copied) === count($changed)) return true;

		foreach(array_reverse($changed) as $file)
		{
			$target = $root . '/' . $file;
			if(!is_file($target) || hash_file('sha256', $target) !== hash_file('sha256', $this->download_path . '/' . $file)) continue;
			if(is_file($backup . '/' . $file)) $this->copyInstallFile($backup . '/' . $file, $target);
			else unlink($target);
			FileHandler::clearStatCache($target);
			FileHandler::invalidateOpcache($target);
		}
		krsort($new_dirs, SORT_STRING); // Remove children before their parents.
		foreach($new_dirs as $dir => $unused)
		{
			if(is_dir($dir)) @rmdir($dir);
		}
		return false;
	}

	/** Replace each file only after its entire contents have been copied. */
	protected function copyInstallFile($source, $target)
	{
		$dir = dirname($target);
		if(!is_dir($dir) && !FileHandler::makeDir($dir)) return false;
		$temp = @tempnam($dir, '.xe-install-');
		if($temp === false) return false;

		$mode = is_file($target) ? fileperms($target) & 0777 : 0666 & ~umask();
		$ok = realpath(dirname($temp)) === realpath($dir) && @copy($source, $temp)
			&& filesize($source) === filesize($temp) && @chmod($temp, $mode) && @rename($temp, $target);
		if(!$ok) @unlink($temp);
		return $ok;
	}

	/** Strip one GitHub wrapper and validate the release before updating any installed file. */
	private function stageRelease($zip)
	{
		$root = strtok($zip->getNameIndex(0), '/') . '/';
		if(!in_array($root, array('xe-module-ncenterlite-3.0.10/', 'xe-module-ncenterlite/'), true)) return false;
		$files = array();
		$total = 0;
		for($i = 0; $i < $zip->numFiles; $i++)
		{
			$entry = $zip->statIndex($i);
			if($entry === false) return false;
			$name = $entry['name'];
			if(strpos($name, $root) !== 0 || strpos($name, "\0") !== false
				|| in_array('..', explode('/', str_replace('\\', '/', $name)), true)) return false;
			$file = substr($name, strlen($root));
			if($file === '' || substr($file, -1) === '/') continue;
			if(!preg_match('~\A[\w-][\w.-]*(?:/[\w-][\w.-]*)*\z~', $file) || isset($files[$file])) return false;
			$total += $entry['size'];
			if($total > 16 * 1024 * 1024) return false;
			$data = $zip->getFromIndex($i);
			$path = $this->download_path . '/' . $file;
			if($data === false || strlen($data) !== $entry['size'] || !FileHandler::makeDir(dirname($path))
				|| file_put_contents($path, $data) !== $entry['size']) return false;
			$files[$file] = $file;
		}
		if(!isset($files['conf/info.xml'], $files['conf/module.xml'], $files['ncenterlite.class.php'],
			$files['ncenterlite.model.php'], $files['ncenterlite.admin.model.php'])) return false;
		$info = XeXmlParser::loadXmlFile($this->download_path . '/conf/info.xml');
		if(!isset($info->module->version->body) || trim($info->module->version->body) !== '3.0.10') return false;
		unset($files['conf/info.xml']);
		$files[] = 'conf/info.xml';
		return $files;
	}
}
