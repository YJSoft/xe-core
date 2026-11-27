<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'modules/admin/ObsoleteFileCleanup.php';

class ObsoleteFileCleanupTest extends CompatibilityTestCase
{
	public function testCleanupRechecksOriginalsAndRetainsLocalFiles()
	{
		$cleanup = new ObsoleteFileCleanup($this->fixtureDir);
		$this->assertSame(array(), $cleanup->checkFiles());
		$name = 'modules/autoinstall/queries/deleteCategory.xml';
		$original = <<<'XML'
<query id="deleteCategory" action="delete">
    <tables>
        <table name="ai_remote_categories" />
    </tables>
    <conditions>
    </conditions>
</query>
XML;
		$file = $this->fixture($name, $original . "\n");
		$custom = $this->fixture('modules/autoinstall/.local', 'keep');
		$this->assertSame(array($name => 'ready'), $cleanup->checkFiles());

		file_put_contents($file, 'edited after preview');
		$this->assertSame(array($name => 'review'), $cleanup->deleteFiles());
		$this->assertSame('edited after preview', file_get_contents($file));

		file_put_contents($file, $original . "\n");
		$this->assertSame(array(), $cleanup->deleteFiles());
		$this->assertFalse(is_dir(dirname($file)));
		$this->assertSame('keep', file_get_contents($custom));
		unlink($custom);
		$this->assertSame(array(), $cleanup->deleteFiles());
		$this->assertFalse(is_dir(dirname($custom)));
		$this->assertSame(array(), $cleanup->deleteFiles());
	}

	public function testLinkedModuleIsLeftForManualReview()
	{
		if(!function_exists('symlink') || DIRECTORY_SEPARATOR === '\\') $this->markTestSkipped('Requires symbolic links.');
		$outside = $this->fixture('separate-install/tpl/index.html', 'keep');
		$modules = $this->fixtureDir . 'modules';
		mkdir($modules);
		$link = $modules . '/autoinstall';
		$this->assertTrue(symlink(dirname(dirname($outside)), $link));
		$cleanup = new ObsoleteFileCleanup($this->fixtureDir);
		$result = $cleanup->deleteFiles();
		// Unlink the fixture before the shared test teardown removes its directories.
		unlink($link);
		$this->assertSame(array('modules/autoinstall' => 'review'), $result);
		$this->assertSame('keep', file_get_contents($outside));
	}
}
