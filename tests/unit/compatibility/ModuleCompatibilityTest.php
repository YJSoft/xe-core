<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class ModuleCompatibilityTest extends CompatibilityTestCase
{
	public function testCommentAttributesPopulateLegacySkinPropertiesFromAnObject()
	{
		$comment = new commentItem();
		$attributes = (object)array('comment_srl' => 42, 'content' => 'Comment', 'nick_name' => 'Author');
		$comment->setAttribute($attributes);
		$this->assertTrue($comment->isExists());
		$this->assertSame(42, $comment->comment_srl);
		$this->assertSame('Comment', $comment->get('content'));
		$this->assertSame('Comment', $comment->content);
		$this->assertSame('Author', $comment->nick_name);
		$comment->setAttribute((object)array('comment_srl' => 0));
		$this->assertFalse($comment->isExists());
	}

	public function testCategoryArrangementAcceptsNullListsAndLeafChildren()
	{
		$model = new documentModel();
		$categories = array();
		$model->_arrangeCategory($categories, null, 0);
		$this->assertSame(array(), $categories);
		$leaf = array('mid' => 'board', 'module_srl' => 1, 'category_srl' => 2, 'parent_srl' => 1, 'text' => 'Child', 'description' => '', 'expand' => 'N', 'color' => '', 'document_count' => 3, 'grant' => true, 'list' => null);
		$parent = $leaf;
		$parent['category_srl'] = 1;
		$parent['parent_srl'] = 0;
		$parent['text'] = 'Parent';
		$parent['document_count'] = 2;
		$parent['list'] = array(2 => $leaf);
		$model->_arrangeCategory($categories, array(1 => $parent), 0);
		$this->assertSame(array(1, 2), array_keys($categories));
		$this->assertSame(5, $categories[1]->document_count);
		$this->assertSame(array(2), $categories[1]->childs);
		$this->assertSame(1, $categories[2]->depth);
		$this->assertTrue($categories[1]->first && $categories[1]->last);
	}

	public function testModuleExtraVariablesAcceptStdClassAndNormalizeNullableColumns()
	{
		$model = new CompatibilityModuleModel();
		$model->extra = array(1 => (object)array('custom' => 'value', 'browser_title' => 'must not replace'));
		$info = (object)array('module_srl' => 1, 'browser_title' => 'Title', 'header_text' => null, 'footer_text' => null, 'content' => null, 'mcontent' => null, 'custom' => null);
		$output = $model->addModuleExtraVars($info);
		$this->assertSame('value', $output->custom);
		$this->assertSame('Title', $output->browser_title);
		foreach(array('header_text', 'footer_text', 'content', 'mcontent') as $field) $this->assertSame('', $output->{$field});
		$model->extra = array(1 => new stdClass());
		$this->assertSame(array($info), $model->addModuleExtraVars(array($info)));
	}

	public function testGuestAndAdministratorGrantsWithoutXmlGrantDefinitions()
	{
		$db = $this->useQueryFixture();
		$db->responses['module.getModuleGrants'] = null;
		$model = new CompatibilityModuleModel();
		$module = (object)array('module' => 'board', 'module_srl' => 1, 'site_srl' => 0);
		$guest = (object)array('member_srl' => 0, 'is_admin' => 'N');
		$xml = (object)array('grant' => null);
		$grant = $model->getGrant($module, $guest, $xml);
		$this->assertTrue($grant->access);
		$this->assertFalse($grant->manager);
		$admin = (object)array('member_srl' => 1, 'is_admin' => 'Y', 'group_list' => array());
		$grant = $model->getGrant($module, $admin, $xml);
		$this->assertTrue($grant->access && $grant->manager && $grant->is_admin);
		$xml->grant = (object)array('view' => (object)array('default' => 'guest'));
		$this->assertTrue($model->getGrant($module, $guest, $xml)->view);
	}

	public function testModuleControllerSavesObjectSkinExtraAndGrantSettings()
	{
		$db = $this->useQueryFixture();
		foreach(array('module.deleteModuleSkinVars', 'module.insertModuleSkinVars', 'module.deleteModuleMobileSkinVars', 'module.insertModuleMobileSkinVars', 'module.deleteModuleExtraVars', 'module.insertModuleExtraVars', 'module.deleteModuleGrants', 'module.insertModuleGrant') as $query) $db->responses[$query] = null;
		$controller = new moduleController();
		$this->assertTrue($controller->insertModuleSkinVars(1, (object)array('color' => 'blue'))->toBool());
		$this->assertTrue($controller->insertModuleMobileSkinVars(1, new stdClass())->toBool());
		$controller->insertModuleExtraVars(1, (object)array('label' => 'extra'));
		$controller->insertModuleGrants(1, (object)array('view' => array(2)));
		$written = array();
		foreach($db->queries as $query) $written[$query[0]] = $query[1];
		$this->assertSame('blue', $written['module.insertModuleSkinVars']->value);
		$this->assertSame('extra', $written['module.insertModuleExtraVars']->value);
		$this->assertSame('view', $written['module.insertModuleGrant']->name);
		$this->assertSame('2', $written['module.insertModuleGrant']->group_srl);
		$this->assertFalse(isset($written['module.insertModuleMobileSkinVars']));
	}

	public function testEditorBuildsOptionsForDocumentAndCommentWithoutNullObjectAssignment()
	{
		$editor = new CompatibilityEditorModel();
		Context::set('is_logged', true, false);
		Context::set('logged_info', (object)array('is_admin' => 'Y', 'group_list' => array()), false);
		foreach(array('document', 'comment') as $type)
		{
			$option = $editor->getModuleEditor($type, 123, 456, 'document_srl', 'content');
			$this->assertInstanceOf('stdClass', $option);
			$this->assertSame($type, $option->module_type);
			$this->assertSame('content', $option->content_key_name);
			$this->assertTrue($option->allow_fileupload);
		}
	}

	public function testMissingMenuConfigurationIsCreatedBeforeAssigningItsId()
	{
		$model = new CompatibilityModuleModel();
		$controller = new CompatibilityModuleConfigController();
		$this->useModuleFixture('module', $model);
		$this->useModuleFixture('module', $controller, 'controller');
		$menu = new CompatibilityMenuController();
		$this->assertSame(987, $menu->getUnlinkedMenu());
		$this->assertSame('menu', $controller->name);
		$this->assertSame(987, $controller->config->unlinked_menu_srl);
	}

	public function testMemberSaveWithoutUploadedProfileImages()
	{
		$this->useModuleFixture('module', new CompatibilityModuleModel());
		Context::set('logged_info', (object)array('is_admin' => 'Y'), false);
		Context::setDBInfo((object)array('default_url' => 'http://localhost/', 'use_ssl' => 'none'));
		Context::set('success_return_url', '/index.php?module=admin', false);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTP_ORIGIN'] = 'http://localhost';
		$model = new CompatibilityMemberModel();
		$member = new CompatibilityMemberController();
		$this->useModuleFixture('member', $model);
		$this->useModuleFixture('member', $member, 'controller');
		$controller = new memberAdminController();
		$controller->procMemberAdminInsert();
		$this->assertSame(55, $controller->get('member_srl'));
		$this->assertTrue($member->signatureSaved);
	}

	public function testMemberExtraFormUsesCallbackReplacementInsteadOfRemovedRegexEval()
	{
		$this->useModuleFixture('module', new CompatibilityModuleModel());
		$model = new CompatibilityJoinFormModel();
		$this->useModuleFixture('member', $model);
		$view = new memberAdminView();
		$tags = $view->_getMemberInputTag((object)array('member_srl' => 1));
		$this->assertCount(1, $tags);
		$this->assertSame('custom_field', $tags[0]->name);
		$this->assertSame('<input type="text" name="custom_field" id="custom_field" value="Saved value" />', $tags[0]->inputTag);
	}

	public function testEditorComponentListAcceptsObjectCacheEntries()
	{
		$cache = new CompatibilityComponentCache();
		$GLOBALS['__XE_CACHE_HANDLER__']['object_file'] = $cache;
		$editor = new editorModel();
		$this->assertSame($cache->components, $editor->getComponentList(false));
	}

	public function testAutoinstallEmptyRemoteResponseDoesNotDeleteStoredCategories()
	{
		$db = $this->useQueryFixture();
		$controller = new CompatibilityAutoinstallController();
		$this->failOnPhpWarnings();
		foreach(array(null, new stdClass(), (object)array('response' => (object)array('packages' => null, 'categorylist' => null))) as $xml)
		{
			$controller->updatePackages($xml);
			$controller->updateCategory($xml);
		}
		$this->assertSame(array(), $db->queries);
	}

	public function testImporterWithoutExtraVariableKeysReturnsEmptyArray()
	{
		$file = $this->fixture('extra.xml', '</extra_vars>');
		$stream = fopen($file, 'r');
		$importer = new importerAdminController();
		$importer->oXmlParser = new XeXmlParser();
		$result = $importer->importExtraVars($stream);
		fclose($stream);
		$this->assertSame(array(), $result);
	}

	public function testSeoHandlesArticleWithoutTagsAndPreservesExistingTags()
	{
		$this->useModuleFixture('module', new CompatibilityModuleModel());
		Context::set('request_uri', 'http://localhost/', false);
		Context::set('site_module_info', (object)array('module_srl' => 1, 'site_srl' => 0, 'domain' => ''), false);
		Context::set('current_module_info', (object)array('module_srl' => 1, 'mid' => 'board'), false);
		Context::set('document_srl', 10, false);
		$_SERVER['HTTP_USER_AGENT'] = 'Compatibility test';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$document = new documentItem();
		$document->document_srl = 10;
		$document->adds((object)array('title' => 'Article', 'content' => 'Article body', 'nick_name' => 'Author', 'is_secret' => 'N', 'uploaded_count' => 0, 'lang_code' => 'en', 'regdate' => '20260101000000', 'last_update' => '20260102000000'));
		Context::set('oDocument', $document, false);
		foreach(array(null, array('PHP', 'XE')) as $tags)
		{
			$document->add('tag_list', $tags);
			$seo = new seoController();
			$html = '';
			$seo->triggerBeforeDisplay($html);
			$meta = array();
			foreach($seo->SEO['meta'] as $item) $meta[$item['property']][] = $item['content'];
			$this->assertSame(array('article'), $meta['og:type']);
			$this->assertSame($tags ? $tags : array(), isset($meta['article:tag']) ? $meta['article:tag'] : array());
		}
	}
}

