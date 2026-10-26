<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class XmlCompatibilityTest extends CompatibilityTestCase
{
	public function testParserCallbacksHandleAttributesRepeatedNodesAndUnicode()
	{
		$parser = new XeXmlParser();
		$result = $parser->parse('<root><item id="one">한글 &amp; XML</item><item id="two"><![CDATA[<text>]]></item></root>');
		$this->assertCount(2, $result->root->item);
		$this->assertSame('one', $result->root->item[0]->attrs->id);
		$this->assertSame('한글 & XML', $result->root->item[0]->body);
		$this->assertSame('<text>', $result->root->item[1]->body);
		$this->assertNull($result->root->missing);
		$result = $parser->parse('<root>second parse</root>');
		$this->assertSame('second parse', $result->root->body);
	}

	public function testStaticFileLoaderAndLanguageSelection()
	{
		$file = $this->fixture('lang.xml', "<root>\n<title xml:lang=\"en\">English</title>\n<title xml:lang=\"ko\">한국어</title>\n</root>");
		Context::getInstance()->lang_type = 'ko';
		$result = XeXmlParser::loadXmlFile($file);
		$this->assertSame('한국어', $result->root->title->body);
		$this->assertNull(XeXmlParser::loadXmlFile($this->fixtureDir . 'absent.xml'));
	}

	public function testXmlCallbacksDoNotUseDeprecatedObjectBinding()
	{
		$source = 'define("__XE__", true); require ' . var_export(_XE_PATH_ . 'config/config.inc.php', true) . ';'
			. 'set_error_handler(function($level, $message) { if(strpos($message, "xml_set_object") !== false) throw new Exception($message); return false; });'
			. '$parser = new XeXmlParser(); $xml = $parser->parse("<root>callback</root>"); echo $xml->root->body;';
		$this->assertSame('callback', $this->runPhp($source));
	}

	public function testJoinConditionsAreXmlNodesRatherThanCountableObjects()
	{
		$this->useQueryFixture();
		$parser = new XeXmlParser();
		$xml = $parser->parse('<table name="documents" alias="doc" type="left join"><conditions><condition operation="equal" column="doc.module_srl" default="modules.module_srl" /></conditions></table>');
		$table = new TableTag($xml->table);
		$this->assertTrue($table->isJoinTable());
		$this->assertTrue(strpos($table->getTableString(), 'new JoinTable(') !== false);
		$table->conditions = null;
		$this->assertFalse($table->isJoinTable());
		$table->conditions = new Xml_Node_();
		$table->join_type = '';
		$this->assertFalse($table->isJoinTable());
	}

	public function testXmlJsFilterWithoutParameterSection()
	{
		$file = $this->fixture('filter.xml', '<filter name="compat_filter" module="member" act="procMemberLogin"><form><node target="user_id" required="true" /></form><response callback_func="complete"><tag name="error" /></response></filter>');
		$filter = new XmlJsFilter(dirname($file), basename($file));
		$filter->js_file = $this->fixtureDir . 'filter.js';
		$filter->_compile();
		$this->assertTrue(is_file($filter->js_file));
		$js = file_get_contents($filter->js_file);
		$this->assertTrue(strpos($js, 'compat_filter') !== false);
		$this->assertTrue(strpos($js, 'user_id') !== false);
	}

	public function testAutoinstallXmlGeneratorCanBeCalledStatically()
	{
		require_once _XE_PATH_ . 'modules/autoinstall/autoinstall.class.php';
		$params = array('act' => 'getPackage', 'title' => '한글 <title> & text');
		$xml = XmlGenerater::generate($params);
		$parser = new XeXmlParser();
		$result = $parser->parse($xml);
		$this->assertSame('resourceapi', $params['module']);
		$this->assertSame('getPackage', $result->methodcall->params->act->body);
		$this->assertSame('한글 <title> & text', $result->methodcall->params->title->body);
	}
}
