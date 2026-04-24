# XpressEngine 1.x 코드베이스 개선 계획

PHP 5.3~7.4 전 범위에서 동작하는 것을 유지하면서, PHP4 시대 호환 코드 및 구식 패턴을 정리한다.  
JavaScript 코드는 수정하지 않는다.

---

## 현황 분석

| 카테고리 | 규모 | 영향 파일 수 |
|---|---|---|
| non-static 메서드가 static으로 호출 | Context 77개, FileHandler 27개 등 | 100+ |
| `var $` 프로퍼티 선언 | 450개소 | 119 |
| 메서드 가시성 미선언 (`function` 단독) | 2,586개소 | 289 |
| `&getInstance()` 레퍼런스 반환 | 13개소 | 11 |
| `mysql_*` 함수 (PHP 7.0에서 제거됨) | 23개소 | 2 |
| `$args->` 초기화 누락 | 14개 함수 | 8 |

`split()` / `ereg()` / `eregi()`: 사용자 코드에서는 발견되지 않음 (외부 라이브러리 내부만 해당, 미수정).

---

## 작업 목록

### Task 1. non-static 메서드를 `static`으로 선언

#### 배경

PHP 5.x에서는 non-static 메서드를 `ClassName::method()` 형태로 호출해도 E_STRICT 경고만 발생했으나, PHP 7.x에서는 경고 수준이 높아진다. 해당 클래스들은 내부적으로 `self::getInstance()` 또는 `$this` 없이 동작하도록 설계되어 있으므로 `static` 키워드만 추가하면 된다.

#### 수정 방법

**판별 기준**:
- 메서드 본문에 `$this`가 없으면 → `static` 추가 대상
- 메서드 본문이 `$self = self::getInstance()` 패턴을 사용하고 `$this`가 없으면 → `static` 추가 대상
- `$this`를 직접 사용하는 메서드(생성자, 일반 인스턴스 메서드) → 수정 불필요

#### 파일별 수정 대상

**`classes/file/FileHandler.class.php`**
- 전체 27개 메서드가 `$this` 미사용 → 전체 `public static function`으로 변경
- 이미 `static`인 `clearStatCache()`, `invalidateOpcache()`는 유지

**`classes/context/Context.class.php`**
- `getInstance()` 포함, `self::getInstance()`를 내부에서 호출하는 메서드 전체에 `static` 추가
- `$this`를 직접 사용하는 `__construct()`, `init()`, `checkSSO()`, `_evalxmlLang()`, `_loadXmlLang()`, `_loadPhpLang()`, `_checkGlobalVars()`, `_setRequestArgument()`, `_recursiveCheckVar()`, `_setJSONRequestArgument()`, `_setXmlRpcArgument()`, `_filterXmlVars()`, `_setUploadedArgument()`, `_getBrowserTitle()` 는 그대로 유지
- 나머지 `close()`, `loadDBInfo()`, `getDBType()`, `set()`, `get()`, `gets()`, `getLang()` 등 getInstance() 경유 메서드 → `static` 추가

**`classes/mobile/Mobile.class.php`**
- `isFromMobilePhone()`, `isMobileCheckByAgent()`, `isMobilePadCheckByAgent()`, `setMobile()` → `static` 추가

**`classes/cache/CacheHandler.class.php`**
- `getCacheKey()`, `get()`, `put()`, `isValid()`, `isSupport()` → `static` 추가
- `getInstance()` 포함

**`classes/template/TemplateHandler.class.php`**
- `getInstance()`, `_replaceVar()` → `static` 추가

**`classes/module/ModuleHandler.class.php`**
- `_clearErrorSession()`, `_setInputValueToSession()`, `getModulePath()`, `_getModuleFilePath()`, `triggerCall()` → `static` 추가
- `getModuleInstance()` (레퍼런스 반환 포함)

