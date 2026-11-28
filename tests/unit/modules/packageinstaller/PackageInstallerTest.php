<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'modules/packageinstaller/PackageManager.php';

class PackageInstallerTest extends CompatibilityTestCase
{
	public function testChangingStoragePreservesHistoryAndRejectsAConcurrentInstall()
	{
		$target = dirname($this->fixture('site/modules/index.html', ''));
		$site = dirname($target);
		$_SERVER['DOCUMENT_ROOT'] = $site;
		$this->fixture('site/files/config/index.html', '');
		$newBase = dirname($this->fixture('private/index.html', ''));
		$original = new PackageStorage($site);
		$new = new PackageStorage($site, $newBase);
		$lock = PackageStorage::lock($site, LOCK_SH);
		try
		{
			$new->save($target);
			$this->fail('A write is already using the old location.');
		}
		catch(RuntimeException $e) { $this->assertSame('pkg_storage_busy', $e->getMessage()); }
		fclose($lock);

		mkdir($original->directory, 0700);
		file_put_contents($original->directory . '/history', 'keep');
		try
		{
			$new->save($target);
			$this->fail('Existing records must stay with the installation.');
		}
		catch(RuntimeException $e) { $this->assertSame('pkg_storage_has_records', $e->getMessage()); }
		rename($original->directory, $new->directory);
		$new->save($target);
		$reloaded = new PackageStorage($site);
		$this->assertSame($newBase, $reloaded->base);
		$this->assertSame('keep', file_get_contents($reloaded->directory . '/history'));
	}

	public function testStorageReportsTheDirectoryThatNeedsAttention()
	{
		$target = dirname($this->fixture('site/modules/index.html', ''));
		$site = dirname($target);
		$_SERVER['DOCUMENT_ROOT'] = $site;
		$public = new PackageStorage($site, $site);
		$this->assertSame(array('error' => 'pkg_storage_public', 'path' => $site), $public->check($target));

		$storage = new PackageStorage($site);
		mkdir($storage->directory, 0700);
		$this->assertNull($storage->check($target));
		$work = $storage->directory . '/ncenterlite';
		mkdir($work, 0700);
		chmod($work, 0755);
		$this->assertSame(array('error' => 'pkg_storage_private', 'path' => $work), $storage->check($target, 'ncenterlite'));
	}

	public function testRecoveryRestoresPreviousDirectoryAfterInterruptedSwap()
	{
		$site = $this->fixtureDir . 'site';
		$_SERVER['DOCUMENT_ROOT'] = $site;
		$base = 'site/modules/ncenterlite/';
		$file = $this->fixture($base . 'conf/info.xml', 'old');
		$live = dirname(dirname($file));
		$old = array('version' => '1.0', 'tree' => PackageArchive::fingerprint($live));
		$mgr = new PackageManager($site, 'ncenterlite');
		$store = $this->fixtureDir . '.xe-packages-' . substr(hash('sha256', realpath($site)), 0, 16) . '/ncenterlite';
		mkdir($store, 0700, true);
		$backup = 'backup-123.45';
		$state = array('active' => array('version' => '2.0', 'tree' => str_repeat('a', 64)), 'previous' => $old, 'backup' => $backup, 'history' => array());
		file_put_contents($store . '/transaction.json', json_encode(array('backup' => $backup, 'state' => $state)));
		rename($live, $store . '/' . $backup);
		$mgr->apply('recover');
		$this->assertSame('old', file_get_contents($file));
		$this->assertFalse(file_exists($store . '/transaction.json'));
		$mgr->apply('recover');
		$this->assertSame($old['tree'], PackageArchive::fingerprint($live));
	}

	public function testRecoveryFinishesPublicationAndRetainsBackup()
	{
		$site = $this->fixtureDir . 'site';
		$_SERVER['DOCUMENT_ROOT'] = $site;
		$live = dirname($this->fixture('site/modules/ncenterlite/release', 'new'));
		$store = $this->fixtureDir . '.xe-packages-' . substr(hash('sha256', realpath($site)), 0, 16) . '/ncenterlite';
		mkdir($store, 0700, true);
		mkdir($store . '/backup-123.45');
		file_put_contents($store . '/backup-123.45/release', 'old');
		$state = array('active' => array('version' => '2.0', 'tree' => PackageArchive::fingerprint($live)),
			'previous' => array('version' => '1.0', 'tree' => PackageArchive::fingerprint($store . '/backup-123.45')),
			'backup' => 'backup-123.45', 'history' => array());
		file_put_contents($store . '/transaction.json', json_encode(array('backup' => $state['backup'], 'state' => $state)));
		$mgr = new PackageManager($site, 'ncenterlite');
		$mgr->apply('recover');
		$state['files'] = array('release' => hash('sha256', 'new'));
		$this->assertSame($state, json_decode(file_get_contents($store . '/state.json'), true));
		$this->assertSame('new', file_get_contents($live . '/release'));
		$this->assertSame('old', file_get_contents($store . '/backup-123.45/release'));
		$this->assertFalse(file_exists($store . '/transaction.json'));
	}

