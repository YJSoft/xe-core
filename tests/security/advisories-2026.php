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

// Autosave authorization, identity, and file restoration (RVE-2026-33).
function escape($value, $double = true) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $double); }
function removeHackTag($content)
{
    $purifier = new Purifier();
    $purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
    $purifier->purify($content);
    return $content;
}
function setUserSequence($srl) { $_SESSION['seq'][] = $srl; $GLOBALS['advisory_sequences'][] = $srl; }
class ModuleHandler
{
    public static $triggers = array();
    public static function triggerCall($name, $position, &$args) { self::$triggers[] = array($name, clone $args); return new BaseObject(); }
}
class AdvisoryDocumentModel
{
    public $documents = array();
    public function getDocument($srl)
    {
        if(isset($this->documents[$srl])) return $this->documents[$srl];
        $document = new AdvisoryDocument();
        $document->exists = false;
        return $document;
    }
}
require_once _XE_PATH_ . 'modules/file/file.class.php';
require_once _XE_PATH_ . 'modules/file/file.controller.php';
require_once _XE_PATH_ . 'modules/file/file.model.php';
class AdvisoryFileController extends fileController
{
    public $moves = array();
    public $deleted = array();
    public function moveFile($source, $module, $target) { $this->moves[] = array($source, $module, $target); }
    public function deleteFile($srl) { $this->deleted[] = $srl; return new BaseObject(); }
}
$advisory_documents = new AdvisoryDocumentModel();
$advisory_files = new AdvisoryFileController();
$advisory_editor = new editorModel();
$GLOBALS['models']['document'] = $advisory_documents;
$GLOBALS['models']['file_controller'] = $advisory_files;
$GLOBALS['models']['editor'] = $advisory_editor;
$GLOBALS['models']['editor_controller'] = $editor_controller;
$advisory_module->grant = (object)array('access' => true, 'write_document' => true, 'manager' => false);
Context::$values = array('module_srl' => 999, 'editor_sequence' => 7, 'primary_key' => 'document_srl');
$row_input = (object)array('module_srl' => 999, 'member_srl' => 99, 'document_srl' => 0, 'title' => '<img src=x>', 'content' => '<p onclick="alert(1)">Safe</p><script>alert(2)</script>');
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
check(!$result->toBool() && !count($GLOBALS['query_log']), 'Autosave requires a routed module before any write');
Context::set('current_module_info', (object)array('module_srl' => 100, 'module' => 'board', 'mid' => 'allowed'));
Context::set('logged_info', (object)array('member_srl' => 41, 'is_admin' => 'N'));
foreach (array('access', 'write_document') as $permission) {
    $advisory_module->grant->$permission = false;
    $GLOBALS['query_log'] = array();
    $result = $editor_controller->doSaveDoc(clone $row_input);
    check(!$result->toBool() && !count($GLOBALS['query_log']), 'Denied autosave never deletes or inserts rows: ' . $permission);
    check($advisory_editor->getSavedDoc(null) === null && !count($GLOBALS['query_log']), 'Revoked permissions block reads too: ' . $permission);
    $advisory_module->grant->$permission = true;
}
foreach (array('member', 'page') as $module_name) {
    Context::get('current_module_info')->module = $module_name;
    $result = $editor_controller->doSaveDoc(clone $row_input);
    check(!$result->toBool(), 'Disable autosave for member fields and unmanaged pages: ' . $module_name);
}
Context::get('current_module_info')->module = 'board';
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
check($result->toBool(), 'An authorized member can autosave without file uploads');
$stored_row = $GLOBALS['query_log'][1][1];
check($stored_row->module_srl === 100 && $stored_row->member_srl === 41, 'Routed module and authenticated author override all submitted identities');
check($stored_row->document_srl === 0, 'Text-only autosave does not acquire an arbitrary upload target');
check(strpos($stored_row->title, '<') === false && strpos($stored_row->content, '<script') === false && strpos($stored_row->content, 'onclick') === false, 'Autosaved title and content pass HTML filters');
$GLOBALS['query_fail_name'] = 'editor.deleteSavedDoc';
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
check(!$result->toBool() && count($GLOBALS['query_log']) === 1, 'Database failure is returned without inserting a duplicate autosave');
unset($GLOBALS['query_fail_name']);
Context::set('title', $row_input->title);
Context::set('content', $row_input->content);
$advisory_module->grant->write_document = false;
$result = $editor_controller->procEditorSaveDoc();
check($result instanceof BaseObject && !$result->toBool(), 'Autosave action propagates permission errors');
$advisory_module->grant->write_document = true;

