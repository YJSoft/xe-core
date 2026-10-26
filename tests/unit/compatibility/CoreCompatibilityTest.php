<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class CoreCompatibilityTest extends CompatibilityTestCase
{
	public function testMissingAndNullContextValuesAreStringsButFalseAndZeroSurvive()
	{
		$this->assertSame('', Context::get('missing'));
		Context::set('null_value', null, false);
		Context::set('false_value', false, false);
		Context::set('zero_value', 0, false);
		$this->assertSame('', Context::get('null_value'));
		$this->assertSame(false, Context::get('false_value'));
		$this->assertSame(0, Context::get('zero_value'));
		$this->assertSame('', trim(Context::get('missing')));
		$this->assertSame('', escape(null));
		$this->assertSame('&lt;test&gt;', escape('<test>'));
	}

	public function testRequestVariablesAreAnIndependentObject()
	{
		$this->assertInstanceOf('stdClass', Context::getRequestVars());
		Context::set('title', 'original', true);
		$vars = Context::getRequestVars();
		$vars->title = 'changed';
		$this->assertSame('original', Context::getRequestVars()->title);
	}

	public function testContextWithoutDatabaseAndUnknownLanguageCode()
	{
		$this->failOnPhpWarnings();
		$this->assertNull(Context::getSslStatus());
		$this->assertNull(Context::getDefaultUrl());
		$this->assertSame('unknown_language_code', Context::getLang('unknown_language_code'));
	}

	public function testFrontendFileMetadataDoesNotReadUndefinedUrlKeys()
	{
		$handler = new FrontEndFileHandler();
		$method = new ReflectionMethod('FrontEndFileHandler', 'getFileInfo');
		$method->setAccessible(true);
		$this->failOnPhpWarnings();
		$local = $method->invoke($handler, './common/css/xe.css', '', 'screen');
		$this->assertSame('', $local->query);
		$this->assertFalse($local->external);
		$this->assertSame('./common/css', $local->dirName);
		$external = $method->invoke($handler, 'https://example.com/style.css?v=1', '', 'print');
		$this->assertTrue($external->external);
		$this->assertSame('v=1', $external->query);
	}

	public function testDatabaseSessionReadAcceptsSelectedColumnsOnPhp71AndLater()
	{
		$db = $this->useQueryFixture();
		$session = new sessionModel();
		$session->session_started = true;
		$db->responses['session.getSession'] = null;
		$this->assertSame('', $session->read('session-key'));
		$db->responses['session.getSession'] = (object)array('session_key' => 'session-key', 'val' => 'member_srl|i:42;');
		$this->assertSame('member_srl|i:42;', $session->read('session-key'));
	}

	public function testStaticFactoriesAndFileCacheRoundTrip()
	{
		class_exists('CacheHandler');
		require_once _XE_PATH_ . 'classes/cache/CacheFile.class.php';
		$GLOBALS['__CacheFile__'] = null;
		$cache = CacheFile::getInstance();
		$cache->cache_dir = $this->fixtureDir . 'cache/';
		$this->assertSame($cache, CacheFile::getInstance());
		$key = $this->fixtureName . ':quoted';
		$this->trackGeneratedFile($cache->getCacheFileName($key));
		$value = (object)array('text' => "한글 'quoted' \\ path", 'items' => array(1, false, null));
		$cache->put($key, $value);
		$this->assertEquals($value, $cache->get($key));
		$cache->put($key, 'updated');
		$this->assertSame('updated', $cache->get($key));
		$cache->delete($key);
		$this->assertFalse($cache->get($key));
		$extra = ExtraVar::getInstance(123);
		$this->assertInstanceOf('ExtraVar', $extra);
		$this->assertSame(123, $extra->module_srl);
		$this->assertSame(__XE_VERSION__ . ':test:key', CacheHandler::getCacheKey('test/key'));
	}

	public function testQueryArrayNormalizesSingleObjectAndPreservesEmptyResults()
	{
		$db = $this->useQueryFixture();
		$row = (object)array('id' => 42);
		foreach(array(null, array(), $row, array($row)) as $data)
		{
			$db->responses['fixture.rows'] = $data;
			$output = executeQueryArray('fixture.rows');
			$this->assertSame(is_object($data) ? array($data) : $data, $output->data);
		}
	}

	public function testModuleLoaderWithoutExtensionConfiguration()
	{
		unset($GLOBALS['__MODULE_EXTEND__']);
		$model = getModel('document');
		$this->assertInstanceOf('documentModel', $model);
		$this->assertSame($model, getModel('document'));
		$this->assertNull(getModel('compatibility_missing_module'));
	}

	public function testScriptPathWithoutDeprecatedStringFilter()
	{
		$source = 'define("__XE__", true); require ' . var_export(_XE_PATH_ . 'config/config.inc.php', true) . ';'
			. '$_SERVER["SCRIPT_NAME"] = "/xe/tools/index.php<script></script>";'
			. 'set_error_handler(function($level, $message) { throw new Exception($message); });'
			. 'echo getScriptPath();';
		$this->assertSame('/xe/', $this->runPhp($source));
	}

	public function testLegacyObjectAliasIsOnlyRegisteredBeforePhp72()
	{
		$object = new BaseObject(-1, 'error');
		$this->assertFalse($object->toBool());
		if(version_compare(PHP_VERSION, '7.2', '<'))
		{
			$this->assertTrue(is_a($object, 'Object'));
		}
		else
		{
			$this->assertFalse(class_exists('Object', false));
		}
	}
}
