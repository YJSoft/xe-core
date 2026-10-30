<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

class InstallDatabaseSelectionTest extends CompatibilityTestCase
{
	public function testInstallerDefaultsToMysqlAndOffersPdoSeparatelyAsBeta()
	{
		Context::loadLang('modules/install/lang');
		$view = new installView();
		$view->install_enable = true;
		$view->dispInstallSelectDB();
		$this->assertSame('mysql', Context::get('defaultDatabase'));

		$source = FileHandler::readFile(_XE_PATH_ . 'modules/install/tpl/select_db.html');
		$this->assertSame(1, preg_match('/<div class="x_control-group" loop=.*?(?=<div class="ibtnArea">)/s', $source, $match));
		$file = $this->fixture('mysql_choices.html', $match[0]);
		$template = new TemplateHandler();
		$compiled = $template->compileDirect(dirname($file), basename($file));
		$this->assertTrue(is_string($compiled) && $compiled !== '');
		$__Context = Context::getAll();
		ob_start();
		$this->assertTrue(eval('?>' . $compiled) !== false);
		$html = ob_get_clean();
		preg_match_all('/name="db_type" type="radio" value="(mysql[^\"]*)"/', $html, $matches);
		$this->assertSame(array('mysql'), $matches[1]);
		$this->assertTrue(strpos($html, 'id="db_type_mysql" checked="checked"') !== false);
		$this->assertTrue(strpos($html, 'MySQL PDO (beta)') !== false);
		$this->assertFalse(strpos($html, 'id="db_type_pdo_mysql" checked="checked"') !== false);
	}

	public function testLegacyDriverNamesReturnToTheUnifiedMysqlForm()
	{
		Context::set('db_type', 'mysqli_innodb');
		$_SERVER['HTTPS'] = 'off';
		$view = new installView();
		$view->install_enable = true;
		$view->dispInstallDBForm();
		$this->assertSame('form.mysql.html', $view->getTemplateFile());
		$this->assertSame('mysql', Context::get('db_type'));
	}

	public function testPdoEngineVariantReturnsToTheExplicitBetaForm()
	{
		Context::loadLang('modules/install/lang');
		Context::set('db_type', 'pdo_mysql_innodb');
		$_SERVER['HTTPS'] = 'off';
		$view = new installView();
		$view->install_enable = true;
		$view->dispInstallDBForm();
		$this->assertSame('form.mysql.html', $view->getTemplateFile());
		$this->assertSame('pdo_mysql', Context::get('db_type'));
		$this->assertTrue(strpos(Context::get('title'), 'MySQL PDO (beta)') !== false);
	}

	public static function engineAvailability()
	{
		$cases = array();
		foreach(array('mysql', 'mysqli', 'pdo_mysql') as $client)
		{
			$cases[] = array($client, 'YES', '', $client . '_innodb');
			$cases[] = array($client, 'DEFAULT', '', $client . '_innodb');
			$cases[] = array($client, 'NO', '', $client);
			$cases[] = array($client, 'DISABLED', '', $client);
			$cases[] = array($client, null, '', $client);
			$cases[] = array($client, 'YES', ' InnoDB , BLACKHOLE ', $client);
		}
		return $cases;
	}

	/** @dataProvider engineAvailability */
	public function testServerCapabilitiesDetermineTheStorageDriver($client, $support, $disabled, $expected)
	{
		$db = new InstallEngineQueryDB($support, $disabled);
		$output = getModel('install')->getMysqlInstallDriver($client, $db);
		$this->assertTrue($output->toBool());
		$this->assertSame($expected, $output->get('db_type'));
	}

	public function testUnavailableEnginesStopInstallation()
	{
		$db = new InstallEngineQueryDB('NO', 'MyISAM');
		$output = getModel('install')->getMysqlInstallDriver('mysqli', $db);
		$this->assertFalse($output->toBool());
		$this->assertNull($output->get('db_type'));
	}

	public static function failedQueries()
	{
		return array(array('SHOW ENGINES'), array("SHOW VARIABLES LIKE 'disabled_storage_engines'"));
	}

	/** @dataProvider failedQueries */
	public function testFailedCapabilityChecksDoNotSilentlySelectMyisam($query)
	{
		$db = new InstallEngineQueryDB('YES', '');
		$db->failedQuery = $query;
		$output = getModel('install')->getMysqlInstallDriver('mysqli', $db);
		$this->assertSame(1234, $output->getError());
		$this->assertNull($output->get('db_type'));
	}
}

/** Replace database I/O while exercising the real installer policy. */
class InstallEngineQueryDB extends DB
{
	public $failedQuery;
	private $engines;
	private $disabled;

	public function __construct($support, $disabled)
	{
		$this->engines = array((object)array('Engine' => 'MyISAM', 'Support' => 'YES'));
		if($support !== null) $this->engines[] = (object)array('Engine' => 'InnoDB', 'Support' => $support);
		$this->disabled = $disabled;
	}

	public function _query($query, $connection = null)
	{
		if($query === $this->failedQuery)
		{
			$this->setError(1234, 'Capability check failed');
			return false;
		}
		return $query;
	}

	public function _fetch($result, $arrayIndexEndValue = null)
	{
		if($result === 'SHOW ENGINES') return count($this->engines) === 1 ? $this->engines[0] : $this->engines;
		if($result === "SHOW VARIABLES LIKE 'disabled_storage_engines'")
		{
			return $this->disabled === '' ? array() : (object)array('Variable_name' => 'disabled_storage_engines', 'Value' => $this->disabled);
		}
		throw new Exception('Unexpected installer query: ' . $result);
	}
}
