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
    public function isSiteAdmin($member, $site = null) { return !empty($GLOBALS['rve_site_admin']); }
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