	public function testCatalogSelectsCompatibleReleaseAndExcludesWithdrawnVersionFromRestore()
	{
		$rows = json_decode(file_get_contents(_XE_PATH_ . 'modules/packageinstaller/conf/catalog.json'), true);
		$p = $rows[0];
		$release = $p;
		$release['notes'] = '';
		$release['withdrawn'] = false;
		$newer = $release;
		$newer['version'] = '4.0.0';
		$newer['php'] = '99.0';
		$newer['tree'] = str_repeat('b', 64);
		$withdrawn = $release;
		$withdrawn['version'] = '5.0.0';
		$withdrawn['withdrawn'] = true;
		$withdrawn['tree'] = str_repeat('c', 64);
		$p['releases'] = array($withdrawn, $release, $newer);
		$detail = json_encode($p);
		$digest = hash('sha256', $detail);
		$details = array($digest => $detail);
		$index = array('packages' => array($p['id'] => $digest));
		$reader = new ReflectionMethod('PackageRegistry', 'loadRows');
		$reader->setAccessible(true);
		$catalog = $reader->invokeArgs(null, array($index, &$details, false));
		$selected = $catalog[$p['id']];
		$this->assertSame($release['version'], $selected['version']);
		$this->assertSame('5.0.0', $selected['latest']);
		$this->assertSame(array($release['tree'] => $release['version']), $selected['approved']);
		$this->assertSame('5.0.0', $selected['upgrade_from'][$withdrawn['tree']]);
	}

	public function testVerifiedArchiveInstallsOnlyInsideEachDeclaredPackageDirectory()
	{
		if(!class_exists('ZipArchive')) $this->markTestSkipped('Requires ZipArchive.');
		foreach(array('module' => 'sample.class.php', 'widget' => 'sample.class.php', 'layout' => 'layout.html', 'addon' => 'sample.addon.php') as $type => $entry)
		{
			$revision = str_repeat('a', 40);
			$prefix = 'example-' . $revision . '/';
			$archive = $this->fixtureDir . $type . '.zip';
			$zip = new ZipArchive();
			$this->assertTrue($zip->open($archive, ZipArchive::CREATE));
			$files = array($entry => 'sample', 'conf/info.xml' => '<' . $type . '/>');
			foreach($files as $name => $data)
			{
				$zip->addFromString($prefix . $name, $data);
				$this->fixture('expected-' . $type . '/' . $name, $data);
			}
			$zip->close();
			$package = array('type' => $type, 'id' => 'sample', 'repo' => 'owner/example', 'revision' => $revision,
				'size' => filesize($archive), 'sha256' => hash_file('sha256', $archive),
				'tree' => PackageArchive::fingerprint($this->fixtureDir . 'expected-' . $type));
			$target = $this->fixtureDir . 'stage-' . $type;
			mkdir($target);
			PackageArchive::extract($package, $archive, $target);
			$this->assertSame('sample', file_get_contents($target . '/' . $entry));
			$this->assertSame($package['tree'], PackageArchive::fingerprint($target));
		}
	}

	public function testLocalAdditionsAndEmptyDirectoriesChangeFingerprint()
	{
		$path = dirname($this->fixture('module/app.php', 'original'));
		$baseline = PackageArchive::fingerprint($path);
		mkdir($path . '/custom');
		$this->assertNotSame($baseline, PackageArchive::fingerprint($path));
		rmdir($path . '/custom');
		file_put_contents($path . '/.local', 'keep');
		$this->assertNotSame($baseline, PackageArchive::fingerprint($path));
		unlink($path . '/.local');
		$this->assertSame($baseline, PackageArchive::fingerprint($path));
	}
}
