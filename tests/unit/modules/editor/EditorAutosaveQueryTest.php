<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

class EditorAutosaveQueryTest extends CompatibilityTestCase
{
	private function compileQuery($name, $args)
	{
		Context::setDBInfo((object)array('master_db' => array('db_type' => 'compatibility')));
		$GLOBALS['__DB__']['compatibility'] = new AutosaveQueryDB();
		$file = $this->fixture($name . '.php', '');
		$parser = new XmlQueryParser();
		$parser->parse_xml_query('editor.' . $name, _XE_PATH_ . 'modules/editor/queries/' . $name . '.xml', $file);
		return include $file;
	}

	public function testGuestZeroRemainsAnOwnershipConditionInAutosaveQueries()
	{
		foreach(array('getSavedDocument', 'deleteSavedDoc', 'updateSavedDocTarget') as $name)
		{
			$args = (object)array('module_srl' => 100, 'member_srl' => 0, 'certify_key' => str_repeat('a', 40), 'document_srl' => 200);
			$query = $this->compileQuery($name, $args);
			$this->assertTrue($query instanceof Query);
			$where = $query->getWhereString();
			$this->assertTrue((bool)preg_match('/member_srl`?\s*=\s*[\x27\x22]?0/', $where), $where);
			$this->assertTrue(strpos($where, 'certify_key') !== false, $where);
			$this->assertTrue(strpos($where, 'module_srl') !== false, $where);
		}
	}

	public function testTargetUpdatesCannotCompileWithoutModuleAndAuthorConditions()
	{
		foreach(array('module_srl', 'member_srl') as $missing)
		{
			$args = (object)array('module_srl' => 100, 'member_srl' => 41, 'document_srl' => 200);
			unset($args->$missing);
			$query = $this->compileQuery('updateSavedDocTarget', $args);
			$this->assertTrue($query instanceof BaseObject && !$query->toBool(), 'An incomplete ownership condition must return a query validation error');
		}
	}
}

class AutosaveQueryDB extends CompatibilityQueryDB
{
	public function addQuotes($value) { return "'" . str_replace("'", "''", $value) . "'"; }
}