// A guest cannot select another guest or member merely by sharing an IP.
Context::set('logged_info', null);
$_COOKIE = array();
$_SESSION['upload_info'] = array();
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
$guest_row = $GLOBALS['query_log'][1][1];
check($result->toBool() && $guest_row->member_srl === 0 && strlen($guest_row->certify_key) === 40, 'Guest autosave creates a secret ownership cookie and stores member_srl zero');
$guest_row->document_srl = 0;
$GLOBALS['query_data']['editor.getSavedDocument'] = array($guest_row);
$GLOBALS['query_log'] = array();
check($advisory_editor->getSavedDoc(null) !== null, 'A guest can restore an autosave with the matching secret');
$identity = $GLOBALS['query_log'][0][1];
check($identity->member_srl === 0 && $identity->certify_key === $guest_row->certify_key && !isset($identity->ipaddress), 'Guest lookup uses the cookie and never an IP fallback');
$_COOKIE = array();
$GLOBALS['query_log'] = array();
check($advisory_editor->getSavedDoc(null) === null && !count($GLOBALS['query_log']), 'Missing guest cookies never expose legacy shared-IP rows');
$editor_controller->deleteSavedDoc(true);
check(!count($GLOBALS['query_log']), 'Missing guest cookies cannot delete shared-IP autosaves');
$_COOKIE['autosave_certify_key_100'] = str_repeat('b', 40);
check($advisory_editor->getSavedDoc(null) === null, 'Mismatching guest secrets fail closed even if a row is returned');
$_COOKIE['autosave_certify_key_100'] = $guest_row->certify_key;
$guest_row->member_srl = 41;
check($advisory_editor->getSavedDoc(null) === null, 'Guest restoration rejects member-owned rows');
$guest_row->member_srl = 0;

// Legacy rows are filtered on read, and loading text never grants new upload rights.
$guest_row->title = '<script>legacy</script>';
$guest_row->content = '<img src="javascript:alert(1)" onerror="alert(2)"><p>Safe</p>';
$reader = new editorController();
$reader->procEditorLoadSavedDocument();
check($reader->get('content') !== null && strpos($reader->get('content'), 'onerror') === false && strpos($reader->get('title'), '<script') === false, 'Legacy autosaves are filtered before returning to an editor');
check(!isset($_SESSION['upload_info'][7]), 'Restoring text cannot mint an upload session');
$advisory_files->setUploadInfo(7, 0, 100);
check($_SESSION['upload_info'][7]->module_srl === 100, 'Upload sessions retain their authorized module');
$reader = new editorController();
$reader->procEditorLoadSavedDocument();
check($_SESSION['upload_info'][7]->enabled && $_SESSION['upload_info'][7]->module_srl === 100, 'An existing upload editor retains its module on restore');
$_SESSION['upload_info'][7]->module_srl = 999;
$reader->procEditorLoadSavedDocument();
check($_SESSION['upload_info'][7]->module_srl === 999, 'Restoring another module cannot overwrite an issued upload session');