class CompatibilityModuleModel extends moduleModel
{
	public $extra = array();
	public function getModuleExtraVars($list_module_srl) { return $this->extra; }
	public function isSiteAdmin($member_info, $site_srl = null) { return false; }
	public function getModuleConfig($module, $site_srl = 0) { return null; }
}

class CompatibilityModuleConfigController
{
	public $name;
	public $config;
	public function updateModuleConfig($name, $config) { $this->name = $name; $this->config = $config; }
}

class CompatibilityMenuController extends menuAdminController
{
	public function addMenu($title, $siteSrl = 0)
	{
		$output = new BaseObject();
		$output->add('menuSrl', 987);
		return $output;
	}
}

class CompatibilityEditorModel extends editorModel
{
	public function getEditorConfig($module_srl = null)
	{
		$config = new stdClass();
		foreach(array('editor_skin', 'content_style', 'content_font', 'content_font_size', 'sel_editor_colorset', 'editor_height', 'enable_autosave', 'comment_editor_skin', 'comment_content_style', 'sel_comment_editor_colorset', 'comment_editor_height') as $key) $config->{$key} = '';
		foreach(array('upload_file_grant', 'enable_default_component_grant', 'enable_component_grant', 'enable_html_grant', 'comment_upload_file_grant', 'enable_comment_default_component_grant', 'enable_comment_component_grant', 'enable_comment_html_grant') as $key) $config->{$key} = array();
		return $config;
	}
	public function getEditor($upload_target_srl = 0, $option = null) { return $option; }
}

