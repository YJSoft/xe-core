<?php
// Standalone regressions for RVE-2026-34 onward. Keep PHP 5.3 compatibility.
require_once _XE_PATH_ . 'modules/document/document.class.php';
require_once _XE_PATH_ . 'modules/document/document.item.php';
require_once _XE_PATH_ . 'modules/board/board.class.php';
require_once _XE_PATH_ . 'modules/board/board.view.php';
require_once _XE_PATH_ . 'modules/comment/comment.class.php';
require_once _XE_PATH_ . 'modules/comment/comment.item.php';
class RveDocument extends documentItem
{
    public $granted = false;
    public function __construct($srl = 200, $module = 100)
    {
        $this->document_srl = $srl;
        foreach(array('document_srl' => $srl, 'module_srl' => $module, 'member_srl' => 99, 'status' => 'PUBLIC', 'comment_status' => 'ALLOW', 'is_notice' => 'N') as $key => $value) $this->add($key, $value);
    }
    public function isGranted() { return $this->granted; }
}
class RveComment extends commentItem
{
    public $granted = false;
    public function __construct($srl = 300, $document = 200, $module = 100)
    {
        $this->comment_srl = $srl;
        foreach(array('comment_srl' => $srl, 'document_srl' => $document, 'module_srl' => $module, 'member_srl' => 99, 'is_secret' => 'N') as $key => $value) $this->add($key, $value);
    }
    public function isGranted() { return $this->granted; }
}
class RveDocumentModel
{
    public $documents = array();
    public function getConfigStatus($key) { return strtoupper($key); }
    public function getDocumentCountByMemberSrl($srl) { return 0; }
    public function getDocument($srl = 0, $admin = false, $extra = true, $columns = array()) { return isset($this->documents[$srl]) ? $this->documents[$srl] : new RveDocument(0); }
}
class RveCommentModel
{
    public $comments = array();
    public function getCommentCountByMemberSrl($srl) { return 0; }
    public function getComment($srl = 0, $admin = false, $columns = array()) { return isset($this->comments[$srl]) ? $this->comments[$srl] : new RveComment(0); }
}
class RveModuleModel
{
    public $modules = array();
    public $grants = array();
    public $category_reads = 0;
    public function getModuleCategories() { $this->category_reads++; return array(); }
    public function syncModuleToSite(&$info) {}
    public function isSiteAdmin($member, $site = null) { return !empty($GLOBALS['rve_site_admin']); }
    public function getModulePartConfig($name, $srl) { return (object)array('use_vote_up' => 'S', 'use_vote_down' => 'S'); }
    public function getModuleInfoByModuleSrl($srl, $columns = array()) { return isset($this->modules[$srl]) ? clone $this->modules[$srl] : null; }
    public function getGrant($module, $member = null, $xml = null) { return isset($this->grants[$module->module_srl]) ? $this->grants[$module->module_srl] : (object)array('access' => false, 'manager' => false); }
}
class RveBoardView extends boardView
{
    public function __construct() {}
    public function dispBoardMessage($message) { return new BaseObject(-1, $message); }
}
$rve_modules = new RveModuleModel();
$rve_documents = new RveDocumentModel();
$rve_comments = new RveCommentModel();
$GLOBALS['models']['module'] = $rve_modules;
$GLOBALS['models']['document'] = $rve_documents;
$GLOBALS['models']['comment'] = $rve_comments;
$rve_modules->modules[100] = (object)array('module_srl' => 100, 'module' => 'board', 'consultation' => 'Y');
$rve_modules->grants[100] = (object)array('access' => true, 'view' => true, 'manager' => false, 'consultation_read' => false, 'write_comment' => true);
$rve_document = new RveDocument();
$rve_documents->documents[200] = $rve_document;
$rve_comment = new RveComment();
$rve_comments->comments[300] = $rve_comment;
$rve_board = new RveBoardView();
$rve_board->module_srl = 100;
$rve_board->module_info = $rve_modules->modules[100];
$rve_board->grant = $rve_modules->grants[100];
Context::$values = array('is_logged' => true, 'logged_info' => (object)array('member_srl' => 10, 'is_admin' => 'N'), 'document_srl' => 200, 'comment_srl' => 300);
$_SESSION['accessible'] = array();
foreach(array('dispBoardWriteComment', 'dispBoardReplyComment') as $action) {
    Context::set('oDocument', null);
    Context::set('oSourceComment', null);
    $result = $rve_board->$action();
    check($result instanceof BaseObject && !$result->toBool(), 'RVE-2026-34 denies another author consultation content in ' . $action);
    check(Context::get('oDocument') === null && Context::get('oSourceComment') === null, 'Denied consultation forms do not expose parent objects');
}
$rve_document->granted = true;
check($rve_board->dispBoardWriteComment() === null, 'Consultation authors retain their comment form');
check($rve_board->dispBoardReplyComment() === null, 'Consultation authors retain reply forms');
$rve_document->granted = false;
$rve_modules->grants[100]->consultation_read = true;
check($rve_board->dispBoardReplyComment() === null, 'Consultation readers retain reply forms');
$rve_modules->grants[100]->consultation_read = false;
$rve_document->add('is_notice', 'Y');
check($rve_board->dispBoardWriteComment() === null, 'Public notices remain visible on consultation boards');
$rve_document->add('is_notice', 'N');
$rve_modules->modules[100]->consultation = 'N';
$rve_document->add('status', 'SECRET');
check(!$rve_document->isAccessible(true), 'Strict document access denies unread secret documents');
$_SESSION['accessible'][200] = true;
check($rve_document->isAccessible(true), 'Password-authorized secret documents remain accessible');
$rve_modules->grants[100]->view = false;
check(!$rve_document->isAccessible(true), 'Password sessions cannot bypass module view grants');
$rve_modules->grants[100]->view = true;
$rve_document->add('status', 'PUBLIC');
$rve_document->add('module_srl', 999);
check(!$rve_board->dispBoardWriteComment()->toBool(), 'Comment forms reject parent documents in another module');
$rve_document->add('module_srl', 100);

