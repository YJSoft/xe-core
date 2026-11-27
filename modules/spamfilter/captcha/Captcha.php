<?php
/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * Based on Rhymix modules/spamfilter at 57157b24fb9ccb4e87bca1b56fa3e1a139310796.
 * reCAPTCHA by Kijin Sung, Turnstile contributed by Lastorder.
 * Modified for XE on 2026-11-27: PHP 5.3 transport and legacy addon policies.
 * See the repository LICENSE and COPYRIGHT.
 */
class SpamfilterCaptcha
{
	public static $actions = array(
		'signup' => array('procMemberInsert'),
		'login' => array('procMemberLogin'),
		'recovery' => array('procMemberFindAccount'),
		'resend' => array('procMemberResendAuthMail'),
		'document' => array('procBoardInsertDocument', 'procIssuetrackerInsertIssue'),
		'comment' => array('procBoardInsertComment', 'procIssuetrackerInsertHistory', 'procTextyleInsertComment'),
	);

	public static function site()
	{
		$site = Context::get('site_module_info');
		return isset($site->site_srl) ? (int)$site->site_srl : 0;
	}

	public static function config()
	{
		// Keep credentials separate from the existing spam filter settings and their admin form.
		return getModel('module')->getModuleConfig('spamfilter_captcha', self::site());
	}

	public static function enabled()
	{
		if (!Context::isInstalled()) return false;
		$config = self::config();
		return !empty($config->provider) && $config->provider !== 'none';
	}

	public static function legacyRules($addons)
	{
		$rules = array();
		foreach (array('captcha', 'captcha_member') as $addon)
		{
			if (!isset($addons[$addon])) continue;
			$row = $addons[$addon];
			$devices = array();
			if ($row->is_used === 'Y') $devices[] = 'pc';
			if ($row->is_used_m === 'Y') $devices[] = 'mobile';
			if (!$devices) continue;
			$vars = !empty($row->extra_vars) ? unserialize($row->extra_vars) : null;
			$actions = array();
			if ($addon === 'captcha')
			{
				$actions = array('document', 'comment');
			}
			else
			{
				foreach (array('apply_signup' => 'signup', 'apply_find_account' => 'recovery', 'apply_resend_auth_mail' => 'resend') as $key => $action)
				{
					if (isset($vars->$key) && $vars->$key === 'apply') $actions[] = $action;
				}
			}
			if (!$actions) continue;
			$rules[$addon] = array('actions' => $actions, 'devices' => $devices,
				'everyone' => $addon === 'captcha_member' || (isset($vars->target) && $vars->target === 'all'),
				'every_time' => $addon === 'captcha' && isset($vars->act_type) && $vars->act_type === 'everytime',
				'mids' => isset($vars->mid_list) && is_array($vars->mid_list) ? array_values($vars->mid_list) : array(),
				'exclude' => isset($vars->xe_run_method) && $vars->xe_run_method === 'no_run_selected');
		}
		return $rules;
	}

	/** Map protected controller actions to their session policy in this request. */
	public static function targets($rules, $device, $mid, $user)
	{
		$result = array();
		if (!empty($user->is_admin) && $user->is_admin === 'Y') return $result;
		foreach ($rules as $name => $rule)
		{
			if ($name !== 'custom' && !empty($user->is_site_admin)) continue;
			if (!in_array($device, $rule['devices'], true) || (!$rule['everyone'] && !empty($user->member_srl))) continue;
			if ($rule['mids'])
			{
				$listed = in_array($mid, $rule['mids'], true);
				if ($rule['exclude'] ? $listed : !$listed) continue;
			}
			foreach ($rule['actions'] as $group)
			{
				$actions = self::$actions[$group];
				if ($name === 'custom' && $group === 'recovery') $actions[] = 'procMemberFindAccountByQuestion';
				foreach ($actions as $act)
				{
					if (empty($result[$act]) || $rule['every_time']) $result[$act] = $rule['every_time'] ? 'every_time' : $name;
				}
			}
		}
		return $result;
	}

	public static function field($provider)
	{
		return $provider === 'turnstile' ? 'cf-turnstile-response' : 'g-recaptcha-response';
	}

	public static function verify($provider, $secret, $token)
	{
		$urls = array('turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			'recaptcha' => 'https://www.google.com/recaptcha/api/siteverify');
		if (!isset($urls[$provider]) || !is_string($token) || $token === '') return new BaseObject(-1, 'msg_captcha_required');
		if (!function_exists('curl_init')) return new BaseObject(-1, 'msg_captcha_connection');
		requirePear();
		require_once 'HTTP/Request2.php';
		try
		{
			$request = new HTTP_Request2($urls[$provider], HTTP_Request2::METHOD_POST, array(
				'adapter' => 'curl', 'timeout' => 10, 'follow_redirects' => false,
				'ssl_verify_peer' => true, 'ssl_verify_host' => true,
			));
			$request->addPostParameter(array('secret' => $secret, 'response' => $token));
			$response = $request->send();
		}
		catch (Exception $e)
		{
			return new BaseObject(-1, 'msg_captcha_connection');
		}
		return self::checkResponse($response->getStatus(), $response->getBody(), parse_url(Context::getRequestUri(), PHP_URL_HOST));
	}

	public static function checkResponse($status, $body, $host)
	{
		$data = json_decode($body, true);
		if (!isset($data['success']) || $status >= 500) return new BaseObject(-1, 'msg_captcha_connection');
		if ($status !== 200 || $data['success'] !== true || empty($data['hostname']) || !is_string($data['hostname'])
			|| !$host || strcasecmp($data['hostname'], $host) !== 0) return new BaseObject(-1, 'msg_captcha_required');
		return new BaseObject();
	}
}
