# PHP compatibility regression tests

The compatibility tests run through the existing Codeception bootstrap and XE
autoloaders. They execute the affected application code and check its results;
they do not search the source for a particular fix. `compatibility` is a test
group, not another copy of the test suite.

## Running the tests

```sh
./vendor/bin/codecept build
./vendor/bin/codecept run unit -g compatibility
./vendor/bin/codecept run database
# Include the existing validator, template, installation and login tests:
./vendor/bin/codecept run --env travis
```

`database` uses `tests/database.suite.dist.yml`. For local credentials, create an
ignored `tests/database.suite.yml`, in the same way as `Install.suite.yml`:

```yaml
modules:
    config:
        Db:
            dsn: 'mysql:host=127.0.0.1;dbname=xe_test'
            user: 'your_test_user'
            password: 'your_test_password'
```

Database tests use SELECT queries and connection-local temporary tables. They
do not clear application tables. A missing database is a failure, not a skipped
test. The existing **Install** suite reinstalls XE and clears `files/`; run it in
a disposable checkout and test databases, as CI does.

## PHP and Codeception versions

| PHP | Framework / test paths | CI checks |
| --- | --- | --- |
| 5.3 | No Codeception dependency installation: the root Composer requirement is PHP >= 5.4 | Syntax check of every tracked application PHP/INC file, existing standalone security regressions |
| 5.4–7.4 | Composer resolves compatible Codeception 2.x; `tests/unit`, `tests/database`, `tests/Install` | Syntax, unit, database, installation/login and security tests |
| 8.0 | Codeception 4.x, same test directories | Same checks |
| 8.1–8.6 | Codeception 5.x, same test directories | Same checks; existing 8.5/8.6 experimental status is retained |

Older Codeception uses `class_name` / `paths.helpers`; newer versions use
`actor` / `paths.support` and generated actor traits in `_support/_generated`.
Both settings already exist in the repository. `tests/_bootstrap.php` provides
the legacy `Codeception\TestCase\Test` alias where necessary. Keep new tests in
PHP 5.3 syntax; do not copy generated actor code between framework versions.

The CI syntax check excludes test tooling because the older installation tests
use PHP 5.4 array syntax. It includes bundled libraries and `.inc` files, so PHP
5.4/7 syntax and PHP 8 removed syntax cannot silently enter application code.

## Commit-to-test coverage

The table records the surviving behavior of PHP compatibility changes. Debug
instrumentation subsequently removed by `f10a64a87`, CI-only edits and retired
PHP 4/5.2 support are distinguished from application regression cases.

