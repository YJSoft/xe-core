<?php

/** Shared fixtures for Codeception 2.x, 4.x and 5.x. Keep PHP 5.3 syntax. */
abstract class CompatibilityTestCase extends \Codeception\TestCase\Test
{
	protected $fixtureDir;
	protected $fixtureName;
	private $contextState;
	private $globalState;
	private $generatedFiles;
	private $bufferLevel;
	private $errorHandlerInstalled;

	protected function _before()
	{
		$this->contextState = get_object_vars(Context::getInstance());
		$this->globalState = array();
		foreach(array('__Context__', '_loaded_module', '_called_constructor', '__MODULE_EXTEND__', '__DB__', '__XE_CACHE_HANDLER__', '__CacheFile__', '__PURIFIER_INSTANCE__', '__EMBEDFILTER_INSTANCE__', 'lang', '_SESSION', '_SERVER', 'HTTP_RAW_POST_DATA', '_time_zone') as $key)
		{
			$this->globalState[$key] = array(array_key_exists($key, $GLOBALS), isset($GLOBALS[$key]) ? $GLOBALS[$key] : null);
		}
		$context = Context::getInstance();
		$context->context = new stdClass();
		$GLOBALS['__Context__'] = $context->context;
		$context->get_vars = new stdClass();
		$context->db_info = null;
		$context->lang_type = 'en';
		$context->request_method = 'GET';
		$context->response_method = 'HTML';
		$context->oFrontEndFileHandler = new FrontEndFileHandler();
		$GLOBALS['lang'] = new stdClass();
		$GLOBALS['_SESSION'] = array();
		$GLOBALS['_time_zone'] = '+0000';
		$GLOBALS['_loaded_module'] = array();
		$GLOBALS['__DB__'] = array();
		$GLOBALS['__XE_CACHE_HANDLER__'] = array();
		$this->fixtureName = uniqid('compat_', true);
		$this->fixtureName = str_replace('.', '_', $this->fixtureName);
		$this->fixtureDir = _XE_PATH_ . 'tests/_output/' . $this->fixtureName . '/';
		FileHandler::makeDir($this->fixtureDir);
		$this->generatedFiles = array();
		$this->bufferLevel = ob_get_level();
		$this->errorHandlerInstalled = false;
	}

	protected function _after()
	{
		if($this->errorHandlerInstalled) restore_error_handler();
		while(ob_get_level() > $this->bufferLevel) ob_end_clean();
		foreach($this->generatedFiles as $file) FileHandler::removeFile($file);
		FileHandler::removeDir($this->fixtureDir);
		foreach($this->contextState as $key => $value) Context::getInstance()->{$key} = $value;
		foreach($this->globalState as $key => $state)
		{
			if($state[0]) $GLOBALS[$key] = $state[1];
			else unset($GLOBALS[$key]);
		}
	}

	protected function failOnPhpWarnings()
	{
		set_error_handler(function($level, $message, $file, $line) {
			throw new ErrorException($message, 0, $level, $file, $line);
		}, E_WARNING | E_NOTICE);
		$this->errorHandlerInstalled = true;
	}

	protected function trackGeneratedFile($file)
	{
		$this->generatedFiles[] = $file;
		return $file;
	}

	protected function fixture($path, $contents)
	{
		$file = $this->fixtureDir . $path;
		FileHandler::writeFile($file, $contents);
		return $file;
	}

	protected function useQueryFixture()
	{
		$db = new CompatibilityQueryDB();
		Context::setDBInfo((object)array('master_db' => array('db_type' => 'compatibility')));
		$GLOBALS['__DB__']['compatibility'] = $db;
		return $db;
	}

	protected function useModuleFixture($name, $object, $type = 'model', $kind = 'svc')
	{
		$GLOBALS['_loaded_module'][$name][$type][$kind] = $object;
	}

	/** Run bootstrap-sensitive cases without sharing classes or static caches. */
	protected function runPhp($source)
	{
		$file = $this->fixture('child.php', "<?php\n" . $source);
		$binary = defined('PHP_BINARY') ? PHP_BINARY : PHP_BINDIR . '/php';
		$process = proc_open(escapeshellarg($binary) . ' ' . escapeshellarg($file), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, _XE_PATH_);
		$this->assertTrue(is_resource($process), 'Start the PHP regression subprocess');
		$output = stream_get_contents($pipes[1]);
		$errors = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		$this->assertSame(0, $status, $output . $errors);
		return $output;
	}
}

/** Replace only query I/O; all tested models/controllers remain real XE code. */
class CompatibilityQueryDB extends DB
{
	public $responses = array();
	public $queries = array();
	public $prefix = 'xe_';

	public static function getParser($force = false)
	{
		return new DBParser('`', '`', 'xe_');
	}

	public function executeQuery($query_id, $args = null, $arg_columns = null, $type = null)
	{
		$this->queries[] = array($query_id, is_object($args) ? clone $args : $args);
		if(!array_key_exists($query_id, $this->responses))
		{
			throw new Exception('Unexpected fixture query: ' . $query_id);
		}
		$output = new BaseObject();
		$output->data = $this->responses[$query_id];
		return $output;
	}
}