class CompatibilityMemberModel
{
	public function getMemberConfig() { return (object)array('signupForm' => array()); }
}

class CompatibilityMemberController
{
	public $signatureSaved = false;
	public function insertMember(&$args) { $args->member_srl = 55; return new BaseObject(); }
	public function putSignature($srl, $signature) { $this->signatureSaved = ($srl === 55); }
}

class CompatibilityJoinFormModel
{
	public function getMemberConfig()
	{
		$form = (object)array('name' => 'custom_field', 'title' => 'Custom field', 'isUse' => true, 'isDefaultForm' => false, 'required' => false, 'member_join_form_srl' => 5);
		return (object)array('identifier' => 'email_address', 'signupForm' => (object)array('custom' => $form));
	}
	public function getCombineJoinForm($memberInfo)
	{
		return array(5 => (object)array('column_name' => 'custom_field', 'column_type' => 'text', 'column_title' => 'Custom field', 'value' => 'Saved value', 'description' => '', 'default_value' => array()));
	}
}

class CompatibilityComponentCache
{
	public $components;
	public function __construct() { $this->components = (object)array('image_link' => (object)array('enabled' => 'Y')); }
	public function isSupport() { return true; }
	public function get($key, $modified_time = 0) { return $this->components; }
}

class CompatibilityAutoinstallController extends autoinstallAdminController
{
	public function __construct() {}
}
