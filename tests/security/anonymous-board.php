<?php
// Exercise the public board actions with the real posting controllers and an in-memory query log.
require_once _XE_PATH_ . 'modules/board/board.class.php';
require_once _XE_PATH_ . 'modules/board/board.controller.php';
require_once _XE_PATH_ . 'modules/document/document.class.php';
require_once _XE_PATH_ . 'modules/document/document.view.php';
require_once _XE_PATH_ . 'modules/document/document.model.php';
require_once _XE_PATH_ . 'modules/member/member.controller.php';
require_once _XE_PATH_ . 'modules/document/document.controller.php';
require_once _XE_PATH_ . 'modules/document/document.item.php';
require_once _XE_PATH_ . 'modules/comment/comment.class.php';
require_once _XE_PATH_ . 'modules/comment/comment.controller.php';
require_once _XE_PATH_ . 'modules/comment/comment.item.php';
require_once _XE_PATH_ . 'modules/member/member.model.php';

class AnonymousBoardFixture extends boardController { public function __construct() {} }
class BoardAuthorFixture extends memberModel { public function __construct() {} }
class BoardPostingModels
{
    public $document;
    public $comment;
    public $use_anonymous = 'Y';
    public function getModuleInfoByModuleSrl($srl) { return (object)array('module_srl' => $srl, 'module' => 'board', 'use_anonymous' => $this->use_anonymous, 'mobile_use_editor' => 'N'); }
    public function getModulePartConfig($name, $srl) { return (object)array('use_history' => 'Y'); }
    public function getGrant($info, $user) { return Context::get('grant'); }
    public function getDocument($srl, $is_admin = false) {
        $item = $srl == $this->document->document_srl ? $this->document : new documentItem();
        if($is_admin && $item->isExists()) $item->setGrant();
        return $item;
    }
    public function getComment($srl) { return $srl == $this->comment->comment_srl ? $this->comment : new commentItem(); }
    public function getFilesCount($srl) { return $srl == 1200 ? 2 : 0; }
    public function getStatusList() { return array('temp' => 'TEMP'); }
    public function getExtraKeys($srl) { return array(); }
    public function getCommentCount($srl) { return 1; }
}
class BoardCommentFixture extends commentController
{
    public function sendEmailToAdminAfterInsertComment($obj) {}
}
class BoardDocumentFixture extends documentController
{
    public function updateCommentCount($document_srl, $comment_count, $last_updater, $comment_inserted = false) { return new BaseObject(); }
}
function submittedBoardRow($query)
{
    foreach($GLOBALS['query_log'] as $entry) if($entry[0] === $query) return $entry[1];
    check(false, 'Expected board write ' . $query);
}
$posting_models = new BoardPostingModels();
$posting_models->document = new documentItem();
$posting_models->document->document_srl = 200;
$posting_models->document->adds(array('document_srl' => 200, 'module_srl' => 100, 'member_srl' => -25, 'status' => 'PUBLIC', 'lang_code' => 'ko', 'comment_status' => 'ALLOW'));
$posting_models->comment = new commentItem();
$posting_models->comment->setAttribute((object)array('comment_srl' => 300, 'document_srl' => 200, 'module_srl' => 100, 'member_srl' => -25));
foreach(array('document', 'comment', 'module', 'file') as $name) $GLOBALS['models'][$name] = $posting_models;
$GLOBALS['models']['document_controller'] = new BoardDocumentFixture();
$GLOBALS['models']['comment_controller'] = new BoardCommentFixture();
$GLOBALS['models']['member'] = new BoardAuthorFixture();
$board = new AnonymousBoardFixture();
$board->module_srl = 100;
$board->module_info = (object)array('module' => 'board', 'use_status' => 'PUBLIC|@|SECRET', 'use_anonymous' => 'Y', 'admin_mail' => '');
$board->grant = (object)array('write_document' => true, 'write_comment' => true, 'manager' => false);
$member = (object)array('member_srl' => 25, 'nick_name' => 'Member', 'user_id' => 'member', 'user_name' => 'Member', 'email_address' => 'member@example.com', 'homepage' => '', 'is_admin' => 'N');
$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
$_SESSION['seq'] = array(1200);
$request = array('module_srl' => 100, 'grant' => $board->grant, 'title' => 'Title', 'content' => 'Text', 'status' => 'SECRET', 'is_secret' => 'Y',
    'comment_status' => 'ALLOW', 'nick_name' => 'Guest', 'member_srl' => 900, 'notify_message' => 'Y', 'ipaddress' => '192.0.2.99',
    'voted_count' => 9, 'blamed_count' => 9, 'readed_count' => 9, 'comment_count' => 9, 'trackback_count' => 9, 'uploaded_count' => 9, 'regdate' => '20010101000000');

