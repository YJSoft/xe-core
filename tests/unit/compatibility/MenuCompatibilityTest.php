<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class MenuCompatibilityTest extends CompatibilityTestCase
{
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
	public function getDefaultMid() { return (object)array('layout_srl' => 0); }
	public function getGroups() { return array(); }
}
