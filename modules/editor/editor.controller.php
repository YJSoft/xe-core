<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * @class  editor
 * @author XEHub (developers@xpressengine.com)
 * @brief editor module's controller class
 */
class editorController extends editor
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief AutoSave
	 */
	function procEditorSaveDoc()
	{

		$this->deleteSavedDoc(false);

		$args = new stdClass;
		$args->document_srl = Context::get('document_srl');
		$args->content = Context::get('content');
		$args->title = Context::get('title');
		$output = $this->doSaveDoc($args);

		$this->setMessage('msg_auto_saved');
	}

	/**
	 * @brief Delete autosaved documents
	 */
	function procEditorRemoveSavedDoc()
	{
		$oEditorController = getController('editor');
		$oEditorController->deleteSavedDoc(true);
	}

	/**
	 * @brief Execute a method of the component when the component requests ajax
	 */
	function procEditorCall()
	{
		$component = Context::get('component');
		$method = Context::get('method');
		if(!$component) return new BaseObject(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

		$oEditorModel = getModel('editor');
		$oComponent = &$oEditorModel->getComponentObject($component);
		if(!$oComponent->toBool()) return $oComponent;

		if(!method_exists($oComponent, $method)) return new BaseObject(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

		//$output = call_user_method($method, $oComponent);
		//$output = call_user_func(array($oComponent, $method));
		if(method_exists($oComponent, $method)) $output = $oComponent->{$method}();
		else return new BaseObject(-1,sprintf('%s method is not exists', $method));

		if((is_a($output, 'BaseObject') || is_subclass_of($output, 'BaseObject')) && !$output->toBool()) return $output;

		$this->setError($oComponent->getError());
		$this->setMessage($oComponent->getMessage());

		$vars = $oComponent->getVariables();
		if(count($vars))
		{
			foreach($vars as $key => $val)
			{
				$this->add($key, $val);
			}
		}
	}

	/**
	 * @brief Save Editor's additional form for each module
	 */
	function procEditorInsertModuleConfig()
	{
		$target_module_srl = Context::get('target_module_srl');
		$target_module_srl = array_map('trim', explode(',', $target_module_srl));
		$logged_info = Context::get('logged_info');
		$module_srl = array();
		$oModuleModel = getModel('module');

		foreach($target_module_srl as $srl)
		{
			if(!$srl) continue;

			$module_info = $oModuleModel->getModuleInfoByModuleSrl($srl);
			if(!$module_info->module_srl)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$module_grant = $oModuleModel->getGrant($module_info, $logged_info);
			if(!$module_grant->manager)
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}

			$module_srl[] = $srl;
		}

		$editor_config = new stdClass;
		$editor_config->editor_skin = Context::get('editor_skin');
		$editor_config->comment_editor_skin = Context::get('comment_editor_skin');
		$editor_config->content_style = Context::get('content_style');
		$editor_config->comment_content_style = Context::get('comment_content_style');
		$editor_config->content_font = Context::get('content_font');
		if($editor_config->content_font)
		{
			$font_list = array();
			$fonts = explode(',',$editor_config->content_font);
			for($i=0,$c=count($fonts);$i<$c;$i++)
			{
				$font = trim(str_replace(array('"','\''),'',$fonts[$i]));
				if(!$font) continue;
				$font_list[] = $font;
			}
			if(count($font_list)) $editor_config->content_font = '"'.implode('","',$font_list).'"';
		}
		$editor_config->content_font_size = Context::get('content_font_size');
		$editor_config->sel_editor_colorset = Context::get('sel_editor_colorset');
		$editor_config->sel_comment_editor_colorset = Context::get('sel_comment_editor_colorset');

		$grants = array('enable_html_grant','enable_comment_html_grant','upload_file_grant','comment_upload_file_grant','enable_default_component_grant','enable_comment_default_component_grant','enable_component_grant','enable_comment_component_grant');

		foreach($grants as $key)
		{
			$grant = Context::get($key);
			if(!$grant)
			{
				$editor_config->{$key} = array();
			}
			else if(is_array($grant))
			{
				$editor_config->{$key} = $grant;
			}
			else
			{
				$editor_config->{$key} = explode('|@|', $grant);
			}
		}

		$editor_config->editor_height = (int)Context::get('editor_height');
		$editor_config->comment_editor_height = (int)Context::get('comment_editor_height');
		$editor_config->enable_autosave = Context::get('enable_autosave');
		if($editor_config->enable_autosave != 'Y') $editor_config->enable_autosave = 'N';

		$oModuleController = getController('module');
		foreach($module_srl as $srl)
		{
			$oModuleController->insertModulePartConfig('editor', $srl, $editor_config);
		}

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief convert editor component codes to be returned and specify content style.
	 */
	function triggerEditorComponentCompile(&$content)
	{
		if(Context::getResponseMethod()!='HTML') return new BaseObject();

		$module_info = Context::get('module_info');
		$module_srl = $module_info->module_srl;
		if($module_srl)
		{
			$oEditorModel = getModel('editor');
			$editor_config = $oEditorModel->getEditorConfig($module_srl);
			$content_style = $editor_config->content_style;
			if($content_style)
			{
				$path = _XE_PATH_ . 'modules/editor/styles/'.$content_style.'/';
				if(is_dir($path) && file_exists($path . 'style.ini'))
				{
					$ini = file($path.'style.ini');
					for($i = 0, $c = count($ini); $i < $c; $i++)
					{
						$file = trim($ini[$i]);
						if(!$file) continue;

						if(substr_compare($file, '.css', -4) === 0)
						{
							Context::addCSSFile('./modules/editor/styles/'.$content_style.'/'.$file, false);
						}
						elseif(substr_compare($file, '.js', -3) === 0)
						{
							Context::addJsFile('./modules/editor/styles/'.$content_style.'/'.$file, false);
						}
					}
				}
			}
			$content_font = $editor_config->content_font;
			$content_font_size = $editor_config->content_font_size;
			if($content_font || $content_font_size)
			{
				$buff = array();
				$buff[] = '<style> .xe_content { ';
				if($content_font) $buff[] = 'font-family:'.$content_font.';';
				if($content_font_size) $buff[] = 'font-size:'.$content_font_size.';';
				$buff[] = ' }</style>';
				Context::addHtmlHeader(implode('', $buff));
			}
		}

		$content = $this->transComponent($content);
		return new BaseObject();
	}

	/**
	 * @brief Convert editor component codes to be returned
	 */
	function transComponent($content)
	{
		$content = preg_replace_callback('!<(?:(div)|img)([^>]*)editor_component=([^>]*)>(?(1)(.*?)</div>)!is', array($this,'transEditorComponent'), $content);
		return $content;
	}

	/**
	 * @brief Convert editor component code of the contents
	 */
	function transEditorComponent($match)
	{
		$script = " {$match[2]} editor_component={$match[3]}";
		$script = preg_replace('/([\w:-]+)\s*=(?:\s*(["\']))?((?(2).*?|[^ ]+))\2/i', '\1="\3"', $script);
		preg_match_all('/([a-z0-9_-]+)="([^"]+)"/is', $script, $m);

		$xml_obj = new stdClass;
		$xml_obj->attrs = new stdClass;
		for($i=0,$c=count($m[0]);$i<$c;$i++)
		{
			if(!isset($xml_obj->attrs)) $xml_obj->attrs = new stdClass;
			$xml_obj->attrs->{$m[1][$i]} = $m[2][$i];
		}
		$xml_obj->body = $match[4];

		if(!$xml_obj->attrs->editor_component) return $match[0];

		// Get converted codes by using component::transHTML()
		$oEditorModel = getModel('editor');
		$oComponent = &$oEditorModel->getComponentObject($xml_obj->attrs->editor_component, 0);
		if(!is_object($oComponent)||!method_exists($oComponent, 'transHTML')) return $match[0];

		return $oComponent->transHTML($xml_obj);
	}

	/**
	 * @brief AutoSave
	 */
	function doSaveDoc($args)
	{
		if(!$args->document_srl) $args->document_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

		// Get the current module if module_srl doesn't exist
		if(!$args->module_srl) $args->module_srl = Context::get('module_srl');
		if(!$args->module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$args->module_srl = $current_module_info->module_srl;
		}

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->certify_key = $_COOKIE['autosave_certify_key_' . $args->module_srl];
			if(!$args->certify_key) $args->certify_key = Password::createSecureSalt(40);
			setcookie('autosave_certify_key_' . $args->module_srl, $args->certify_key, $_SERVER['REQUEST_TIME'] + 3600, '', '', false, true);
		}

		return executeQuery('editor.insertSavedDoc', $args);
	}

	/**
	 * @brief Load the srl of autosaved document - for those who uses XE older versions.
	 */
	function procEditorLoadSavedDocument()
	{
		$editor_sequence = Context::get('editor_sequence');
		$primary_key = Context::get('primary_key');
		$oEditorModel = getModel('editor');
		$oFileController = getController('file');

		$saved_doc = $oEditorModel->getSavedDoc(null);

		$oFileController->setUploadInfo($editor_sequence, $saved_doc->document_srl, intval($saved_doc->module_srl));
		$vars = $this->getVariables();
		$this->add("editor_sequence", $editor_sequence);
		$this->add("key", $primary_key);
		$this->add("title", $saved_doc->title);
		$this->add("content", $saved_doc->content);
		$this->add("document_srl", $saved_doc->document_srl);
	}

	/**
	 * @brief A trigger to remove auto-saved document when inserting/updating the document
	 */
	function triggerDeleteSavedDoc(&$obj)
	{
		$this->deleteSavedDoc(false);
		return new BaseObject();
	}

	/**
	 * @brief Delete the auto-saved document
	 * Based on the current logged-in user
	 */
	function deleteSavedDoc($mode = false)
	{
		$args = new stdClass();
		$args->module_srl = Context::get('module_srl');

		// Get the current module if module_srl doesn't exist
		if(!$args->module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$args->module_srl = $current_module_info->module_srl;
		}
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->certify_key = $_COOKIE['autosave_certify_key_' . $args->module_srl];
			// @see https://github.com/xpressengine/xe-core/issues/2208
			// 변경 이전에 작성된 게시물 호환성 유지
			if(!$args->certify_key) {
				unset($args->certify_key);
				$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			}
		}
		// Check if the auto-saved document already exists
		$output = executeQuery('editor.getSavedDocument', $args);
		$saved_doc = $output->data;
		if(!$saved_doc) return;

		$oDocumentModel = getModel('document');
		$oSaved = $oDocumentModel->getDocument($saved_doc->document_srl);
		if(!$oSaved->isExists())
		{
			if($mode)
			{
				$output = executeQuery('editor.getSavedDocument', $args);
				$output = ModuleHandler::triggerCall('editor.deleteSavedDoc', 'after', $saved_doc);
			}
		}

		$output = executeQuery('editor.deleteSavedDoc', $args);
		return $output;
	}

	/**
	 * @brief ERemove editor component information used on the virtual site
	 */
	function removeEditorConfig($site_srl)
	{
		$args->site_srl = $site_srl;
		executeQuery('editor.deleteSiteComponent', $args);
	}

	/**
	 * @brief Caching a list of editor component (editorModel::getComponentList)
	 * For the editor component list, use a caching file because of DB query and Xml parsing
	 */
	function makeCache($filter_enabled = true, $site_srl)
	{
		$oEditorModel = getModel('editor');
		$args = new stdClass;

		if($filter_enabled) $args->enabled = "Y";

		if($site_srl)
		{
			$args->site_srl = $site_srl;
			$output = executeQuery('editor.getSiteComponentList', $args);
		}
		else $output = executeQuery('editor.getComponentList', $args);
		$db_list = $output->data;

		// Get a list of files
		$downloaded_list = FileHandler::readDir(_XE_PATH_.'modules/editor/components');

		// Get information about log-in status and its group
		$is_logged = Context::get('is_logged');
		if($is_logged)
		{
			$logged_info = Context::get('logged_info');
			if($logged_info->group_list && is_array($logged_info->group_list))
			{
				$group_list = array_keys($logged_info->group_list);
			}
			else $group_list = array();
		}

		// Get xml information for looping DB list
		if(!is_array($db_list)) $db_list = array($db_list);
		$component_list = new stdClass();
		foreach($db_list as $component)
		{
			if(in_array($component->component_name, array('colorpicker_text','colorpicker_bg'))) continue;

			$component_name = $component->component_name;
			if(!$component_name) continue;

			if(!in_array($component_name, $downloaded_list)) continue;

			unset($xml_info);
			$xml_info = $oEditorModel->getComponentXmlInfo($component_name);
			$xml_info->enabled = $component->enabled;

			if($component->extra_vars)
			{
				$extra_vars = unserialize($component->extra_vars);
				if($extra_vars->target_group)
				{
					$xml_info->target_group = $extra_vars->target_group;
				}

				if($extra_vars->mid_list && count($extra_vars->mid_list))
				{
					$xml_info->mid_list = $extra_vars->mid_list;
				}
				/*
				// Permisshin check if you are granted
				if($extra_vars->target_group) {
				// Stop using if not logged-in
				if(!$is_logged) continue;
				// Compare a target group with the current logged-in user group
				$target_group = $extra_vars->target_group;
				unset($extra_vars->target_group);

				$is_granted = false;
				foreach($group_list as $group_srl) {
				if(in_array($group_srl, $target_group)) {
				$is_granted = true;
				break;
				}
				}
				if(!$is_granted) continue;
				}
				// Check if the target module exists
				if($extra_vars->mid_list && count($extra_vars->mid_list) && Context::get('mid')) {
				if(!in_array(Context::get('mid'), $extra_vars->mid_list)) continue;
				}*/
				// Check the configuration of the editor component
				if($xml_info->extra_vars)
				{
					foreach($xml_info->extra_vars as $key => $val)
					{
						$xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
					}
				}
			}

			$component_list->{$component_name} = $xml_info;
			// Get buttons, icons, images
			$icon_file = _XE_PATH_.'modules/editor/components/'.$component_name.'/icon.gif';
			$component_icon_file = _XE_PATH_.'modules/editor/components/'.$component_name.'/component_icon.gif';
			if(file_exists($icon_file)) $component_list->{$component_name}->icon = true;
			if(file_exists($component_icon_file)) $component_list->{$component_name}->component_icon = true;
		}

		// Return if it checks enabled only
		if(!$filter_enabled) {
			// Get xml_info of downloaded list
			foreach($downloaded_list as $component_name)
			{
				if(!is_dir(_XE_PATH_.'modules/editor/components/'.$component_name)) continue;
				if(in_array($component_name, array('colorpicker_text','colorpicker_bg'))) continue;
				// Pass if configured
				if($component_list->{$component_name}) continue;
				// Insert data into the DB
				$oEditorController = getAdminController('editor');
				$oEditorController->insertComponent($component_name, false, $site_srl);
				// Add to component_list
				unset($xml_info);
				$xml_info = $oEditorModel->getComponentXmlInfo($component_name);
				$xml_info->enabled = 'N';

				$component_list->{$component_name} = $xml_info;
			}
		}

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport()) {
			$cache_key = $oEditorModel->getComponentListCacheKey($filter_enabled, $site_srl);
			$oCacheHandler->put($cache_key, $component_list);
		}

		return $component_list;
	}

	/**
	 * @brief Delete cache files
	 */
	function removeCache($site_srl = 0)
	{
		$oEditorModel = getModel('editor');
		FileHandler::removeFile($oEditorModel->getCacheFile(true, $site_srl));
		FileHandler::removeFile($oEditorModel->getCacheFile(false, $site_srl));

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport()) {
			$cache_key = $oEditorModel->getComponentListCacheKey(true, $site_srl);
			$oCacheHandler->delete($cache_key);
			$cache_key = $oEditorModel->getComponentListCacheKey(false, $site_srl);
			$oCacheHandler->delete($cache_key);
		}
	}

	function triggerCopyModule(&$obj)
	{
		$oModuleModel = getModel('module');
		$editorConfig = $oModuleModel->getModulePartConfig('editor', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList AS $key=>$moduleSrl)
			{
				$oModuleController->insertModulePartConfig('editor', $moduleSrl, $editorConfig);
			}
		}
	}
}
/* End of file editor.controller.php */
/* Location: ./modules/editor/editor.controller.php */
