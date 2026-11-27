<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/* CAPTCHA integration adapted from Rhymix on 2026-11-27. See COPYRIGHT and LICENSE. */
/**
 * @class  spamfilterController
 * @author XEHub (developers@xpressengine.com)
 * @brief The controller class for the spamfilter module
 */
class spamfilterController extends spamfilter
{
	/**
	 * CAPTCHA trigger adapted from Rhymix on 2026-11-27; see COPYRIGHT and LICENSE.
	 * Verify before executing the action, including JSON and legacy XML requests.
	 */
	function triggerCheckCaptcha(&$module)
	{
		$config = SpamfilterCaptcha::config();
		if (empty($config->provider) || $config->provider === 'none') return;
		$user = Context::get('logged_info');
		$targets = SpamfilterCaptcha::targets($config->rules, Mobile::isFromMobilePhone() ? 'mobile' : 'pc', Context::get('mid'), $user);
		$scope = SpamfilterCaptcha::site() . ':' . $config->revision . ':' . (isset($user->member_srl) ? $user->member_srl : 0);
		foreach ($targets as $act => $policy)
		{
			if ($policy !== 'every_time' && !empty($_SESSION['spamfilter_captcha'][$scope][$policy])) unset($targets[$act]);
		}
		$act = strtolower($module->act);
		if (strpos($act, 'proc') === 0)
		{
			$targets = array_change_key_case($targets, CASE_LOWER);
			if (!isset($targets[$act])) return;
			$field = SpamfilterCaptcha::field($config->provider);
			$token = Context::get($field);
			Context::set($field, null);
			Context::loadLang(_XE_PATH_ . 'modules/spamfilter/lang');
			$output = SpamfilterCaptcha::verify($config->provider, $config->secret_key, $token);
			if (!$output->toBool()) return $output;
			if ($targets[$act] !== 'every_time') $_SESSION['spamfilter_captcha'][$scope][$targets[$act]] = true;
		}
		elseif ($targets && Context::getResponseMethod() === 'HTML')
		{
			Context::loadLang(_XE_PATH_ . 'modules/spamfilter/lang');
			Context::set('spamfilter_captcha_actions', $targets, false);
			Context::loadFile(array('./modules/spamfilter/tpl/js/captcha.js', 'body'));
			Context::addHtmlFooter('<div hidden id="xe-captcha-config" data-failed="' . htmlspecialchars(Context::getLang('msg_captcha_connection'), ENT_QUOTES, 'UTF-8') . '" data-provider="' . htmlspecialchars($config->provider, ENT_QUOTES, 'UTF-8')
				. '" data-sitekey="' . htmlspecialchars($config->site_key, ENT_QUOTES, 'UTF-8') . '" data-actions="' . implode(',', array_keys($targets)) . '"></div>');
		}
	}

	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Call this function in case you need to stop the spam filter's usage during the batch work
	 */
	function setAvoidLog()
	{
		$_SESSION['avoid_log'] = true;
	}

	/**
	 * @brief The routine process to check the time it takes to store a document, when writing it, and to ban IP/word
	 */
	function triggerInsertDocument(&$obj)
	{
		if($_SESSION['avoid_log']) return new BaseObject();
		// Check the login status, login information, and permission
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$grant = Context::get('grant');
		// In case logged in, check if it is an administrator
		if($is_logged)
		{
			if($logged_info->is_admin == 'Y') return new BaseObject();
			if($grant->manager) return new BaseObject();
		}

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = '';
		if($is_logged)
		{
			$text = $obj->title . ' ' . $obj->content . ' ' . $obj->tags;
		}
		else
		{
			$text = $obj->title . ' ' . $obj->content . ' ' . $obj->nick_name . ' ' . $obj->homepage . ' ' . $obj->tags;	
		}
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// Check the specified time beside the modificaiton time
		if($obj->document_srl == 0)
		{
			$output = $oFilterModel->checkLimited();
			if(!$output->toBool()) return $output;
		}
		// Save a log
		$this->insertLog();

		return new BaseObject();
	}

