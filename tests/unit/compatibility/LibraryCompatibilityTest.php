<?php
require_once _XE_PATH_ . 'tests/_support/CompatibilityTestCase.php';
require_once _XE_PATH_ . 'libs/ftp.class.php';

/** @group compatibility */
class LibraryCompatibilityTest extends CompatibilityTestCase
{
	public function testFtpConstructorAndRemoteListingWithoutEach()
	{
		$ftp = new CompatibilityFtp();
		$this->assertSame(30, $ftp->timeout);
		$this->assertSame(0022, $ftp->umask);
		$this->assertSame(1, $ftp->ftp_file_exists('present.txt'));
		$this->assertSame(0, $ftp->ftp_file_exists('absent.txt'));
		$ftp->listing = false;
		$this->assertSame(-1, $ftp->ftp_file_exists('present.txt'));
	}

	public function testMailQuotedPrintableFallbackPreservesUnicodeAndLineBreaks()
	{
		require_once _XE_PATH_ . 'libs/phpmailer/phpmailer.php';
		$mail = new PHPMailer();
		$source = ".leading dot\r\n한글 = value \r\n" . str_repeat('long line ', 20);
		$encoded = $mail->EncodeQPphp($source);
		$this->assertSame($source . "\r\n", quoted_printable_decode($encoded));
		$this->assertTrue(strpos($encoded, '=2Eleading') === 0);
		foreach(explode("\r\n", $encoded) as $line) $this->assertTrue(strlen($line) <= 76);
	}

	public function testIdnaStringOffsetsEncodeAndDecodeInternationalDomainNames()
	{
		require_once _XE_PATH_ . 'libs/idna_convert/idna_convert.class.php';
		$idna = new idna_convert(array('idn_version' => 2008));
		$this->assertSame('xn--bcher-kva.example', $idna->encode('bücher.example'));
		$this->assertSame('bücher.example', $idna->decode('xn--bcher-kva.example'));
	}

	public function testGuestPurificationLoadsHtmlParserAndPurifierAutoloaders()
	{
		unset($GLOBALS['__PURIFIER_INSTANCE__'], $GLOBALS['__EMBEDFILTER_INSTANCE__']);
		Context::set('logged_info', null, false);
		$content = '<p>Safe</p><font size="+2">Font</font><script>alert(1)</script><a href="https://example.com/">Link</a>';
		purifierHtml($content);
		$this->assertTrue(strpos($content, 'Safe') !== false);
		$this->assertTrue(strpos($content, 'Font') !== false);
		$this->assertFalse(strpos($content, '<script'));
		$this->assertTrue(strpos($content, 'nofollow') !== false);
	}

	public static function pearVersions()
	{
		return array(array('PEAR.1.9'), array('PEAR.1.9.5'));
	}

	/** @dataProvider pearVersions */
	public function testPearHttpRequestWithoutRemovedMagicQuotesFunctions($version)
	{
		$source = 'set_include_path(' . var_export(_XE_PATH_ . 'libs/' . $version, true) . ' . PATH_SEPARATOR . get_include_path());'
			. 'require "HTTP/Request2.php"; require "HTTP/Request2/Adapter/Mock.php";'
			. '$adapter = new HTTP_Request2_Adapter_Mock();'
			. '$adapter->addResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nresponse body");'
			. '$request = new HTTP_Request2("https://example.com/"); $request->setAdapter($adapter);'
			. '$response = $request->send(); echo $response->getStatus(), ":", $response->getBody();';
		$this->assertSame('200:response body', $this->runPhp($source));
	}

	/** @dataProvider pearVersions */
	public function testPearDestructorCallbacksWithoutEach($version)
	{
		$source = 'require ' . var_export(_XE_PATH_ . 'libs/' . $version . '/PEAR.php', true) . ';'
			. 'class CompatibilityDestructor { function _CompatibilityDestructor() { echo "destroyed"; } }'
			. '$GLOBALS["_PEAR_destructor_object_list"] = array(new CompatibilityDestructor());'
			. '_PEAR_call_destructors(); $GLOBALS["_PEAR_destructor_object_list"] = array();';
		$this->assertSame('destroyed', $this->runPhp($source));
	}

	public function testPasswordPbkdf2AndConstantTimeComparisonAreStatic()
	{
		// RFC 6070 PBKDF2-HMAC-SHA1 test vector, including the legacy fallback.
		$hash = Password::pbkdf2('password', 'salt', 'sha1', 1, 20);
		$this->assertSame('0c60c80f961f0e71f3a9b524af6012062fe037a6', bin2hex($hash));
		$this->assertTrue(Password::strcmpConstantTime($hash, $hash));
		$this->assertFalse(Password::strcmpConstantTime($hash, $hash . 'x'));
		$this->assertFalse(Password::strcmpConstantTime($hash, str_repeat('x', strlen($hash))));
	}
}

class CompatibilityFtp extends ftp
{
	public $listing = array('present.txt', 'other.txt');
	public function ftp_nlist($arg = '', $pathname = '') { return $this->listing; }
}
