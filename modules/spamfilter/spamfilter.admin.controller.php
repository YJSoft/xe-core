<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/* CAPTCHA integration adapted from Rhymix on 2026-11-27. See COPYRIGHT and LICENSE. */
/**
 * @class  spamfilterAdminController
 * @author XEHub (developers@xpressengine.com)
 * @brief The admin controller class of the spamfilter module
 */
class spamfilterAdminController extends spamfilter
{
	function procSpamfilterAdminTestCaptcha()
	{
		return $this->configureCaptcha(true);
	}

	function procSpamfilterAdminSaveCaptcha()
	{
		return $this->configureCaptcha(false);
	}

	private function configureCaptcha($test)
	{
		$user = Context::get('logged_info');
		if (empty($user->is_admin) || $user->is_admin !== 'Y' || Context::getRequestMethod() === 'GET') return new BaseObject(-1, 'msg_not_permitted');
		$old = SpamfilterCaptcha::config();
		$provider = Context::get('captcha_provider');
		if (!in_array($provider, array('none', 'recaptcha', 'turnstile'), true)) return new BaseObject(-1, 'msg_invalid_request');
		$site_key = Context::get('captcha_site_key');
		$secret = Context::get('captcha_secret_key');
		$token = Context::get(SpamfilterCaptcha::field($provider));
		Context::set('captcha_secret_key', null);
		Context::set('cf-turnstile-response', null);
		Context::set('g-recaptcha-response', null);
		if (!is_string($site_key) || !is_string($secret)) return new BaseObject(-1, 'msg_invalid_request');
		$site_key = trim($site_key);
		$secret = trim($secret);
		if ($secret === '' && isset($old->provider) && $old->provider === $provider) $secret = $old->secret_key;
		$site = SpamfilterCaptcha::site();
		$fingerprint = hash('sha256', serialize(array($user->member_srl, $site, $provider, $site_key, $secret)));
		if ($provider !== 'none')
		{
			if ($secret === '' || $site_key === '') return new BaseObject(-1, 'msg_captcha_keys');
			if ($test)
			{
				$output = SpamfilterCaptcha::verify($provider, $secret, $token);
				if (!$output->toBool()) return $output;
				$_SESSION['spamfilter_captcha_test'] = array($fingerprint, time());
				$this->setMessage('msg_captcha_test_ok');
				return;
			}
			if (empty($_SESSION['spamfilter_captcha_test']) || $_SESSION['spamfilter_captcha_test'][0] !== $fingerprint
				|| $_SESSION['spamfilter_captcha_test'][1] < time() - 900) return new BaseObject(-1, 'msg_captcha_test_first');
			if (!getModel('module')->getTrigger('moduleObject.proc', 'spamfilter', 'controller', 'triggerCheckCaptcha', 'before'))
				return new BaseObject(-1, 'msg_captcha_update');
		}
		elseif ($test) return new BaseObject(-1, 'msg_captcha_keys');

		$legacy = isset($old->legacy) && !empty($old->provider) && $old->provider !== 'none' ? $old->legacy :
			SpamfilterCaptcha::legacyRules(getAdminModel('addon')->getInsertedAddons($site));
		// XE may deliver repeated HTML form values as a |@| separated string.
		$actions = Context::get('captcha_actions');
		$devices = Context::get('captcha_devices');
		$custom = array('actions' => array_values(array_intersect(array_keys(SpamfilterCaptcha::$actions), is_array($actions) ? $actions : explode('|@|', (string)$actions))),
			'devices' => array_values(array_intersect(array('pc', 'mobile'), is_array($devices) ? $devices : explode('|@|', (string)$devices))),
			'everyone' => Context::get('captcha_users') === 'everyone', 'every_time' => Context::get('captcha_frequency') !== 'session',
			'mids' => array(), 'exclude' => false);
		$inherit = Context::get('captcha_inherit') === 'Y';
		$rules = $inherit ? $legacy : array();
		if ($custom['actions'] && $custom['devices']) $rules['custom'] = $custom;
		if ($provider !== 'none' && !$rules) return new BaseObject(-1, 'msg_captcha_targets');

		$config = (object)array('provider' => $provider, 'site_key' => $site_key, 'secret_key' => $provider === 'none' ? '' : $secret,
			'legacy' => $legacy, 'custom' => $custom, 'inherit' => $inherit, 'rules' => $rules, 'revision' => uniqid('', true));
		$db = DB::getInstance();
		$db->begin();
		$output = getController('module')->insertModuleConfig('spamfilter_captcha', $config, $site);
		if (!$output->toBool())
		{
			$db->rollback();
			return $output;
		}
		$db->commit();
		unset($_SESSION['spamfilter_captcha_test']);
		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfigCaptcha'));
	}

	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function procSpamfilterAdminInsertConfig()
	{
		// Get the default information
		$argsConfig = Context::gets('limits','check_trackback');
		$flag = Context::get('flag');
		//interval, limit_count
		if($argsConfig->check_trackback!='Y') $argsConfig->check_trackback = 'N';
		if($argsConfig->limits!='Y') $argsConfig->limits = 'N';
		// Create and insert the module Controller object
		$oModuleController = getController('module');
		$moduleConfigOutput = $oModuleController->insertModuleConfig('spamfilter',$argsConfig);
		if(!$moduleConfigOutput->toBool()) return $moduleConfigOutput;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfigBlock');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertDeniedIP()
	{
		//스팸IP  추가
		$ipaddress_list = Context::get('ipaddress_list');
		$oSpamfilterController = getController('spamfilter');
		if($ipaddress_list)
		{
			$output = $oSpamfilterController->insertIP($ipaddress_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(Context::getLang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(Context::getLang('success_registed').$message_fail);
		}


		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertDeniedWord()
	{
		//스팸 키워드 추가
		$word_list = Context::get('word_list');
		if($word_list)
		{
			$output = $this->insertWord($word_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(Context::getLang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(Context::getLang('success_registed').$message_fail);
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the banned IP
	 */
	function procSpamfilterAdminDeleteDeniedIP()
	{
		$ipAddressList = Context::get('ipaddress');
		if($ipAddressList) $this->deleteIP($ipAddressList);

		$this->setMessage(Context::getLang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the prohibited Word
	 */
	function procSpamfilterAdminDeleteDeniedWord()
	{
		$wordList = Context::get('word');
		$this->deleteWord($wordList);

		$this->setMessage(Context::getLang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList','active','word');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete IP
	 * Remove the IP address which was previously registered as a spammers
	 */
	function deleteIP($ipaddress)
	{
		if(!$ipaddress) return;

		$args = new stdClass;
		$args->ipaddress = $ipaddress;
		return executeQuery('spamfilter.deleteDeniedIP', $args);
	}

	/**
	 * @brief Register the spam word
	 * The post, which contains the newly registered spam word, should be considered as a spam
	 */
	function insertWord($word_list)
	{

		$word_list = str_replace("\r","",$word_list);
		$word_list = explode("\n",$word_list);

		foreach($word_list as $word)
		{
			if(!preg_match("/^(.{2,40}[\r\n]+)*.{2,40}$/", $word))
			{
				return new BaseObject(-1, 'msg_invalid');
			}
		}

		$fail_word = '';
		foreach($word_list as $word)
		{
			$args = new stdClass;
			if(trim($word)) $args->word = $word;
			$output = executeQuery('spamfilter.insertDeniedWord', $args);
			if(!$output->toBool()) $fail_word .= $word.'<br />';
		}
		$output->add('fail_list',$fail_word);
		return $output;
	}

	/**
	 * @brief Remove the spam word
	 * Remove the word which was previously registered as a spam word
	 */
	function deleteWord($word)
	{
		if(!$word) return;
		$args = new stdClass;
		$args->word = $word;
		return executeQuery('spamfilter.deleteDeniedWord', $args);
	}
}
/* End of file spamfilter.admin.controller.php */
/* Location: ./modules/spamfilter/spamfilter.admin.controller.php */
