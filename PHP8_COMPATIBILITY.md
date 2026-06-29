# XpressEngine 1.11.22 — PHP 8 호환성 분석 보고서

> 작성일: 2026-06-26  
> 분석 범위: PHP 파일 609개 + HTML/TPL 템플릿 파일 409개  
> 기준 PHP 버전: 8.0 / 8.1 / 8.2 / 8.3  
> PHP 5.3 호환성 유지 수정 가능 여부를 각 항목에 병기

---

## 목차

1. [심각도 요약](#1-심각도-요약)
2. [PHP 8.0 — Fatal Error 항목](#2-php-80--fatal-error-항목)
   - 2-1. 템플릿 엔진 문자열 오프셋 `{n}` 표기
   - 2-2. `each()` 제거
   - 2-3. `create_function()` 제거
   - 2-4. `get_magic_quotes_runtime()` 제거
3. [PHP 7.0 이전 제거 — 설정에 따라 Fatal Error](#3-php-70-이전-제거--설정에-따라-fatal-error)
   - 3-1. `mysql_*` 함수 (PHP 7.0 제거)
   - 3-2. `eregi()`, `split()` (PHP 7.0 제거)
   - 3-3. `$GLOBALS['HTTP_RAW_POST_DATA']` (PHP 7.0 제거)
4. [PHP 8.1 — Deprecated (대량)](#4-php-81--deprecated-대량)
   - 4-1. `null`을 string 내장함수에 전달 — PHP 파일
   - 4-2. `null`을 string 내장함수에 전달 — 템플릿 파일
5. [PHP 8.2 — Deprecated](#5-php-82--deprecated)
   - 5-1. 동적 프로퍼티 (`ModuleHandler`)
   - 5-2. 동적 프로퍼티 (템플릿 내 객체)
6. [PHP 7.0 — Deprecated (경고만)](#6-php-70--deprecated-경고만)
   - 6-1. PHP 4 스타일 생성자
7. [버그 — PHP 버전 무관](#7-버그--php-버전-무관)
   - 7-1. `TemplateHandler` 변수명 오타
   - 7-2. `strpos() > 0` 패턴
8. [동작 변경 — 논리 오류 가능성](#8-동작-변경--논리-오류-가능성)
   - 8-1. `0 == "non-numeric-string"` 비교 (PHP 8.0)
   - 8-2. `usort()` 안정 정렬 변경 (PHP 8.0)
9. [PHP 7.2 — 실질적 영향 없음](#9-php-72--실질적-영향-없음)
   - 9-1. `mcrypt_*` 함수
10. [HTML/TPL 템플릿 파일 이슈 모음](#10-htmltpl-템플릿-파일-이슈-모음)
    - 10-1. 템플릿 엔진(`TemplateHandler`) 자체의 PHP 8 문제
    - 10-2. 템플릿 문법 구조 및 변환 방식
    - 10-3. 템플릿 파일 내 PHP 8.1 Deprecated
    - 10-4. 템플릿 파일 내 PHP 8.2 Deprecated
    - 10-5. 영향 없음으로 판정된 항목

---

## 1. 심각도 요약

| 심각도 | 항목 | 위치 | PHP 버전 | 5.3 호환 수정 |
|--------|------|------|----------|--------------|
| 🔴 **Critical** | `{n}` 문자열 오프셋 (템플릿 엔진 자체) | `TemplateHandler.class.php:651,951` | PHP 8.0 Fatal | ✅ 가능 |
| 🔴 **Critical** | `each()` — 이메일 발송 불가 | `libs/phpmailer/phpmailer.php:1645` | PHP 8.0 Fatal | ✅ 가능 |
| 🔴 **Critical** | `each()` — FTP 업로드 불가 | `libs/ftp.class.php:218` | PHP 8.0 Fatal | ✅ 가능 |
| 🔴 **Critical** | `create_function()` — 폼 검증 불가 | `classes/validator/Validator.class.php:345,626` | PHP 8.0 Fatal | ✅ 가능 |
| 🔴 **Critical** | `get_magic_quotes_runtime()` — 원격요청 불가 | `libs/PEAR.1.9.5/HTTP/Request2.php:928` | PHP 8.0 Fatal | ✅ 가능 |
| 🔴 **Critical** | `get_magic_quotes_runtime()` — 동일 | `libs/PEAR.1.9/HTTP/Request2.php:805` | PHP 8.0 Fatal | ✅ 가능 |
| 🟠 **High** | `mysql_*` 함수 (mysql 타입 설정 시) | `classes/db/DBMysql.class.php` 등 | PHP 7.0 Fatal | ✅ 가능 (mysqli로 전환) |
| 🟠 **High** | `$dirSkin` 오타 (autoescape 미작동) | `TemplateHandler.class.php:1080` | 모든 버전 버그 | ✅ 가능 |
| 🟡 **Medium** | 동적 프로퍼티 (`ModuleHandler`) | `ModuleHandler.class.php` | PHP 8.2 Deprecated | ✅ 가능 |
| 🟡 **Medium** | `null` → string 함수 (PHP 파일, 수십 곳) | 광범위 | PHP 8.1 Deprecated | ✅ 가능 |
| 🟡 **Medium** | `null` → string 함수 (템플릿, 16개+) | 광범위 | PHP 8.1 Deprecated | ✅ 가능 |
| 🟡 **Medium** | 동적 프로퍼티 (템플릿 내 객체) | `layouts/xedition/layout.html` 등 | PHP 8.2 Deprecated | ✅ 가능 |
| 🟢 **Low** | PHP 4 스타일 생성자 | `libs/ftp.class.php`, `libs/tar.class.php` 등 | PHP 7.0 Deprecated | ✅ 가능 |
| 🟢 **Low** | `usort()` 안정 정렬 변경 | 3개 파일 | PHP 8.0 동작변경 | ✅ 가능 |

---

## 2. PHP 8.0 — Fatal Error 항목

### 2-1. 템플릿 엔진 문자열 오프셋 `{n}` 표기

**파일:** `classes/template/TemplateHandler.class.php`

| 라인 | 문제 코드 |
|------|-----------|
| 651 | `if($m[1]{0} == '@')` |
| 951 | `if($mm[1]{0} == 'e')` |

`$str{n}` 방식의 문자열 인덱스 접근은 PHP 7.4에서 deprecated, **PHP 8.0에서 Fatal Error**로 격상되었다. 이 파일이 XE 요청 처리 중에 항상 로드되므로, PHP 8에서 **모든 페이지 렌더링이 실패**한다.

**PHP 5.3 호환 수정 가능: ✅**

```php
// 수정 전
if($m[1]{0} == '@')
if($mm[1]{0} == 'e')

// 수정 후 (PHP 5.3+ 호환)
if($m[1][0] == '@')
if($mm[1][0] == 'e')
```

`[]` 배열 인덱스 표기는 PHP 5.3부터 문자열에도 사용 가능하다.

---

### 2-2. `each()` 제거

PHP 7.2에서 deprecated, **PHP 8.0에서 제거**.

| 파일 | 라인 | 사용처 | 실제 로드 여부 |
|------|------|--------|---------------|
| **`libs/phpmailer/phpmailer.php`** | **1645** | `while(list(, $line) = each($lines))` | **항상 로드됨** (`Mail.class.php` → `phpmailer.php`) |
| **`libs/ftp.class.php`** | **218** | `while(list(,$value) = each($remote_list))` | **항상 로드됨** (FileHandler, admin 등) |
| `libs/PEAR.1.9.5/PEAR.php` | 750 | `while(list($k, $objref) = each(...))` | requirePear() 시 로드 가능 |
| `libs/PEAR.1.9/PEAR.php` | 788 | 동일 | requirePear() 시 로드 가능 |
| `libs/PEAR/PEAR.php` | 779 | 동일 | PHP < 5.3 환경 전용 |
| `libs/phpmailer/smtp.php` | 388, 418 | 동일 | XE core에서 직접 로드 안 함 |
| `libs/phpmailer/class.smtp.php` | 342, 369, 453 | 동일 | XE core에서 직접 로드 안 함 |

- `phpmailer.php:1645` — `EncodeQP()` 메서드 내, **이메일 발송 시 항상 실행** → PHP 8에서 이메일 발송 완전 불가
- `ftp.class.php:218` — FTP 디렉토리 확인 시 실행 → PHP 8에서 FTP 파일 전송 완전 불가

**PHP 5.3 호환 수정 가능: ✅**

```php
// 수정 전 (phpmailer.php:1645)
while(list(, $line) = each($lines)) {

// 수정 후
foreach($lines as $line) {
```

```php
// 수정 전 (ftp.class.php:218)
reset($remote_list);
while(list(,$value) = each($remote_list)) {

// 수정 후
foreach($remote_list as $value) {
```

`foreach`는 PHP 4부터 지원되므로 완전 호환.

---

### 2-3. `create_function()` 제거

PHP 7.2에서 deprecated, **PHP 8.0에서 제거**.

| 파일 | 라인 | 코드 | 실제 로드 여부 |
|------|------|------|---------------|
| **`classes/validator/Validator.class.php`** | **345** | `$func = create_function('$c', "return !!({$func_body});");` | **항상 로드됨** |
| **`classes/validator/Validator.class.php`** | **626** | `$rule['func_test'] = create_function('$a', 'return (...);');` | **항상 로드됨** |
| `libs/PEAR/HTTP/Request.php` | 921 | `create_function('$a', 'return $a[0] . \'=\' . $a[1];')` | proxy 사용 시, PHP < 5.3 환경 전용 |

`Validator.class.php`는 폼 유효성 검사의 핵심 클래스. **조건 기반 룰셋 사용 시 PHP 8에서 Fatal Error**.

**PHP 5.3 호환 수정 가능: ✅**

```php
// 수정 전 (Validator.class.php:345)
$func = create_function('$c', "return !!({$func_body});");

// 수정 후 (PHP 5.3부터 익명함수 지원)
$func = eval('return function($c) { return !!(' . $func_body . '); };');
```

```php
// 수정 전 (Validator.class.php:626)
$rule['func_test'] = create_function('$a', 'return (' . $expr . ');');

// 수정 후
$rule['func_test'] = eval('return function($a) { return (' . $expr . '); };');
```

`create_function`은 내부적으로 eval을 사용하므로 보안 수준은 동일하다. PHP 5.3부터 익명함수(`function() {}`)를 변수에 할당 가능.

---

### 2-4. `get_magic_quotes_runtime()` / `set_magic_quotes_runtime()` 제거

PHP 7.4에서 제거. **PHP 8.0에서 Fatal Error**.

| 파일 | 라인 | guard 여부 | 영향 |
|------|------|-----------|------|
| **`libs/PEAR.1.9.5/HTTP/Request2.php`** | **928–944** | **guard 없음** | **Fatal Error** |
| **`libs/PEAR.1.9/HTTP/Request2.php`** | **805–821** | **guard 없음** | **Fatal Error** |
| `classes/db/DBMysql.class.php` | 134 | `version_compare(PHP_VERSION,"5.4.0","<")` guard | 안전 |
| `classes/db/DBMysqli.class.php` | 90 | 동일 guard | 안전 |
| `classes/db/DBMysqli_innodb.class.php` | 148 | 동일 guard | 안전 |
| `classes/db/DBMssql.class.php` | 102 | 동일 guard | 안전 |
| `classes/db/DBCubrid.class.php` | 119–120 | 동일 guard | 안전 |
| `classes/context/Context.class.php` | 1459 | 동일 guard | 안전 |
| `classes/security/htmlpurifier/...` | 615 | `function_exists()` guard | 안전 |

`libs/PEAR.1.9.5/HTTP/Request2.php`는 `FileHandler::getRemoteResource()` 실행 시 로드된다. **외부 URL 원격 요청 시 PHP 8에서 Fatal Error**.

**PHP 5.3 호환 수정 가능: ✅**

```php
// 수정 전 (PEAR.1.9.5/HTTP/Request2.php:928)
if ($magicQuotes = get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(false);
}

// 수정 후 (PHP 5.3 ~ 8.x 모두 호환)
if (function_exists('get_magic_quotes_runtime') && ($magicQuotes = get_magic_quotes_runtime())) {
    set_magic_quotes_runtime(false);
}
```

동일 패턴으로 `libs/PEAR.1.9/HTTP/Request2.php`도 수정 필요.

---

## 3. PHP 7.0 이전 제거 — 설정에 따라 Fatal Error

### 3-1. `mysql_*` 함수 (PHP 7.0 제거)

| 파일 | 라인 | 코드 |
|------|------|------|
| `classes/db/DBMysql.class.php` | 76 | `$result = @mysql_connect(...)` |
| `classes/db/DBMysql.class.php` | 82–97 | `mysql_error()`, `mysql_errno()`, `mysql_select_db()` |
| `classes/db/DBMysql.class.php` | 124 | `@mysql_close($connection)` |
| `classes/db/DBMysql.class.php` | 140 | `@mysql_real_escape_string($string)` |
| `classes/db/DBMysql.class.php` | 189–193 | `mysql_query()`, `mysql_error()`, `mysql_errno()` |
| `classes/db/DBMysql.class.php` | 669 | `return mysql_insert_id($connection)` |
| `classes/db/DBMysql.class.php` | 679 | `return mysql_fetch_object($result)` |
| `classes/db/DBMysql.class.php` | 689 | `return mysql_free_result($result)` |
| `classes/db/DBMysql_innodb.class.php` | 46 | `@mysql_close($connection)` |
| `classes/db/DBMysql_innodb.class.php` | 117–121 | `@mysql_query()`, `mysql_error()`, `mysql_errno()` |
| `tools/dbxml_validator/connect_wrapper.php` | 357 | `@mysql_real_escape_string($string)` |

**영향 범위 판단:**

- `DBMysql.class.php`의 `$isSupported = function_exists('mysql_connect')` 가 PHP 7+에서 `false`이므로, **설정에서 `mysqli` 또는 `mysqli_innodb` 타입을 사용하면 런타임 호출 없음**.
- DB 설정 타입이 `mysql`(non-i)인 경우 Fatal Error 발생.
- `tools/dbxml_validator/connect_wrapper.php`는 개발자 도구로 실행 시 즉시 오류.

**PHP 5.3 호환 수정 가능: ✅**

- XE 설정에서 DB 타입을 `mysqli`로 변경하면 코드 수정 없이 회피 가능.
- `tools/dbxml_validator/connect_wrapper.php:357`은 `mysqli_real_escape_string()`으로 교체.

---

### 3-2. `eregi()`, `split()` (PHP 7.0 제거)

PHP 5.3에서 deprecated, PHP 7.0에서 제거.

| 파일 | 라인 | 코드 | 실제 로드 여부 |
|------|------|------|---------------|
| `libs/phpmailer/class.phpmailer.php` | 592 | `eregi('^(.+):([0-9]+)$', ...)` | **XE core에서 직접 로드 안 함** |
| `libs/phpmailer/class.phpmailer.php` | 470, 1723 | `split(',', $to)` 등 | **XE core에서 직접 로드 안 함** |

현재 XE는 `libs/phpmailer/phpmailer.php`(구버전)을 사용하며 `class.phpmailer.php`(신버전)을 로드하지 않는다. **직접적 영향 없음.**

**PHP 5.3 호환 수정 (필요 시):**
- `eregi(pattern, str)` → `preg_match('/pattern/i', str)` 로 교체.
- `split(',', $str)` → `explode(',', $str)` 로 교체.

---

### 3-3. `$GLOBALS['HTTP_RAW_POST_DATA']` 의존 (PHP 7.0 제거)

| 파일 | 라인 | 상태 |
|------|------|------|
| `classes/context/Context.class.php` | 204–211 | PHP 5.6+ 감지 후 `php://input`으로 보완 (부분 핸들링됨) |
| `classes/context/Context.class.php` | 1192, 1296, 1317 | 직접 참조 |
| `classes/xml/XmlParser.class.php` | 99 | 직접 참조 |

`Context::init()`이 먼저 실행되면 PHP 5.6+ 환경에서 `HTTP_RAW_POST_DATA`를 `php://input`으로 채우므로 부분 완화되지만, `XmlParser`를 Context 초기화 없이 직접 사용하는 경우 문제 가능.

**PHP 5.3 호환 수정 가능: ✅** — `$GLOBALS['HTTP_RAW_POST_DATA']` 대신 `file_get_contents('php://input')`을 사용하는 별도 래퍼 변수로 통일.

---

## 4. PHP 8.1 — Deprecated (대량)

### 4-1. `null`을 string 내장함수에 전달 — PHP 파일

PHP 8.1부터 `strpos(null, ...)`, `trim(null)`, `strtoupper(null)` 등이 `Deprecated: Passing null to parameter #1` 경고를 발생시킨다. PHP 9에서 `TypeError`(Fatal)로 격상 예정.

`Context::get(key)`는 키가 없으면 `null`을 반환하며, 이를 직접 string 함수에 전달하는 패턴이 XE 전반에 수백 곳 존재한다.

| 파일 | 라인 | 코드 |
|------|------|------|
| `addons/adminlogging/adminlogging.addon.php` | 13 | `stripos(Context::get('act'), 'admin')` |
| `classes/display/HTMLDisplayHandler.php` | 33, 66, 429 | `strpos(Context::get('act'), 'Admin')` |
| `modules/layout/layout.model.php` | 1031 | `strpos(Context::get('act'), 'Admin')` |
| `addons/captcha/captcha.addon.php` | 307 | `strtoupper(Context::get('secret_text'))` |
| `addons/captcha_member/captcha_member.addon.php` | 318 | `strtoupper(Context::get('secret_text'))` |
| `modules/comment/comment.model.php` | 709–710, 845–846 | `trim(Context::get('search_target'))` 등 |
| `modules/communication/communication.controller.php` | 70, 76 | `trim(Context::get('title'))` 등 |
| (이외 수십 개 파일) | — | 동일 패턴 |

**PHP 5.3 호환 수정 가능: ✅**

```php
// 수정 전
trim(Context::get('title'))

// 수정 후 (PHP 5.3 호환 — ??: PHP 5.3 미지원이므로 삼항 사용)
trim(isset($_Context['title']) ? $_Context['title'] : '')
// 또는 가장 효과적인 방법: Context::get() 자체를 수정
// Context::get()에서 null 대신 ''을 반환하도록 변경
```

가장 효율적인 수정: `Context::get()` 메서드에서 키가 없거나 null인 경우 빈 문자열 `''`을 반환하도록 변경. 단, `null` 여부를 체크하는 코드가 있다면 영향받을 수 있으므로 검토 필요.

---

### 4-2. `null`을 string 내장함수에 전달 — 템플릿 파일

템플릿 파일에서 객체 프로퍼티를 직접 string 함수에 전달하는 패턴. 프로퍼티가 null일 경우 PHP 8.1 Deprecated 발생.

**`trim()` 관련:**

| 파일 | 라인 | 코드 |
|------|------|------|
| `widgetstyles/simple/widgetstyle.html` | 10 | `{@ $ws_more_url = trim($widgetstyle_extra_var->ws_more_url); }` |
| `modules/admin/tpl/_dashboard_counter.html` | 33, 52 | `<!--@if(trim($value->getTitle()))-->` 등 |
| `modules/admin/tpl/_dashboard_default.html` | 26, 50 | 동일 |
| `modules/document/tpl/document_list.html` | 51 | `<!--@if(trim($oDocument->getTitleText()))-->` |
| `modules/trash/tpl/trash_list.html` | 45 | `cond="!trim($oTrashVO->getTitle())"` |
| `modules/addon/tpl/addon_info.html` | 28, 37 | `{nl2br(trim($addon_info->license))}` 등 |
| `modules/editor/tpl/view_component.html` | 28, 37 | `{nl2br(trim($component->license))}` 등 |
| `modules/module/tpl/skin_config.html` | 49, 55, 124 | `{nl2br(trim($skin_info->license))}` 등 |
| `modules/module/tpl/module_info.html` | 29, 38 | `{nl2br(trim($module_info->license))}` 등 |
| `modules/integration_search/tpl/skin_info.html` | 54, 61, 124 | 동일 |
| `modules/module/tpl/skin_info.html` | 32, 42 | 동일 |

**`strlen()` 관련:**

| 파일 | 라인 | 코드 |
|------|------|------|
| `modules/comment/tpl/comment_list.html` | 54 | `<!--@if(strlen($comment))-->` |

**`strpos()` 관련:**

| 파일 | 라인 | 코드 |
|------|------|------|
| `modules/board/tpl/board_insert.html` | 27, 75, 83 | `<!--@if(strpos($module_info->browser_title, '$user_lang->') === false)-->` 등 |
| `modules/document/tpl/extra_keys.html` | 33 | `<!--@if(strpos($selected_var->name, '$user_lang->') === false)-->` |
| `modules/layout/tpl/layout_info_view.html` | 80, 85 | `<!--@if(strpos($var->value, ...) !== false)-->` |
| `modules/module/tpl/skin_config.html` | 93, 96 | 동일 |
| `modules/page/tpl/page_info.html` | 39 | 동일 |
| `modules/seo/tpl/AdminSetting.html` | 53, 60, 72, 79 | 동일 |

**`htmlspecialchars()` 관련:**

| 파일 | 라인 | 코드 |
|------|------|------|
| `modules/addon/tpl/setup_addon.html` | 54, 55 | `{htmlspecialchars($var->value, ...)}` |
| `modules/board/m.skins/default/read.html` | 57 | `{htmlspecialchars($val->title)}` |
| `modules/board/skins/default/_trackback.html` | 10, 13 | `{htmlspecialchars($val->blog_name)}` 등 |
| `modules/board/skins/default/comment_form.html` | 15 | `{htmlspecialchars($oComment->get('content'))}` |
| `modules/board/m.skins/default/_list.html` | 43 | `{htmlspecialchars($search_keyword)}` |

**PHP 5.3 호환 수정 가능: ✅** (`??` 연산자 미사용 필요)

```php
// PHP 5.3 호환 방법 (삼항 연산자 사용)
trim(isset($value) ? (string)$value : '')
htmlspecialchars(isset($var->value) ? $var->value : '', ENT_COMPAT, 'UTF-8')
strpos(isset($module_info->browser_title) ? $module_info->browser_title : '', '$user_lang->')
```

---

## 5. PHP 8.2 — Deprecated

### 5-1. 동적 프로퍼티 (`ModuleHandler`)

PHP 8.2부터 클래스에 선언되지 않은 프로퍼티를 동적으로 할당하면 `E_DEPRECATED`가 발생한다 (`stdClass` 제외).

**영향받는 클래스:**

| 클래스/파일 | 동적으로 설정되는 프로퍼티 |
|------------|--------------------------|
| `classes/module/ModuleHandler.class.php` | `$module`, `$act`, `$mid`, `$document_srl`, `$module_srl`, `$entry`, `$error`, `$httpStatusCode`, `$module_info` |
| `modules/document/document.model.php` | `$documentConfig` (line 968) |

`ModuleHandler`는 XE 모든 요청 처리의 핵심 클래스이므로 **모든 HTTP 요청에서** PHP 8.2 Deprecated 발생.

**PHP 5.3 호환 수정 가능: ✅**

```php
// ModuleHandler.class.php 상단에 프로퍼티 선언 추가
class ModuleHandler extends Handler
{
    var $module = null;
    var $act = null;
    var $mid = null;
    var $document_srl = null;
    var $module_srl = null;
    var $entry = null;
    var $error = null;
    var $httpStatusCode = null;
    var $module_info = null;
    // ...
}
```

`var` 키워드는 PHP 4부터 PHP 8.3까지 유효하며 `public`과 동일. 완전 호환.

---

### 5-2. 동적 프로퍼티 (템플릿 내 객체)

| 파일 | 라인 | 코드 |
|------|------|------|
| `layouts/xedition/layout.html` | 22–30, 46–48, 103, 116 | `{@ $layout_info->use_demo = 'Y'}` 등 |

템플릿 내에서 `{@ ... }` 블록으로 객체 프로퍼티를 동적으로 추가하는 패턴. `$layout_info`가 `stdClass`가 아닌 일반 클래스 인스턴스이면 PHP 8.2에서 Deprecated.

**PHP 5.3 호환 수정 가능: ✅** — 대상 클래스에 `__set()` 매직 메서드를 추가하는 것이 PHP 버전 범위를 모두 커버하는 방법이다 (`#[AllowDynamicProperties]` 어트리뷰트는 PHP 8.2 전용 문법이라 5.3과 공존 불가).

---

## 6. PHP 7.0 — Deprecated (경고만)

### 6-1. PHP 4 스타일 생성자

클래스명과 동일한 이름의 메서드가 생성자로 사용되는 패턴. PHP 7.0에서 deprecated, PHP 8에서 여전히 동작하지만 `E_DEPRECATED` 발생. PHP 9에서 제거 예정.

| 파일 | 라인 | 클래스명 | 실제 사용처 |
|------|------|----------|------------|
| `libs/ftp.class.php` | 56 | `ftp::ftp()` | FileHandler, admin, autoinstall에서 `new ftp()` 사용 |
| `libs/tar.class.php` | 79 | `tar::tar()` | layout admin, autoinstall에서 `new tar()` 사용 |
| `libs/PEAR.1.9.5/PEAR.php` | 826 | `PEAR_Error::PEAR_Error()` | requirePear() 시 로드 |
| `libs/PEAR.1.9/PEAR.php` | 866 | `PEAR_Error::PEAR_Error()` | PHP < 5.3 환경 |
| `libs/PEAR/PEAR.php` | 857 | `PEAR_Error::PEAR_Error()` | PHP < 5.3 환경 |
| `libs/PEAR/HTTP/Request.php` | 288, 1140 | `HTTP_Request`, `HTTP_Response` | proxy 사용 시 |
| `libs/PEAR/Net/URL.php` | 106 | `Net_URL` | PHP < 5.3 환경 |

**PHP 5.3 호환 수정 가능: ✅**

```php
// libs/ftp.class.php:56 수정
// function ftp() → function __construct()
function __construct() {
    $this->debug = false;
    // ...
}
```

PHP 5.0부터 `__construct()` 지원. 완전 호환.

---

## 7. 버그 — PHP 버전 무관

### 7-1. `TemplateHandler` 변수명 오타

**파일:** `classes/template/TemplateHandler.class.php`  
**라인:** 1071 (정의), 1080 (사용)

```php
// 1071: 정의
$dirSkins = '(layouts\/default|layouts\/user_layout|...)';

// 1080: 사용 — 's' 누락된 오타
if(preg_match('/^(\.\/)?\(' . $dirSkin . '\//', $absPath))
//                                   ^^^^^^^^ 미정의 변수 ($dirSkins 가 맞음)
```

`isAutoescape()` 함수가 skin 경로에 대해 항상 `false`를 반환하게 되어 **autoescape 기능이 완전히 미작동**한다.

**PHP 5.3 호환 수정 가능: ✅** — `$dirSkin` → `$dirSkins`로 변경.

---

### 7-2. `strpos() > 0` 패턴

```php
// classes/display/HTMLDisplayHandler.php:66
if(Context::get('module') != 'admin' && strpos(Context::get('act'), 'Admin') > 0 ...)
```

`'Admin'`이 문자열 첫머리에 있을 때 `strpos()`가 `0`을 반환하고 `0 > 0`이 `false`가 되어 의도하지 않은 동작이 발생한다. PHP 버전과 무관한 기존 버그.

**PHP 5.3 호환 수정 가능: ✅** — `strpos(...) > 0` → `strpos(...) !== false`

---

## 8. 동작 변경 — 논리 오류 가능성

### 8-1. `0 == "non-numeric-string"` 비교 (PHP 8.0)

PHP 7: `0 == "admin"` → `true`  
PHP 8: `0 == "admin"` → `false` (string이 0으로 변환되지 않고 "admin"이 int로 변환됨)

XE의 에러코드 시스템 (`BaseObject::$error`)은 항상 정수형으로 설정되므로 `$this->error == 0` 비교는 안전하다. 사용자 코드나 서드파티 에드온에서 에러코드를 비숫자 문자열로 설정하는 경우에만 영향받는다.

**PHP 5.3 호환 수정 가능: ✅** — `==` 대신 `===` 사용 또는 `(int)` 명시적 형변환.

---

### 8-2. `usort()` 안정 정렬 변경 (PHP 8.0)

PHP 8.0부터 `usort()`, `uasort()`, `uksort()`가 **안정 정렬(stable sort)**로 변경. 동일한 비교값을 가진 원소의 상대 순서가 PHP 7과 달라질 수 있다.

| 파일 | 함수 | 비교 기준 |
|------|------|-----------|
| `classes/db/DB.class.php:307` | `_sortDBMS()` | DBMS priority (정수) |
| `modules/layout/layout.admin.view.php:107` | `sortLayoutInstance()` | title (문자열) |
| `modules/layout/layout.model.php:365` | `sortLayoutByTitle()` | title (문자열) |

세 콜백 모두 명확한 비교값을 반환하므로 실질적인 동작 변경은 미미하다. 동일 title 레이아웃의 표시 순서만 달라질 수 있다.

---

## 9. PHP 7.2 — 실질적 영향 없음

### 9-1. `mcrypt_*` 함수

| 파일 | 라인 | 코드 |
|------|------|------|
| `classes/security/Password.class.php` | 254–260 | `mcrypt_create_iv(...)` |

`function_exists('mcrypt_create_iv')` 로 guard되어 있으므로 PHP 7.2+에서 `mcrypt_create_iv()` 분기는 실행되지 않고 openssl fallback을 사용한다. **실질적 영향 없음**.

---

## 10. HTML/TPL 템플릿 파일 이슈 모음

> XE 템플릿은 `TemplateHandler.class.php`가 파싱하여 PHP 코드로 변환된다.  
> 분석 대상: `*.html`, `*.tpl` 파일 409개 + 템플릿 엔진 `TemplateHandler.class.php`

### 템플릿 문법 → PHP 코드 변환 대응표

| 템플릿 문법 | 변환 결과 |
|---|---|
| `{@ phpcode }` | `<?php phpcode ?>` |
| `{$var}` | `<?php echo $__Context->var ?>` (autoescape 적용) |
| `{func($arg)}` | `<?php echo func($__Context->arg) ?>` |
| `<!--@if(...)-->` | `<?php if(...){ ?>` |
| `<!--@foreach(...)-->` | `<?php foreach(...){ ?>` |
| `<!--@end-->` | `<?php } ?>` |
| `loop="$arr => $k,$v"` (HTML 속성) | `<?php foreach($arr as $k=>$v){ ?>` |
| `cond="$expr"` (HTML 속성) | `<?php if($expr){ ?>` |

`{$var}`는 내부적으로 `$__Context->var`로 치환되며, `$__Context`는 `stdClass` 인스턴스이므로 동적 프로퍼티 접근이 PHP 8.2에서도 허용된다.

---

### 10-1. 템플릿 엔진(`TemplateHandler`) 자체의 PHP 8 문제

**🔴 PHP 8.0 Fatal Error — 문자열 오프셋 `{n}` 표기**  
→ 상세 내용: [2-1절 참조](#2-1-템플릿-엔진-문자열-오프셋-n-표기)

| 파일 | 라인 | 문제 코드 | 영향 |
|------|------|-----------|------|
| `classes/template/TemplateHandler.class.php` | 651 | `if($m[1]{0} == '@')` | 모든 페이지 렌더링 불가 |
| `classes/template/TemplateHandler.class.php` | 951 | `if($mm[1]{0} == 'e')` | 동일 |

수정: `{0}` → `[0]` (PHP 5.3 호환)

**🟠 버그 — `$dirSkin` 변수명 오타 (autoescape 미작동)**  
→ 상세 내용: [7-1절 참조](#7-1-templatehandler-변수명-오타)

| 파일 | 라인 | 문제 |
|------|------|------|
| `classes/template/TemplateHandler.class.php` | 1071 | `$dirSkins`로 정의 |
| `classes/template/TemplateHandler.class.php` | 1080 | `$dirSkin`으로 참조 (s 누락 오타) → `isAutoescape()` 항상 false 반환 |

수정: `$dirSkin` → `$dirSkins` (PHP 5.3 호환)

---

### 10-2. 템플릿 문법 구조 및 변환 방식

`{@ ... }` 블록은 그대로 PHP 코드로 출력되므로, 블록 안에 PHP 8 비호환 코드가 있으면 그대로 문제가 된다.  
분석 결과 `{@ }` 블록 내에서 직접 사용된 `each()`, `ereg()`, `create_function()` 등 제거된 함수는 발견되지 않았다.

`{func($arg)}` 패턴에서 `money_format`, `convert_cyr_string` 등 PHP 8.0에서 제거된 함수를 직접 호출하는 사례도 발견되지 않았다.

---

### 10-3. 템플릿 파일 내 PHP 8.1 Deprecated

**🟡 `null`을 string 내장함수에 전달**  
→ 상세 내용: [4-2절 참조](#4-2-null을-string-내장함수에-전달--템플릿-파일)

PHP 8.1부터 `trim(null)`, `strlen(null)`, `strpos(null, ...)`, `htmlspecialchars(null)` 등이 Deprecated.  
PHP 9에서 `TypeError`(Fatal)로 격상 예정.

영향 파일 요약:

| 함수 | 영향 파일 수 | 대표 파일 |
|------|------------|---------|
| `trim()` | 11개 | `modules/addon/tpl/addon_info.html`, `modules/admin/tpl/_dashboard_counter.html` 등 |
| `strlen()` | 1개 | `modules/comment/tpl/comment_list.html:54` |
| `strpos()` | 8개 | `modules/board/tpl/board_insert.html`, `modules/seo/tpl/AdminSetting.html` 등 |
| `htmlspecialchars()` | 5개 | `modules/addon/tpl/setup_addon.html`, `modules/board/m.skins/default/read.html` 등 |

PHP 5.3 호환 수정:
```php
// trim(null) → 삼항 연산자 사용 (?? 는 PHP 7+이므로 미사용)
trim(isset($val) ? (string)$val : '')

// strpos(null, ...) →
strpos(isset($module_info->browser_title) ? $module_info->browser_title : '', '$user_lang->')

// htmlspecialchars(null, ...) →
htmlspecialchars(isset($var->value) ? $var->value : '', ENT_COMPAT, 'UTF-8')
```

---

### 10-4. 템플릿 파일 내 PHP 8.2 Deprecated

**🟡 동적 프로퍼티 — 템플릿 `{@ }` 블록 내 객체 프로퍼티 동적 추가**  
→ 상세 내용: [5-2절 참조](#5-2-동적-프로퍼티-템플릿-내-객체)

| 파일 | 라인 | 코드 |
|------|------|------|
| `layouts/xedition/layout.html` | 22–30 | `{@ $layout_info->use_demo = 'Y'}` 등 |
| `layouts/xedition/layout.html` | 46–48, 103, 116 | 동일 패턴 |

`$layout_info`가 `stdClass`이면 PHP 8.2에서도 문제없으나, 일반 클래스 인스턴스이면 `E_DEPRECATED`.  
PHP 5.3 호환 수정: 대상 클래스에 `__set()` 매직 메서드 추가 (`#[AllowDynamicProperties]`는 PHP 8.2 전용 문법이라 5.3과 공존 불가).

---

### 10-5. 영향 없음으로 판정된 항목

| 항목 | 이유 |
|------|------|
| `${varName}` 문자열 보간 (PHP 8.2 Deprecated) | 템플릿 내 `${...}` 패턴은 모두 `<script type="text/x-jquery-tmpl">` 내부의 JavaScript 템플릿 문법으로, PHP가 파싱하지 않음 |
| `== 0` 느슨한 비교 (PHP 8.0 동작 변경) | 템플릿 내 `== 0` 비교는 모두 숫자형 컨텍스트(`depth`, `error`, `api_handler`)라 실질적 영향 없음 |
| `ereg()`, `mysql_*`, `create_function()` 등 제거 함수 | 템플릿 파일에서 직접 호출하는 사례 없음 |
| JavaScript `split()` 사용 | `<script>` 태그 내 JS 코드로 PHP와 무관 |

---

## 부록: 수정 우선순위

### 즉시 수정 필요 (PHP 8에서 Fatal Error)

1. `classes/template/TemplateHandler.class.php:651,951` — `{0}` → `[0]`  
   → **모든 페이지 렌더링 불가**. 2줄 수정으로 해결.

2. `libs/phpmailer/phpmailer.php:1645` — `each()` → `foreach()`  
   → **이메일 발송 완전 불가**. 1줄 수정.

3. `libs/ftp.class.php:218` — `each()` → `foreach()`  
   → **FTP 파일 전송 완전 불가**. 2줄 수정.

4. `classes/validator/Validator.class.php:345,626` — `create_function()` → `eval()+closure`  
   → **폼 유효성 검사 룰셋 사용 불가**. 2줄 수정.

5. `libs/PEAR.1.9.5/HTTP/Request2.php:928` — `function_exists()` guard 추가  
   → **외부 URL 원격 요청 불가**. 1줄 수정.

6. `libs/PEAR.1.9/HTTP/Request2.php:805` — 동일.

### 권장 수정 (경고/Deprecated, PHP 9 대비)

7. `classes/template/TemplateHandler.class.php:1080` — `$dirSkin` → `$dirSkins` 오타 수정
8. `classes/module/ModuleHandler.class.php` — 프로퍼티 명시적 선언 추가 (PHP 8.2 Deprecated)
9. `libs/ftp.class.php`, `libs/tar.class.php` — PHP4 스타일 생성자 `__construct()`로 변경
10. `null` → string 함수 전달 패턴 — `Context::get()` 반환값 기본값 처리

---

## 결론

**모든 이슈는 PHP 5.3 호환성을 유지하면서 수정 가능하다.**

| 수정 방법 | PHP 최소 버전 |
|-----------|--------------|
| `each()` → `foreach()` | PHP 4 |
| `{n}` → `[n]` 문자열 인덱스 | PHP 5.3 |
| `function_exists()` guard 추가 | PHP 4 |
| `var $prop = null;` 프로퍼티 선언 | PHP 4 |
| `function __construct()` 생성자 | PHP 5.0 |
| `create_function()` → `eval('return function(){};')` | **PHP 5.3** (익명함수 도입) |
| 명시적 string 캐스팅 `(string)$val` | PHP 4 |

유일한 주의사항: `create_function()` → eval+익명함수 교체는 **PHP 5.3 미만 불가**. 그러나 PHP 5.2는 2011년 공식 지원 종료이므로 실질적으로 고려 불필요.