foreach(array('document', 'comment') as $kind) {
    $is_document = $kind === 'document';
    $action = $is_document ? 'procBoardInsertDocument' : 'procBoardInsertComment';
    $query = $is_document ? 'document.insertDocument' : 'comment.insertComment';
    $id = $kind . '_srl';
    foreach(array('member', 'guest', 'named') as $writer) {
        $board->module_info->use_anonymous = $writer === 'named' ? 'N' : 'Y';
        Context::$values = $request;
        Context::set('document_srl', $is_document ? 1200 : 200);
        Context::set($id, 1200);
        Context::set('logged_info', $writer === 'guest' ? null : $member);
        Context::set('is_logged', $writer !== 'guest');
        $GLOBALS['query_log'] = ModuleHandler::$triggers = array();
        $board->$action();
        $row = submittedBoardRow($query);
        check($board->get($id) === 1200 && $row->uploaded_count === 2, 'Keep the editor reservation and actual attachments for ' . $kind);
        check($row->ipaddress === $_SERVER['REMOTE_ADDR'] && !isset($row->regdate), 'Use server metadata for ' . $kind);
        check($row->voted_count === 0 && $row->blamed_count === 0, 'Initialize votes on ' . $kind);
        if($is_document) {
            check($row->readed_count === 0 && $row->comment_count === 0 && $row->trackback_count === 0, 'Initialize document counters');
            check($row->status === 'SECRET', 'Honor permitted secret documents');
        } else {
            check($row->is_secret === 'Y', 'Honor permitted secret comments');
        }
        $author = $writer === 'guest' ? 0 : ($writer === 'named' ? 25 : -25);
        check($row->member_srl === $author && $row->nick_name === ($writer === 'named' ? 'Member' : 'anonymous'), 'Store the correct ' . $writer . ' identity for ' . $kind);
        if($writer !== 'named') {
            $before = ModuleHandler::$triggers[0][1];
            check($before->member_srl === $author && $before->user_name === 'anonymous' && $before->user_id === '' &&
                $before->email_address === '' && $before->homepage === '' && $before->notify_message === 'N', 'Anonymize before ' . $kind . ' triggers');
        }
    }

    $board->module_info->use_anonymous = 'Y';
    Context::set($id, 400);
    $GLOBALS['query_log'] = array();
    $result = $board->$action();
    check(!$result->toBool() && !$GLOBALS['query_log'], 'Reject unreserved ' . $id . ' before writing');
    Context::set($id, array(1200));
    $result = $board->$action();
    check(!$result->toBool() && !$GLOBALS['query_log'], 'Posting IDs must be scalar');
    Context::set($id, 0);
    $GLOBALS['board_csrf_denied'] = true;
    $result = $board->$action();
    check(!$result->toBool() && !$GLOBALS['query_log'], 'Anonymous ' . $kind . ' requires CSRF validation');
    unset($GLOBALS['board_csrf_denied']);

    $board->module_info->use_status = 'PUBLIC';
    $board->$action();
    $row = submittedBoardRow($query);
    check($row->$id > 1000 && $board->get($id) === $row->$id, 'Return generated ' . $id);
    check($is_document ? $row->status === 'PUBLIC' : !isset($row->is_secret), 'Respect public-only ' . $kind . ' settings');
    $board->module_info->use_status = 'PUBLIC|@|SECRET';
}
$posting_models->document->add('comment_status', 'DENY');
$GLOBALS['query_log'] = array();
check(!$board->procBoardInsertComment()->toBool() && !$GLOBALS['query_log'], 'Anonymous comments respect closed documents');

// A moderator may publish a named draft on an anonymous board.
$board->grant->manager = true;
$posting_models->document->add('status', 'TEMP');
$posting_models->document->add('member_srl', 25);
$posting_models->document->add('nick_name', 'Draft author');
foreach(array('document', 'comment') as $kind) {
    $action = $kind === 'document' ? 'procBoardInsertDocument' : 'procBoardInsertComment';
    Context::set('document_srl', 200);
    Context::set('comment_srl', 300);
    Context::set('logged_info', (object)array('member_srl' => 40, 'is_admin' => 'N'));
    // Stop at the write boundary and inspect the author a moderator edit would persist.
    $query = $kind . '.update' . ucfirst($kind);
    $GLOBALS['query_fail_name'] = $query;
    $GLOBALS['query_log'] = array();
    $board->$action();
    if($kind === 'document') {
        $history = submittedBoardRow('document.insertHistory');
        check($history->member_srl === -25 && $history->nick_name === 'anonymous', 'Publishing a draft keeps its history anonymous');
    }
    $row = submittedBoardRow($query);
    check($row->member_srl === -25 && $row->nick_name === 'anonymous' && $row->email_address === '', 'Moderator edits retain original anonymous ' . $kind . ' ownership');
}
unset($GLOBALS['query_fail_name']);

