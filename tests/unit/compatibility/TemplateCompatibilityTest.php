<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';

/** @group compatibility */
class TemplateCompatibilityTest extends CompatibilityTestCase
{
	private function render($source)
	{
		$file = $this->fixture('template.html', $source);
		$template = new TemplateHandler();
		$compiled = $template->compileDirect(dirname($file), basename($file));
		$this->assertTrue(is_string($compiled) && $compiled !== '', 'The template must compile before its output can be checked');
		$__Context = Context::getAll();
		ob_start();
		$result = eval('?>' . $compiled);
		$this->assertTrue($result !== false, 'The generated template PHP must execute successfully');
		return ob_get_clean();
	}

	public static function loopValues()
	{
		return array(
			'empty object' => array(new stdClass(), ''),
			'array' => array(array('one' => 'A', 'two' => 'B'), 'AB'),
			'object' => array((object)array('one' => 'A', 'two' => 'B'), 'AB'),
		);
	}

	/** @dataProvider loopValues */
	public function testCompiledAttributeLoopsExecuteWithObjectsAndEmptyValues($items, $expected)
	{
		Context::set('items', $items, false);
		$this->assertSame($expected, $this->render('<block loop="$items => $key, $item">{$item}</block>'));
	}

	/** @dataProvider loopValues */
	public function testCompiledCommentLoopsExecuteWithObjectsAndEmptyValues($items, $expected)
	{
		Context::set('items', $items, false);
		$this->assertSame($expected, $this->render('<!--@foreach($items as $key => $item)-->{$item}<!--@end-->'));
	}

	public static function autoescapePaths()
	{
		return array(
			array('modules/admin/tpl/', true),
			array('modules/board/skins/default/', true),
			array('layouts/xedition/', true),
			array('custom/third_party/', false),
		);
	}

	/** @dataProvider autoescapePaths */
	public function testBundledTemplatesEnableAutoescape($path, $expected)
	{
		$template = new TemplateHandler();
		$property = new ReflectionProperty('TemplateHandler', 'path');
		$property->setAccessible(true);
		$property->setValue($template, _XE_PATH_ . $path);
		$this->assertSame($expected, $template->isAutoescape(), $path);
	}

	public static function boardTags()
	{
		$cases = array();
		foreach(array('default', 'xedition') as $skin)
		{
			foreach(array(null, array('regression')) as $tags)
			{
				$cases[] = array($skin, $tags);
			}
		}
		return $cases;
	}

	/** @dataProvider boardTags */
	public function testActualBoardTagMarkupWithUntaggedAndTaggedDocuments($skin, $tags)
	{
		$source = file_get_contents(_XE_PATH_ . 'modules/board/skins/' . $skin . '/_read.html');
		// Exercise the live tag section, including its nested span and loop.
		$start = strpos($source, '{@ $tag_list');
		$this->assertTrue($start !== false, 'The board tag section must exist in ' . $skin);
		$end = strpos($source, '<a class="document_', $start);
		$this->assertTrue($end !== false, 'The board tag section must have an end in ' . $skin);
		$document = new documentItem();
		$document->add('tag_list', $tags);
		Context::set('oDocument', $document, false);
		Context::set('site_module_info', (object)array('domain' => ''), false);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$html = $this->render(substr($source, $start, $end - $start));
		$this->assertSame(!empty($tags), strpos($html, 'class="tags"') !== false);
		if($tags) $this->assertTrue(strpos($html, 'regression') !== false);
	}

	public function testPointGroupTemplateAcceptsMissingConfiguration()
	{
		$source = file_get_contents(_XE_PATH_ . 'modules/point/tpl/config.html');
		$this->assertSame(1, preg_match('/\{@\s*\$point_group\s*=.*?\}/', $source, $match));
		foreach(array(null, array(7 => 100)) as $groups)
		{
			Context::set('config', (object)array('point_group' => $groups), false);
			$this->render($match[0]);
			$this->assertSame($groups ? array(100 => 7) : array(), Context::get('point_group'));
		}
	}

