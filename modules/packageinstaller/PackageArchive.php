<?php
/** Download immutable GitHub source archives and extract only verified regular files. */
class PackageArchive
{
	const MAX_BYTES = 33554432;
	const MAX_ENTRY = 8388608;
	const MAX_ENTRIES = 8192;

	public static function download($package, $path)
	{
		$url = PackageRegistry::BASE . 'files/' . $package['sha256'] . '.zip';
		$file = @fopen($path, 'xb');
		if(!$file) throw new RuntimeException('pkg_storage_error');
		$count = 0;
		$size = $package['size'];
		$curl = curl_init($url);
		$options = array(CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 90, CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_FAILONERROR => true,
			CURLOPT_WRITEFUNCTION => function($handle, $bytes) use ($file, &$count, $size) {
				$count += strlen($bytes);
				return $count <= $size ? fwrite($file, $bytes) : 0;
			});
		$ok = curl_setopt_array($curl, $options) && curl_exec($curl) && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200;
		curl_close($curl);
		fclose($file);
		if(!$ok) throw new RuntimeException('pkg_download_error');
	}

	public static function extract($package, $archive, $directory)
	{
		if(filesize($archive) !== $package['size'] || hash_file('sha256', $archive) !== $package['sha256'])
			throw new RuntimeException('pkg_archive_error');
		$zip = new ZipArchive();
		if($zip->open($archive, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('pkg_archive_error');
		$prefix = (isset($package['source_path']) ? PackageRegistry::installPath($package) : basename($package['repo']) . '-' . $package['revision']) . '/';
		$total = 0;
		$seen = array();
		if($zip->numFiles > self::MAX_ENTRIES) throw new RuntimeException('pkg_archive_error');
		for($i = 0; $i < $zip->numFiles; $i++)
		{
			$stat = $zip->statIndex($i);
			if(!$stat || strpos($stat['name'], $prefix) !== 0) throw new RuntimeException('pkg_archive_error');
			if($stat['name'] === $prefix) continue;
			$name = substr($stat['name'], strlen($prefix));
			$is_directory = substr($name, -1) === '/';
			$name = rtrim($name, '/');
			if(!preg_match('~\A[-a-zA-Z0-9_.@+ ]+(?:/[-a-zA-Z0-9_.@+ ]+)*\z~D', $name)
				|| preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $name) || isset($seen[strtolower($name)]))
				throw new RuntimeException('pkg_archive_error');
			$seen[strtolower($name)] = true;
			$total += $stat['size'];
			if($stat['size'] > self::MAX_ENTRY || $total > self::MAX_BYTES) throw new RuntimeException('pkg_archive_error');
			if(method_exists($zip, 'getExternalAttributesIndex') && $zip->getExternalAttributesIndex($i, $os, $attrs)
				&& !in_array(($attrs >> 28) & 15, array(0, 4, 8), true)) throw new RuntimeException('pkg_archive_error');
			$path = $directory . '/' . $name;
			$parent = $is_directory ? $path : dirname($path);
			if(!is_dir($parent) && !@mkdir($parent, 0755, true)) throw new RuntimeException('pkg_storage_error');
			if($is_directory) continue;
			// Legacy ZipArchive lacks external attributes. A pinned ZIP supplies only data;
			// never delegate creation of a link or special file to the archive.
			$data = $stat['size'] ? $zip->getFromIndex($i, $stat['size']) : '';
			if($data === false || strlen($data) !== $stat['size'] || file_put_contents($path, $data) !== $stat['size'])
				throw new RuntimeException('pkg_archive_error');
		}
		$zip->close();
		$entries = array('module' => $package['id'] . '.class.php', 'widget' => $package['id'] . '.class.php',
			'addon' => $package['id'] . '.addon.php', 'layout' => 'layout.html');
		$skin = isset(PackageRegistry::$skinParents[$package['type']]);
		if((!$skin && !is_file($directory . '/' . $entries[$package['type']])) || !is_file($directory . ($skin ? '/skin.xml' : '/conf/info.xml'))
			|| self::fingerprint($directory) !== $package['tree']) throw new RuntimeException('pkg_archive_error');
	}

	/** Include empty directories and dotfiles when comparing with the approved release. */
	public static function fingerprint($directory, &$inventory = null)
	{
		if(is_link($directory) || !is_dir($directory)) return false;
		$files = array();
		$total = 0;
		$base = strlen($directory) + 1;
		try
		{
			$tree = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST);
			foreach($tree as $item)
			{
				if(count($files) >= self::MAX_ENTRIES || $item->isLink()) return false;
				$name = str_replace(DIRECTORY_SEPARATOR, '/', substr($item->getPathname(), $base));
				if($item->isDir()) $files[$name . '/'] = '-';
				else
				{
					if(!$item->isFile() || $item->getSize() > self::MAX_ENTRY) return false;
					$total += $item->getSize();
					if($total > self::MAX_BYTES || !($hash = @hash_file('sha256', $item->getPathname()))) return false;
					$files[$name] = $hash;
				}
			}
		}
		catch(UnexpectedValueException $e) { return false; }
		ksort($files, SORT_STRING);
		$inventory = $files;
		$hash = hash_init('sha256');
		foreach($files as $name => $digest) hash_update($hash, $name . "\0" . $digest . "\n");
		return hash_final($hash);
	}
}
