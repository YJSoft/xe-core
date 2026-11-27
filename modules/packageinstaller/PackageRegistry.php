<?php
/** Protocol v1. The trusted public key is distributed with the core. */
class PackageRegistry
{
	public static $directories = array('module' => 'modules', 'addon' => 'addons', 'widget' => 'widgets', 'layout' => 'layouts');
	private static $reserved = array(
		'module' => ' addon admin adminlogging autoinstall board comment communication counter document editor file importer install integration_search krzip layout member menu message module packageinstaller page point poll rss seo session spamfilter tag trash widget ',
		'addon' => ' adminlogging autolink captcha captcha_member counter member_communication member_extra_info oembed point_level_icon resize_image ',
		'widget' => ' content counter_status language_select login_info mcontent ',
		'layout' => ' default user_layout xedition ',
	);
	const BASE = 'https://yjsoft.github.io/xe-package-registry/v1/';
	private static $catalogs = array();

	private static function fetch($resource, $limit)
	{
		if(!function_exists('curl_init')) throw new RuntimeException('pkg_registry_error');
		$data = '';
		$curl = curl_init(self::BASE . $resource);
		$ok = curl_setopt_array($curl, array(CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_FOLLOWLOCATION => false, CURLOPT_FAILONERROR => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12,
			CURLOPT_WRITEFUNCTION => function($handle, $chunk) use (&$data, $limit) {
				if(strlen($data) + strlen($chunk) > $limit) return 0;
				$data .= $chunk;
				return strlen($chunk);
			})) && curl_exec($curl) && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200;
		curl_close($curl);
		if(!$ok) throw new RuntimeException('pkg_registry_error');
		return $data;
	}

	private static function index($envelope)
	{
		$e = json_decode($envelope, true);
		if(!isset($e['payload'], $e['signature']) || !is_string($e['payload']) || !is_string($e['signature'])
			|| !function_exists('openssl_verify')) throw new RuntimeException('pkg_registry_error');
		$payload = base64_decode($e['payload'], true);
		$sig = base64_decode($e['signature'], true);
		$key = file_get_contents(dirname(__FILE__) . '/conf/registry.pem');
		if(!$payload || !$sig || openssl_verify($payload, $sig, $key, 'sha256') !== 1) throw new RuntimeException('pkg_registry_error');
		$index = json_decode($payload, true);
		if(!isset($index['schema'], $index['issued'], $index['expires'], $index['generation'], $index['packages'])
			|| $index['schema'] !== 1 || !is_int($index['generation']) || !is_int($index['issued']) || !is_int($index['expires'])
			|| $index['generation'] < 1 || $index['issued'] > time() + 300 || $index['expires'] <= $index['issued']
			|| $index['expires'] - $index['issued'] > 31 * 86400 || !is_array($index['packages']) || count($index['packages']) > 256)
			throw new RuntimeException('pkg_registry_error');
		return $index;
	}

	private static function isVersion($value)
	{
		return is_string($value) && preg_match('/\A[0-9][a-zA-Z0-9_.+-]{0,63}\z/D', $value);
	}

