YJSoft XE Fork
============

[![License](https://img.shields.io/badge/license-GPLv2%2B-brightgreen.svg)](LICENSE)
[![Latest release](http://img.shields.io/github/release/YJSoft/xe-core.svg)](https://github.com/YJSoft/xe-core/releases)
[![CI](https://github.com/YJSoft/xe-core/actions/workflows/ci.yml/badge.svg)](https://github.com/YJSoft/xe-core/actions/workflows/ci.yml)

YJSoft XE Fork는 누구나 쉽고 편하고 자유롭게 콘텐츠를 발행을 할 수 있도록 하기 위한 CMS(Content Management System)인 XpressEngine의 비공식 보안 패치 버전입니다.
오픈소스 라이선스로 누구나 사용 또는 개작할 수 있습니다.

본 저장소는 [XEHub 저장소](https://github.com/xpressengine/xe-core)에서 시작한 포크이며, 검색 편의성 등을 위해 독립적으로 운영하고 있습니다.

### 확장형 구조

XE 코어는 모듈, 애드온, 에디터 컴포넌트, 위젯, 레이아웃의 구조를 기반으로 결과물을 생성합니다.

이렇게 각각의 기능과 디자인이 구조적으로 연결되는 모듈형 구조는 개발 및 유지보수를 쉽게 하도록 도와주며 관리자는 손쉽게 설정과 디자인을 변경할 수 있습니다.

레이아웃, 모듈 스킨 그리고 위젯의 스타일과 스킨을 활용하면 여러분만의 개성을 가진 웹 사이트를 만들 수 있습니다. XE와 함께 더 다채롭고 개성있는 웹사이트를 만들어보세요!

### 기존 사이트에서 master로 업데이트할 때

배포 파일을 덮어쓴 뒤 관리자 대시보드에 **이전 버전의 코어 파일 정리**가 표시되면 목록을 확인하고 **원본 파일 정리**를 누른 다음 캐시파일을 재생성해 주세요. 예전 쉬운설치 모듈의 배포 원본만 정리합니다. 직접 수정한 파일은 목록에 **수동 확인**으로 표시되며, 쉬운설치로 설치했던 모듈·애드온·위젯·레이아웃은 계속 사용할 수 있습니다.

### 새 쉬운설치 사용하기 (master)

**관리자 → 고급 → 쉬운설치**(`index.php?module=admin&act=dispAutoinstallAdminIndex`)에서 자료명이나 제작자를 검색하세요. 자료 카드를 누르면 버전·출처·설치 위치와 변경 내역이 표시됩니다. 상세페이지에서 설치·업데이트·복원을 실행하면 페이지 이동 없이 결과와 현재 설치 버전을 확인할 수 있습니다. 파일 설치 후 DB 테이블 생성이나 모듈 업데이트가 필요하면 **설치된 모듈**에 표시됩니다. [공개 자료실](https://yjsoft.github.io/xe-package-registry/) · [자료 등록 저장소](https://github.com/YJSoft/xe-package-registry)

상세페이지의 **함께 설치할 자료**에서 필수 자료와 설치 상태를 확인할 수 있습니다. 심플스트랩 레이아웃을 설치하면 회원 스킨, 커뮤니케이션 스킨, 사이드바 최신 글 위젯 스킨도 함께 설치합니다. 설치가 중단되면 완료된 자료는 그대로 두고 다시 설치할 수 있습니다. 이미 설치한 레이아웃에 스킨이 빠져 있다면 **누락된 필수 자료 설치**를 누르세요. 에디터 본문 스타일은 별도로 선택할 수 있습니다.

파일 설치에는 PHP cURL·OpenSSL·ZIP 확장, HTTPS CA 인증서, 자료를 설치할 폴더와 `files/config/`의 쓰기 권한이 필요합니다. **쉬운설치 → 작업 폴더 설정**에 ‘현재 위치를 모듈 설치에 사용할 수 있습니다’가 표시되면 바로 설치하세요. 쉬운설치가 첫 작업 때 다운로드와 백업을 보관할 사이트별 폴더를 만듭니다.

기본 위치에 폴더를 만들 수 없으면 화면에 표시된 **현재 사이트의 작업 폴더** 경로를 호스팅 업체에 전달하고, PHP 실행 계정 소유로 권한 700을 설정해 달라고 요청하세요. 다른 위치를 제공받았다면 **보관 위치**에 그 절대 경로를 입력하고 **확인 후 저장**을 누르세요. 이 위치는 웹 공개 폴더 밖에 있어야 하며 설치할 자료와 같은 파일시스템에 있어야 합니다. 기존 설치 기록이 있는 사이트는 위치를 변경하기 전에 현재 사이트의 작업 폴더 전체를 새 보관 위치로 옮겨야 백업도 계속 사용할 수 있습니다.

설치 직전에 자료실 서명과 릴리스 상태를 다시 확인합니다. 등록된 이전 버전과 파일이 일치하면 수동 설치본도 업데이트할 수 있습니다. 로컬 변경이 있으면 파일 작업을 중단합니다. 설치 파일을 제거하면 직전 파일을 백업하며, 승인된 호환 버전은 복원할 수 있습니다. DB 설정과 작성된 콘텐츠는 이 파일 복원 범위에 포함되지 않습니다.

### 코드 공헌 가이드
보안 패치나 PHP 상위 버전에서 발생하는 오동작을 수정하는 것 이외 코드 공헌은 받지 않습니다.

## Server Requirements

| 항목 | 요구 사항 |
| --- | --- |
| PHP | **5.3.0 이상** (7.4.0 이상 권장). 호환성 검증을 계속 진행하고 있으며, 버전별 검증 범위는 [CI 설정](.github/workflows/ci.yml)과 [호환성 테스트 문서](tests/PHP_COMPATIBILITY.md)를 참고하세요. |
| MySQL | **4.1 이상**. |
| DB 연결 확장 | PHP `pdo_mysql`, `mysqli`, 레거시 `mysql` 확장 중 하나가 필요합니다. 기본 `mysql` 선택지는 **mysqli → mysql** 순서로 사용하며, PDO는 별도의 **MySQL PDO (beta)** 항목에서 선택합니다. PDO 경로는 PHP 5.3.6 이상에서 사용하며, PHP 5.3.0~5.3.5에서는 mysqli/mysql을 사용합니다. `mysql` 확장은 [PHP 7.0에서 제거](https://www.php.net/manual/en/book.mysql.php)되었습니다. |
| XML | PHP `xml` 확장(`xml_parser_create()`). 코어의 XML 설정 및 쿼리 파싱에 필요합니다. |
| DOM | PHP `dom` 확장(`DOMDocument`). 일반 사용자의 SVG 업로드 정화 처리에 필요합니다. |
| GD | GIF/JPEG/PNG를 지원하는 PHP `gd` 확장. 이미지 및 썸네일 처리에 필요하며, 설치 검사는 `imagecreatefromgif()` 유무를 확인합니다. |
| Fileinfo | PHP `fileinfo` 확장. 업로드 파일의 MIME 형식 검사에 필요합니다. |
| 문자 인코딩 | PHP `iconv` 확장. 문자셋 변환에 필요합니다. 우체국 우편번호 API 검색을 사용한다면 `mbstring` 확장도 필요합니다. |
| 기본 PHP 기능 | `session`, `hash`, `json` 기능을 사용할 수 있어야 합니다. |
| PHP 설정 | `session.auto_start = Off` (`php.ini`). |
| 파일 권한 | PHP 실행 사용자가 `files/`와 그 하위 디렉터리에 쓸 수 있어야 합니다. 설치 시 `files/`가 없다면 생성할 수 있도록 설치 경로에 쓰기 권한이 필요합니다. |

<!-- 현재 MySQL 테스트는 MySQL 5.7에서만 수행합니다. -->

설치 화면은 기본 `mysql`과 별도 `MySQL PDO (beta)` 선택지를 제공합니다. 기본 `mysql`은 mysqli를 우선하고, mysqli가 없을 때만 레거시 mysql을 사용합니다. PDO는 사용자가 beta 항목을 명시적으로 선택한 경우에만 사용하며, 서드파티 자료의 호환성 검증이 완료되기 전까지 기본 선택에 포함하지 않습니다. 접속·인증·쿼리 오류가 발생하면 다른 드라이버로 재시도하지 않고 오류를 반환합니다. DB 접속 후 InnoDB를 사용할 수 있으면 InnoDB를, 사용할 수 없으면 MyISAM을 선택합니다. 두 엔진 모두 사용할 수 없거나 지원 여부 확인에 실패하면 설치를 중단합니다. 이 선택 방식은 설정 파일을 통한 자동 설치에도 적용되며, 기존 사이트는 저장된 드라이버와 MyISAM 설정 및 테이블을 유지합니다. 테이블 생성에는 `utf8` / `utf8_general_ci` 지원이 필요합니다.

MySQL 8.x와 구형 PHP를 함께 사용할 때는 [PHP MySQL 클라이언트의 인증 방식 호환성](https://www.php.net/manual/en/mysqli.requirements.php)을 확인해야 합니다. 기존 mysqli/mysql 드라이버의 `mysql_old_password` 해시 검증은 [MySQL 5.7에서 제거된 `OLD_PASSWORD()`](https://dev.mysql.com/blog-archive/removal-and-deprecation-in-mysql-5-7/)에 의존하므로 별도의 비밀번호 전환이 필요합니다. PDO 드라이버는 해당 구형 해시를 PHP에서 검증합니다.

## Contributors
https://github.com/YJSoft/xe-core/graphs/contributors

## License

이 변경을 포함한 배포본부터 YJSoft XE Fork 코어는 GNU 일반 공중 사용 허가서 제2판 또는 그 이후 판(`GPL-2.0-or-later`)에 따라 배포됩니다. [라이선스 전문](LICENSE)

이전 배포본의 이용 조건은 해당 버전의 라이선스 파일에서 확인할 수 있습니다. 별도의 라이선스를 명시한 구성요소에는 해당 라이선스가 적용됩니다.

XpressEngine의 이름 및 로고는 (주)엑스이허브 소유입니다. [브랜드 가이드라인](https://xe1.xpressengine.com/brand)에 따라 XpressEngine의 이름 및 로고를 사용하고 있습니다.

Copyright 2014 XEHub. <https://www.xehub.io>
