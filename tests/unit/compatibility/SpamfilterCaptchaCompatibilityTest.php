<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'modules/spamfilter/captcha/Captcha.php';

/** @group compatibility */
class SpamfilterCaptchaCompatibilityTest extends CompatibilityTestCase
{
	public function testLegacyMemberAndBoardProtectionKeepIndependentScopes()
	{
		$board = (object)array('is_used' => 'Y', 'is_used_m' => 'N', 'extra_vars' => serialize((object)array(
			'mid_list' => array('notice'), 'xe_run_method' => 'no_run_selected', 'act_type' => 'everytime')));
		$member = (object)array('is_used' => 'N', 'is_used_m' => 'Y', 'extra_vars' => serialize((object)array(
			'mid_list' => array('notice'), 'apply_signup' => 'apply')));
		$rules = SpamfilterCaptcha::legacyRules(array('captcha' => $board, 'captcha_member' => $member));
		$pc = SpamfilterCaptcha::targets($rules, 'pc', 'forum', null);
		$this->assertSame('every_time', $pc['procBoardInsertDocument']);
		$this->assertSame(array(), SpamfilterCaptcha::targets($rules, 'pc', 'notice', null));
		$this->assertSame(array(), SpamfilterCaptcha::targets($rules, 'pc', 'forum', (object)array('member_srl' => 1)));
		$this->assertSame(array('procMemberInsert' => 'captcha_member'), SpamfilterCaptcha::targets($rules, 'mobile', 'notice', null));
		$this->assertSame(array(), SpamfilterCaptcha::targets($rules, 'mobile', 'forum', null));
		$this->assertSame(array(), SpamfilterCaptcha::targets($rules, 'mobile', 'notice', (object)array('is_site_admin' => true)));
		$rules['custom'] = $rules['captcha_member'];
		$rules['custom']['every_time'] = true;
		$this->assertSame(array('procMemberInsert' => 'every_time'), SpamfilterCaptcha::targets($rules, 'mobile', 'notice', null));
		$member->extra_vars = '';
		$this->assertSame(array(), SpamfilterCaptcha::legacyRules(array('captcha_member' => $member)));
	}

	public function testTriggerConsumesOnlyTheConfiguredProviderOnProtectedActions()
	{
		$this->useQueryFixture();
		Context::set('site_module_info', (object)array('site_srl' => 7));
		Context::set('logged_info', null);
		$rule = array('actions' => array('signup'), 'devices' => array('pc', 'mobile'),
			'everyone' => true, 'mids' => array(), 'exclude' => false, 'every_time' => true);
		$GLOBALS['__ModuleConfig__'][7]['spamfilter_captcha'] = (object)array('provider' => 'turnstile',
			'secret_key' => 'test', 'rules' => array('custom' => $rule), 'revision' => 'fixture');
		$module = (object)array('act' => 'procOtherModuleSubmit');
		$controller = new spamfilterController();
		Context::set('cf-turnstile-response', 'other-form', true);
		$this->assertNull($controller->triggerCheckCaptcha($module));
		$this->assertSame('other-form', Context::get('cf-turnstile-response'));

		$module->act = 'procMemberInsert';
		Context::set('cf-turnstile-response', array(), true);
		$result = $controller->triggerCheckCaptcha($module);
		$this->assertFalse($result->toBool());
		$vars = Context::getRequestVars();
		$this->assertFalse(isset($vars->{'cf-turnstile-response'}));
	}

	public function testOnlySuccessfulVerificationForTheCurrentHostIsAccepted()
	{
		$success = '{"success":true,"hostname":"example.org"}';
		$this->assertTrue(SpamfilterCaptcha::checkResponse(200, $success, 'Example.ORG')->toBool());
		$this->assertFalse(SpamfilterCaptcha::checkResponse(200, $success, 'elsewhere.example')->toBool());
		$this->assertFalse(SpamfilterCaptcha::checkResponse(502, $success, 'example.org')->toBool());
		$this->assertFalse(SpamfilterCaptcha::checkResponse(200, '{}', 'example.org')->toBool());
		$this->assertFalse(SpamfilterCaptcha::checkResponse(200, '{"success":false,"error-codes":["timeout-or-duplicate"]}', 'example.org')->toBool());
		$this->assertFalse(SpamfilterCaptcha::checkResponse(200, '{"success":"true","hostname":"example.org"}', 'example.org')->toBool());
		$this->assertFalse(SpamfilterCaptcha::verify('turnstile', 'secret', null)->toBool());
		$this->assertFalse(SpamfilterCaptcha::verify('recaptcha', 'secret', array('token'))->toBool());
	}
}