**`classes/db/DB.class.php`**
- `getInstance()`, `create()`, `getSupportedList()`, `isSupported()`, `setQueryLog()`, `getSelectSql()`, `getClickCountQuery()`, `getDeleteSql()`, `getUpdateSql()`, `getInsertSql()` → `static` 추가

#### 주의 사항

- `static` 추가 후 해당 메서드 내부에서 `self::` 호출이 있는지 확인 (문제없음)
- 서브클래스에서 오버라이드하는 메서드는 부모/자식 모두 일관성 있게 수정
- 각 클래스가 `interface`를 구현하는 경우, 인터페이스 메서드도 동일하게 수정 필요

---

### Task 2. `$args->` 초기화 누락 수정

#### 배경

`$args->property` 형태로 접근하기 전에 `$args = new stdClass` 초기화가 없는 경우, PHP 5에서는 E_WARNING이, PHP 8에서는 오류가 발생한다. 단, 함수 파라미터로 객체를 받는 경우나 `Context::getRequestVars()` 반환값을 사용하는 경우는 제외한다.

#### 수정 대상 함수 (14개)

각 함수의 첫 번째 `->` 접근 직전에 `$args = new stdClass` 추가.  
단, 파라미터 `$obj` 등으로 받은 경우 의미를 유지하기 위해 초기화 위치를 함수 최상단에 배치.

| 파일 | 함수 | 비고 |
|---|---|---|
| `modules/comment/comment.model.php` | `getCommentCountByDate()` | 조건부 초기화 필요 |
| `modules/document/document.admin.model.php` | `getDocumentTrashList($obj)` | `$obj` 파라미터 null 가능성 확인 |
| `modules/document/document.admin.model.php` | `getDocumentTrash($trash_srl)` | |
| `modules/document/document.model.php` | `getDocumentList($obj, ...)` | `$obj` 파라미터 null 가능성 확인 |
| `modules/document/document.model.php` | `getDocumentPage($oDocument, $opt)` | `$opt` 파라미터 |
| `modules/editor/editor.controller.php` | `removeEditorConfig($site_srl)` | |
| `modules/file/file.admin.model.php` | `getFilesCountByDate($date = '')` | |
| `modules/layout/layout.class.php` | `moduleUpdate()` | |
| `modules/member/member.admin.model.php` | `getSiteMemberList($site_srl, ...)` | |
| `modules/member/member.admin.model.php` | `getMemberGroupMemberCountByDate($date = '')` | |
| `modules/member/member.model.php` | `getMembersGroups($member_srls, ...)` | |
| `modules/member/member.model.php` | `getJoinForm($member_join_form_srl)` | |
| `modules/menu/menu.admin.controller.php` | `updateMenuLayout($layout_srl, ...)` | |

#### 초기화 위치 결정 규칙

1. 파라미터로 받은 변수(`$obj`, `$opt` 등)의 경우 → `if(!$obj) $obj = new stdClass` 조건부 초기화
2. 함수 내부에서 새로 만드는 변수인 경우 → 함수 최상단에 `$args = new stdClass` 무조건 초기화
3. 루프나 분기 내에서만 쓰이는 경우 → 해당 분기 직전에 초기화

---

### Task 3. `var $` → `public $` (프로퍼티 가시성)

#### 배경

`var` 키워드는 PHP4 호환을 위해 남아있는 deprecated 문법이다. PHP5+에서는 `public`, `protected`, `private`으로 선언해야 한다. `var`은 PHP5에서 `public`과 동일하게 처리되므로 동작은 바뀌지 않는다.

#### 수정 방법

- 원칙: 모든 `var $` → `public $`로 일괄 치환
- 예외: 서브클래스에서 override하거나 외부 접근을 막아야 하는 명백한 경우만 `protected`/`private` 사용  
  (범위가 크므로 우선 `public`으로 일괄 변경 후 보수적으로 접근)

#### 규모

- 450개소, 119개 파일
- `libs/`, `classes/security/htmlpurifier/` 내부는 외부 라이브러리이므로 제외

---

### Task 4. 메서드 가시성 선언 추가

#### 배경

