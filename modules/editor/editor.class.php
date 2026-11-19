<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * @class  editor
 * @author XEHub (developers@xpressengine.com)
 * @brief high class of the editor odule 
 */
class editor extends ModuleObject
{
	/** Use the routed module for both authorization and storage, never request module_srl. */
	protected function getAutosaveModuleInfo()
	{
		$module_info = Context::get('current_module_info');
		if(!is_object($module_info) || empty($module_info->module_srl) || empty($module_info->module)) return null;
		$oModuleModel = getModel('module');
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(empty($grant->access) || (isset($grant->write_document) && !$grant->write_document)) return null;
		if(!isset($grant->write_document) && isset($grant->write_comment) && !$grant->write_comment) return null;
		if($module_info->module === 'member' || ($module_info->module === 'page' && empty($grant->manager))) return null;
		return $module_info;
	}

	/** Guest autosaves require a secret cookie; an IP address is not proof of ownership. */
	protected function getAutosaveIdentity($module_srl, $create = false)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$logged_info = Context::get('logged_info');
		$args->member_srl = !empty($logged_info->member_srl) ? $logged_info->member_srl : 0;
		if(!$args->member_srl)
		{
			$cookie = 'autosave_certify_key_' . $module_srl;
			$key = isset($_COOKIE[$cookie]) ? $_COOKIE[$cookie] : null;
			if(!is_string($key) || !preg_match('/^[a-f0-9]{40,100}$/iD', $key))
			{
				if(!$create) return null;
				$key = Security::getRandomToken(20);
				if(!$key) return null;
			}
			if($create)
			{
				$secure = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
				setcookie($cookie, $key, time() + 7 * 86400, getScriptPath(), '', $secure, true);
				$_COOKIE[$cookie] = $key;
			}
			$args->certify_key = $key;
		}
		return $args;
	}

	/** A download URL never contains the browser's draft credential. */
	public function getGuestUploadKey($module_srl, $file_srl, $create = false)
	{
		$identity = $this->getAutosaveIdentity($module_srl, $create);
		if(empty($identity->certify_key)) return null;
		return hash_hmac('sha256', $module_srl . ':' . $file_srl, $identity->certify_key);
	}

	/**
	 * Return the pending files owned by this writer. A guest with an unverified
	 * file can recover the text only (null). Other invalid targets return false.
	 * Use the persistent credential only after authenticating the saved draft.
	 */
	protected function getAutosaveFiles($target_srl, $module_srl, $restore_guest = false)
	{
		if(!$target_srl) return array();
		$oDocumentModel = getModel('document');
		$document = $oDocumentModel->getDocument($target_srl);
		if($document->isExists()) return false;

		$args = new stdClass();
		$args->upload_target_srl = $target_srl;
		$output = executeQueryArray('file.getFiles', $args);
		if(!$output->toBool()) return false;
		$logged_info = Context::get('logged_info');
		$member_srl = !empty($logged_info->member_srl) ? $logged_info->member_srl : 0;
		$files = array();
		$unverified_guest_file = false;
		foreach((array)$output->data as $file)
		{
			if((string)$file->module_srl !== (string)$module_srl || $file->isvalid !== 'N') return false;
			if($file->member_srl)
			{
				if((string)$file->member_srl !== (string)$member_srl) return false;
			}
			elseif(empty($_SESSION['__XE_UPLOADING_FILES_INFO__'][$file->file_srl]))
			{
				if(!$restore_guest || empty($file->guest_upload_key) || $file->guest_upload_key !== $this->getGuestUploadKey($module_srl, $file->file_srl))
				{
					$unverified_guest_file = true;
				}
			}
			$files[] = $file;
		}
		return $unverified_guest_file ? null : $files;
	}

	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');
		// Add the default editor component
		$oEditorController = getAdminController('editor');
		$oEditorController->insertComponent('colorpicker_text',true);
		$oEditorController->insertComponent('colorpicker_bg',true);
		$oEditorController->insertComponent('emoticon',true);
		$oEditorController->insertComponent('url_link',true);
		$oEditorController->insertComponent('image_link',true);
		$oEditorController->insertComponent('multimedia_link',true);
		$oEditorController->insertComponent('quotation',true);
		$oEditorController->insertComponent('table_maker',true);
		$oEditorController->insertComponent('poll_maker',true);
		$oEditorController->insertComponent('image_gallery',true);
		// Create a directory to use in the editor module
		FileHandler::makeDir('./files/cache/editor');
		// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
		$oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		$oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		// 2007. 10. 23 Add an editor trigger on the module addition setup
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');
		// 2009. 04. 14 Add a trigger from compiled codes of the editor component
		$oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');

		return new BaseObject();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$version_update_id = implode('.', array(__CLASS__, __XE_VERSION__, 'updated'));
		if($oModuleModel->needUpdate($version_update_id))
		{
			// 2009. 06. 15 Save module_srl when auto-saving
			if(!$oDB->isColumnExists("editor_autosave","module_srl")) return true;
			if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) return true;

			// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
			if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
			// 2007. 10. 23 Add an editor trigger on the module addition setup
			if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) return true;
			// 2009. 04. 14 Add a trigger from compiled codes of the editor component
			if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) return true;
			// 2009. 06. 19 Remove unused trigger
			if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) return true;

			// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
			if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after')) return true;

			if(!$oDB->isColumnExists("editor_autosave", "certify_key")) return true;
			if(!$oDB->isIndexExists('editor_autosave', 'idx_certify_key')) return true;

			$oModuleController->insertUpdatedLog($version_update_id);
		}

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$version_update_id = implode('.', array(__CLASS__, __XE_VERSION__, 'updated'));
		if($oModuleModel->needUpdate($version_update_id))
		{
			// Save module_srl when auto-saving 15/06/2009
			if(!$oDB->isColumnExists("editor_autosave","module_srl")) 
				$oDB->addColumn("editor_autosave","module_srl","number",11);

			// create an index on module_srl
			if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) $oDB->addIndex("editor_autosave","idx_module_srl", "module_srl");

			// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
			if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
				$oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
			if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
				$oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
			// 2007. 10. Add an editor trigger on the module addition setup
			if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) 
				$oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');
			// 2009. 04. 14 Add a trigger from compiled codes of the editor component
			if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) 
				$oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');
			// 2009. 06. 19 Remove unused trigger
			if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) 
				$oModuleController->deleteTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before');

			// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
			if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after'))
			{
				$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after');
			}

			// @see https://github.com/xpressengine/xe-core/issues/2229
			if(!$oDB->isColumnExists('editor_autosave','certify_key'))
			{
				$oDB->addColumn('editor_autosave', 'certify_key', 'varchar', 100);
			}
			if(!$oDB->isIndexExists("editor_autosave","idx_certify_key"))
			{
				$oDB->addIndex("editor_autosave","idx_certify_key", "certify_key");
			}

			$oModuleController->insertUpdatedLog($version_update_id);
		}

		return new BaseObject(0, 'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file editor.class.php */
/* Location: ./modules/editor/editor.class.php */
