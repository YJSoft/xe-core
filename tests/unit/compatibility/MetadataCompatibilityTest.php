<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class MetadataCompatibilityTest extends CompatibilityTestCase
{
	private function xml($tag, $extra = '')
	{
		return '<' . $tag . ' version="0.2"><title>Fixture</title><version>1.0</version><date>2026-01-02</date><author email_address="author@example.com" link="https://example.com"><name>Author</name></author>' . $extra . '</' . $tag . '>';
	}

	private function assertOptionalMetadata($info)
	{
		$this->assertSame('Fixture', $info->title);
		$this->assertSame('1.0', $info->version);
		foreach(array('description', 'homepage', 'license', 'license_link') as $field) $this->assertSame('', $info->{$field}, $field);
	}

	public function testWidgetCacheInitializesExtraVariablesAndMissingMetadata()
	{
		$this->fixture('conf/info.xml', $this->xml('widget', '<extra_vars><var id="color" type="text"><name>Color</name></var></extra_vars>'));
		$model = new CompatibilityWidgetMetadata();
		$model->fixturePath = $this->fixtureDir;
		$cache = $this->trackGeneratedFile(_XE_PATH_ . 'files/cache/widget/' . $this->fixtureName . '.en.cache.php');
		$info = $model->getWidgetInfo($this->fixtureName);
		$this->assertOptionalMetadata($info);
		$this->assertInstanceOf('stdClass', $info->extra_var);
		$this->assertSame('Color', $info->extra_var->color->name);
		$this->assertSame('', $info->extra_var->color->value);
		$this->assertSame('20260102', $info->date);
		// A second read must exercise the generated PHP cache too.
		touch($cache, time() + 2);
		clearstatcache();
		$this->assertEquals($info, $model->getWidgetInfo($this->fixtureName));
	}

	public function testWidgetStyleWithoutPreviewCreatesDefaultPreviewAndExtraVariableObject()
	{
		$this->fixture('skin.xml', $this->xml('widgetstyle', '<extra_vars><var id="color" type="text"><name>Color</name></var></extra_vars>'));
		$this->fixture('preview.jpg', 'preview fixture');
		$model = new CompatibilityWidgetMetadata();
		$model->fixturePath = $this->fixtureDir;
		$this->trackGeneratedFile(_XE_PATH_ . 'files/cache/widgetstyles/' . $this->fixtureName . '.en.cache.php');
		$info = $model->getWidgetStyleInfo($this->fixtureName);
		$this->assertSame($this->fixtureDir . 'preview.jpg', $info->preview);
		$this->assertInstanceOf('stdClass', $info->extra_var);
		$this->assertSame('Color', $info->extra_var->color->name);
		$this->assertSame(1, $info->extra_var_count);
	}

	public function testLayoutCacheInitializesAuthorsAndExtraVariableValues()
	{
		$this->fixture('conf/info.xml', $this->xml('layout', '<extra_vars><var name="color" type="text" /></extra_vars>'));
		$model = new CompatibilityLayoutMetadata();
		$model->fixturePath = $this->fixtureDir;
		$this->useModuleFixture('module', new CompatibilityMetadataConfig());
		$info = $model->getLayoutInfo($this->fixtureName);
		$this->assertOptionalMetadata($info);
		$this->assertSame('Author', $info->author[0]->name);
		$this->assertSame('', $info->extra_var->color->value);
		$this->assertSame('', $info->extra_var->color->title);
		$this->assertSame('20260102', $info->date);
	}

	public function testAddonMetadataWithoutDescriptionOrLicense()
	{
		$this->fixture('conf/info.xml', $this->xml('addon'));
		$db = $this->useQueryFixture();
		$db->responses['addon.getSiteAddonInfo'] = (object)array('extra_vars' => serialize(new stdClass()));
		$model = new CompatibilityAddonMetadata();
		$model->fixturePath = $this->fixtureDir;
		$info = $model->getAddonInfoXml($this->fixtureName);
		$this->assertOptionalMetadata($info);
		$this->assertSame('20260102', $info->date);
		$this->assertSame(array(), $info->mid_list);
	}

	public function testEditorComponentMetadataAndCacheWithoutOptionalFields()
	{
		$this->fixture('components/' . $this->fixtureName . '/info.xml', $this->xml('component'));
		$model = new editorModel();
		$model->module_path = $this->fixtureDir;
		$cache = $this->trackGeneratedFile(_XE_PATH_ . 'files/cache/editor/' . $this->fixtureName . '.en.php');
		$info = $model->getComponentXmlInfo($this->fixtureName);
		$this->assertOptionalMetadata($info);
		touch($cache, time() + 2);
		clearstatcache();
		$this->assertEquals($info, $model->getComponentXmlInfo($this->fixtureName));
	}

	public function testSkinMetadataWithoutOptionalFields()
	{
		$this->fixture('skins/fixture/skin.xml', $this->xml('skin', '<extra_vars><var name="color" type="text" /></extra_vars>'));
		$model = new moduleModel();
		$info = $model->loadSkinInfo($this->fixtureDir, 'fixture');
		$this->assertOptionalMetadata($info);
		$this->assertSame('20260102', $info->date);
	}

	public function testLegacyWidgetMetadataInitializesDateAndAuthorObjects()
	{
		$this->fixture('conf/info.xml', '<widget version="0.1"><title>Legacy</title><author date="2026. 1. 2" email_address="author@example.com"><name>Author</name></author></widget>');
		$model = new CompatibilityWidgetMetadata();
		$model->fixturePath = $this->fixtureDir;
		$this->trackGeneratedFile(_XE_PATH_ . 'files/cache/widget/' . $this->fixtureName . '.en.cache.php');
		$info = $model->getWidgetInfo($this->fixtureName);
		$this->assertSame('Legacy', $info->title);
		$this->assertSame('20260102', $info->date);
		$this->assertSame('Author', $info->author[0]->name);
	}
}

class CompatibilityWidgetMetadata extends widgetModel
{
	public $fixturePath;
	public function getWidgetPath($widget) { return $this->fixturePath; }
	public function getWidgetStylePath($widgetStyle) { return $this->fixturePath; }
}

class CompatibilityLayoutMetadata extends layoutModel
{
	public $fixturePath;
	public function getLayoutPath($layout_name = '', $layout_type = 'P') { return $this->fixturePath; }
	public function getLayoutCache($layout_name, $lang_type, $layout_type = 'P') { return $this->fixturePath . 'layout.cache.php'; }
}

class CompatibilityAddonMetadata extends addonAdminModel
{
	public $fixturePath;
	public function getAddonPath($addon) { return $this->fixturePath; }
}

class CompatibilityMetadataConfig
{
	public function getModulePartConfig($module, $srl) { return (object)array('header_script' => ''); }
}