// An editor may supply different names, but only the stored anonymous alias is public.
$posting_models->document->add('status', 'PUBLIC');
Context::set('nick_name', 'Editor name');
foreach(array('document', 'comment') as $kind) {
    $item = $posting_models->$kind;
    $action = $kind === 'document' ? 'procBoardInsertDocument' : 'procBoardInsertComment';
    $query = $kind . '.update' . ucfirst($kind);
    $item->add('user_name', 'Private name');
    $item->add('email_address', 'private@example.com');
    $item->add('user_id', 'private');
    $item->add('homepage', 'https://example.com/');
    foreach(array(array(-25, 'Reader 7', 'Reader 7'), array(-25, '0', '0'), array(-25, '', 'anonymous'),
        array(25, 'Member name', 'anonymous'), array(0, 'Guest name', 'anonymous')) as $case) {
        list($owner, $name, $expected) = $case;
        $item->add('member_srl', $owner);
        $item->add('nick_name', $name);
        $GLOBALS['query_fail_name'] = $query;
        $GLOBALS['query_log'] = ModuleHandler::$triggers = array();
        $board->$action();
        $row = submittedBoardRow($query);
        check(ModuleHandler::$triggers[0][0] === $query, 'Observe the first ' . $kind . ' update trigger');
        foreach(array(ModuleHandler::$triggers[0][1], $row) as $author) {
            check($author->member_srl === -abs($owner) && $author->nick_name === $expected && $author->user_name === $expected &&
                $author->user_id === '' && $author->email_address === '' && $author->homepage === '' && $author->notify_message === 'N',
                'Keep only the public alias before triggers and at the ' . $kind . ' write');
        }
        if($kind === 'document') {
            $history = submittedBoardRow('document.insertHistory');
            check($history->nick_name === $expected && $history->member_srl === -abs($owner), 'Keep the same anonymous author in history');
        }
    }
}
unset($GLOBALS['query_fail_name']);

// XE stores document and comment read permissions separately, even at the same numeric ID.
$document_at_same_id = new documentItem();
$document_at_same_id->document_srl = $posting_models->comment->comment_srl;
$posting_models->comment->setAccessible();
check(!$document_at_same_id->isAccessible(), 'Comment read access is scoped to comments');


// A normal comment API call also resolves anonymity from its board when no flag is supplied.
Context::$values = $request;
Context::set('logged_info', $member);
Context::set('is_logged', true);
$board->grant->manager = false;
$posting_models->document->add('comment_status', 'ALLOW');
foreach(array('N' => 25, 'Y' => -25) as $setting => $owner) {
    $posting_models->use_anonymous = $setting;
    $args = Context::getRequestVars();
    $args->document_srl = 200;
    $args->comment_srl = 1200;
    ModuleHandler::$triggers = $GLOBALS['query_log'] = array();
    $GLOBALS['models']['comment_controller']->insertComment($args);
    check(submittedBoardRow('comment.insertComment')->member_srl === $owner &&
        ModuleHandler::$triggers[0][1]->member_srl === $owner, 'Apply the board identity policy before comment insert triggers');
}
$posting_models->comment->add('member_srl', -25);
$posting_models->comment->add('nick_name', 'Reader 7');
$args->comment_srl = 300;
$GLOBALS['query_fail_name'] = 'comment.updateComment';
ModuleHandler::$triggers = $GLOBALS['query_log'] = array();
$GLOBALS['models']['comment_controller']->updateComment($args);
check(submittedBoardRow('comment.updateComment')->nick_name === 'Reader 7' && ModuleHandler::$triggers[0][1]->email_address === '',
    'Preserve the stored alias on a regular comment update');
unset($GLOBALS['query_fail_name']);