// Published documents, foreign drafts, and valid attachments are never restored or deleted.
$published = new AdvisoryDocument();
$advisory_documents->documents[200] = $published;
$guest_row->document_srl = 200;
$GLOBALS['advisory_sequences'] = array();
check($advisory_editor->getSavedDoc(null) === null && !count($GLOBALS['advisory_sequences']), 'Poisoned autosaves cannot grant an existing document sequence');
ModuleHandler::$triggers = array();
$editor_controller->deleteSavedDoc(true);
check(!count(ModuleHandler::$triggers), 'Deleting a poisoned autosave cannot delete published document attachments');
$guest_row->document_srl = 999999;
$GLOBALS['advisory_sequences'] = array();
$restored = $advisory_editor->getSavedDoc(null);
check($restored !== null && $restored->document_srl === 0 && !count($GLOBALS['advisory_sequences']), 'Legacy text-only rows cannot reserve arbitrary unused document IDs');
$guest_row->document_srl = 300;
$foreign_file = (object)array('file_srl' => 500, 'module_srl' => 100, 'member_srl' => 0, 'isvalid' => 'N');
$GLOBALS['query_data']['file.getFiles'] = array($foreign_file);
$restored = $advisory_editor->getSavedDoc(null);
check($restored && !$restored->document_srl && !isset($_SESSION['__XE_UPLOADING_FILES_INFO__'][500]), 'A legacy guest row still yields text without granting unverified attachments');
ModuleHandler::$triggers = array();
$editor_controller->deleteSavedDoc(true);
check(!count(ModuleHandler::$triggers), 'A guest cannot delete another guest pending attachments through autosave removal');
Context::set('logged_info', (object)array('member_srl' => 41, 'is_admin' => 'N'));
$guest_row->member_srl = 41;
$foreign_file->member_srl = 99;
check($advisory_editor->getSavedDoc(null) === null, 'A member cannot restore another member pending attachments');
$foreign_file->member_srl = 41;
$foreign_file->isvalid = 'Y';
check($advisory_editor->getSavedDoc(null) === null, 'Published comment or document attachments cannot be restored as drafts');
$foreign_file->isvalid = 'N';
$foreign_file->module_srl = 999;
check($advisory_editor->getSavedDoc(null) === null, 'Pending attachments cannot cross module boundaries');
$foreign_file->module_srl = 100;
check($advisory_editor->getSavedDoc(null) !== null, 'An owner can restore pending attachments in the same module');
$GLOBALS['query_error'] = true;
check($advisory_editor->getSavedDoc(null) === null, 'Failed autosave queries do not return a row');
$GLOBALS['query_error'] = false;
$GLOBALS['query_fail_name'] = 'file.getFiles';
check($advisory_editor->getSavedDoc(null) === null, 'Failed attachment ownership queries fail closed');
unset($GLOBALS['query_fail_name']);
$guest_row->module_srl = 999;
check($advisory_editor->getSavedDoc(null) === null, 'A returned autosave row must match the routed module');
$guest_row->module_srl = 100;

// Autosave saves only the target belonging to the issued editor sequence.
$_SESSION['upload_info'] = array();
$GLOBALS['query_data']['file.getFiles'] = array();
$advisory_files->setUploadInfo(7, 300, 100);
$_SESSION['seq'] = array();
foreach (array(0, 300) as $unissued_target) {
    $row_input->document_srl = $unissued_target;
    $GLOBALS['query_log'] = array();
    $result = $editor_controller->doSaveDoc(clone $row_input);
    check(!$result->toBool() && !count($GLOBALS['query_log']), 'An upload slot alone cannot authorize an autosave target');
}
$restored = $advisory_editor->getSavedDoc(null);
check($restored && !$restored->document_srl && !checkUserSequence(300), 'Restoring text cannot issue a sequence from an upload slot alone');
Context::set('document_srl', 200);
$restored = $advisory_editor->getSavedDoc(301);
check($restored && !$restored->document_srl && !checkUserSequence(300), 'An unused destination cannot authorize a legacy text-only source');
Context::set('document_srl', null);
setUserSequence(300);
setUserSequence(301);
$row_input->document_srl = 301;
$result = $editor_controller->doSaveDoc(clone $row_input);
check(!$result->toBool(), 'Autosave rejects document_srl differing from its issued upload target');
$row_input->document_srl = 300;
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
$stored_row = end($GLOBALS['query_log']);
check($result->toBool() && $stored_row[1]->document_srl === 300, 'Autosave preserves the authorized pending upload target');
Context::set('editor_sequence', null);
$GLOBALS['query_log'] = array();
$result = $editor_controller->doSaveDoc(clone $row_input);
$stored_row = end($GLOBALS['query_log']);
check($result->toBool() && $stored_row[1]->document_srl === 300, 'Older editor clients can autosave an already issued target without editor_sequence');
Context::set('editor_sequence', 7);
$row_input->document_srl = 200;
$result = $editor_controller->doSaveDoc(clone $row_input);
check(!$result->toBool(), 'Autosave rejects existing document targets');
$row_input->document_srl = 0;
$GLOBALS['query_data']['file.getFiles'] = array($foreign_file);
$advisory_files->moves = array();
check($advisory_editor->getSavedDoc(302) === null && !$advisory_files->moves, 'Restoration requires an issued destination before moving files');
$restored = $advisory_editor->getSavedDoc(301);
check($restored !== null && $restored->document_srl === 301 && $advisory_files->moves === array(array(300, 100, 301)), 'Authorized attachment moves update the returned autosave target');
$update = end($GLOBALS['query_log']);
check($update[0] === 'editor.updateSavedDocTarget' && $update[1]->module_srl === 100 && $update[1]->member_srl === 41 && $update[1]->document_srl === 301, 'Moved attachments persist their new target with author and module conditions');