	private static function loadRows($index, &$details, $fetch)
	{
		$rows = array();
		foreach($index['packages'] as $id => $digest)
		{
			if(!preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $id) || !is_string($digest)
				|| !preg_match('/\A[0-9a-f]{64}\z/D', $digest)) throw new RuntimeException('pkg_registry_error');
			if($fetch && (!isset($details[$digest]) || hash('sha256', $details[$digest]) !== $digest)) $details[$digest] = self::fetch('details/' . $digest . '.json', 262144);
			if(!isset($details[$digest]) || hash('sha256', $details[$digest]) !== $digest) throw new RuntimeException('pkg_registry_error');
			$p = json_decode($details[$digest], true);
			if(!isset($p['id'], $p['repo'], $p['title'], $p['author'], $p['license'], $p['releases'], $p['upgrade_from'])
				|| $p['id'] !== $id || !preg_match('~\A[A-Za-z0-9_-]+/[A-Za-z0-9_-]+\z~D', $p['repo'])
				|| !is_string($p['title']) || !is_string($p['author']) || !is_string($p['license'])
				|| !is_array($p['upgrade_from']) || !is_array($p['releases']) || !$p['releases']) throw new RuntimeException('pkg_registry_error');
			$type = isset($p['type']) ? $p['type'] : 'module';
			if(!is_string($type) || !isset(self::$directories[$type]) || strpos(self::$reserved[$type], ' ' . $id . ' ') !== false)
				throw new RuntimeException('pkg_registry_error');
			$p['type'] = $type;
			foreach($p['upgrade_from'] as $tree => $version)
			{
				if(!preg_match('/\A[0-9a-f]{64}\z/D', $tree) || !self::isVersion($version)) throw new RuntimeException('pkg_registry_error');
			}
			$latest = $choice = null;
			$approved = array();
			foreach($p['releases'] as $r)
			{
				if(!isset($r['version'], $r['revision'], $r['php'], $r['core'], $r['size'], $r['sha256'], $r['tree'], $r['withdrawn'], $r['snapshot'], $r['notes'])
					|| !preg_match('/\A[0-9a-f]{40}\z/D', $r['revision']) || !preg_match('/\A[0-9a-f]{64}\z/D', $r['tree'])
					|| !preg_match('/\A[0-9a-f]{64}\z/D', $r['sha256']) || !is_int($r['size']) || $r['size'] <= 0 || $r['size'] > 33554432
					|| !self::isVersion($r['version']) || !self::isVersion($r['php']) || !self::isVersion($r['core'])
					|| !is_bool($r['withdrawn']) || !is_bool($r['snapshot']) || !is_string($r['notes'])) throw new RuntimeException('pkg_registry_error');
				if(!$latest || version_compare($r['version'], $latest['version'], '>')) $latest = $r;
				if(!$r['withdrawn'] && version_compare(PHP_VERSION, $r['php'], '>=') && version_compare(__XE_VERSION__, $r['core'], '>='))
				{
					$approved[$r['tree']] = $r['version'];
					if(!$choice || version_compare($r['version'], $choice['version'], '>')) $choice = $r;
				}
				$p['upgrade_from'][$r['tree']] = $r['version'];
			}
			$releaseFields = array('version', 'revision', 'php', 'core', 'size', 'sha256', 'tree', 'withdrawn', 'snapshot', 'notes');
			$selected = array_intersect_key($choice ? $choice : $latest, array_flip($releaseFields));
			$rows[$id] = array_merge($p, $selected, array('approved' => $approved, 'available' => (bool)$choice, 'latest' => $latest['version']));
		}
		return $rows;
	}

	/** Cache only a complete, verified generation. Preserve the high-water mark across XE cache rebuilds. */
	public static function catalog($site, $fresh = false)
	{
		$site = realpath($site);
		if(!$fresh && isset(self::$catalogs[$site])) return self::$catalogs[$site];
		$path = $site . '/files/config/package-registry.json';
		$rows = array();
		foreach(json_decode(file_get_contents(dirname(__FILE__) . '/conf/catalog.json'), true) as $p) $rows[$p['id']] = $p;
		$valid = false;
		$lock = is_dir(dirname($path)) ? @fopen($path . '.lock', 'c') : false;
		if($lock && flock($lock, LOCK_EX))
		{
			try
			{
				$previous = null;
				$details = array();
				$refresh = true;
				if(file_exists($path))
				{
					$cached = json_decode(file_get_contents($path), true);
					if(!isset($cached['index'], $cached['details'], $cached['checked'])) throw new RuntimeException('pkg_registry_error');
					$previous = self::index($cached['index']);
					$details = $cached['details'];
					try
					{
						$rows = self::loadRows($previous, $details, false);
						$refresh = $fresh || $previous['expires'] <= time() || time() - $cached['checked'] >= 14400;
					}
					catch(RuntimeException $e)
					{
						// Redownload damaged details while retaining the verified generation floor.
						$details = array();
					}
				}
				if($refresh)
				{
					$envelope = self::fetch('index.json', 262144);
					$index = self::index($envelope);
					if($index['expires'] <= time() || ($previous && ($index['generation'] < $previous['generation']
						|| ($index['generation'] === $previous['generation'] && $index !== $previous)))) throw new RuntimeException('pkg_registry_error');
					$newRows = self::loadRows($index, $details, true);
					$details = array_intersect_key($details, array_flip(array_values($index['packages'])));
					$data = json_encode(array('checked' => time(), 'index' => $envelope, 'details' => $details));
					if(!$data || file_put_contents($path . '.tmp', $data) !== strlen($data) || !rename($path . '.tmp', $path))
						throw new RuntimeException('pkg_registry_error');
					$rows = $newRows;
				}
				$valid = true;
			}
			catch(RuntimeException $e) { $valid = false; }
		}
		if($lock) fclose($lock);
		foreach($rows as &$row) $row['online'] = $valid;
		unset($row);
		self::$catalogs[$site] = $rows;
		return $rows;
	}
}