require_once _XE_PATH_ . 'modules/member/member.controller.php';
class RveMemberModel
{
    public $members = array();
    public function getMemberInfoByMemberSrl($srl, $site = 0, $columns = array()) { return isset($this->members[$srl]) ? $this->members[$srl] : null; }
}
class RveMemberController extends memberController
{
    public $updates = 0;
    public function __construct() {}
    public function updateMember($args, $is_admin = false) { $this->updates++; return new BaseObject(); }
    public function _clearMemberCache($srl, $site = 0) {}
}
$rve_members = new RveMemberModel();
$rve_members->members[99] = (object)array('member_srl' => 99, 'is_admin' => 'Y', 'user_id' => 'root', 'email_address' => 'root@example.com', 'nick_name' => 'Root', 'description' => '');
$GLOBALS['models']['member'] = $rve_members;
$rve_member = new RveMemberController();
$rve_modules->grants[100]->manager = true;
Context::set('member_srl', 99);
Context::set('module_srl', 100);
Context::set('cnt_loop', 1);
$result = $rve_member->procMemberSpammerManage();
check(!$result->toBool() && $rve_member->updates === 0, 'RVE-2026-35 module managers cannot ban site administrators');
$rve_members->members[99]->is_admin = 'N';
$result = $rve_member->procMemberSpammerManage();
check(!$result->toBool() && $rve_member->updates === 0, 'Module managers cannot globally ban ordinary members either');
Context::set('cnt_loop', 2);
check(!$rve_member->procMemberSpammerManage()->toBool(), 'Module managers cannot bypass authorization in the content-deletion phase');
Context::set('cnt_loop', 1);
$spammer_view = new FixtureMemberView();
check(!$spammer_view->dispMemberSpammer()->toBool(), 'Spammer management UI is restricted to site administrators');
Context::get('logged_info')->is_admin = 'Y';
$rve_members->members[99]->is_admin = 'Y';
check(!$rve_member->procMemberSpammerManage()->toBool() && $rve_member->updates === 0, 'Even administrators cannot classify another administrator as a spammer');
$rve_members->members[99]->is_admin = 'N';
check($rve_member->procMemberSpammerManage()->toBool() && $rve_member->updates === 1, 'Administrators can still ban ordinary spam accounts');
$spammer_view->dispMemberSpammer();
check($spammer_view->getTemplateFile() === 'spammer.html', 'Administrators retain the spammer management UI');
Context::set('member_srl', 0);
check(!$rve_member->procMemberSpammerManage()->toBool() && $rve_member->updates === 1, 'Spammer management rejects empty member IDs');
Context::set('member_srl', 10);
check(!$rve_member->procMemberSpammerManage()->toBool() && $rve_member->updates === 1, 'Administrators cannot mark themselves as spammers');