`function methodName()` 형태의 메서드 선언은 PHP4 호환 방식이다. PHP5+에서는 `public function methodName()` 형태가 표준이다. 미선언 시 `public`으로 처리되므로 동작은 바뀌지 않는다.

#### 수정 방법

- `function methodName(` → `public function methodName(` 일괄 변경
- `static function` → `public static function`으로 변경
- **예외**: `__construct()`, `__destruct()`, `__get()`, `__set()` 등 magic method는 이미 처리된 것이 많으므로 중복 추가하지 않음
- **예외**: `abstract`, `private`, `protected` 키워드가 이미 있는 메서드는 건드리지 않음

#### 규모

- 2,586개소, 289개 파일
- Task 1(static 추가)과 병행 처리 가능: `function` → `public static function` 또는 `public function`

---

### Task 5. `mysql_*` 드라이버 정리

#### 배경

`classes/db/DBMysql.class.php`와 `classes/db/DBMysql_innodb.class.php`는 PHP의 `mysql_*` 확장을 사용한다. 이 확장은 PHP 5.5에서 deprecated, PHP 7.0에서 완전히 제거되었다. 이미 `DBMysqli.class.php` / `DBMysqli_innodb.class.php`가 존재하므로 `mysql` 드라이버는 PHP 7.0+ 환경에서 동작 불가 상태다.

#### 수정 방법

- `DBMysql.class.php`, `DBMysql_innodb.class.php` 상단에 PHP 버전 체크 추가:
  ```php
  if(version_compare(PHP_VERSION, '7.0.0', '>=')) {
      throw new Exception('mysql driver is not supported on PHP 7.0+. Use mysqli driver instead.');
  }
  ```
- 또는: 두 파일을 삭제하고 `DB.class.php`의 드라이버 맵에서 `mysql`/`mysql_innodb` 항목 제거  
  (더 깔끔한 방안이나, 구버전 PHP 사용자가 있다면 보수적으로 오류 처리 방식을 선택)
- `DB.class.php` 드라이버 지원 목록의 우선순위에서 `mysql`(3, 4)은 유지하되 `isSupported` 체크가 PHP 버전까지 확인하도록 보강

#### 주의

- `classes/security/Password.class.php`와 `config/func.inc.php`의 `mysql_old_password`, `mysql_pre4_hash_password` 등은 MySQL 프로토콜 관련 **문자열 상수/해시 알고리즘 이름**으로, PHP 확장 함수 호출이 아님. 수정 불필요.
- `tools/dbxml_validator/connect_wrapper.php`는 개발 도구이므로 mysqli로 포팅하거나 주석 추가로 처리

---

### Task 6. 레퍼런스 반환(`&`) 제거 ✅ 완료

#### 배경

PHP4에서는 객체를 값으로 전달했기 때문에 싱글턴에서 `function &getInstance()`와 같이 레퍼런스 반환을 사용했다. PHP5 이후 객체는 기본적으로 참조 핸들로 동작하므로 이 패턴은 불필요하며, PHP 7에서 E_DEPRECATED 경고를 발생시킨다.

#### 안전성 검토

모든 대상 함수가 객체를 반환한다. PHP5+에서 객체 변수는 내부적으로 핸들(포인터)을 보유하므로, `$a = func()`와 `$a = &func()`는 동일하게 동작한다. `Extravar::getInstance()`는 매번 `new`로 새 인스턴스를 반환하므로 `&`가 더욱 불필요했다.

#### 수정 완료 내용

**정의부 수정 (함수 시그니처에서 `&` 제거 및 `public static` 추가)**