| Commits | Regression coverage |
| --- | --- |
| `1eab95f10`, XML rename in `b95f5b68f` | Existing `XmlParserTest`: legacy include/class alias, repeated includes, preservation of PHP's internal XMLParser. `XmlCompatibilityTest`: callbacks, Unicode, attributes, repeated nodes, static file loading and language selection. Metadata and query tests exercise parser consumers. |
| `e620b28e0` | `XmlCompatibilityTest::testXmlCallbacksDoNotUseDeprecatedObjectBinding`; `CoreCompatibilityTest::testScriptPathWithoutDeprecatedStringFilter`. Temporary debug logging was later removed. |
| `51ea8d94d` | Core Context/null/escaping tests; existing Validator condition and expression-rule tests; template execution/autoescape; metadata generation and cache reloads; FTP, PHPMailer and both PEAR versions. |
| `b95f5b68f` | Core static factory/cache/query-array behavior; Query select/update/IN SQL and nullable arguments; MySQL, InnoDB, CUBRID and MSSQL schema generation without index lists; XML consumers; metadata dates and object initialization; empty importer data; password PBKDF2. Existing Install tests cover module installation and login. |
| `17a0bc325` | Strict warning checks for absent DB/language values and frontend URL metadata; module loading without extension configuration; existing frontend file-list tests. |
| `188d54594`, mysqli portions of `f10a64a87` | `database/MysqliCompatibilityTest`: enable strict mysqli reporting before XE connects, then verify failed SQL returns XE error state instead of throwing; both drivers. |
| `ea07c15af` | Both drivers: missing-column/index lookups return false, clear error state, and permit the next SELECT. |
| `eae7a589a`, `7fbe79099` | Both drivers: prepared fetches with named columns, duplicate names including NULL, independent multi-row values, empty/indexed results, LONGTEXT, and a following query after statement close. |
| `a3eb762ba` | Parse real XML join conditions and construct TableTag/JoinConditionsTag; check joins with node/null conditions. Temporary installation tracing was subsequently removed. |
| `ecfe108ee` | Static CacheFile factory and cache update/delete round trip; real module controller skin/extra/grant writes with stdClass settings and an empty mobile configuration. |
| `e0530d640` | Module extra-variable merging with nonempty and empty stdClass values; normalization of nullable string columns and preservation of existing titles. |
| `43f4e7ff9` | Actual static ExtraVar factory call and module ID preservation. |
| `6872222b0` | Guest and administrator grants without XML grant definitions, plus an existing guest grant. Existing security regressions cover failed permission queries. |
| `93f56718d`, XmlGenerater part of `4a2bfa54d` | Static XML generation, Unicode/CDATA round trip, required resourceapi module and invalid input. `getXmlDoc` callable API is checked without contacting the download server. |
| `b76013406` | Null category lists and nested leaf nodes with null children; category order, accumulated counts and depth. |
| `4a2bfa54d` | Compiled attribute/comment foreach with null/array/stdClass; XmlJsFilter without parameters; object editor cache entries; member saving with no profile uploads; point-group template with null/array config; widget extra-variable cache initialization. |
| `f1d3d88c3` | Document/comment editor options; compile and execute the actual autoinstall sort-link section and layout extra-variable condition. |
| `ab4d6ae2e` | Missing menu config is initialized before saving the unlinked menu ID. Temporary exception diagnostics were subsequently removed. |
| `8e2f67ad8` | Guest HTML purification; sitemap recreates a missing menu cache and reuses it, accepting an empty stdClass list. |
| `5eefab8af` | Execute the actual tag section of both default and xedition board skins with null, empty and nonempty tag lists. |
| `9c8fa57ef` | Run the real SEO display trigger for an article with null and nonempty tags and inspect generated article metadata. |
| `72bda641a` | Real commentItem attribute assignment from stdClass, including legacy skin properties and a nonexistent comment. |
| `7f420a7b8` | Empty/null autoinstall responses do not query or delete stored categories. |
| `fff248399`, `a166645b8`, `dc063f1f6`, `4dadfe340`, `3d4a4d195` | Application-wide PHP/INC lint on every CI PHP version; executable guest HTML purification and IDNA round trips; existing XSS, validator, file and template tests. |
| `c9f1cd18f`, `06b1aeaf0`, `9bfa5dc8b` | PHP 5.3 application-wide lint catches reintroduced short arrays/coalescing/unsupported syntax. Request vars, board/editor consumers and execution of generated metadata caches check surviving runtime behavior. |
| `e106578fb` | Widget style without a preview node uses preview.jpg and initializes extra-variable objects; cached PHP executes. |
| `03d8631b4` | DB session reading with selected columns and absent/present session rows. |
| `86680238f`, `3d4883ef7`, `15d93075c` | Existing XSS parser tests, quoted-printable fallback test, member extra-field HTML callback replacement, and application lint on PHP 7/8. |
| `9771b1ac0`, `f4d4c3218`, `80ab9dffc`, `2579042e4` | Existing Context request/response-method tests and Core Context/static/request-variable tests. |
| `169e950e6` | Cache overwrite/read/delete test also exercises OPcache invalidation when enabled. |
| `1a5aa23fd`, `b6746d7f7`, `5b90a4684` | Application-wide PHP 5.3 lint covers editor/page array and coalescing fixes; existing installation/login exercises page rendering. Configuration-only recommended-version/constant edits are included in syntax checks. |
| `6473115ed` | Loading the query/schema fixture subclasses checks compatible inherited signatures on PHP 8; existing installation tests exercise transaction entry points. |
| `92073d05e`, `f84b690a3`, `435710b72`, `7f9aee3e7` | Existing installation/login tests, real mysqli tests, and PHP syntax checks across the supported matrix. |
| `83e68168c` | CA bundle validity is maintained by the separate update-cacert workflow (official SHA-256 and OpenSSL parsing). Tests do not require public-network HTTPS availability. |
| `b95f5b68f`, `6592a3a6a`, `ac78e9870`, `e4a0911ed`, `dbf54b9f1`, `27dd42d1c`, `bfee7e4f8`, `e52eb6f7b` (CI portions) | Preserve the version matrix, the shared bootstrap and version-specific dependency installation. All suites are discovered by the existing full Codeception command. |