	public function testLayoutExtraVariableSectionAcceptsNullAndObjectSettings()
	{
		$source = file_get_contents(_XE_PATH_ . 'modules/layout/tpl/layout_info_view.html');
		$this->assertSame(1, preg_match('/^\s*(<section\b[^\r\n]*\$selected_layout->extra_var[^\r\n]*>)\s*$/m', $source, $match));
		foreach(array(null, (object)array('color' => 'blue')) as $vars)
		{
			Context::set('selected_layout', (object)array('extra_var' => $vars), false);
			$html = $this->render($match[1] . 'extra-settings</section>');
			$this->assertSame($vars !== null, strpos($html, 'extra-settings') !== false);
		}
	}

	public function testAutoinstallSortLinksClearPageWithoutUsingAnUndefinedConstant()
	{
		$source = file_get_contents(_XE_PATH_ . 'modules/autoinstall/tpl/list.html');
		$start = strpos($source, '<div cond="$act');
		$this->assertTrue($start !== false, 'The actual autoinstall sort section must exist');
		$end = strpos($source, '</div>', $start);
		$this->assertTrue($end !== false, 'The autoinstall sort section must have an end');
		Context::set('act', 'dispAutoinstallAdminIndex', false);
		Context::set('order_target', 'newest', false);
		Context::set('order_type', 'desc', false);
		Context::set('page', 5, true);
		Context::set('site_module_info', (object)array('domain' => ''), false);
		Context::set('lang', (object)array('order_newest' => 'Newest', 'order_download' => 'Downloads', 'order_popular' => 'Popular'), false);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$html = $this->render(substr($source, $start, $end + 6 - $start));
		$this->assertSame(3, substr_count($html, '<a href='));
		$this->assertFalse(strpos($html, 'page=5'));
		$this->assertTrue(strpos($html, 'order_type=asc') !== false);
	}

	public function testPhp2ShellWarningRendersDangerPartialMitigationAndFixedStates()
	{
		$lang = new stdClass();
		foreach(array('php2shell_danger_title', 'php2shell_warning_title', 'msg_php_warning_now_version', 'php2shell_danger_description', 'php2shell_warning_description', 'php2shell_update_required', 'php2shell_patch_guidance', 'php2shell_configuration_guidance', 'php2shell_verification_limit', 'php2shell_official_advisory', 'cmd_close') as $key)
		{
			$lang->{$key} = $key;
		}
		Context::set('lang', $lang, false);
		$template = new TemplateHandler();
		$compiled = $template->compileDirect(_XE_PATH_ . 'modules/admin/tpl/', '_php2shell_warning.html');
		$this->assertTrue(is_string($compiled) && $compiled !== '');
		$progress_on = array('global_value' => '1', 'local_value' => '1');
		$progress_off = array('global_value' => '0', 'local_value' => '0');
		foreach(array(array('7.4.33', $progress_on, 'danger', true), array('8.1.34', $progress_off, 'warning', true), array('8.2.34', $progress_off, 'danger', false), array('8.4.26', $progress_on, 'danger', false), array('8.4.27', $progress_on, '', false)) as $case)
		{
			list($version, $settings, $level, $dismissible) = $case;
			Context::set('php2shell_status', Security::getPhp2ShellStatus($version, $settings), false);
			$__Context = Context::getAll();
			ob_start();
			$result = eval('?>' . $compiled);
			$html = ob_get_clean();
			$this->assertTrue($result !== false);
			if($level === '')
			{
				$this->assertSame('', trim($html));
				continue;
			}
			$this->assertTrue(strpos($html, 'role="alert"') !== false);
			$this->assertTrue(strpos($html, 'php2shell-warning ' . $level) !== false);
			$this->assertSame($level === 'danger', strpos($html, 'php2shell_danger_title') !== false);
			$this->assertSame($level === 'warning', strpos($html, 'php2shell_warning_title') !== false);
			$this->assertSame($level === 'danger', strpos($html, 'php2shell_configuration_guidance') !== false);
			$this->assertSame($dismissible, strpos($html, 'php2shell-dismiss') !== false);
			$this->assertSame(!$dismissible, strpos($html, 'php2shell_update_required') !== false);
			$this->assertTrue(strpos($html, 'php2shell_verification_limit') !== false);
			$this->assertTrue(strpos($html, $version) !== false);
		}
	}
}