// Closing the browser loses its PHP session. Restore into a newly opened editor,
// then add a pending file and save again under the same member or guest identity.
foreach (array(0, 41) as $author) {
    $_SESSION = array();
    $_COOKIE = array();
    Context::set('logged_info', $author ? (object)array('member_srl' => $author, 'is_admin' => 'N') : null);
    Context::set('editor_sequence', 7);
    $row_input->document_srl = 300;
    $row_input->title = 'first draft';
    $row_input->content = '<p>first draft body</p>';
    setUserSequence(300);
    $advisory_files->setUploadInfo(7, 300, 100);
    $pending = (object)array('file_srl' => 501, 'member_srl' => $author, 'module_srl' => 100, 'isvalid' => 'N');
    $_SESSION['__XE_UPLOADING_FILES_INFO__'][501] = true;
    $GLOBALS['query_data']['file.getFiles'] = array($pending);
    $GLOBALS['query_log'] = array();
    $result = $editor_controller->doSaveDoc($row_input);
    $write = end($GLOBALS['query_log']);
    check($result->toBool() && $write[0] === 'editor.insertSavedDoc', 'Persist the first draft before closing the browser');
    $GLOBALS['query_data']['editor.getSavedDocument'] = array($write[1]);
    if (!$author) {
        foreach ($GLOBALS['query_log'] as $query) {
            if ($query[0] === 'file.updateGuestUploadKey') $pending->guest_upload_key = $query[1]->guest_upload_key;
        }
        check(strlen($pending->guest_upload_key) === 64 && $pending->guest_upload_key !== $write[1]->certify_key, 'Backfill a verifiable guest file without storing the browser credential on it');
    }

    $_SESSION = array();
    $advisory_files->setUploadInfo(19, 0, 100);
    Context::set('editor_sequence', 19);
    $reader = new editorController();
    $reader->procEditorLoadSavedDocument();
    check($reader->get('title') === $row_input->title && $reader->get('content') === $row_input->content, 'Restore the draft text in a fresh PHP session');
    check($reader->get('document_srl') == 300 && checkUserSequence(300) && $_SESSION['upload_info'][19]->upload_target_srl === 300, 'Bind the restored draft to the new upload editor');
    if (!$author) check(!empty($_SESSION['__XE_UPLOADING_FILES_INFO__'][501]), 'Restore the verified guest file grant');

    $added = clone $pending;
    $added->file_srl = 502;
    if (!$author) $added->guest_upload_key = $advisory_editor->getGuestUploadKey(100, 502, true);
    $GLOBALS['query_data']['file.getFiles'][] = $added;
    $_SESSION['__XE_UPLOADING_FILES_INFO__'][502] = true;
    $row_input->title = 'edited draft';
    $result = $editor_controller->doSaveDoc($row_input);
    $write = end($GLOBALS['query_log']);
    check($result->toBool() && $write[1]->title === 'edited draft' && $write[1]->document_srl === 300, 'Save again after adding a file to the restored draft');
    $GLOBALS['query_data']['editor.getSavedDocument'] = array($write[1]);
    $_SESSION = array();
    $restored = $advisory_editor->getSavedDoc(null);
    check($restored && $restored->title === 'edited draft' && checkUserSequence(300), 'Reopen the edited draft a second time');
    if (!$author) {
        check(!empty($_SESSION['__XE_UPLOADING_FILES_INFO__'][501]) && !empty($_SESSION['__XE_UPLOADING_FILES_INFO__'][502]), 'Both guest uploads retain their owners on a second restart');
        $_SESSION = array();
        $added->guest_upload_key = $pending->guest_upload_key;
        $restored = $advisory_editor->getSavedDoc(null);
        check($restored && !$restored->document_srl && !checkUserSequence(300) && empty($_SESSION['__XE_UPLOADING_FILES_INFO__']), 'A file key belongs to a single file and a partial match cannot grant the target');
    }
}

