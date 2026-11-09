<?php
// Included by regressions.php; all fixtures and syntax support PHP 5.3.
class AdvisoryDocument extends FixtureDocument
{
    public $exists = true;
    public $granted = false;
    public $secret = false;
    public $accessible = false;
    public function isExists() { return $this->exists; }
    public function isGranted() { return $this->granted; }
    public function isSecret() { return $this->secret; }
    public function isAccessible() { return $this->accessible; }
}
class AdvisoryModuleModel
{
    public $grant;
    public function __construct() { $this->grant = (object)array('access' => true, 'view' => true, 'manager' => false); }
    public function getModuleInfoByModuleSrl($srl) { return (object)array('module_srl' => $srl, 'module' => 'board'); }
    public function getGrant($module, $member) { return $this->grant; }
}
class AdvisoryWidgetController extends widgetController
{
    public $executed = false;
    public function arrangeWidgetVars($widget, $request, &$vars) { $vars = $request; return array(); }
    public function execute($widget, $args, $javascript_mode = false, $escaped = true) { $this->executed = true; return 'preview'; }
}
class AdvisoryModuleController
{
    public function replaceDefinedLangCode(&$content) {}
}
$advisory_module = new AdvisoryModuleModel();
$GLOBALS['models']['module'] = $advisory_module;
$GLOBALS['models']['module_controller'] = new AdvisoryModuleController();
$GLOBALS['models']['document'] = new FixtureDocumentModel();
$GLOBALS['document'] = new AdvisoryDocument();
Context::$values = array('selected_widget' => 'widgetContent', 'document_srl' => 200);
foreach (array('access', 'view') as $permission) {
    $advisory_module->grant->$permission = false;
    $preview = new AdvisoryWidgetController();
    $result = $preview->procWidgetGenerateCodeInPage();
    check($result instanceof BaseObject && !$result->toBool() && !$preview->executed, 'Widget preview checks the source module ' . $permission . ' grant');
    $advisory_module->grant->$permission = true;
}
$GLOBALS['document']->secret = true;
$preview = new AdvisoryWidgetController();
$result = $preview->procWidgetGenerateCodeInPage();
check($result instanceof BaseObject && !$result->toBool() && !$preview->executed, 'Widget preview cannot expose a secret document');
$GLOBALS['document']->accessible = true;
$preview = new AdvisoryWidgetController();
$preview->procWidgetGenerateCodeInPage();
check($preview->executed, 'Widget preview allows a password-authorized secret document');
$GLOBALS['document']->exists = false;
$preview = new AdvisoryWidgetController();
$result = $preview->procWidgetGenerateCodeInPage();
check($result instanceof BaseObject && !$result->toBool() && !$preview->executed, 'Widget preview rejects nonexistent documents');
$GLOBALS['document']->exists = true;
$GLOBALS['document']->secret = false;
$preview = new AdvisoryWidgetController();
$preview->procWidgetGenerateCodeInPage();
check($preview->executed, 'Widget preview preserves readable public documents');

require_once _XE_PATH_ . 'classes/security/UploadFileFilter.class.php';
foreach (array(
    'example.php.jpg' => 'example.jpg',
    'example.PHP.GIF' => 'example.GIF',
    'example.php.aspx.exe.jpg' => 'example.jpg',
    'example .php. webp' => 'example .webp',
    'example.  PHP  .  ASPX .gif' => 'example.gif',
    'example.php.foo.php8.jpg' => 'example.foo.jpg',
    'example.phtml.phar.pht.cgi.pl.jsp.inc.png' => 'example.png',
    'example.php' => 'example.php-x',
    'example.php.jpg. ' => 'example.jpg',
    '../example.php.jpg' => 'example.jpg',
    'C:\\fakepath\\example.PHP.GIF' => 'example.GIF',
    '.htaccess' => 'htaccess',
    'example.jpg' => 'example.jpg',
    'my.photo.2026.PNG' => 'my.photo.2026.PNG',
    '한글 이미지.jpg' => '한글 이미지.jpg'
) as $input => $expected) {
    $filename = UploadFileFilter::sanitizeFilename($input);
    check($filename === $expected, 'Sanitize all filename extensions: ' . $input);
    check(UploadFileFilter::sanitizeFilename($filename) === $filename, 'Filename sanitization is stable: ' . $input);
}

require_once _XE_PATH_ . 'modules/editor/editor.class.php';
require_once _XE_PATH_ . 'modules/editor/editor.controller.php';
require_once _XE_PATH_ . 'modules/editor/editor.model.php';
class AdvisoryComponent
{
    public function transHTML($xml) { return '<p>' . $xml->attrs->custom_value . '</p>'; }
}
class AdvisoryComponentModel
{
    public $component;
    public function &getComponentObject($name, $sequence = 0) { return $this->component; }
}
$component_model = new AdvisoryComponentModel();
$GLOBALS['models']['editor'] = $component_model;
$editor_controller = new editorController();
foreach (array('x-init', 'ng-init', 'v-effect', 'hx-on', 'wire-init', 'X-INIT') as $directive) {
    $content = '<div editor_component="missing" ' . $directive . '="alert(1)" custom_value="safe" data-file-srl="123">TEST</div>';
    $purifier = new Purifier();
    $purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
    $purifier->purify($content);
    check(stripos($content, $directive . '=') === false, 'Component HTML cannot whitelist frontend directive ' . $directive);
    check(strpos($content, 'custom_value="safe"') !== false && strpos($content, 'data-file-srl="123"') !== false, 'Preserve component values and data attributes');
}
foreach (array(null, new BaseObject(-1, 'missing'), new stdClass()) as $component) {
    $component_model->component = $component;
    $content = '<div editor_component="missing" x-init="alert(1)">TEST</div><p>Keep</p>';
    check($editor_controller->transComponent($content) === '<p>Keep</p>', 'Unknown or unrenderable components cannot survive in rendered HTML');
}
$component_model->component = new AdvisoryComponent();
check($editor_controller->transComponent('<div editor_component="known" custom_value="safe">TEST</div>') === '<p>safe</p>', 'Known editor components still render');