// A draft uses its board's identity policy from the first save, including on legacy rows.
$drafts = $GLOBALS['models']['document_controller'];
$drafts->module_info = $posting_models->getModuleInfoByModuleSrl(100);
$drafts->grant = $board->grant;
$drafts->grant->access = true;
$drafts->grant->manager = false;
Context::$values = $request;
Context::set('logged_info', $member);
Context::set('is_logged', true);
Context::set('document_srl', 1200);
foreach(array('N' => 25, 'Y' => -25) as $setting => $owner) {
    $posting_models->use_anonymous = $setting;
    $GLOBALS['query_log'] = ModuleHandler::$triggers = array();
    $drafts->procDocumentTempSave();
    $row = submittedBoardRow('document.insertDocument');
    check($row->status === 'TEMP' && $row->member_srl === $owner && $drafts->get('document_srl') === 1200, 'Save a draft under its original editor reservation');
    check($row->nick_name === ($owner < 0 ? 'anonymous' : $member->nick_name) && ModuleHandler::$triggers[0][1]->nick_name === $row->nick_name, 'Draft identity is ready before insert triggers');
    check(submittedBoardRow('file.updateFileValid')->upload_target_srl === 1200, 'Mark only the saved draft attachments');
}
Context::set('document_srl', 200);
$source = $posting_models->document;
$source->add('status', 'TEMP');
$source->add('nick_name', 'Old draft name');
foreach(array(25, -25) as $owner) {
    $source->add('member_srl', $owner);
    $source->grant_cache = null;
    foreach(array('save', 'publish') as $operation) {
        $GLOBALS['query_log'] = ModuleHandler::$triggers = array();
        if($operation === 'save') $drafts->procDocumentTempSave();
        else $board->procBoardInsertDocument();
        $row = submittedBoardRow('document.updateDocument');
        check($row->status === ($operation === 'save' ? 'TEMP' : 'SECRET') && $row->member_srl === -25, 'Keep ownership on draft ' . $operation);
        check($row->nick_name === 'anonymous' && ModuleHandler::$triggers[0][1]->nick_name === 'anonymous' &&
            submittedBoardRow('document.insertHistory')->nick_name === 'anonymous', 'Generate a fresh anonymous name for legacy drafts');
    }
}
foreach(array(array(26, 'TEMP', 100), array(25, 'PUBLIC', 100), array(25, 'TEMP', 101)) as $case) {
    list($owner, $status, $module_srl) = $case;
    $source->adds(array('member_srl' => $owner, 'status' => $status, 'module_srl' => $module_srl));
    $source->grant_cache = null;
    $drafts->grant->manager = $module_srl != 100; // A manager of this board cannot grant access on another.
    unset($_SESSION['own_document'][200]);
    $GLOBALS['query_log'] = ModuleHandler::$triggers = array();
    check(!$drafts->procDocumentTempSave()->toBool() && !$GLOBALS['query_log'] && !ModuleHandler::$triggers && empty($_SESSION['own_document'][200]), 'Check original board, draft status and grant before any write');
}
$drafts->grant->manager = false;
$drafts->grant->write_document = false;
Context::set('document_srl', 0);
check(!$drafts->procDocumentTempSave()->toBool() && !$GLOBALS['query_log'], 'Saving a draft requires write permission on its board');
$drafts->grant->write_document = true;
Context::set('document_srl', 1200);
$GLOBALS['query_fail_name'] = 'document.insertDocument';
$result = $drafts->procDocumentTempSave();
$last_query = end($GLOBALS['query_log']);
check(!$result->toBool() && $last_query[0] === 'document.insertDocument', 'A failed save leaves attachments alone');
unset($GLOBALS['query_fail_name']);

// Both saved-document screens must restrict the real list query to this member and TEMP status.
$_SESSION['is_logged'] = true;
$_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
$GLOBALS['models']['document'] = new documentModel();
$GLOBALS['query_data']['document.getDocumentList'] = array();
foreach(array('dispTempSavedList' => new documentView(), 'dispMemberSavedDocument' => new FixtureMemberView()) as $action => $view) {
    $GLOBALS['query_log'] = array();
    $view->$action();
    $args = submittedBoardRow('document.getDocumentList');
    check($args->statusList === array('TEMP') && $args->member_srls === array(25, -25), 'List only the signed-in member in both author formats in ' . $action);
}
$GLOBALS['models']['document'] = $posting_models;
class DraftMemberControllerFixture extends memberController { public function __construct() {} }
$deletion = new DraftMemberControllerFixture();
Context::set('document_srl', 200);
$source->adds(array('status' => 'TEMP', 'member_srl' => -25, 'module_srl' => 100));
$source->grant_cache = null;
$GLOBALS['query_log'] = array();
$GLOBALS['query_fail_name'] = 'document.deleteDocument';
$deletion->procMemberDeleteSavedDocument();
check(submittedBoardRow('document.deleteDocument')->document_srl === 200, 'Members can delete their own anonymous drafts');
unset($GLOBALS['query_fail_name']);
foreach(array(array(-26, 'TEMP'), array(-25, 'PUBLIC')) as $case) {
    $source->adds(array('member_srl' => $case[0], 'status' => $case[1]));
    $GLOBALS['query_log'] = array();
    check(!$deletion->procMemberDeleteSavedDocument()->toBool() && !$GLOBALS['query_log'], 'Draft deletion respects owner and status');
}
