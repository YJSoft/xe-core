# PDO MySQL 드라이버 추가 설계안

작성일: 2026-10-30. 상태: 구현 및 신규 설치 기본 사용 전환.

**XE의 기존 DB 인터페이스를 유지하는 PDO MySQL 드라이버를 추가했으며, 설치 화면에 별도 `MySQL PDO (beta)` 선택지로 제공한다.** PDO 연결 코드를 만드는 것보다 XML 쿼리의 매개변수, 결과 형태, 오류 처리와 기존 설정의 호환성을 맞추는 일이 중요하다.

기본 `mysql`은 mysqli → 레거시 mysql 순서를 유지한다. PDO는 별도 beta 항목을 명시적으로 선택한 경우에만 사용한다. 서드파티 자료의 호환성 문제가 모두 해결되었다고 확인한 뒤 기본 `mysql`의 우선순위를 PDO MySQL → mysqli → mysql로 올린다. 엔진은 InnoDB를 우선하고 사용할 수 없을 때만 MyISAM을 사용한다. 기존 사이트의 저장된 드라이버는 자동 전환하지 않는다. PHP 5.3.0 이상과 MySQL 4.1 이상이라는 기존 요구 사항을 이 문서에서 변경하지 않는다.

## 1. Rhymix에서 확인한 구조

