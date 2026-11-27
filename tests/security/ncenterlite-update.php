<?php
/** Exercise the dashboard release installer without a network connection or a live XE site. */
if(PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
$core = dirname(dirname(dirname(__FILE__))) . '/';
$fixture = tempnam(sys_get_temp_dir(), 'xe-release-test-');
if($fixture === false || !unlink($fixture) || !mkdir($fixture, 0700)) throw new Exception('Cannot create a test directory');
define('_XE_PATH_', $fixture . '/');
define('__DEBUG__', 0);
require $core . 'classes/file/FileHandler.class.php';
require $core . 'classes/xml/XeXmlParser.class.php';
require $core . 'classes/object/BaseObject.class.php';
register_shutdown_function(array('FileHandler', 'removeDir'), $fixture);
require $core . 'modules/admin/NcenterliteReleaseInstaller.php';
class Context
{
	public static function getLangType() { return 'ko'; }
	public static function getLang($name) { return $name; }
}
class FixtureReleaseInstaller extends NcenterliteReleaseInstaller
{
	public $downloads = 0;
	public $available = true;
	public $fail_file;
	public $writes = array();
	public function installModule() {}
	protected function downloadRelease($archive)
	{
		$this->downloads++;
		return $this->available && copy(_XE_PATH_ . 'release.zip', $archive);
	}
	protected function copyInstallFile($source, $target)
	{
		if(basename($target) === $this->fail_file) return false;
		$this->writes[] = basename($target);
		return parent::copyInstallFile($source, $target);
	}
}
function verify($condition, $description)
{
	if(!$condition) throw new Exception($description);
}
function manifest($version)
{
	return '<module version="0.2"><version>' . $version . '</version></module>';
}
function makeRelease($version)
{
	$zip = new ZipArchive();
	$zip->open(_XE_PATH_ . 'release.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$prefix = 'xe-module-ncenterlite-3.0.10/';
	$zip->addEmptyDir(rtrim($prefix, '/'));
	foreach(array('conf/info.xml'=>manifest($version), 'conf/module.xml'=>'<module/>',
		'ncenterlite.class.php'=>'class', 'ncenterlite.model.php'=>'patched', 'skins/new/file.css'=>'style',
		'ncenterlite.admin.model.php'=>'admin') as $file => $content)
	{
		$zip->addFromString($prefix . $file, $content);
	}
	$zip->close();
}

$module = _XE_PATH_ . 'modules/ncenterlite/';
mkdir($module . 'conf', 0755, true);
mkdir(_XE_PATH_ . 'files/cache', 0755, true);
$info = $module . 'conf/info.xml';
$installer = new FixtureReleaseInstaller();
verify($installer->getAffectedVersion() === false, 'uninstalled module');
file_put_contents($module . 'ncenterlite.class.php', 'class');
foreach(array('1.3.0'=>'1.3.0', '3.0.8'=>'3.0.8', '3.0.9'=>'3.0.9', '3.0.10'=>false, '3.1.0'=>false) as $version=>$expected)
{
	file_put_contents($info, manifest($version));
	verify($installer->getAffectedVersion() === $expected, 'version ' . $version);
}
file_put_contents($info, manifest('3.0.9'));
if(!class_exists('ZipArchive') || !function_exists('curl_init'))
{
	verify(!$installer->canInstall() && !$installer->install()->toBool(), 'manual installation fallback');
	echo "PASS version detection and manual fallback (ZIP or cURL unavailable)\n";
	return;
}
file_put_contents($module . 'ncenterlite.model.php', 'original');
file_put_contents($module . 'custom-skin.txt', 'custom');
verify($installer->canInstall(), 'direct install supported');
if(function_exists('posix_geteuid') && posix_geteuid() !== 0)
{
	chmod($module . 'ncenterlite.model.php', 0444);
	clearstatcache();
	verify(!$installer->canInstall() && !$installer->install()->toBool() && !$installer->downloads, 'read-only installation');
	chmod($module . 'ncenterlite.model.php', 0644);
	clearstatcache();
}
$installer->available = false;
verify(!$installer->install()->toBool() && !$installer->writes, 'unpublished release');
$installer->available = true;
makeRelease('3.0.9');
verify(!$installer->install()->toBool() && !$installer->writes, 'reject wrong release before writing');
makeRelease('3.0.10');
$lock = fopen(_XE_PATH_ . 'files/cache/ncenterlite-release.lock', 'c');
flock($lock, LOCK_EX);
verify(!$installer->install()->toBool() && $installer->downloads === 2, 'concurrent update');
fclose($lock);
$installer->fail_file = 'ncenterlite.admin.model.php';
verify(!$installer->install()->toBool(), 'report write failure');
verify(file_get_contents($module . 'ncenterlite.model.php') === 'original' && !file_exists($module . 'skins')
	&& file_get_contents($info) === manifest('3.0.9'), 'roll back previous writes');
$installer->fail_file = null;
verify($installer->install()->toBool(), 'retry');
verify(end($installer->writes) === 'info.xml' && $installer->getAffectedVersion() === false, 'publish version last');
verify(file_get_contents($module . 'ncenterlite.model.php') === 'patched' && file_get_contents($module . 'custom-skin.txt') === 'custom'
	&& !file_exists($module . 'xe-module-ncenterlite-3.0.10'), 'overlay the module directory');
$downloads = $installer->downloads;
verify($installer->install()->toBool() && $installer->downloads === $downloads, 'no download after update');
echo "PASS version detection, ZIP layout, permissions, lock, rollback, retry and custom files\n";
