<?php
/** Standalone security regressions. Run with php53, php54, and current PHP. */
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
define('_XE_PATH_', dirname(dirname(dirname(__FILE__))) . '/');
define('__DEBUG__', 0);
define('__XE__', true);
$GLOBALS['checks'] = 0;
$GLOBALS['models'] = array();
$GLOBALS['query_error'] = false;
$GLOBALS['query_log'] = array();
$GLOBALS['fixture_dir'] = sys_get_temp_dir() . '/xe-security-' . getmypid() . '/';
mkdir($GLOBALS['fixture_dir']);
function check($condition, $message)
{
    $GLOBALS['checks']++;
    if (!$condition) { fwrite(STDERR, "FAIL: " . $message . "\n"); exit(1); }
}
function removeFixture($path)
{
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) as $entry) if ($entry !== '.' && $entry !== '..') removeFixture($path . '/' . $entry);
        rmdir($path);
    } else { unlink($path); }
}
register_shutdown_function(function() { removeFixture($GLOBALS['fixture_dir']); });
class Context
{
    public static $values = array();
    public static function getRequestVars() { return (object)self::$values; }
    public static function get($key) { return isset(self::$values[$key]) ? self::$values[$key] : null; }
    public static function set($key, $value) { self::$values[$key] = $value; }
    public static function loadLangSupported() { return array('ko' => 'Korean', 'en' => 'English'); }
    public static function getLangType() { return 'ko'; }
    public static function getRequestMethod() { return 'HTML'; }
    public static function getDBInfo() { return (object)array('lang_type' => 'ko'); }
    public static function getDefaultUrl() { return self::get('default_url'); }
    public static function getRequestUri() { return self::get('request_url'); }
    public static function isInstalled() { return true; }
    public static function getLang($key) { return $key; }
}
class FileHandler
{
    public static function removeFile($path) { return !file_exists($path) || unlink($path); }
    public static function makeDir($path) { return is_dir($path) || mkdir($path, 0777, true); }
}
function getModel($name) { return $GLOBALS['models'][$name]; }
function getController($name) { return $GLOBALS['models'][$name . '_controller']; }
function getAdminController($name) { return getController($name); }
function executeQuery($query, $args = null)
{
    $GLOBALS['query_log'][] = array($query, is_object($args) ? clone $args : $args);
    $output = new BaseObject(($GLOBALS['query_error'] || (isset($GLOBALS['query_fail_name']) && $GLOBALS['query_fail_name'] === $query)) ? -1 : 0, 'fixture_db_error');
    $output->data = isset($GLOBALS['query_data'][$query]) ? $GLOBALS['query_data'][$query] : null;
    return $output;
}
function executeQueryArray($query, $args = null) { return executeQuery($query, $args); }
function getNotEncodedUrl() { return '/index.php'; }
require _XE_PATH_ . 'classes/object/BaseObject.class.php';
require _XE_PATH_ . 'classes/module/ModuleObject.class.php';
require _XE_PATH_ . 'classes/xml/XeXmlParser.class.php';
require _XE_PATH_ . 'modules/layout/layout.class.php';
require _XE_PATH_ . 'modules/layout/layout.admin.controller.php';
class FixtureLayoutModel
{
    public function getLayout($srl) { return null; }
    public function getUserLayoutCache($srl, $lang) { return $GLOBALS['fixture_dir'] . $srl . '/' . $lang . '.cache.php'; }
}
$GLOBALS['models']['layout'] = new FixtureLayoutModel();
$parser = new XeXmlParser();
$result = $parser->parse('<root><item key="value">XML check</item></root>');
check($result->root->item->body === 'XML check', 'XML callbacks must parse tag bodies');
check($result->root->item->attrs->key === 'value', 'XML callbacks must parse attributes');
FileHandler::makeDir($GLOBALS['fixture_dir'] . '123');
foreach (array('ko.cache.php', 'en.cache.php', 'unused.cache.php', 'layout.ini') as $file) file_put_contents($GLOBALS['fixture_dir'] . '123/' . $file, 'fixture');
$layout = new layoutAdminController();
$args = (object)array('layout_srl' => 123);
$layout->updateLayout($args);
check(!file_exists($GLOBALS['fixture_dir'] . '123/en.cache.php'), 'Updating a layout must invalidate other languages');
check(!file_exists($GLOBALS['fixture_dir'] . '123/unused.cache.php'), 'Disabled languages must not retain stale caches');
check(file_exists($GLOBALS['fixture_dir'] . '123/layout.ini'), 'Cache invalidation must preserve layout settings');
file_put_contents($GLOBALS['fixture_dir'] . '123/en.cache.php', 'fixture');
$GLOBALS['query_error'] = true;
$layout->updateLayout($args);
check(file_exists($GLOBALS['fixture_dir'] . '123/en.cache.php'), 'Failed layout updates must preserve caches');
$GLOBALS['query_error'] = false;
require _XE_PATH_ . 'classes/security/Security.class.php';
require _XE_PATH_ . 'classes/display/VirtualXMLDisplayHandler.php';
Context::set('default_url', 'http://example.com/xe/');
Context::set('request_url', 'http://example.com/xe/');
$_SERVER['HTTPS'] = 'off';
foreach (array('./index.php', '/index.php', 'index.php?a=1', '?a=1', '#anchor', 'http://EXAMPLE.com/', 'http://example.com:80/', '//example.com/', 'https://example.com/') as $url) {
    check(Security::isInternalURL($url), 'Allow internal URL: ' . $url);
}
foreach (array('javascript:alert(1)', 'data:text/html;base64,AAAA', 'file:///example.com/etc/passwd', 'mailto:a@example.com', 'tel:123', 'myapp://example.com/', 'http://evil.example/', '//evil.example/', 'https:\\\\evil.example/', 'http://example.com:8080/', 'https://example.com:80/', 'http://example.com@evil.example/', 'http://evil.example@example.com/', 'http:///example.com', '///evil.example/', "\tjavascript:alert(1)", 'http://example.com/<script>', 'http://example.com:0/', 'http://example.com:65536/', array('url')) as $url) {
    check(!Security::isInternalURL($url), 'Reject unsafe URL: ' . var_export($url, true));
}
Context::set('default_url', 'http://example.com:8080/');
Context::set('_https_port', 8443);
check(Security::isInternalURL('http://example.com:8080/'), 'Allow configured HTTP port');
check(Security::isInternalURL('https://example.com:8443/'), 'Allow configured HTTPS port');
check(!Security::isInternalURL('http://example.com/'), 'Reject missing custom HTTP port');
check(!Security::isInternalURL('https://example.com/'), 'Reject missing custom HTTPS port');
check(Security::isSameOrigin('https://example.com:443/a', 'https://example.com/b'), 'Default port equivalence');
check(!Security::isSameOrigin('https://example.com/a', 'http://example.com/a'), 'Scheme is part of origin');
check(!Security::isSameOrigin('http://example.com:8080/', 'http://example.com/'), 'Port is part of origin');
Context::set('default_url', 'http://한글.example/');
require_once _XE_PATH_ . 'libs/idna_convert/idna_convert.class.php';
$idn = new idna_convert(array('idn_version' => 2008));
check(Security::isInternalURL($idn->encode('http://한글.example/')), 'IDN and punycode equivalence');
Context::set('default_url', 'http://example.com/');
Context::set('site_module_info', (object)array('domain' => 'http://tenant.example:8080/'));
check(Security::isTrustedRequestURL('http://tenant.example:8080/a'), 'Current virtual site origin');
check(!Security::isTrustedRequestURL('http://tenant.example:80/a'), 'Virtual site port must match');
check(!Security::isTrustedRequestURL('http://example.com/'), 'Main site is not the current tenant origin');
Context::set('site_module_info', null);
Context::set('_https_port', null);
$object = new ModuleObject();
$object->setRedirectUrl('javascript:alert(1)');
check($object->get('redirect_url') === 'http://example.com/xe/', 'Module redirect must be safe');
$object = new ModuleObject();
$handler = new VirtualXMLDisplayHandler();
foreach (array('javascript:alert(1)', 'https://evil.example/', 'data:text/html,test') as $url) {
    Context::set('xeRequestURI', $url);
    $html = $handler->toDoc($object);
    check(strpos($html, 'win.location.href = "http:\\/\\/example.com') !== false, 'VirtualXML rejects unsafe destination');
    check(strpos($html, $url) === false, 'VirtualXML must not emit unsafe destination');
}
Context::set('xeRequestURI', '/index.php?a=1');
$object->setMessage('</script><script>alert(1)</script>');
$html = $handler->toDoc($object);
check(substr_count($html, '</script>') === 1, 'VirtualXML messages must not escape script context');
check(strpos($html, 'index.php?a=1') !== false, 'VirtualXML preserves internal destinations');
check(strlen(Security::getRandomToken(16)) === 32, 'Random tokens work on legacy PHP');
require _XE_PATH_ . 'modules/module/module.class.php';
require _XE_PATH_ . 'modules/module/module.model.php';
class FixtureGrantModel extends moduleModel
{
    public function isSiteAdmin($member_info, $site_srl = null) { return false; }
}
$grant_model = new FixtureGrantModel();
$module_info = (object)array('module_srl' => 100, 'module' => 'board', 'site_srl' => 0);
$guest = (object)array('member_srl' => 0, 'is_admin' => 'N');
$xml_info = (object)array('grant' => array('view' => (object)array('default' => 'guest')));
$GLOBALS['query_error'] = true;
$blocked = false;
try { $grant_model->getGrant($module_info, $guest, $xml_info); }
catch (Exception $e) { $blocked = $e->getMessage() === 'msg_db_query_failed'; }
check($blocked, 'DB errors must not activate default guest grants');
$GLOBALS['query_error'] = false;
$grant = $grant_model->getGrant($module_info, $guest, $xml_info);
check($grant->access && $grant->view, 'Successful empty grant query must retain XML defaults');
require _XE_PATH_ . 'modules/widget/widget.class.php';
require _XE_PATH_ . 'modules/widget/widget.controller.php';
class FixtureWidgetModule
{
    public $manager = true;
    public function getModuleInfoByModuleSrl($srl, $columns = array()) { return (object)array('module_srl' => $srl, 'module' => 'page', 'site_srl' => 0); }
    public function getGrant($module, $member) { return (object)array('manager' => $this->manager); }
}
class FixtureDocument
{
    public $document_srl = 200;
    public $module_srl = 100;
    public function isExists() { return true; }
    public function get($key) { return $this->$key; }
}
class FixtureDocumentModel
{
    public function getDocument($srl) { return $GLOBALS['document']; }
}
class FixtureDocumentController
{
    public $updates = 0;
    public $copies = 0;
    public $deletes = 0;
    public function updateDocument($document, $args) { $this->updates++; return new BaseObject(); }
    public function copyDocumentModule($srls, $target, $site) { $this->copies++; $out = new BaseObject(); $out->add('copied_srls', array(200 => 201)); return $out; }
    public function deleteDocument($srl) { $this->deletes++; return new BaseObject(); }
}
$GLOBALS['models']['module'] = new FixtureWidgetModule();
$GLOBALS['models']['document'] = new FixtureDocumentModel();
$GLOBALS['models']['document_controller'] = new FixtureDocumentController();
$GLOBALS['document'] = new FixtureDocument();
Context::set('module_srl', 999);
Context::set('document_srl', 200);
Context::set('logged_info', (object)array('member_srl' => 10));
$widget = new widgetController();
$out = $widget->procWidgetInsertDocument();
check(is_object($out) && !$out->toBool(), 'Widget document update must reject another module');
check($GLOBALS['models']['document_controller']->updates === 0, 'Rejected widget update must not modify documents');
Context::set('module_srl', 100);
$widget->procWidgetInsertDocument();
check($GLOBALS['models']['document_controller']->updates === 1, 'Allow own module widget update');
$GLOBALS['models']['module']->manager = false;
$out = $widget->procWidgetCopyDocument();
check(is_object($out) && !$out->toBool(), 'Widget copy requires source module permission');
$out = $widget->procWidgetDeleteDocument();
check(is_object($out) && !$out->toBool(), 'Widget delete requires source module permission');
check($GLOBALS['models']['document_controller']->copies === 0 && $GLOBALS['models']['document_controller']->deletes === 0, 'Denied copy/delete must not mutate');
$GLOBALS['models']['module']->manager = true;
$widget->procWidgetCopyDocument();
$widget->procWidgetDeleteDocument();
check($GLOBALS['models']['document_controller']->copies === 1 && $GLOBALS['models']['document_controller']->deletes === 1, 'Allow own module copy/delete');
require _XE_PATH_ . 'modules/communication/communication.class.php';
require _XE_PATH_ . 'modules/communication/communication.controller.php';
class FixtureCommunicationModel
{
    public function getSelectedMessage($srl) { return $GLOBALS['message']; }
}
$GLOBALS['models']['communication'] = new FixtureCommunicationModel();
Context::set('is_logged', true);
Context::set('message_srl', 300);
$communication = new communicationController();
foreach (array('R', 'T', 'CUSTOM') as $type) {
    $GLOBALS['message'] = (object)array('message_type' => $type, 'sender_srl' => 10, 'receiver_srl' => 99);
    $GLOBALS['query_log'] = array();
    $out = $communication->procCommunicationDeleteMessage();
    check(is_object($out) && !$out->toBool(), 'Reject foreign archived/received message: ' . $type);
    check(count($GLOBALS['query_log']) === 0, 'Denied message deletion must not execute SQL');
    $GLOBALS['message']->receiver_srl = 10;
    $communication->procCommunicationDeleteMessage();
    check(count($GLOBALS['query_log']) === 1, 'Allow own received message: ' . $type);
}
$GLOBALS['message'] = (object)array('message_type' => 'S', 'sender_srl' => 99, 'receiver_srl' => 10);
$out = $communication->procCommunicationDeleteMessage();
check(is_object($out) && !$out->toBool(), 'Sent messages belong to sender');
$GLOBALS['message']->sender_srl = 10;
check($communication->procCommunicationDeleteMessage() === null, 'Allow own sent message');
class EmbedFilter
{
    public static function getInstance() { return new self(); }
    public function getWhiteIframeUrlList() { return array('https://trusted.example/'); }
}
require _XE_PATH_ . 'classes/security/Purifier.class.php';
$content = '<p><img editor_component="x" widget="widgetContent" body="PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==" src="foo.jpg" /></p>';
$purifier = new Purifier();
$purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
$purifier->purify($content);
check(!preg_match('/\swidget\s*=/i', $content), 'Editor components must not whitelist executable widget attributes');
$content = '<img editor_component="image_link" src="javascript:alert(1)" onerror="alert(2)" custom_component_value="ok" />';
$purifier = new Purifier();
$purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
$purifier->purify($content);
check(stripos($content, 'onerror') === false && stripos($content, 'javascript:') === false, 'Component attributes must retain HTMLPurifier security types');
$content = '<img editor_component="image_link" src="https://trusted.example/image.png" custom_component_value="ok" />';
$purifier = new Purifier();
$purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
$purifier->purify($content);
check(strpos($content, 'editor_component="image_link"') !== false && strpos($content, 'custom_component_value="ok"') !== false, 'Preserve legitimate editor component attributes');
require _XE_PATH_ . 'modules/page/page.class.php';
require _XE_PATH_ . 'modules/page/page.admin.controller.php';
class FixturePageController extends pageAdminController
{
    public static function allowed($path) { return parent::_isAllowedExternalPath($path); }
}
check(!FixturePageController::allowed('files/faceOff/../cache/future.php'), 'Reject traversal to files which do not yet exist');
check(!FixturePageController::allowed('missing.php'), 'Reject nonexistent local external pages');
check(!FixturePageController::allowed('php://filter/resource=index.php'), 'Reject PHP stream wrappers');
check(!FixturePageController::allowed("index.php\0"), 'Reject NUL bytes in paths');
file_put_contents($GLOBALS['fixture_dir'] . 'allowed.php', '<?php echo "fixture";');
FileHandler::makeDir($GLOBALS['fixture_dir'] . 'files/cache');
file_put_contents($GLOBALS['fixture_dir'] . 'files/cache/payload.php', 'fixture');
symlink($GLOBALS['fixture_dir'] . 'files/cache/payload.php', $GLOBALS['fixture_dir'] . 'linked.php');
check(!FixturePageController::allowed($GLOBALS['fixture_dir'] . 'linked.php'), 'Reject symlinks to forbidden directories');
check(FixturePageController::allowed($GLOBALS['fixture_dir'] . 'allowed.php'), 'Allow existing local external page');
check(FixturePageController::allowed(''), 'Allow unset mobile external page');
check(FixturePageController::allowed('https://external.example/page.html'), 'Preserve HTTP external pages');
$cwd = getcwd();
chdir($GLOBALS['fixture_dir']);
check(FixturePageController::allowed('../' . basename($GLOBALS['fixture_dir']) . '/allowed.php'), 'Preserve leading parent path to an existing file');
chdir($cwd);
class DB
{
    public $begins = 0;
    public $commits = 0;
    public $rollbacks = 0;
    public static function getInstance() { static $db; if (!$db) $db = new self(); return $db; }
    public function begin() { $this->begins++; }
    public function commit() { $this->commits++; }
    public function rollback() { $this->rollbacks++; }
}
require _XE_PATH_ . 'modules/module/module.admin.controller.php';
class FixtureLangController extends moduleAdminController
{
    public $cache_updates = 0;
    public function makeCacheDefinedLangCode($site_srl = 0) { $this->cache_updates++; }
}
Context::set('site_module_info', (object)array('site_srl' => 0));
Context::set('lang_code', 'userLang_test');
Context::set('lang_name', '');
Context::set('lang_supported', array('ko' => 'Korean', 'en' => 'English'));
Context::set('ko', '<img src=x onerror=alert(1)>');
Context::set('en', '0');
$GLOBALS['query_log'] = array();
$lang_controller = new FixtureLangController();
$lang_controller->procModuleAdminInsertLang();
$inserted = array();
foreach ($GLOBALS['query_log'] as $query) if ($query[0] === 'module.insertLang') $inserted[$query[1]->lang_code] = $query[1]->value;
check(isset($inserted['ko']) && strpos($inserted['ko'], '<') === false, 'Language values must be escaped');
check(isset($inserted['en']) && $inserted['en'] === '0', 'Keep a translation whose value is zero');
check(DB::getInstance()->commits === 1 && $lang_controller->cache_updates === 1, 'Commit and invalidate cache after successful language save');
Context::set('lang_code', 'bad<name');
$GLOBALS['query_log'] = array();
$out = $lang_controller->procModuleAdminInsertLang();
check(is_object($out) && !$out->toBool() && count($GLOBALS['query_log']) === 0, 'Reject invalid language name before any DB mutation');
Context::set('lang_code', 'valid_name');
Context::set('lang_supported', array('ko<script>' => 'invalid'));
$out = $lang_controller->procModuleAdminInsertLang();
check(is_object($out) && !$out->toBool() && count($GLOBALS['query_log']) === 0, 'Reject unknown language codes before mutation');
Context::set('lang_supported', array('ko' => 'Korean'));
$GLOBALS['query_fail_name'] = 'module.insertLang';
$out = $lang_controller->procModuleAdminInsertLang();
check(is_object($out) && !$out->toBool() && DB::getInstance()->rollbacks === 1, 'Rollback failed translation save');
check($lang_controller->cache_updates === 1, 'Failed translation saves must not publish cache');
unset($GLOBALS['query_fail_name']);
Context::set('lang_code', '');
$lang_controller->procModuleAdminInsertLang();
check((bool)preg_match('/^userLang[0-9]{8}[a-f0-9]{32}$/D', $lang_controller->get('name')), 'Generated language IDs contain 128 bits of randomness');
$_SESSION = array();
$_SERVER = array('REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'example.com');
Context::set('site_module_info', null);
Context::set('default_url', 'http://example.com/');
Context::set('act', 'dispMemberLogout');
check(!Security::checkLogoutRequest(), 'No-header GET must not automatically log out');
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
$_SERVER['HTTP_SEC_FETCH_DEST'] = 'image';
check(!Security::checkLogoutRequest(), 'Cross-site image must not log out');
$_SERVER['HTTP_SEC_FETCH_DEST'] = 'document';
check(!Security::checkLogoutRequest(), 'Cross-site navigation must not log out');
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-site';
check(!Security::checkLogoutRequest(), 'Same-site sibling origins must not log out');
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
check(Security::checkLogoutRequest(), 'Preserve same-origin logout links');
$_SERVER['HTTP_SEC_FETCH_DEST'] = 'image';
check(!Security::checkLogoutRequest(), 'Embedded images must not log out even within the site');
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'none';
$_SERVER['HTTP_SEC_FETCH_DEST'] = 'document';
$_SERVER['HTTP_SEC_FETCH_MODE'] = 'navigate';
check(Security::checkLogoutRequest(), 'Preserve address-bar navigation');
unset($_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['HTTP_SEC_FETCH_DEST'], $_SERVER['HTTP_SEC_FETCH_MODE']);
$_SERVER['HTTP_REFERER'] = 'http://example.com/page';
check(Security::checkLogoutRequest(), 'Legacy internal Referer logout');
$_SERVER['HTTP_REFERER'] = 'http://example.com:8080/page';
check(!Security::checkLogoutRequest(), 'Other ports must not log out');
$_SERVER['HTTP_REFERER'] = 'http://evil.example/';
check(!Security::checkLogoutRequest(), 'Foreign legacy Referer must not log out');
unset($_SERVER['HTTP_REFERER']);
require _XE_PATH_ . 'modules/member/member.class.php';
require _XE_PATH_ . 'modules/member/member.view.php';
class FixtureMemberController
{
    public $logouts = 0;
    public function procMemberLogout() { $this->logouts++; return new BaseObject(); }
}
$GLOBALS['models']['member_controller'] = new FixtureMemberController();
class FixtureMemberView extends memberView { public function __construct() {} }
$member_view = new FixtureMemberView();
$member_view->dispMemberLogout();
check($GLOBALS['models']['member_controller']->logouts === 0 && $member_view->getTemplateFile() === 'logout_confirm.html', 'Headerless logout must render confirmation without destroying session');
$token = $_SESSION['XE_LOGOUT_TOKEN'];
check(is_string($token) && strlen($token) === 64, 'Logout confirmation has a session token');
$_SERVER['REQUEST_METHOD'] = 'POST';
Context::set('logout_token', str_repeat('0', 64));
check(!Security::checkLogoutRequest(), 'Reject incorrect logout token');
Context::set('logout_token', $token);
check(Security::checkLogoutRequest() && Security::checkCSRFRequest(), 'Confirmation works without origin headers');
$member_view->dispMemberLogout();
check($GLOBALS['models']['member_controller']->logouts === 1, 'Confirmed logout executes once');
Context::set('act', 'procExampleSave');
Context::set('logout_token', null);
$_SERVER['HTTP_REFERER'] = 'http://example.com/page';
check(Security::checkCSRFRequest(), 'Preserve ordinary internal POST');
$_SERVER['HTTP_REFERER'] = 'http://example.com:8080/page';
check(!Security::checkCSRFRequest(), 'CSRF checks custom ports');
$_SERVER['HTTP_ORIGIN'] = 'http://evil.example';
$_SERVER['HTTP_REFERER'] = 'http://example.com/page';
check(!Security::checkCSRFRequest(), 'Untrusted Origin takes precedence over Referer');
unset($_SERVER['HTTP_ORIGIN']);
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
$_SERVER['HTTP_REFERER'] = 'http://evil.example/';
check(!Security::checkCSRFRequest(), 'Reject cross-site POST');
check(!Security::isInternalURL("/invalid-\xff"), 'Reject invalid UTF-8 URLs');
$trusted_redirect = new ModuleObject();
$trusted_redirect->setRedirectUrl('https://sso.example/after-login', null, true);
check($trusted_redirect->get('redirect_url') === 'https://sso.example/after-login', 'Allow explicitly trusted external configuration');
$trusted_redirect->setRedirectUrl('javascript:alert(1)', null, true);
check($trusted_redirect->get('redirect_url') === 'http://example.com/xe/', 'Trusted redirects still reject non-HTTP schemes');
$trusted_redirect->setRedirectUrl('https://sso.example/after-login', null, true);
$trusted_html = $handler->toDoc($trusted_redirect);
check(strpos($trusted_html, 'sso.example') !== false, 'VirtualXML preserves explicitly trusted external configuration');
class LegacyRedirectOverride extends ModuleObject
{
    public function setRedirectUrl($url = './', $output = null) { return parent::setRedirectUrl($url, $output); }
}
check(new LegacyRedirectOverride() instanceof ModuleObject, 'Legacy redirect overrides remain compatible');
class CacheHandler
{
    public static $data = array();
    public static function getInstance($type, $extra = null, $force = true) { return new self(); }
    public function isSupport() { return true; }
    public function getGroupKey($group, $key) { return $group . ':' . $key; }
    public function put($key, $value) { self::$data[$key] = $value; }
    public function get($key) { return isset(self::$data[$key]) ? self::$data[$key] : false; }
}
Context::set('lang_supported', array('ko' => 'Korean', 'en' => 'English'));
Context::set('site_module_info', (object)array('site_srl' => 0));
$actual_lang_controller = new moduleAdminController();
$GLOBALS['query_data']['module.getLang'] = array(
    (object)array('lang_code' => 'ko', 'name' => 'old_raw', 'value' => '<script>alert(1)</script>'),
    (object)array('lang_code' => 'ko', 'name' => 'new_escaped', 'value' => '&lt;script&gt;alert(1)&lt;/script&gt;'),
    (object)array('lang_code' => 'ko', 'name' => 'literal', 'value' => '&amp;lt;script&amp;gt;'),
    (object)array('lang_code' => 'ko', 'name' => 'bad<name', 'value' => 'unsafe key')
);
$map = $actual_lang_controller->makeCacheDefinedLangCode();
check($map['old_raw'] === '&lt;script&gt;alert(1)&lt;/script&gt;', 'Escape previously stored raw translations');
check($map['new_escaped'] === '&lt;script&gt;alert(1)&lt;/script&gt;', 'Do not double-escape new translations');
check($map['literal'] === '&amp;lt;script&amp;gt;', 'Preserve literal entity text');
check(!isset($map['bad<name']), 'Exclude legacy invalid language identifiers');
CacheHandler::$data['site_and_module:user_defined_langs:0:ko'] = array('old_raw' => '<script>cached</script>');
require _XE_PATH_ . 'modules/module/module.controller.php';
$GLOBALS['models']['module_controller'] = $actual_lang_controller;
$module_controller = new moduleController();
$text = '$user_lang->old_raw';
$module_controller->replaceDefinedLangCode($text);
check($text === '&lt;script&gt;alert(1)&lt;/script&gt;', 'Ignore legacy unsafe cache namespace');
$GLOBALS['query_data']['module.getLang'] = array();
$actual_lang_controller->makeCacheDefinedLangCode();
check(CacheHandler::$data['site_and_module:user_defined_langs:v2:0:ko'] === array(), 'Deleting final translation clears language cache');
Context::set('_http_port', 8080);
check(Security::isInternalURL('http://example.com:8080/'), 'Honor configured HTTP port when default URL omits it');
check(!Security::isInternalURL('http://example.com/'), 'Configured nondefault port must not trust default port');
Context::set('_http_port', null);
Context::set('default_url', 'http://[::1]:8080/');
check(Security::isInternalURL('http://[::1]:8080/index.php'), 'IPv6 custom port');
check(!Security::isInternalURL('http://[::1]/index.php'), 'IPv6 port mismatch');
Context::set('default_url', 'http://example.com/');
foreach (array('planet', 'board') as $module_name) {
    $module_info->module = $module_name;
    $GLOBALS['query_error'] = true;
    $blocked = false;
    try { $grant_model->getGrant($module_info, (object)array('member_srl' => 10, 'is_admin' => 'N'), $xml_info); }
    catch (Exception $e) { $blocked = $e->getMessage() === 'msg_db_query_failed'; }
    check($blocked, 'Admin lookup DB errors also fail closed: ' . $module_name);
    $blocked = false;
    try { $grant_model->getGrant($module_info, $guest, $xml_info); }
    catch (Exception $e) { $blocked = $e->getMessage() === 'msg_db_query_failed'; }
    check($blocked, 'Guest grant DB errors fail closed: ' . $module_name);
}
$GLOBALS['query_error'] = false;
foreach (array('<img class="zbxe_widget_output" widget="widgetContent" body="test" src="foo.jpg">', '<img editor_component="x" WIDGET="widgetContent" src="foo.jpg">', "<img editor_component='x' widget=widgetContent src='foo.jpg'>") as $content) {
    $purifier = new Purifier();
    $purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
    $purifier->purify($content);
    check(!preg_match('/\swidget\s*=/i', $content), 'Block executable widgets with alternate attribute syntax');
}
check(!communication::isMessageOwner((object)array('message_type' => 'T', 'receiver_srl' => '92233720368547758080'), '92233720368547758081'), 'Large IDs must not alias after integer overflow');
check(communication::isMessageOwner((object)array('message_type' => 'T', 'receiver_srl' => '92233720368547758080'), '92233720368547758080'), 'Large matching IDs remain valid');
check(!communication::isMessageOwner((object)array('message_type' => 'T', 'receiver_srl' => '10invalid'), 10), 'Malformed owner IDs must not match');
Context::set('default_url', 'http://example.com:8080/');
Context::set('_https_port', 8443);
$_SERVER = array('REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'example.com:8443', 'HTTPS' => 'on', 'HTTP_ORIGIN' => 'http://example.com:8080', 'HTTP_SEC_FETCH_SITE' => 'cross-site');
check(Security::checkCSRFRequest(), 'Preserve configured optional-SSL HTTP to HTTPS forms');
check(Security::isInternalURL('//example.com:8443/path'), 'Protocol-relative URLs use actual HTTPS scheme');
check(!Security::isInternalURL('//example.com/path'), 'HTTPS protocol-relative URLs must retain custom port');
require _XE_PATH_ . 'modules/module/module.admin.model.php';
$admin_model = new moduleAdminModel();
Context::set('name', 'edit_test');
Context::set('lang_name', 'edit_test');
$GLOBALS['query_data']['module.getLang'] = array((object)array('lang_code' => 'ko', 'name' => 'edit_test', 'value' => 'A&amp;B &lt;tag&gt; &amp;lt;literal&amp;gt;'), (object)array('lang_code' => 'en', 'name' => 'edit_test', 'value' => '0'));
$admin_model->getModuleAdminLangCode();
$editable = $admin_model->get('langs');
check($editable['ko'] === 'A&B <tag> &lt;literal&gt;', 'Editing API decodes storage encoding exactly once');
check($editable['en'] === '0', 'Editing API preserves zero translations');
$GLOBALS['query_data']['module.getLangListByName'] = $GLOBALS['query_data']['module.getLang'];
$admin_model->getModuleAdminLangListByName();
$editable_list = $admin_model->get('lang_list');
check($editable_list[0]->value === $editable['ko'], 'Legacy editing API returns plain text');
$GLOBALS['query_data']['module.getLangListByLangcode'] = array((object)array('value' => '<img src=x onerror=alert(1)>'), (object)array('value' => '&lt;img src=x onerror=alert(1)&gt;'));
$display_list = $admin_model->getLangListByLangcode(new stdClass());
check($display_list->data[0]->value === '&lt;img src=x onerror=alert(1)&gt;', 'Search list escapes legacy raw HTML');
check($display_list->data[1]->value === '&lt;img src=x onerror=alert(1)&gt;', 'Search list does not double-escape stored HTML');
// Passive PHP2Shell checks only: fixture versions and INI values, no exploit probes.
$progress_on = array('global_value' => '1', 'local_value' => '1');
$progress_off = array('global_value' => '0', 'local_value' => '0');
foreach(array('5.3.29', '5.4.45', '5.6.40', '7.0.33', '7.1.33', '7.2.34', '7.3.33', '7.4.33', '8.0.30', '8.1.34', '8.1.34-8.vendor', '8.2.34', '8.3.35', '8.4.26', '8.5.11', '8.6.0RC3', '8.6.0-dev', '8.7.0', 'unknown') as $version) {
    $status = Security::getPhp2ShellStatus($version, $progress_on);
    check(!$status['patched'] && $status['level'] === 'danger', 'Unverified PHP with upload progress enabled: ' . $version);
    $status = Security::getPhp2ShellStatus($version, $progress_off);
    $legacy = preg_match('/\A[0-9]+\.[0-9]+/', $version, $branch) && version_compare($branch[0], '8.2', '<');
    check($status['dismissible'] === (bool)$legacy, 'Only PHP <= 8.1 notices are dismissible: ' . $version);
    check(!$status['patched'] && $status['level'] === ($legacy ? 'warning' : 'danger'), 'Supported branches require updates even with upload progress disabled: ' . $version);
}
foreach(array('8.2.35', '8.2.36', '8.3.36', '8.3.37', '8.4.27', '8.4.28', '8.5.12', '8.5.13', '8.4.27-1+deb12u1', '8.6.0RC4', '8.6.0RC5', '8.6.0', '8.6.1') as $version) {
    foreach(array($progress_on, $progress_off, false) as $settings) {
        $status = Security::getPhp2ShellStatus($version, $settings);
        check($status['patched'] && $status['level'] === '', 'Known fixed PHP2Shell release: ' . $version);
    }
}
foreach(array('8.2.35RC1', '8.3.36beta1', '8.4.27-dev', '8.5.12alpha1') as $version) {
    $status = Security::getPhp2ShellStatus($version, $progress_on);
    check(!$status['patched'], 'Prerelease must not inherit the stable release fix: ' . $version);
}
foreach(array(false, array(), array('local_value' => '0'), array('global_value' => '0'), array('global_value' => '1', 'local_value' => '0'), array('global_value' => '0', 'local_value' => '1'), array('global_value' => false, 'local_value' => false), array('global_value' => 'unexpected', 'local_value' => '0')) as $settings) {
    $status = Security::getPhp2ShellStatus('7.4.33', $settings);
    check(!$status['upload_progress_disabled'] && $status['level'] === 'danger', 'Missing, ambiguous or late INI override must not suppress the danger warning');
}
foreach(array('', '0', 'Off', 'FALSE', 'no', 'None') as $value) {
    $status = Security::getPhp2ShellStatus('7.4.33', array('global_value' => $value, 'local_value' => $value));
    check($status['upload_progress_disabled'] && $status['level'] === 'warning', 'Recognize explicit INI off value: ' . $value);
}
$session_settings = function_exists('ini_get_all') && extension_loaded('session') ? ini_get_all('session') : false;
$progress_settings = isset($session_settings['session.upload_progress.enabled']) ? $session_settings['session.upload_progress.enabled'] : false;
check(Security::getPhp2ShellStatus() === Security::getPhp2ShellStatus(PHP_VERSION, $progress_settings), 'Default assessment reads this PHP runtime and its session INI settings');
echo 'OK: ', $GLOBALS['checks'], ' security checks on PHP ', PHP_VERSION, "\n";