class RveModuleController
{
    public $inserted = array();
    public $updated = array();
    public function insertModule($args) { $this->inserted[] = clone $args; $out = new BaseObject(); $out->add('module_srl', 101); return $out; }
    public function updateModule($args) { $this->updated[] = clone $args; $out = new BaseObject(); $out->add('module_srl', $args->module_srl); return $out; }
}
$rve_module_controller = new RveModuleController();
$GLOBALS['models']['module_controller'] = $rve_module_controller;
$rve_page = new pageAdminController();
Context::$values = array('is_logged' => true, 'logged_info' => (object)array('member_srl' => 10, 'is_admin' => 'N'), 'module_srl' => 100, 'page_name' => 'page', 'page_type' => 'OUTSIDE', 'path' => '', 'mpath' => '');
$result = $rve_page->procPageAdminUpdate();
check($result instanceof BaseObject && !$result->toBool() && !count($rve_module_controller->updated), 'RVE-2026-36 cannot convert another module into a page');
$rve_modules->modules[100]->module = 'page';
$rve_modules->modules[100]->page_type = 'ARTICLE';
$rve_modules->modules[100]->site_srl = 0;
$rve_page->origin_module_info = clone $rve_modules->modules[100];
$rve_page->procPageAdminUpdate();
check(count($rve_module_controller->updated) === 1 && $rve_module_controller->updated[0]->page_type === 'ARTICLE', 'Page updates preserve the existing page type');
$rve_modules->grants[100]->manager = false;
$result = $rve_page->procPageAdminUpdate();
check($result instanceof BaseObject && !$result->toBool() && count($rve_module_controller->updated) === 1, 'Page updates recheck manager permission on the target module');
$rve_modules->grants[100]->manager = true;
$rve_page->origin_module_info->module_srl = 999;
check(!$rve_page->procPageAdminUpdate()->toBool(), 'A routed page cannot update a different page ID');
$rve_page->origin_module_info = null;
$result = $rve_page->procPageAdminInsert();
check($result instanceof BaseObject && !$result->toBool() && !count($rve_module_controller->inserted), 'Insert action cannot be used as an update action');
Context::set('module_srl', null);
check(!$rve_page->procPageAdminInsert()->toBool(), 'A module manager cannot create arbitrary pages');
Context::get('logged_info')->is_admin = 'Y';
$rve_page->procPageAdminInsert();
check(count($rve_module_controller->inserted) === 1 && $rve_module_controller->inserted[0]->module === 'page', 'Administrators can create pages');
Context::set('module_srl', 777);
$result = $rve_page->procPageAdminUpdate();
check($result instanceof BaseObject && !$result->toBool() && count($rve_module_controller->inserted) === 1, 'Updating a missing page cannot silently create one');
Context::set('module_srl', null);
Context::set('site_srl', 999);
Context::set('site_module_info', (object)array('site_srl' => 20));
Context::get('logged_info')->is_admin = 'N';
$GLOBALS['rve_site_admin'] = true;
$rve_page->procPageAdminInsert();
check(count($rve_module_controller->inserted) === 2 && $rve_module_controller->inserted[1]->site_srl === 20, 'Virtual site administrators create pages only in their current site');
$GLOBALS['rve_site_admin'] = false;