These tests do not claim to cover all possible PHP compatibility issues. They
cover the identified historical failure modes; optional external DB extensions,
real FTP/SMTP servers and public download services are not needed for unit tests.
Schema tests for CUBRID/MSSQL test SQL generation, not server execution.

## Additional defects found by the regressions

- Template autoescape still expected a literal `(` before skin/layout paths after
  the earlier variable-name fix. Removed that literal from the regular expression.
- FrontEndFileHandler's relative URL branch read `$file` instead of its `$path`
  argument. Corrected the variable and made undefined-variable warnings fail the
  corresponding regression test.

## Local validation (2026-10-26)

- PHP 8.4 / Codeception 5.3.7: complete disposable-checkout run, 228 tests,
  602 assertions; one existing legacy XML alias test is skipped because PHP
  provides an internal XMLParser class. This includes installation, automatic
  installation, login, unit tests and the new database suite.
- PHP 8.4 / Codeception 4.2.2: 214 unit tests and 12 database tests pass, with
  the same expected XML alias skip.
- PHP 5.4.45 / Codeception 2.2.12: 214 unit tests and 12 database tests pass.
  The official PHP 5.4 PHAR was extracted, then its Codeception engine API was
  used because the local legacy PHP build lacks the CLI's curl/Phar extensions.
- PHP 5.3.29, 5.4.45 and 8.4.26: all 595 tracked application PHP/INC files and
  the new PHP test files pass syntax checks; 148 existing security checks pass
  on each runtime. Other PHP versions are covered by the existing CI matrix,
  not claimed as locally executed.
- Nine regression mutations were independently detected in disposable copies:
  template/comment object counts, null category counts, non-static ExtraVar,
  reversed implode arguments, missing widget object initialization, frontend
  undefined variable, named mysqli result bindings and strict mysqli exceptions.
  The original workspace source was not reverted for these checks.

## Historical audit index

The following index is based on the current branch's non-merge commit history,
matching PHP version/compatibility terms in commit messages, plus the explicit
legacy XML alias fix. Entries about retired PHP 4/5.2 or removed modules describe
historical support, not a promise to restore those runtimes. The coverage table
above groups equivalent and superseded fixes by their current behavior.