Context::set('editor_sequence', 7);

// The file model must never equate guest ID zero with ownership.
$GLOBALS['query_data']['file.getFiles'] = array();
$advisory_module->grant->manager = false;
$file_model = new fileModel();
$grant_file = (object)array('file_srl' => 600, 'module_srl' => 100, 'upload_target_srl' => 400, 'member_srl' => 0);
$guest = (object)array('member_srl' => 0, 'is_admin' => 'N');
check(!$file_model->getFileGrant($grant_file, $guest)->is_deletable, 'Guest ID zero cannot authorize deleting another guest file');
$_SESSION['__XE_UPLOADING_FILES_INFO__'][600] = true;
check($file_model->getFileGrant($grant_file, $guest)->is_deletable, 'A guest retains deletion rights for its own session upload');
unset($_SESSION['__XE_UPLOADING_FILES_INFO__'][600]);
$grant_file->member_srl = 41;
check($file_model->getFileGrant($grant_file, Context::get('logged_info'))->is_deletable, 'A member retains deletion rights for its own uploads');
$grant_file->member_srl = 99;
check(!$file_model->getFileGrant($grant_file, Context::get('logged_info'))->is_deletable, 'A member cannot delete another member upload');
$advisory_module->grant->manager = true;
check($file_model->getFileGrant($grant_file, Context::get('logged_info'))->is_deletable, 'XE module managers retain their existing document moderation permissions');
$advisory_module->grant->manager = false;

// Iframe uploads no longer delete a requested file before checking the upload.
Context::set('file_srl', 600);
Context::set('Filedata', array('tmp_name' => ''));
$advisory_files->module_srl = 100;
$advisory_files->setUploadInfo(7, 400, 100);
$advisory_files->procFileIframeUpload();
check(!count($advisory_files->deleted), 'An empty iframe upload cannot be used as a file deletion endpoint');

// RVE-2026-29 is already blocked by XE's strict URL parser.
Context::set('default_url', 'http://example.com/');
Context::set('request_url', 'http://example.com/');
Context::set('site_module_info', null);
foreach (array(0, 8, 9, 10, 13, 32, 127) as $char) {
    foreach (array('jav' . chr($char) . 'ascript:alert(1)', 'jav%' . sprintf('%02X', $char) . 'ascript:alert(1)') as $url) {
        check(!Security::isInternalURL($url), 'Obfuscated JavaScript schemes fail URL validation');
        check(Security::getSafeRedirectURL($url, '/index.php') === '/index.php', 'Obfuscated login and signup redirects use a safe fallback');
    }
}

