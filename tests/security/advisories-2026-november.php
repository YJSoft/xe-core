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
    public function getDocument($srl = 0, $admin = false, $extra = true, $columns = array()) { return isset($this->documents[$srl]) ? $this->documents[$srl] : new RveDocument(0); }
}
class RveCommentModel
{
    public $comments = array();
    public function getComment($srl = 0, $admin = false, $columns = array()) { return isset($this->comments[$srl]) ? $this->comments[$srl] : new RveComment(0); }
}
class RveModuleModel
{
    public $modules = array();
    public $grants = array();
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