require_once _XE_PATH_ . 'modules/document/document.model.php';
$rve_vote_model = new documentModel();
$rve_modules->modules[100]->module = 'board';
$rve_modules->modules[100]->consultation = 'N';
$rve_modules->grants[100]->manager = false;
Context::$values = array('document_srl' => 200, 'logged_info' => (object)array('member_srl' => 10, 'is_admin' => 'N'));
foreach(array('access', 'view') as $permission) {
    $rve_modules->grants[100]->$permission = false;
    $GLOBALS['query_log'] = array();
    $result = $rve_vote_model->getDocumentVotedMemberList();
    check($result instanceof BaseObject && !$result->toBool() && !count($GLOBALS['query_log']), 'RVE-2026-38 voter lookup checks actual source document ' . $permission . ' permission');
    $rve_modules->grants[100]->$permission = true;
}
$_SESSION['accessible'] = array();
$rve_document->add('status', 'SECRET');
check(!$rve_vote_model->getDocumentVotedMemberList()->toBool(), 'Voter lists of unread secret documents are unavailable');
$rve_document->add('status', 'PUBLIC');
$rve_modules->modules[100]->consultation = 'Y';
check(!$rve_vote_model->getDocumentVotedMemberList()->toBool(), 'Voter lists cannot expose another author consultation document');
$rve_document->granted = true;
$GLOBALS['query_log'] = array();
$rve_vote_model->getDocumentVotedMemberList();
check(count($GLOBALS['query_log']) === 1 && $GLOBALS['query_log'][0][0] === 'document.getVotedMemberList', 'An authorized author can still retrieve the voter list');
$rve_document->granted = false;
$rve_modules->modules[100]->consultation = 'N';
Context::set('document_srl', 999999);
check(!$rve_vote_model->getDocumentVotedMemberList()->toBool(), 'Voter lists require an existing document');
Context::set('document_srl', 200);
foreach(array('TEMP', 'PRIVATE') as $status) {
    $rve_document->add('status', $status);
    check(!$rve_vote_model->getDocumentVotedMemberList()->toBool(), 'Unpublished document voter lists are restricted: ' . $status);
}
$rve_document->add('status', 'PUBLIC');

// Insertion tests execute the real controller and capture the actual INSERT arguments.
require_once _XE_PATH_ . 'modules/document/document.controller.php';
require_once _XE_PATH_ . 'modules/comment/comment.controller.php';
function checkCSRF() { return true; }
function checkUserSequence($srl) { return $srl >= 1000; }
function getNextSequence() { static $next = 1000; return ++$next; }
class Mobile { public static function isFromMobilePhone() { return false; } }
class RveFileModel { public function getFilesCount($srl) { return 2; } }
function rveInsertedRow($name)
{
    foreach($GLOBALS['query_log'] as $entry) if($entry[0] === $name) return $entry[1];
    check(false, 'Insertion must reach its database query: ' . $name);
}
$GLOBALS['models']['file'] = new RveFileModel();
Context::set('logged_info', (object)array('member_srl' => 10, 'user_id' => 'member', 'user_name' => 'Member', 'nick_name' => 'Member', 'is_admin' => 'N', 'email_address' => '', 'homepage' => ''));
Context::set('grant', (object)array('manager' => false));
$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
$insert_document = new documentController();
foreach(array(array(false, false), array(true, false), array(false, true)) as $mode) {
    $args = (object)array('module_srl' => 100, 'document_srl' => 0, 'title' => 'Title', 'content' => '<p>Text</p>', 'nick_name' => 'Import author', 'comment_status' => 'ALLOW', 'status' => 'PUBLIC', 'ipaddress' => '203.0.113.99', 'readed_count' => 777, 'voted_count' => 777, 'blamed_count' => 777, 'comment_count' => 777, 'trackback_count' => 777, 'uploaded_count' => 777);
    $GLOBALS['query_log'] = array();
    $GLOBALS['query_fail_name'] = 'document.insertDocument';
    $insert_document->insertDocument($args, $mode[0], $mode[1]);
    $row = rveInsertedRow('document.insertDocument');
    foreach(array('readed_count', 'voted_count', 'blamed_count', 'comment_count', 'trackback_count') as $counter) {
        check($row->$counter === ($mode[0] || $mode[1] ? 777 : 0), 'RVE-2026-39 document insertion protects ' . $counter . ' while preserving trusted imports/restores');
    }
    check($row->ipaddress === ($mode[0] || $mode[1] ? '203.0.113.99' : '192.0.2.10'), 'Document insertion binds IP to the current request except trusted imports/restores');
    if(!$mode[0] && !$mode[1]) check($row->uploaded_count === 2, 'Document attachment count is calculated from stored files');
}
$insert_comment = new commentController();
foreach(array(false, true) as $manual) {
    $args = (object)array('module_srl' => 100, 'document_srl' => 200, 'comment_srl' => 0, 'parent_srl' => 0, 'content' => '<p>Text</p>', 'nick_name' => 'Import author', 'ipaddress' => '203.0.113.99', 'voted_count' => 777, 'blamed_count' => 777, 'uploaded_count' => 777);
    $GLOBALS['query_log'] = array();
    $GLOBALS['query_fail_name'] = 'comment.insertComment';
    $insert_comment->insertComment($args, $manual);
    $row = rveInsertedRow('comment.insertComment');
    check($row->voted_count === ($manual ? 777 : 0) && $row->blamed_count === ($manual ? 777 : 0), 'Comment insertion protects voting counts while preserving trusted imports');
    check($row->ipaddress === ($manual ? '203.0.113.99' : '192.0.2.10'), 'Comment insertion binds IP to the current request except trusted imports');
    if(!$manual) check($row->uploaded_count === 2, 'Comment attachment count is calculated from stored files');
}
unset($GLOBALS['query_fail_name']);