조사 기준은 Rhymix `master`의 [커밋 `6234bfd2bc133286113421e8a525f38a9fa3eaf5`](https://github.com/rhymix/rhymix/commit/6234bfd2bc133286113421e8a525f38a9fa3eaf5), 버전 2.1.39이다. 아래 설명은 이 시점의 구조에 한정한다.

**현재 Rhymix는 mysqli와 PDO를 선택하는 구조가 아니다.** `classes/db`에는 호환용 `DB.class.php`만 있으며, 실제 DB 처리는 PDO MySQL을 사용하는 프레임워크 계층이 맡는다. 지원 목록과 설치 환경 검사도 `pdo_mysql` 확장을 기준으로 한다. [호환 클래스](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/classes/db/DB.class.php), [지원 목록](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/DB.php#L1494-L1539), [설치 검사](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/modules/install/install.controller.php#L291-L318).

| 확인한 부분 | Rhymix의 방식 | XE에서 참고할 점 |
| --- | --- | --- |
| DB 진입점 | 기존 `DB` 이름을 프레임워크 DB 클래스에 연결 | 호출부의 DB 인터페이스를 보존하는 접근 |
| 연결 | MySQL DSN에 접속 정보와 문자셋을 지정 | 서버 종류와 PHP 연결 확장을 구분 |
| 실행 | 매개변수가 있으면 prepare/execute, 없으면 직접 실행 | XML 쿼리의 값과 SQL 구조를 분리 |
| PDO 설정 | 예외 모드, 에뮬레이션 비활성화, 비버퍼링 사용 | 설정을 기본값에 맡기지 않되 XE의 사용 방식에 맞게 선택 |
| 연결·문장 보조 계층 | PDO와 PDOStatement를 확장하여 실행·오류·로그 처리 | XE에서도 모든 실행 경로의 오류와 로그를 일관되게 처리 |
| 결과 | 조회 결과와 오류를 상위 DB 결과 객체로 정리 | PDOStatement를 기존 모듈에 직접 노출하지 않기 |
| 트랜잭션 | 중첩 단계와 SAVEPOINT 처리 | XE의 기존 중첩 카운터 계약에 맞추기 |

근거: [DB 연결·직접 실행](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/DB.php#L119-L258), [DBHelper](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/helpers/DBHelper.php), [DBStmtHelper](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/helpers/DBStmtHelper.php), [DBResultHelper](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/helpers/DBResultHelper.php), [결과 처리·트랜잭션](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/common/framework/DB.php#L547-L715).

XE에서는 처음부터 Rhymix와 같은 전면 교체를 하지 않는다. PHP 지원 범위와 기존 드라이버·확장 모듈의 계약이 다르므로, 기존 SQL 생성기를 활용하는 추가 드라이버부터 구현한다. Rhymix의 비버퍼링, 문자셋 자동 확대, 프레임워크 클래스 구조를 그대로 채택할 필요도 없다.

## 2. 독립 구현 원칙

이 저장소의 [LICENSE](../LICENSE)는 LGPL 2.1 이상이고, Rhymix는 [프로젝트 설명](https://github.com/rhymix/rhymix/blob/6234bfd2bc133286113421e8a525f38a9fa3eaf5/README.md)에서 GPL v2 이상을 명시한다. 사용자가 요청한 비복제 원칙에 따라 다음 방식으로 작업한다.

1. Rhymix는 구조와 관찰 가능한 동작을 확인하는 참고 자료로만 사용한다.
2. Rhymix의 함수 본문, 주석, 보조 클래스, 테스트, 패치를 가져오거나 이름·문법만 바꿔 이식하지 않는다.
3. 실제 구현은 이 저장소의 `DB`, `DBMysql`, XML 쿼리 객체와 PHP 공식 PDO 문서의 API 계약을 기준으로 작성한다.
4. 재사용하는 코드는 이 저장소 안의 기존 코드로 한정하고, 기존 저작권 표기를 유지한다.
5. 구현 PR에는 참고한 외부 링크와 자체 설계 결정을 기록한다. 이 문서 자체에도 Rhymix의 구현 코드를 싣지 않는다.

## 3. 설정과 설치 정책

### 드라이버 이름

초기 구현에서는 기존 설정 형식을 유지하고 다음 두 내부 드라이버를 추가한다.

| 저장할 `db_type` | 연결 API | 새 테이블의 엔진 |
| --- | --- | --- |
| `pdo_mysql` | PDO MySQL | MyISAM |
| `pdo_mysql_innodb` | PDO MySQL | InnoDB |

구현 파일은 `classes/db/DBPdo_mysql.class.php`, `classes/db/DBPdo_mysql_innodb.class.php`이다. 현재 [DB 팩토리와 파일 검색](../classes/db/DB.class.php)의 이름 규칙에 맞추기 위한 명명이며, 구현 시 팩토리·자동 검색 양쪽에서 확인한다. PDO 접속용 DSN의 접두사는 내부 드라이버명과 달리 `mysql:`이다. [PHP DSN 문서](https://www.php.net/manual/en/ref.pdo-mysql.connection.php).

연결 API와 스토리지 엔진을 별도 설정으로 분리하는 장기 설계도 가능하지만, 처음부터 기존 네 드라이버의 설정 형식까지 바꾸지는 않는다.

### 단계별 우선순위

| 단계 | 신규 설치 정책 | 기존 설치 정책 |
| --- | --- | --- |
| 현재: beta 검증 | 기본 `mysql`은 mysqli → mysql, 별도 `MySQL PDO (beta)`는 명시 선택 | 저장된 드라이버로 연결 |
| 향후: 호환성 검증 완료 후 기본 사용 전환 | `PDO MySQL → mysqli → mysql` 순서로 자동 선택 | 자동 전환하지 않음 |
| 기존 사이트의 PDO 전환 | 해당 없음 | 관리자가 명시적으로 전환하고 되돌릴 설정을 보관 |

현재 PDO는 기본 선택이 아니다. 설치 화면에서 `MySQL PDO (beta)`를 선택하거나 자동 설치 설정에 `pdo_mysql`을 명시해야 한다. PDO만 설치된 환경에서도 beta 항목을 자동으로 미리 선택하지 않는다. 코어 테스트 통과만으로 서드파티 호환성이 모두 확인되었다고 보지 않으며, 그 검증이 끝나기 전까지 기본 `mysql`의 우선순위를 유지한다.

여기서 차선 선택은 **PHP 확장의 부재나 명시적인 지원 조건 불충족**에 대한 것이다. PDO를 선택한 뒤 인증 실패·접속 오류·SQL 오류가 났다는 이유로 mysqli/mysql로 몰래 재시도하지 않는다. 특히 쓰기 쿼리의 성공 여부가 불명확할 때 다른 드라이버로 재실행해서는 안 된다.

엔진 판정은 현재 신규 설치 로직을 활용한다. 접속 후 `SHOW ENGINES`의 `YES`/`DEFAULT`와 비활성화 설정을 확인하고 InnoDB, MyISAM 순서로 고른다. 두 엔진 모두 불가하거나 검사 자체가 실패하면 오류를 반환한다. API 선택과 엔진 선택은 각각 수행한다.

### 기존 설정 보존

- 기존 `mysql`, `mysql_innodb`, `mysqli`, `mysqli_innodb` 설정은 PDO 확장을 설치해도 자동 변경하지 않는다.
- 기존 MyISAM 사이트를 PDO로 바꿀 때는 `pdo_mysql`로 연결한다. InnoDB 사이트는 `pdo_mysql_innodb`를 사용한다.
- PDO 추가로 테이블 엔진이나 문자셋을 자동 변환하지 않는다. 혼합 엔진 DB의 테이블도 그대로 둔다.
- 저장된 PDO 드라이버를 쓸 수 없는 환경에서는 명확한 오류를 반환한다. 다른 연결 API로 자동 우회하지 않는다.

## 4. PHP 버전과 연결 설정

### PHP 5.3 유지와 PDO의 적용 범위

새 드라이버의 애플리케이션 코드는 PHP 5.3 문법으로 작성한다. scalar/return type 선언, 짧은 배열, `finally`, `Throwable`에 의존하지 않는다. PHP 8.4 전용 `Pdo\Mysql` 클래스를 전제로 하지 않고 기존 `PDO` API를 사용한다.

문자셋에는 별도 경계가 있다. PDO MySQL의 DSN `charset` 처리는 PHP 5.3.6에서 지원되었으므로, PHP 5.3.0~5.3.5를 최신 PDO와 같게 취급할 수 없다. [PHP 공식 버그 기록](https://bugs.php.net/bug.php?id=54960).

구현에서는 **PDO 경로의 활성화 조건을 PHP 5.3.6 이상으로 잡고**, 5.3.0~5.3.5에서는 기존 mysqli/mysql 경로를 유지하는 것이다. 이는 프로젝트 전체의 PHP 최소 버전 변경이 아니다. 그 이전 버전에서도 PDO가 반드시 필요하다면 초기화 명령, 실제 연결 문자셋, 인용 함수와 prepare 동작을 별도로 구현·검증한 후 활성화한다. `SET NAMES` 한 줄 추가만으로 문자셋 처리가 검증되었다고 보지 않는다.

### 권장 초기 설정

| 항목 | 초기 설계 |
| --- | --- |
| 지원 여부 | `PDO` 존재와 `PDO::getAvailableDrivers()`의 `mysql` 등록을 확인 |
| 접속 | 기존 host/port/database/user/password를 DSN 및 생성자 인수에 매핑 |
| 문자셋 | 현재 XE와 동일한 `utf8`; 새 드라이버 도입과 utf8mb4 전환을 분리 |
| 오류 | `PDO::ERRMODE_EXCEPTION`을 명시하고 XE 드라이버 경계에서 변환 |
| prepared statement | `PDO::ATTR_EMULATE_PREPARES = false`를 명시하고 실제 서버 조합 검증 |
| 조회 버퍼 | 초기에는 버퍼링 사용. 비버퍼링은 별도 성능·호환성 검증 후 검토 |
| 영구 연결 | 초기에는 비활성화 |
| 다중 문장 | 불필요한 multi-statement 실행을 허용하지 않도록 지원 옵션 확인 |

PDO MySQL은 기본적으로 prepare 에뮬레이션을 사용한다. PHP 버전에 따라 오류 모드 기본값도 달라지므로 이를 명시한다. 에뮬레이션 비활성화 요청만으로 모든 서버·문장이 항상 native prepare로 처리된다고 단정하지 않는다. [PDO MySQL](https://www.php.net/manual/en/ref.pdo-mysql.php), [PDO 오류 처리](https://www.php.net/manual/en/pdo.error-handling.php), [PDO::prepare](https://www.php.net/manual/en/pdo.prepare.php).

버전별로 없는 PDO 상수는 사용 전에 확인한다. DSN에 넣는 값은 세미콜론 등 구분자가 다른 설정을 끼워 넣지 못하도록 검증하고, 비밀번호는 DSN 문자열에 합치지 않는다. `localhost`의 Unix socket 의미와 `127.0.0.1`의 TCP 연결 차이도 보존한다. [PDO MySQL DSN](https://www.php.net/manual/en/ref.pdo-mysql.connection.php).

## 5. 클래스 구성과 구현 범위

최소 변경안은 `DBPdo_mysql extends DBMysql`, `DBPdo_mysql_innodb extends DBPdo_mysql`이다. 이때 **SQL 생성 기능은 활용하되 mysql 함수에 의존하는 메서드는 전부 대체**해야 한다. `DBMysqli`를 상속하면 PDO-only 환경에서도 mysqli 호출이 남기 쉬우므로 피한다.

| 메서드·기능 | PDO 드라이버에서 필요한 작업 |
| --- | --- |
| 생성자·`create()`·지원 검사 | 연결하지 않는 생성 경로 보존, PDO 확장이 없어도 파일 로드 가능 |
| `__connect()` | DSN, 옵션, 예외 처리와 문자셋 초기화 |
| `_afterConnect()` | 부모의 문자셋 초기화와 중복되지 않게 정리 |
| `__query()` | 직접 SQL과 prepared SQL 실행, PDOStatement 반환 및 오류 변환 |
| CRUD 실행 메서드 | 기존 Query 객체에서 SQL과 매개변수를 얻어 실행 |
| `_fetch()`·`db_fetch_object()` | XE의 결과 형태와 중복 열 이름 정책 보존 |
| `db_free_result()` | `closeCursor()` 등 결과 수명 관리 |
| `addQuotes()` | PDO의 인용 기능을 기존 XE 호출 계약에 맞춤 |
| `db_insert_id()` | 같은 master 연결의 `lastInsertId()` 사용 |
| `_begin()`·`_commit()`·`_rollback()` | InnoDB의 실제 트랜잭션과 SAVEPOINT 연결 |
| `close()`·`_close()` | 연결 상태뿐 아니라 PDO/statement 객체 참조도 해제 |
| `_createTable()` | MyISAM/InnoDB를 구분하되 기존 XML 스키마 결과 보존 |

MySQL의 스키마 생성기를 또 통째로 복제하지 않도록, 기존 `DBMysql::_createTable()`의 엔진 선택 부분만 작은 메서드로 분리하는 방법을 권장한다. PDO InnoDB 하위 클래스는 허용된 엔진 이름만 반환하고 트랜잭션 동작을 구현한다. 기존 드라이버의 SQL 생성 결과를 먼저 고정한 뒤 공통화한다.

지원 검사도 주의한다. 현재 `DB::isSupported()`는 `self::$isSupported`를 읽고 여러 하위 클래스가 같은 상속 속성을 설정한다. 신규 PDO 드라이버에서는 부모의 공유 플래그를 덮어쓰지 말고, 독립된 지원 검사를 구현한다. PDO를 로드한 순서에 따라 다른 드라이버의 가용성이 달라지면 안 된다.

## 6. SQL·매개변수·결과 계약

### XML 쿼리와 prepare

기존 [Query 객체](../classes/db/queryparts/Query.class.php), [조건의 placeholder 생성](../classes/db/queryparts/condition/ConditionWithArgument.class.php), [mysqli 매개변수 처리](../classes/db/DBMysqli.class.php)를 기준으로 어댑터를 설계한다.

- SQL을 생성한 뒤 문자열 치환으로 값을 바인딩하지 않는다. SQL과 순서가 일치하는 매개변수 목록을 함께 구성한다.
- 기존 위치 매개변수 `?`를 활용한다. `IN` 배열은 원소마다 placeholder를 만들고 값을 같은 순서로 펼친다.
- 열 이름·테이블 이름·정렬 방향은 값으로 바인딩할 수 없다. 기존 SQL 생성기의 허용 목록·식별자 처리 경로를 사용한다.
- NULL은 NULL, 정수는 표현 가능한 범위에서 정수로 바인딩한다. BIGINT와 소수는 PHP 정수 범위를 넘거나 정밀도를 잃지 않도록 문자열 처리를 검증한다. PDO에는 별도의 범용 float 매개변수 타입이 없다.
- `execute($params)`의 기본 타입 변환에만 맡기지 말고 필요한 타입에는 `bindValue()`를 명시한다. LIKE, 빈 배열, NULL 조건과 숫자 모양 문자열을 포함해 검증한다.
- prepare/execute 실패를 인용 문자열 SQL로 재시도하지 않는다.
- 직접 SQL, DDL, `SHOW` 문은 Query 객체의 이전 매개변수를 재사용하지 않는다. 실패 후에도 임시 매개변수 상태를 정리한다.
- 페이지 조회의 COUNT와 본 조회는 각각 필요한 매개변수 집합이 다를 수 있다. SELECT 식 등에 값이 들어가는 경우까지 포함해 개수와 순서를 확인한다.

값 전체만 placeholder가 될 수 있고 식별자나 `IN` 목록 전체를 매개변수 하나로 대체할 수 없다는 제약은 [PDO::prepare](https://www.php.net/manual/en/pdo.prepare.php)를, 타입 지정은 [PDOStatement::bindValue](https://www.php.net/manual/en/pdostatement.bindvalue.php)를 따른다.

`use_prepared_statements` 설정의 기존 의미도 보존한다. `Y`일 때 XML 값 바인딩 경로를 사용하고, `N`인 기존 호출·직접 SQL 경로도 정상 동작해야 한다. PDO 추가를 계기로 이 설정을 무시하거나 모든 SQL을 강제로 prepare하는 변경은 별도로 다룬다.

### 반환 형태

기존 `_fetch()` 계약을 유지한다. 일반 조회는 빈 결과에 빈 배열, 한 행에 객체, 여러 행에 객체 배열을 반환한다. 페이지 인덱스 인수가 지정된 경우에는 한 행도 배열이며, 기존 가상 번호와 감소 순서를 보존한다. 상위 `executeQuery()`는 계속 `BaseObject`와 `data`, 페이지 정보, 실행 시간 정보를 반환한다. DDL·INSERT 등 결과 열이 없는 문장에는 행 조회를 시도하지 않는다.

`PDO::FETCH_OBJ`만 적용하면 중복 열 이름이 덮어써질 수 있다. 현재 mysqli prepared 경로가 보존하는 원래 열과 `repeat_` 열의 결과를 기준으로, 숫자 인덱스 행 조회와 열 메타데이터로 객체를 구성하는 방법을 검토한다. NULL, LONGTEXT, 여러 행 사이의 값 독립성, 숫자의 타입 차이도 비교한다. `getColumnMeta()`의 지원 범위는 PHP/PDO MySQL 조합별로 확인한다. [PDO 열 메타데이터](https://www.php.net/manual/en/pdostatement.getcolumnmeta.php).

조회 건수를 `PDOStatement::rowCount()`로 계산하지 않는다. 기존 COUNT 쿼리를 유지한다. 조회가 끝나거나 실패하면 커서를 정리하고 다음 쿼리가 실행되는지 확인한다. Rhymix처럼 이전 문장을 일괄 닫는 정책은 XE의 외부 결과 순회와 충돌할 수 있으므로 그대로 적용하지 않는다. [rowCount](https://www.php.net/manual/en/pdostatement.rowcount.php), [closeCursor](https://www.php.net/manual/en/pdostatement.closecursor.php).

`addQuotes()`는 이름과 달리 기존 호출부에서 바깥따옴표를 붙이는 경우가 있으므로 `PDO::quote()`의 전체 반환값을 바로 반환하면 안 된다. 호출 계약을 유지하는 별도 어댑터로 만들고, 일반 값은 바인딩을 우선한다. 따옴표·역슬래시·멀티바이트 문자와 SQL 모드에 대한 회귀 검증이 필요하다.

### 오류·트랜잭션·연결 수명

PDOException은 드라이버 안에서 잡아 기존 `setError()`/`getError()`/`BaseObject` 흐름으로 변환한다. SQLSTATE 문자열을 그대로 정수 `errno`처럼 취급하지 않는다. MySQL 오류 번호가 있으면 보존하고, 없으면 XE의 일반 오류 코드와 보조 진단 정보로 정리한다. 문장 실행 오류는 연결의 errorInfo만 확인하지 말고 해당 문장의 오류 정보를 사용한다. [PDO 오류 정보](https://www.php.net/manual/en/pdo.errorinfo.php).

존재하지 않는 열·인덱스 검사처럼 실패를 예상하는 호출은 기존 오류 초기화 동작을 유지해야 한다. 연결·prepare·execute·fetch·commit·rollback 실패를 각각 확인하고, SQL 로그에 비밀번호나 사용자 입력값을 새로 펼쳐 기록하지 않는다. 실행 로그는 기존 `actStart()`/`actFinish()`와 중복 집계되지 않게 한다.

트랜잭션은 [DB의 중첩 카운터](../classes/db/DB.class.php)와 맞춘다. 최초 begin, 중첩 SAVEPOINT, 내부 rollback, 최종 commit을 실제 DB로 검증한다. MyISAM 경로가 롤백을 보장하는 것처럼 동작하거나 안내해서는 안 된다. DDL의 암시적 commit과 혼합 엔진의 한계는 PDO로 바뀌어도 사라지지 않는다.

PDO 연결 종료는 객체 참조 해제가 필요하다. `_close($connection)`의 지역 변수만 NULL로 바꾸면 `master_db`, `slave_db`, 보관된 PDOStatement의 참조가 남을 수 있다. 기존 `close()` 시그니처를 유지하면서 관련 참조와 연결 상태를 정리하고 재연결·shutdown의 중복 종료도 검증한다. 조회는 요청된 slave 연결을, 쓰기·시퀀스·트랜잭션은 master를 사용하도록 기존 라우팅을 보존한다. insert ID는 같은 연결에서 즉시 얻고, 큰 ID를 무조건 PHP 정수로 변환해 손실시키지 않는다.

## 7. 드라이버 파일 밖에서 바꿔야 할 곳

| 파일·영역 | 필요한 변경 |
| --- | --- |
| [DB 팩토리·지원 목록](../classes/db/DB.class.php) | 두 PDO 이름의 생성·지원 검사·캐시 분리 확인 |
| [설치 모델](../modules/install/install.model.php) | PDO-only 환경에서도 별도 `MySQL PDO (beta)` 항목을 활성화, 기본 `mysql`과 분리 |
| [설치 컨트롤러](../modules/install/install.controller.php) | PDO 명시 설정/도입 단계별 우선순위, MySQL 계열 목록, 연결 캐시 정리, 엔진 선택에 PDO 추가 |
| [설치 뷰](../modules/install/install.view.php) | 내부 PDO 이름으로 돌아와도 `form.mysql`을 표시 |
| [Context의 설정 로드](../classes/context/Context.class.php) | 기존 mysql→mysqli 호환 변환이 PDO 이름을 건드리지 않는지 확인. 기존 설정 자동 PDO 전환은 추가하지 않음 |
| [HintTableTag](../classes/xml/xmlquery/tags/table/HintTableTag.class.php) | PDO 이름으로 존재하지 않는 `Pdo_mysqlTableWithHint` 클래스를 생성하지 않게 MySQL 방언으로 매핑 |
| [QueryTag](../classes/xml/xmlquery/tags/query/QueryTag.class.php) | `index_hint for="mysql"` 적용 여부를 내부 API명과 분리해 확인 |
| [Password](../classes/security/Password.class.php) | 현재 `db_type`의 `mysql` 접두사 검사에 PDO가 빠지는 문제 처리 |
| [구형 비밀번호 검증](../config/func.inc.php) | PDO에서는 기존 PHP 해시 함수를 사용. 31비트 마스크와 탭 처리 보완 후 기존 회원 로그인 검증 |
| 쿼리 캐시 | 현재 파일명에는 `db_type`이 포함됨. PDO용 캐시와 MySQL 방언 판정 변경 후의 재생성 검증 |
| README·설치 언어 | 구현 완료 후에만 PDO 지원과 실제 선택 우선순위를 안내 |

SQL 방언을 확인하는 메서드나 매핑을 두어 `pdo_mysql`, `pdo_mysql_innodb`, `mysqli` 계열이 모두 MySQL SQL을 사용한다고 표현하는 것이 좋다. 내부 `db_type`을 계속 `mysql`이라고 속여 저장하면 기존 레거시 드라이버와 구분할 수 없으므로 피한다.

원시 연결에 `mysqli_*()`를 직접 호출하는 외부 모듈은 PDO로 자동 호환되지 않는다. 코어의 `db_fetch_object()` 등 기존 어댑터를 유지하고, 서드파티가 내부 resource를 직접 쓰는 경우는 전환 전 확인 대상으로 분류한다.

## 8. 검증 계획

현재 [CI](../.github/workflows/ci.yml)는 MySQL 5.7을 사용하고 PHP에 PDO MySQL 확장도 설치한다. 그러나 Codeception의 Db 모듈이 PDO로 테스트 DB에 접속하는 것과 **XE 코어의 PDO 드라이버가 검증되는 것은 다르다.** 별도 드라이버 계약 테스트와 실제 설치가 필요하다.

| 검증 범위 | 필수 사례 |
| --- | --- |
| 확장 조합 | PDO+mysqli, PDO만 있음, mysqli만 있음, PHP 5.x의 mysql만 있음, 셋 다 없음 |
| PHP | 5.3.6 경계 정책, 기존 5.3.29/5.4 실행 환경, 7.x·8.x. 5.3.0~5.3.5에서는 PDO를 기본 선택하지 않는지 확인 |
| 연결 | TCP/포트/socket, 잘못된 인증, 잘못된 DB명, 재입력 시 이전 연결을 재사용하지 않음 |
| 값 바인딩 | CRUD, IN 배열, NULL, 빈 문자열, 한글, 인용 문자, BIGINT, 소수, placeholder 순서와 개수 |
| SQL 구조 | JOIN, 서브쿼리, DISTINCT/GROUP BY COUNT, 페이지 조회, MySQL 인덱스 힌트 |
| 결과 | 빈 결과/한 행/여러 행, 페이지 인덱스, 중복 열 이름, LONGTEXT, 다음 쿼리 실행 |
| 엔진 | InnoDB 사용 가능, NO/DISABLED, 비활성화 변수, MyISAM만 가능, 둘 다 불가, 검사 오류 |
| 트랜잭션 | commit/rollback, 중첩 SAVEPOINT, 실패 시 카운터·오류 상태, MyISAM의 비트랜잭션 동작 |
| 연결 라우팅 | master/slave의 결과 커서·오류·insert ID가 서로 섞이지 않음 |
| 호환성 | 기존 네 드라이버와 설정 유지, MyISAM 테이블 유지, 기존 로그인·구형 해시 경로 |
| 설치 | 기본 `mysql`과 별도 PDO beta 선택지, 일반/자동 설치, 저장된 실제 PDO 이름, 실제 InnoDB/MyISAM 테이블, 관리자 로그인 |

기존 [mysqli DB 테스트](../tests/database/MysqliCompatibilityTest.php)의 결과 계약을 활용하되, 연결·조회 코드가 mysqli에 묶인 부분은 공통 테스트 어댑터로 분리한다. 새 PDO 테스트도 실제 PDO MySQL 연결을 사용한다. 확장이 없는 조합은 전체 SQL 테스트를 묵묵히 skip하지 말고 설치 선택·명시 설정의 오류 동작을 검증한다.

PHP 5.3에서는 현재 CI가 Codeception을 실행하지 않으므로 PDO의 실제 연결·바인딩·조회·트랜잭션을 확인하는 독립 실행 검증을 추가한다. 문법 검사만으로 PDO 호환성이 확인됐다고 보고하지 않는다. 설치 테스트는 `files/`와 테스트 DB를 초기화하므로 일회용 복사본과 전용 DB에서 실행한다.

DB 버전 검증은 우선 기존 MySQL 5.7 대상으로 추가하고, 8.x/MariaDB는 별도 조합으로 확대한다. PHP 지원 버전과 MySQL 서버 지원 버전을 혼동하지 않고, PDO 도입을 이유로 기존 최소 서버 버전을 임의로 올리지 않는다. MySQL 4.1 등 구형 서버에서의 native prepare·인증·문자셋 지원은 실제 조합 검증 사항으로 남긴다.

## 9. 권장 구현 순서와 완료 기준

1. **호환 계약 고정:** 기존 결과 형태·오류·시퀀스·트랜잭션·MySQL 방언 분기를 테스트로 정리한다.
2. **명시 설정으로 PDO 구현:** 두 내부 드라이버, 연결·조회·바인딩·결과·오류·연결 해제를 구현한다. 기존 사이트의 저장된 드라이버는 유지한다.
3. **코어 통합:** XML 힌트, 구형 비밀번호 판별, 설정 로드와 캐시, master/slave 경로를 점검한다.
4. **beta 설치 통합:** PDO-only 설치와 명시 PDO 자동 설치를 지원하고 `MySQL PDO (beta)`를 별도 선택지로 제공한다. 실제 생성 엔진과 로그인까지 검증한다.
5. **향후 기본 사용 전환:** 서드파티 자료의 호환성 문제가 모두 해결되었다고 확인된 뒤에만 기본 `mysql`에서 PDO를 우선한다. 기존 사이트의 자동 전환은 하지 않는다.

추가 드라이버의 완료 기준은 PDO-only 환경의 설치·로그인, 기존 쿼리 결과·오류 계약의 일치, MyISAM 보존, mysqli가 남아 있는 환경과 없는 환경 모두의 정상 동작이다. mysqli 제거, XML 쿼리 시스템 전면 교체, utf8mb4 일괄 전환, 기존 사이트의 자동 마이그레이션은 별도 작업으로 둔다.