| Commit | Date | Subject |
| --- | --- | --- |
| `1eab95f10` | 2026-10-21 | Fix legacy XmlParser compatibility |
| `1e1bb8e74` | 2026-10-20 | Fix RVE-2026-17, 18, 19, 20, 21, 22, 23, 24, 25, 26,27 |
| `e52eb6f7b` | 2026-08-14 | Fix PHP 8.6 CI |
| `bfee7e4f8` | 2026-08-14 | Try php 8.6.0-dev |
| `7f420a7b8` | 2026-08-03 | Fix PHP8 CI |
| `72bda641a` | 2026-07-01 | Fix PHP 8 TypeError: count(stdClass) in comment.item.php |
| `9c8fa57ef` | 2026-07-01 | Fix PHP 8 TypeError: count(null) in seo.controller.php |
| `5eefab8af` | 2026-07-01 | Fix PHP 8 TypeError: count(null) in board read skin templates |
| `8e2f67ad8` | 2026-07-01 | Fix PHP 8 null access + auto-regenerate missing menu cache files |
| `f1d3d88c3` | 2026-07-01 | Fix PHP 8 errors: null object assignment, undefined constant, count(stdClass) |
| `4a2bfa54d` | 2026-07-01 | Fix multiple PHP 8 compatibility issues in admin/editor/widget/template |
| `b76013406` | 2026-07-01 | Fix PHP 8 TypeError: count(null) in document.model.php _arrangeCategory() |
| `93f56718d` | 2026-07-01 | Fix PHP 8: add static to XmlGenerater::generate() |
| `6872222b0` | 2026-07-01 | Fix PHP 8 TypeError: count(null) in module.model.php getGrant() |
| `43f4e7ff9` | 2026-07-01 | Fix PHP 8: add static to ExtraVar::getInstance() |
| `e0530d640` | 2026-07-01 | Fix PHP 8 TypeError: count() on stdClass in module.model.php |
| `4dadfe340` | 2026-07-01 | Fix PHP 8 parse errors: curly-brace indexing and &new operator |
| `dc063f1f6` | 2026-07-01 | Fix PHP 8.0: replace __autoload() with spl_autoload_register() |
| `a166645b8` | 2026-07-01 | Fix PHP 8 parse error: typo 'php?>' -> '?>' in htmlparser.inc |
| `ab4d6ae2e` | 2026-07-01 | Fix PHP 8 null property access in getUnlinkedMenu; add trace to install errors |
| `fff248399` | 2026-07-01 | Fix PHP 8 parse error: remove curly-brace string index syntax |
| `7fbe79099` | 2026-07-01 | Fix PHP 8 TypeError: replace mysqli_stmt_close() with OOP $stmt->close() |
| `eae7a589a` | 2026-07-01 | Fix PHP 8.1+ ArgumentCountError in mysqli_stmt_bind_result via call_user_func_array |
| `f10a64a87` | 2026-07-01 | Remove all debug/trace instrumentation added during PHP 8 investigation |
| `ecfe108ee` | 2026-07-01 | Fix PHP 8 TypeError: count(stdClass) and non-static CacheFile::getInstance |
| `a3eb762ba` | 2026-07-01 | Fix PHP 8 TypeError from count(Xml_Node_) in TableTag::isJoinTable |
| `188d54594` | 2026-07-01 | Fix PHP 8.1+ mysqli exception mode breaking XE1 error handling |
| `d3b9b5fac` | 2026-07-01 | Fix debug logging: use errstr string instead of getError() BaseObject |
| `17a0bc325` | 2026-07-01 | Fix PHP 8 undefined variable/property warnings in render path |
| `ea07c15af` | 2026-07-01 | Fix persistent DB errno causing cascade failure in module installation |
| `e620b28e0` | 2026-06-30 | Fix PHP 8.4 errors: debug getErrno typo, xml_set_object, FILTER_SANITIZE_STRING |
| `b95f5b68f` | 2026-06-29 | Fix PHP8 CI |
| `6592a3a6a` | 2026-06-29 | Add PHP8 as experimental versions |
| `51ea8d94d` | 2026-06-29 | Fix PHP8 Errors |
| `c9f1cd18f` | 2026-06-25 | php 5.3 호환성 개선 |
| `06b1aeaf0` | 2026-04-24 | php5.3 호환성 개선 |
| `5b90a4684` | 2026-03-25 | Update PHP version and clean up deprecated constants |
| `ac78e9870` | 2026-02-15 | Update CI configuration to remove PHP 8 experimental versions |
| `e4a0911ed` | 2026-02-15 | Upgrade Codeception and add module-db installation |
| `dbf54b9f1` | 2026-02-15 | Enhance CI workflow for PHP 8.x support |
| `27dd42d1c` | 2026-02-15 | Update CI workflow to support PHP 8.x versions |
| `9bfa5dc8b` | 2024-07-31 | remove null coalescing operator |
| `3d4a4d195` | 2023-10-07 | Fix some PHP8+ error |
| `e106578fb` | 2023-07-06 | PHP 호환성 개선 |
| `1a5aa23fd` | 2022-07-21 | Version up to 1.11.9 |
| `b6746d7f7` | 2022-07-20 | Version up to 1.11.8 |
| `33dbc11c8` | 2017-12-06 | travis-ci php7.2 추가 |
| `03d8631b4` | 2017-02-17 | Fix #2033 PHP 7.1 환경에서 DB 세션 사용 시 로그인 등 세션처리 문제 고침 - 필요 없는 column 확인 코드 제거 |
| `f84b690a3` | 2017-02-15 | #1995 PHP 7.x에서 설치하지 못하는 문제 고침 (1) |
| `92073d05e` | 2016-10-27 | 설치 테스트 시 PHP 7에서 삭제된 `split` 함수 대체 |
| `86680238f` | 2016-09-19 | Fix PHP HTML Parser compatibility with PHP 7 (no ereg function) |
| `9771b1ac0` | 2016-09-02 | Fix fatal error in PHP 7.1: Using $this when not in object context |
| `83e68168c` | 2016-06-27 | Fix FileHandler::getRemoteResource() inability to fetch some SSL URLs |
| `3d4883ef7` | 2015-12-21 | php7를 위한 phpmailer 수정 (추후 phpmailer 업데이트를 하면 좋겠음) |
| `15d93075c` | 2015-12-21 | php7 지원하지않는 e옵션 대체 추가된 가입폼 나오지않는 문제수정 |
| `6473115ed` | 2015-12-08 | Fix incompatible signatures of inherited methods |
| `435710b72` | 2015-08-12 | php7 환경에서 발생하는 오류 수정 |
| `f4d4c3218` | 2015-05-18 | PHP 호환성 개선 - $HTTP_RAW_POST_DATA - $_SERVER['HTTP_CONTENT_TYPE'] |
| `80ab9dffc` | 2015-05-18 | PHP 호환성 개선 - $HTTP_RAW_POST_DATA - $_SERVER['HTTP_CONTENT_TYPE'] |
| `2579042e4` | 2015-05-18 | PHP 호환성 개선 - $HTTP_RAW_POST_DATA - $_SERVER['HTTP_CONTENT_TYPE'] |
| `7f9aee3e7` | 2015-04-18 | PHP 5.4 이상에서는 magic_quotes_gpc 상태를 체크하지 않음 |
| `1af4c9521` | 2015-04-08 | #1370 불필요한 PHP4용 코드 삭제 |
| `169e950e6` | 2015-04-08 | PHP 5.5 이상에서 캐시파일 변경시 opcache_invalidate() 호출 |
| `9587c6fcb` | 2015-03-18 | #1334 안전하지 않은 PHP 버전 사용에 대한 경고 및 쉬운설치 차단 - PHP 5.3이하 버전 사용에 대한 경고문 추가 - PHP 5.3이하 버전에서 XE Core 1.8로 업데이트 차단 |
| `64c50c083` | 2015-02-08 | travis-ci PHP 5.2 지원 종료에 따른 제거 |
| `2df137e82` | 2014-11-14 | Always prefer PBKDF2 to bcrypt, for better PHP 5.2 compatibility |
| `3e198f94f` | 2014-11-14 | Always prefer PBKDF2 to bcrypt, for better PHP 5.2 compatibility |
| `33d26ca17` | 2014-08-16 | Router class 개선 및 오류 수정 |
| `8cf12f63b` | 2014-08-16 | Router class 개선 및 오류 수정 |
| `0b972b48e` | 2014-06-22 | #742 이슈 외 php54 호환성 개선. |
| `eec20ea82` | 2014-06-22 | PHP 5.4 reduce error of Create default object. |
| `cf8a88404` | 2014-05-17 | Update .travis.yml |
| `31a85815e` | 2014-04-14 | point 모듈 php 5.4 호환성 개선 |
| `af6d03287` | 2014-03-27 | php 5.4 호환성 개선 |
| `0bf580499` | 2014-03-24 | 모듈 포맷 0.1 처리 부분의 php 5.4 대응 stdClass 정의 누락 수정 |
| `809a52679` | 2014-02-08 | fix #416 PHP 5.3.6 이상에서만 사용 가능한 상수 제거 |
| `9af3c9efc` | 2014-02-08 | fix #416 PHP 5.3.6 이상에서만 사용 가능한 상수 제거 |
| `7813cb00a` | 2014-01-29 | #381 php 5.4  호환성문제 수정 |
| `d4e706fe4` | 2014-01-28 | PHP 5.4에서 발생하는 호환성 문제 수정 |
| `2a1a9960b` | 2014-01-09 | NOISSUE php 버전 호환성 문제 고침. |
| `1419a0927` | 2013-12-23 | NOISSUE PHP 버전 호환성 개선 |
| `4111957d7` | 2013-12-21 | NOISSUE PHP 버전 호환성 개선. |
| `c5c69bba4` | 2013-12-19 | NOISSUE PHP 버전 호환성 개선 |
| `2e419e2f3` | 2013-12-19 | NOISSUE PHP 버전 호환성 개선 |
| `c5db15fbe` | 2013-12-18 | NOISSUE PHP 버전 호환성 개선. |
| `7c35707f6` | 2013-12-18 | NOISSUE PHP 버전 호환성 개선. |
| `efa5ec4c0` | 2013-12-17 | NOISSUE PHP 버전 호환성 개선 |
| `dc41e3c4a` | 2013-12-17 | NOISSUE PHP 버전 호환성 개선. |
| `0ccf7bb6a` | 2013-12-17 | NOISSUE PHP 버전 호환성 개선 |
| `1b60833ba` | 2013-12-04 | #133 PHP 5.4 이상에서의 호환성 개선 |
| `7daa63ced` | 2013-12-04 | PHP 5.4 이상에서의 호환성 문제 개선. |
| `41ff8ace3` | 2013-11-30 | NOISSUE PHP 5.4 이상 호환성 개선. |
| `b85762743` | 2013-11-28 | header에 HTTP/1.1 추가 |
| `f8af9ef6c` | 2013-11-25 | NOISSUE, change some code for php5 |
| `7b09dc833` | 2013-11-24 | #99 menu, layout, module cache 파일의 PHP 호환성 개선 (PHP 5.4 or later) |
| `5693e340f` | 2013-11-13 | issue 3645, php5 대응, 함수 대체 |
| `d9f001266` | 2013-11-12 | issue 3644, php5대응 file_put_contents 함수 적용 |
| `2e8b49acc` | 2013-02-07 | issue 2119. supporting php 5.4. counter module. |
| `687fe8683` | 2013-02-07 | issue 2119. supporting php 5.4. communication module. |
| `e2affff98` | 2013-02-07 | issue 2119. supporting php 5.4. comment module. |
| `6be23a3ac` | 2013-02-06 | issue 2119. supporting php 5.4. autoinstall module. |
| `6823f75f4` | 2013-02-06 | issue 2119. supporting php 5.4. adminlogging module |
| `3df6b3ecd` | 2013-02-06 | issue 2119. supporting php 5.4. admin module. |
| `ee9247e86` | 2013-02-06 | issue 2119. supporting php 5.4. addon module. |
| `8a7c28bab` | 2013-02-05 | issue 2119. supporting php 5.4. modules and widgets. |
| `9f0e24d59` | 2013-02-05 | issue 2119. supporting php 5.4. config |
| `ba9800ff3` | 2013-02-05 | issue 2119. supporting php 5.4. widget and xml classes. |
| `19cda127e` | 2013-02-05 | issue 2119. supporting php 5.4. validator class. |
| `0973ed647` | 2013-02-05 | issue 2119. supporting php 5.4. template class. |
| `915cdb382` | 2013-02-05 | issue 2119. supporting php 5.4. object, page and security classes. |
| `2383b8d71` | 2013-02-05 | issue 2119. supporting php 5.4. module classes. |
| `9aa87c343` | 2013-02-05 | issue 2119. supporting php 5.4. mail and mobile classes. |
| `7305cebf3` | 2013-02-05 | issue 2119. supporting php 5.4. frontend file handler, handler and xe http request classes. |
| `b69abcca6` | 2013-02-05 | issue 2119. supporting php 5.4. editor, extravar and file classes. |
| `1de3e9cd1` | 2013-02-04 | issue 2119. supporting php 5.4. display classes. |
| `41fdaf00c` | 2013-02-04 | issue 2119. supporting php 5.4. db classes. |
| `7fe03148f` | 2013-02-04 | issue 2119. supporting php 5.4. context class. |
| `bd1fa3651` | 2013-02-04 | issue 2119. supporting php 5.4. |
| `669c9a594` | 2013-02-04 | issue 2119. supporting php 5.4. |
| `e01a412e3` | 2013-01-31 | User-Agent string checker update in Mobile class for PHP5 |
| `ccfaedc2f` | 2013-01-18 | issue2802 modified callback parameter. Classname\:\:function doesn`t support lower version than php5.2.3 |
| `c7fb2d5ae` | 2012-12-11 | issue 2740 add unique visitor statistics api json_encode2 function modify (Because Maserati only supports php5.2.4 above) |
| `58803a8c2` | 2012-09-17 | issue 2473, remove php5 syntax |
| `a3193e105` | 2012-05-24 | Remove php5 syntax |
| `f0034715b` | 2012-05-24 | adds comment for phpDoc and remove php5 syntax |
| `890c10dd2` | 2012-04-24 | Remove PHP5 only function from Mobile class. |
| `99669e0b7` | 2012-03-05 | remove php5 syntax |
| `092d7988b` | 2012-02-29 | php5 syntax change to php4 |
| `490f4e9c8` | 2012-02-28 | Issue 1567 some php5 syntax on branche 1.5 |
| `90b399ef7` | 2012-02-24 | issue 1516, clone a ExtraItem in DocumentModel::setToAllDocumentExtraVars(). Because, PHP5 allocate Object reference to variable when assign a Object. There are a problem that change ExtraItem in $GLOBALS['XE_EXTRA_KEYS'][$module_srl] after call DocumentModel::setToAllDocumentExtraVars(). |
| `cb8e8cbcc` | 2011-09-15 | Bug fix for compatibility of db classes with php 4. |
| `056afa28d` | 2011-07-29 | Fix - removed static class property from QueryArgument class (for PHP 4 compatibility). |
| `61e00415d` | 2011-07-14 | issue 77 Remove PHP 5.3.x deprecated features |
| `23e991bec` | 2011-06-01 | Bug fixes: DBCubrid.class.php - remove "WHERE" clause when all conditions are empty queryargument/QueryArgument.class.php and queryargument/validator/QueryArgumentValidator.class.php - this is for backwords compatibility - there are many xml files that have variable names (var) given with "." table/TableTag.class.php - autocomplete default table name alias with table name. |
| `e112ad048` | 2011-03-21 | fix r8189. Compatibility with PHP4 1. Use is_a function instead of 'instanceof' operator. 2. Use reference operator &. |
| `c67daa3cf` | 2010-05-11 | #18864041 : fixed issues on php5.3 |
| `8c0adaef2` | 2010-05-11 | make it compatible to PHP4 |
| `5600ba63c` | 2010-04-29 | #18864023 : set default time zone (for php5.3) |
| `44f5930b3` | 2009-06-20 | svn 시간값을 파싱할때 php5에서는 strtotime으로 처리 가능한 반면 php4에서는 strtotime 처리 불가능하여 time zone적용하여 제대로 시간값을 가져오도록 수정 |
| `01a374c40` | 2009-05-20 | php4, php5 에서 참조 호출 및 자기 자신의 객체 교체등에 대한 버전별 오류를 수정 |
| `35c15f5da` | 2009-03-09 | #17756508 : change newline into PHP_EOL to make it compatible to windows |
| `935d26cf8` | 2009-01-05 | #17605348 * debugPrint()에 FirePHP 연동 추가   * PHP 5.2 이상에서 작동. 만족하지 못하면 _debug_message.php 파일에 작성됨   * FirePHP 파이어폭스 플러그인 필요함. https://addons.mozilla.org/en-US/firefox/addon/6149 |
| `ef0c5cb06` | 2008-06-10 | php4에서의 호환을 위해서 static 지시자 rollback |
| `93a6bd053` | 2008-04-27 | qmail등 비표준 개행문자 사용 mta를 지원하기 위한 qmail_compatibility 변수를 db.config.php에 추가하고 관리자 페이지에서 설정토록 함 |
| `0865bddce` | 2008-01-30 | php5 이전버전일때 pdo관련 DB를 사용하지 않도록 한 코드의 잘못된 비교부분 수정 |
| `28d9bd25b` | 2008-01-16 | stripos 함수대신에 preg_match를 사용하도록 코드 변경 (stripos는 php5용) |
| `a43737877` | 2007-09-06 | 이모티콘 출력 부분에서 php5.x에서 발생하는 array_merge관련 함수때문에 코드 정리 |
| `ccd891926` | 2007-09-06 | #160. php 5.1.2이상에서 imagepng의 3번째, quality인자값이 0~9사이인데 100을 지정해서 생기는 에러 방지. 네로님께서 알려주심. |
| `ce3a27e00` | 2007-07-09 | 설치시 php 5.2.2 설치 불가로 표시. mysql의 경우 127.0.0.1보다 자주 사용되는 localhost로 DB주소 변경 (이는 DB서버 관리자의 설정인데 localhost로 더 많이 사용한다고 판단) |