require_once _XE_PATH_ . 'modules/board/board.admin.view.php';
class RveBoardAdminView extends boardAdminView
{
    public function __construct() {}
    public function stop($message) { $this->stop_proc = true; return new BaseObject(-1, $message); }
}
$rve_modules->modules[200] = (object)array('module_srl' => 200, 'module' => 'board', 'use_status' => 'PUBLIC|@|SECRET');
$rve_modules->grants[200] = (object)array('access' => true, 'manager' => false);
Context::$values = array('module_srl' => 200, 'logged_info' => (object)array('member_srl' => 10, 'is_admin' => 'N'));
$settings = new RveBoardAdminView();
$settings->origin_module_info = $rve_modules->modules[100];
$settings->module_srl = 100;
$result = $settings->init();
check($result instanceof BaseObject && !$result->toBool() && Context::get('module_info') === null, 'RVE-2026-40 settings check permission on the requested board before publishing its configuration');
check($rve_modules->category_reads === 0, 'Rejected settings requests do not load administrator data');
$rve_modules->grants[200]->manager = true;
$settings = new RveBoardAdminView();
$settings->origin_module_info = $rve_modules->modules[100];
check(!$settings->init()->toBool(), 'A routed board settings request cannot switch board IDs');
$settings = new RveBoardAdminView();
$settings->origin_module_info = $rve_modules->modules[200];
$settings->init();
check(!$settings->stop_proc && Context::get('module_info')->module_srl === 200, 'Managers retain access to the board they actually administer');
Context::set('module_srl', 999999);
$settings = new RveBoardAdminView();
check(!$settings->init()->toBool(), 'Missing board IDs cannot turn into a board list request');

// RVE-2026-41 relies on Rhymix-only property encoding and restoration.
foreach(array(
    '<a title=\'<div editor_component="x" srcdoc="&lt;script&gt;window.__xeRve41=1&lt;/script&gt;">\'>TEST</a>',
    '<iframe src="https://trusted.example/" rx_encoded_datas="copied-signed-value" srcdoc="&lt;script&gt;window.__xeRve41=1&lt;/script&gt;"></iframe>',
    '<div editor_component="image_link" srcdoc="&lt;script&gt;window.__xeRve41=1&lt;/script&gt;">TEST</div><iframe src="https://trusted.example/" rx_encoded_datas="copied-signed-value"></iframe>'
) as $content) {
    $had_iframe = strpos($content, '<iframe') !== false;
    $purifier = new Purifier();
    $purifier->setConfig('Cache.SerializerPath', $GLOBALS['fixture_dir']);
    $purifier->purify($content);
    check(!preg_match('/<iframe\b[^>]*(?:srcdoc|rx_encoded_datas|rx_encoded_properties)\s*=/i', $content), 'RVE-2026-41 payloads cannot restore iframe attributes in XE');
    check(stripos($content, '<script') === false, 'RVE-2026-41 payloads do not produce executable script elements in XE');
    if($had_iframe) check(strpos($content, '<iframe src="https://trusted.example/"') !== false, 'Filtering preserves safe iframe sources while removing malicious attributes');
}