| 파일 | 수정 전 | 수정 후 |
|---|---|---|
| `classes/context/Context.class.php` | `function &getInstance()` | `public static function getInstance()` |
| `classes/cache/CacheHandler.class.php` | `function &getInstance(...)` | `public static function getInstance(...)` |
| `classes/mobile/Mobile.class.php` | `function &getInstance()` | `public static function getInstance()` |
| `classes/module/ModuleHandler.class.php` | `function &getModuleInstance(...)` | `public static function getModuleInstance(...)` |
| `classes/template/TemplateHandler.class.php` | `static public function &getInstance()` | `public static function getInstance()` |
| `classes/xml/XmlQueryParser.class.php` | `function &getInstance()` | `public static function getInstance()` |
| `classes/xml/XmlQueryParser.class.php` | `function &parse_xml_query(...)` | `public function parse_xml_query(...)` |
| `classes/db/DBMysql.class.php` | `function &getParser(...)` | `public function getParser(...)` |
| `classes/extravar/Extravar.class.php` | `function &getInstance(...)` | `public static function getInstance(...)` |
| `config/func.inc.php` | `function &getMobile(...)` | `function getMobile(...)` |

**호출부 수정 (`= &func()` → `= func()` 일괄 변환)**

대상: `DB::getInstance`, `TemplateHandler::getInstance`, `Context::getInstance`, `Mobile::getInstance`, `XmlQueryParser::getInstance`, `CacheHandler::getInstance`, `ModuleHandler::getModuleInstance`, `Extravar::getInstance`, `DB::getParser`, `getModel`, `getAdminModel`, `getMobile`

결과: **132개소** 치환, **65개 파일** 수정 (문자열 리터럴 내 패턴 포함)

#### 미수정 항목 (스코프 외)

- `classes/security/htmlpurifier/library/HTMLPurifier/Context.php` — 외부 라이브러리
- `tools/dbxml_validator/connect_wrapper.php` — 개발 도구 (`db_fetch_object`)

---

### Task 7. GLOBALS 레퍼런스 할당 정리

#### 배경

`$this->context = &$GLOBALS['__Context__']` 같은 패턴은 PHP4 시절 배열 요소를 참조로 가져오던 방식이다. PHP5 이후 배열은 여전히 값 복사이므로 이 패턴은 의미가 있지만, 객체의 경우 참조 없이도 동일 인스턴스를 가리킨다.

#### 수정 대상

주로 `classes/context/Context.class.php`의 `__construct()` 내:
```php
$this->context = &$GLOBALS['__Context__'];
$this->lang = &$GLOBALS['lang'];
```

이 패턴은 `$GLOBALS` 배열의 *요소*에 대한 레퍼런스이므로, `$GLOBALS['__Context__']`가 배열/스칼라인 경우 레퍼런스 의미가 있음. 실제 동작 확인 후 필요한 경우에만 수정 (오동작 위험이 있으므로 신중하게 처리).

---

## 수정하지 않을 항목

- `libs/` 내 외부 라이브러리 전체 (PEAR, PHPMailer, HTMLPurifier, FirePHP)
- `classes/security/htmlpurifier/` 내부
- JavaScript 파일 (`.js`)
- PHP4 스타일 생성자 (`function ClassName()`)는 이미 `__construct()`로 대체되어 있고, 잔존하는 경우는 외부 라이브러리 내부 뿐

---

## 수정 순서 (우선순위)

1. **Task 3 + Task 4** - `var $` 및 메서드 가시성: 단순 치환, 동작 변화 없음. 가장 범위가 크지만 안전함.
2. **Task 1** - static 선언: Task 4와 함께 처리 (가시성 + static을 한 번에).
3. **Task 6** - 레퍼런스 반환 제거: 호출부까지 함께 수정 필요.
4. **Task 2** - `$args` 초기화: 함수별로 의미 확인이 필요하므로 신중하게 처리.
5. **Task 5** - mysql_* 드라이버: PHP 버전 정책 결정 후 처리.
6. **Task 7** - GLOBALS 레퍼런스: 동작 분석 후 신중하게 처리.

---

## 검증

각 Task 완료 후:
- `tests/` 내 기존 테스트 실행
- PHP 5.6 및 PHP 7.4에서 `php -l`(문법 검사) 수행
- XE 설치/기본 동작(로그인, 게시판 CRUD) 수동 확인