// Only declared skin fields and module-owned images participate in a settings update.
class AdvisorySkinModel
{
    public $stored = array('P' => array(), 'M' => array());
    public function getModuleInfoByModuleSrl($id, $columns) {
        return (object)array('module_srl' => $id, 'module' => 'board', 'site_srl' => 3, 'is_skin_fix' => 'N', 'is_mskin_fix' => 'N');
    }
    public function getModuleDefaultSkin($module, $mode, $site) { return $mode . $site; }
    public function loadSkinInfo($path, $skin, $dir = 'skins') {
        if ($skin !== ($dir === 'skins' ? 'P3' : 'M3')) return null;
        return (object)array('colorset' => array((object)array('name' => 'blue')), 'extra_vars' => array(
            (object)array('name' => 'label', 'type' => 'text'), (object)array('name' => 'options', 'type' => 'checkbox'),
            (object)array('name' => 'logo', 'type' => 'image')));
    }
    public function getModuleSkinVars($id) { return $this->stored['P']; }
    public function getModuleMobileSkinVars($id) { return $this->stored['M']; }
}
class AdvisorySkinWriter
{
    public $saved;
    public $fail = false;
    public function insertModuleSkinVars($id, $obj) { $this->saved = $obj; return new BaseObject($this->fail ? -1 : 0); }
    public function insertModuleMobileSkinVars($id, $obj) { return $this->insertModuleSkinVars($id, $obj); }
}
class AdvisorySkinAdminModel extends moduleAdminModel
{
    public function checkbox($value) { return $this->decodeSkinCheckbox($value); }
}
$skin_model = new AdvisorySkinModel();
$skin_writer = new AdvisorySkinWriter();
$GLOBALS['models']['module'] = $skin_model;
$GLOBALS['models']['module_controller'] = $skin_writer;
$skin_admin = new moduleAdminController();
$cwd = getcwd();
chdir($GLOBALS['fixture_dir']);
$folder = './files/attach/images/100/';
FileHandler::makeDir($folder);
$image = $folder . 'logo.png';
file_put_contents($image, 'fixture');
$unmanaged = './separate.png';
file_put_contents($unmanaged, 'fixture');
foreach (array('P', 'M') as $mode) {
    $skin_model->stored[$mode]['logo'] = (object)array('value' => $unmanaged);
    Context::$values = array('module_srl' => 100, '_mode' => $mode, 'label' => 'A & B', 'options' => array('A & B', array('nested')),
        'colorset' => 'blue', 'extra' => 'unlisted', 'del_logo' => 'Y', 'del_label' => 'Y');
    $skin_admin->procModuleAdminUpdateSkinInfo();
    check($skin_writer->saved->label === 'A &amp; B' && $skin_writer->saved->options === array('A &amp; B') && $skin_writer->saved->colorset === 'blue', 'Declared skin values are escaped for ' . $mode);
    check(count((array)$skin_writer->saved) === 3 && is_file($unmanaged), 'Ignore undeclared settings and leave unmanaged files intact for ' . $mode);
    Context::$values = array('module_srl' => 100, '_mode' => $mode, 'label' => '<sample title="quoted">', 'options' => serialize(array('blue')));
    Context::set('label ', 'unused alias');
    Context::set("logo\t", 'unused image field');
    $skin_admin->procModuleAdminUpdateSkinInfo();
    check((array)$skin_writer->saved === array('label' => '&lt;sample title=&quot;quoted&quot;&gt;'), 'Only the exact field name is stored, markup is escaped and a serialized scalar is ignored for ' . $mode);
}
$skin_model->stored['P']['logo']->value = $image;
$skin_model->stored['M']['logo']->value = $image;
Context::set('_mode', 'P');
Context::set('del_logo', null);
Context::set('logo', $unmanaged);
Context::set('colorset', 'missing');
$skin_admin->procModuleAdminUpdateSkinInfo();
check($skin_writer->saved->logo === $image && !isset($skin_writer->saved->colorset), 'Retain the managed image and accept only a declared colorset');
Context::set('del_logo', 'Y');
$skin_admin->procModuleAdminUpdateSkinInfo();
check(is_file($image) && !isset($skin_writer->saved->logo), 'Keep an image still referenced by the other skin');
$skin_model->stored['M'] = array();
$skin_writer->fail = true;
$result = $skin_admin->procModuleAdminUpdateSkinInfo();
check(!$result->toBool() && is_file($image), 'Failed settings writes preserve the original image');
$skin_writer->fail = false;
$skin_admin->procModuleAdminUpdateSkinInfo();
check(!file_exists($image) && is_file($unmanaged), 'Delete the last reference to a managed skin image after saving');
chdir($cwd);
$skin_reader = new AdvisorySkinAdminModel();
$choices = array('first', '한글";', '0');
check($skin_reader->checkbox(serialize($choices)) === $choices, 'Legacy checkbox values retain byte lengths and order');
foreach (array(serialize(new stdClass()), serialize(array(array('nested'))), serialize($choices) . 'extra', 'a:1:{') as $invalid) {
    check($skin_reader->checkbox($invalid) === array(), 'Reject malformed or non-list checkbox storage');
}

