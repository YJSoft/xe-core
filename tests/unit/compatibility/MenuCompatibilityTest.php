<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class MenuCompatibilityTest extends CompatibilityTestCase
{
	public function testArrangingLeafMenusPreservesParentsAndSiblingOrder()
	{
		$db = $this->useQueryFixture();
		$db->responses['menu.updateMenu'] = null;
		Context::set('item_key', array(10, 11, 12, 20), false);
		Context::set('parent_key', array(0, 10, 10, 0), false);
		Context::set('success_return_url', '/', false);
		$controller = new CompatibilityMenuArrangeController();
		$controller->procMenuAdminArrangeItem();
		$this->assertSame(array(
			array(0, 0, 10), array(10, 0, 11), array(10, 11, 12), array(0, 10, 20)
		), $controller->moves);
	}

	public function testEmptySitemapAppearsInTheAllMenusAndSingleMenuResponses()
	{
		$id = $this->fixtureName;
		$record = (object)array('menu_srl' => $id, 'title' => 'Empty menu', 'site_srl' => 0);
		$db = $this->useQueryFixture();
		$db->responses['menu.getMenus'] = array($record);
		$db->responses['menu.getMenu'] = $record;
		$db->responses['menu.getMenuItems'] = null;
		$this->useModuleFixture('module', new CompatibilitySitemapServices($id));
		Context::set('site_srl', 0, false);
		$model = new menuAdminModel();
		$file = $this->trackGeneratedFile(_XE_PATH_ . 'files/cache/menu/' . $id . '.php');
		$this->trackGeneratedFile(_XE_PATH_ . 'files/cache/menu/' . $id . '.xml.php');
		FileHandler::writeFile($file, '<?php if(!defined("__XE__")) exit(); ?>');
		Context::set('menu_srl', 0, false);
		$model->getMenuAdminSiteMap();
		$list = $model->get('menuList');
		$this->assertCount(1, $list);
		$this->assertSame($id, $list[0]->menuSrl);
		$this->assertSame(array(), $list[0]->menuItems->list);

		FileHandler::removeFile($file);
		Context::set('menu_srl', $id, false);
		$model->getMenuAdminSiteMap();
		$this->assertTrue(is_file($file));
		$this->assertEquals($list, $model->get('menuList'));
		$this->assertSame(array(), Context::get('included_menu')->list);
	}

	public function testSitemapRegeneratesMissingMenuCacheAndAcceptsEmptyObjectLists()
	{
		$id = $this->fixtureName;
		$file = $this->trackGeneratedFile(_XE_PATH_ . 'files/cache/menu/' . $id . '.php');
		$services = new CompatibilitySitemapServices($id);
		$this->useModuleFixture('menu', $services, 'model', 'admin');
		$this->useModuleFixture('menu', $services, 'controller', 'admin');
		$this->useModuleFixture('admin', $services, 'class');
		$this->useModuleFixture('layout', $services);
		$this->useModuleFixture('module', $services);
		$this->useModuleFixture('member', $services);
		$this->useModuleFixture('page', $services, 'controller');
		Context::set('site_srl', 1, false);
		$view = new menuAdminView();
		$view->dispMenuAdminSiteMap();
		$this->assertSame(1, $services->generated);
		$this->assertTrue(is_file($file));
		$list = Context::get('menu_list');
		$this->assertCount(1, $list);
		$this->assertSame('Menu fixture', $list[0]->title);
		$this->assertInstanceOf('stdClass', $list[0]->menuItems->list);
		$view->dispMenuAdminSiteMap();
		$this->assertSame(1, $services->generated, 'An existing cache should be reused');
	}
}

class CompatibilityMenuArrangeController extends menuAdminController
{
	public $moves = array();

	public function moveMenuItem($menu_srl, $parent_srl, $source_srl, $target_srl, $mode, $isShortcut = 'Y', $url = null)
	{
		$this->moves[] = array($parent_srl, $source_srl, $target_srl);
	}
}

class CompatibilitySitemapServices
{
	public $id;
	public $generated = 0;
	public function __construct($id) { $this->id = $id; }
	public function getMenus() { return array((object)array('menu_srl' => $this->id, 'title' => 'Menu fixture', 'desc' => '')); }
	public function getAdminMenuName() { return 'admin'; }
	public function makeXmlFile($id)
	{
		$this->generated++;
		FileHandler::writeFile(_XE_PATH_ . 'files/cache/menu/' . $id . '.php', '<?php $menu = new stdClass(); $menu->list = new stdClass();');
	}
	public function getModuleListInSitemap($site_srl) { return array(); }
	public function getLayoutList() { return array(); }
	public function getSiteInfo($siteSrl, $columns) { return (object)array('mid' => 'home'); }
	public function getDefaultMid() { return (object)array('layout_srl' => 0); }
	public function getGroups() { return array(); }
}