	/**
	 * @brief The routine process to check the time it takes to store a comment, and to ban IP/word
	 */
	function triggerInsertComment(&$obj)
	{
		if($_SESSION['avoid_log']) return new BaseObject();
		// Check the login status, login information, and permission
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$grant = Context::get('grant');
		// In case logged in, check if it is an administrator
		if($is_logged)
		{
			if($logged_info->is_admin == 'Y') return new BaseObject();
			if($grant->manager) return new BaseObject();
		}

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = '';
		if($is_logged)
		{
			$text = $obj->content;
		}
		else
		{
			$text = $obj->content . ' ' . $obj->nick_name . ' ' . $obj->homepage;	
		}
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// If the specified time check is not modified
		if(!$obj->__isupdate)
		{
			$output = $oFilterModel->checkLimited();
			if(!$output->toBool()) return $output;
		}
		unset($obj->__isupdate);
		// Save a log
		$this->insertLog();

		return new BaseObject();
	}

	/**
	 * @brief Inspect the trackback creation time and IP
	 */
	function triggerInsertTrackback(&$obj)
	{
		if($_SESSION['avoid_log']) return new BaseObject();

		$oFilterModel = getModel('spamfilter');
		// Confirm if the trackbacks have been added more than once to your document
		$output = $oFilterModel->isInsertedTrackback($obj->document_srl);
		if(!$output->toBool()) return $output;

		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = $obj->blog_name . ' ' . $obj->title . ' ' . $obj->excerpt . ' ' . $obj->url;
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// Start Filtering
		$oTrackbackModel = getModel('trackback');
		$oTrackbackController = getController('trackback');

		list($ipA,$ipB,$ipC,$ipD) = explode('.',$_SERVER['REMOTE_ADDR']);
		$ipaddress = $ipA.'.'.$ipB.'.'.$ipC;
		// In case the title and the blog name are indentical, investigate the IP address of the last 6 hours, delete and ban it.
		if($obj->title == $obj->excerpt)
		{
			$oTrackbackController->deleteTrackbackSender(60*60*6, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
			$this->insertIP($ipaddress.'.*', 'AUTO-DENIED : trackback.insertTrackback');
			return new BaseObject(-1,'msg_alert_trackback_denied');
		}
		// If trackbacks have been registered by one C-class IP address more than once for the last 30 minutes, ban the IP address and delete all the posts
		/* 호스팅 환경을 감안하여 일단 이 부분은 동작하지 않도록 주석 처리
		   $count = $oTrackbackModel->getRegistedTrackback(30*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
		   if($count > 1) {
		   $oTrackbackController->deleteTrackbackSender(3*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
		   $this->insertIP($ipaddress.'.*');
		   return new BaseObject(-1,'msg_alert_trackback_denied');
		   }
		 */

		return new BaseObject();
	}

	/**
	 * @brief IP registration
	 * The registered IP address is considered as a spammer
	 */
	function insertIP($ipaddress_list, $description = null)
	{
		$regExr = "/^((\d{1,3}(?:.(\d{1,3}|\*)){3})\s*(\/\/(.*)\s*)?)*\s*$/";
		if(!preg_match($regExr,$ipaddress_list)) return new BaseObject(-1, 'msg_invalid');
		$ipaddress_list = str_replace("\r","",$ipaddress_list);
		$ipaddress_list = explode("\n",$ipaddress_list);
		foreach($ipaddress_list as $ipaddressValue)
		{
			$args = new stdClass();
			preg_match("/(\d{1,3}(?:.(\d{1,3}|\*)){3})\s*(\/\/(.*)\s*)?/",$ipaddressValue,$matches);
			if($ipaddress=trim($matches[1]))
			{
				$args->ipaddress = $ipaddress;
				if(!$description && $matches[4]) $args->description = $matches[4];
				else $args->description = $description;
			}
			$output = executeQuery('spamfilter.insertDeniedIP', $args);
			if(!$output->toBool()) $fail_list .= $ipaddress.'<br/>';
		}

		$output->add('fail_list',$fail_list);
		return $output;
	}

	/**
	 * @brief The routine process to check the time it takes to store a message, when writing it, and to ban IP/word
	 */
	function triggerSendMessage(&$obj)
	{
		if($_SESSION['avoid_log']) return new BaseObject();

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y') return new BaseObject();

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = $obj->title . ' ' . $obj->content;
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// Check the specified time
		$output = $oFilterModel->checkLimited(TRUE);
		if(!$output->toBool()) return $output;
		// Save a log
		$this->insertLog();

		return new BaseObject();
	}

	/**
	 * @brief Log registration
	 * Register the newly accessed IP address in the log. In case the log interval is withing a certain time,
	 * register it as a spammer
	 */
	function insertLog()
	{
		$output = executeQuery('spamfilter.insertLog');
		return $output;
	}
}
/* End of file spamfilter.controller.php */
/* Location: ./modules/spamfilter/spamfilter.controller.php */