// Existing outside pages are updated using the stored type, with the same path rules as rendering.
class AdvisoryPageModel
{
    public $type = 'OUTSIDE';
    public function getModuleInfoByModuleSrl($id, $columns = array()) {
        return (object)array('module_srl' => $id, 'module' => 'page', 'page_type' => $this->type);
    }
    public function getGrant($module, $member) { return (object)array('manager' => true, 'access' => true); }
}
class AdvisoryPageWriter
{
    public $saved;
    public function updateModule($settings) {
        $this->saved = clone $settings;
        $result = new BaseObject();
        $result->add('module_srl', $settings->module_srl);
        return $result;
    }
}
require_once _XE_PATH_ . 'modules/page/page.view.php';
class AdvisoryOutsideView extends pageView
{
    public function executeFile($path, $interval, $cache) { return 'local ' . $path; }
    public function getHtmlPage($path, $interval, $cache) { return 'remote ' . $path; }
}
chdir($GLOBALS['fixture_dir']);
FileHandler::makeDir('./files/cache/opage');
file_put_contents('./files/cache/opage/111.pc.cache.php', 'different page');
$page_writer = new AdvisoryPageWriter();
$GLOBALS['models']['module'] = new AdvisoryPageModel();
$GLOBALS['models']['module_controller'] = $page_writer;
$settings = array('module_srl' => 110, 'page_name' => 'outside', 'is_logged' => true,
    'logged_info' => (object)array('member_srl' => 41), 'path' => $GLOBALS['fixture_dir'] . 'allowed.php');
foreach (array(null, 'ARTICLE') as $submitted_type) {
    Context::$values = $settings;
    if ($submitted_type) Context::set('page_type', $submitted_type);
    file_put_contents('./files/cache/opage/110.pc.cache.php', 'old page');
    file_put_contents('./files/cache/opage/110.m.compiled.php', 'old template');
    $page_writer->saved = null;
    $admin = new pageAdminController();
    $admin->procPageAdminUpdate();
    check(!file_exists('./files/cache/opage/110.pc.cache.php') && !file_exists('./files/cache/opage/110.m.compiled.php') && is_file('./files/cache/opage/111.pc.cache.php'), 'Invalidate only this outside page cache after updating');
    check($page_writer->saved && $page_writer->saved->page_type === 'OUTSIDE' && $page_writer->saved->path === $settings['path'], 'An update uses the stored page type and the allowed desktop path');
}
foreach (array('path', 'mpath') as $key) {
    Context::$values = $settings;
    Context::set($key, array('invalid type'));
    $page_writer->saved = null;
    $admin = new pageAdminController();
    $admin->procPageAdminUpdate();
    check(!$admin->toBool() && !$page_writer->saved, 'Non-string path is rejected before updating ' . $key);
    Context::$values = $settings;
    Context::set($key . ' ', $settings['path']);
    $result = $admin->procPageAdminUpdate();
    check($result instanceof BaseObject && !$result->toBool() && !$page_writer->saved, 'Reject ambiguous setting names before storage for ' . $key);
}
foreach (array('ARTICLE', 'WIDGET') as $type) {
    $GLOBALS['models']['module']->type = $type;
    Context::$values = $settings;
    Context::set('page_type', 'OUTSIDE');
    Context::set('path', 'old-unused-page.html');
    Context::set('mpath', 'old-unused-mobile.html');
    $page_writer->saved = null;
    $admin = new pageAdminController();
    $admin->procPageAdminUpdate();
    check($page_writer->saved && $page_writer->saved->page_type === $type && !isset($page_writer->saved->path) && !isset($page_writer->saved->mpath), 'Discard unused outside paths when saving a ' . $type . ' page');
}
$view = new AdvisoryOutsideView();
$view->path = $GLOBALS['fixture_dir'] . 'linked.php';
check($view->_getOutsideContent() === '', 'A previously stored restricted page is skipped before reading cache or content');
$view->path = $settings['path'];
check($view->_getOutsideContent() === 'local ' . realpath($view->path), 'Render the validated local path');
$view->path = 'https://external.example/page.html';
check($view->_getOutsideContent() === 'remote ' . $view->path, 'Keep the HTTP page renderer for a valid URL');
chdir($cwd);
