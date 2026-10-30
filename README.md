### 적극적 유지보수 중단 안내.
본 저장소는 더이상 적극적인 보안 패치가 이루어지지 않을 예정입니다. 취약점이 있거나 발생할 수 있으나 패치되지 않은 취약점은 이슈로 올려두겠습니다. 이 부분을 수정하여 기여하는 것은 받으나 그 이외 적극적으로 보안 취약점 패치를 진행할 예정은 없습니다.

YJSoft XE Fork
============

[![License](http://img.shields.io/badge/license-GNU%20LGPL-brightgreen.svg)](http://www.gnu.org/licenses/gpl.html)
[![Latest release](http://img.shields.io/github/release/YJSoft/xe-core.svg)](https://github.com/YJSoft/xe-core/releases)
[![CI](https://github.com/YJSoft/xe-core/actions/workflows/ci.yml/badge.svg)](https://github.com/YJSoft/xe-core/actions/workflows/ci.yml)

YJSoft XE Fork는 누구나 쉽고 편하고 자유롭게 콘텐츠를 발행을 할 수 있도록 하기 위한 CMS(Content Management System)인 XpressEngine의 비공식 보안 패치 버전입니다.
오픈소스 라이선스로 누구나 사용 또는 개작할 수 있습니다.

본 저장소는 사실상 유지보수가 전면 중단된 [XEHub측 저장소](https://github.com/xpressengine/xe-core)를 포크하여 운영하고 있었으나, 검색의 편의성 등 여러 이유로 원본 저장소에서 분리하여 운영하고 있습니다.

### 확장형 구조

XE 코어는 모듈, 애드온, 에디터 컴포넌트, 위젯, 레이아웃의 구조를 기반으로 결과물을 생성합니다.

이렇게 각각의 기능과 디자인이 구조적으로 연결되는 모듈형 구조는 개발 및 유지보수를 쉽게 하도록 도와주며 관리자는 손쉽게 설정과 디자인을 변경할 수 있습니다.

레이아웃, 모듈 스킨 그리고 위젯의 스타일과 스킨을 활용하면 여러분만의 개성을 가진 웹 사이트를 만들 수 있습니다. XE와 함께 더 다채롭고 개성있는 웹사이트를 만들어보세요!

### 코드 공헌 가이드
보안 패치나 PHP 상위 버전에서 발생하는 오동작을 수정하는 것 이외 코드 공헌은 받지 않습니다.

## Server Requirements

| 항목 | 요구 사항 |
| --- | --- |
| PHP | **5.3.0 이상** (7.4.0 이상 권장). 호환성 검증을 계속 진행하고 있으며, 버전별 검증 범위는 [CI 설정](.github/workflows/ci.yml)과 [호환성 테스트 문서](tests/PHP_COMPATIBILITY.md)를 참고하세요. |
| MySQL | **4.1 이상**. |
| DB 연결 확장 | PHP `mysqli` 확장(`mysqli` / `mysqli_innodb` 드라이버) 또는 레거시 `mysql` 확장(`mysql` / `mysql_innodb` 드라이버)이 필요합니다. `mysql` 확장은 [PHP 7.0에서 제거](https://www.php.net/manual/en/book.mysql.php)되었으므로 PHP 7 이상에서는 `mysqli`를 사용해야 합니다. PDO MySQL만 설치해서는 XE의 MySQL 드라이버를 사용할 수 없습니다. |
| XML | PHP `xml` 확장(`xml_parser_create()`). 코어의 XML 설정 및 쿼리 파싱에 필요합니다. |
| DOM | PHP `dom` 확장(`DOMDocument`). 일반 사용자의 SVG 업로드 정화 처리에 필요합니다. |
| GD | GIF/JPEG/PNG를 지원하는 PHP `gd` 확장. 이미지 및 썸네일 처리에 필요하며, 설치 검사는 `imagecreatefromgif()` 유무를 확인합니다. |
| Fileinfo | PHP `fileinfo` 확장. 업로드 파일의 MIME 형식 검사에 필요합니다. |
| 문자 인코딩 | PHP `iconv` 확장. 문자셋 변환에 필요합니다. 우체국 우편번호 API 검색을 사용한다면 `mbstring` 확장도 필요합니다. |
| 기본 PHP 기능 | `session`, `hash`, `json` 기능을 사용할 수 있어야 합니다. |
| PHP 설정 | `session.auto_start = Off` (`php.ini`). |
| 파일 권한 | PHP 실행 사용자가 `files/`와 그 하위 디렉터리에 쓸 수 있어야 합니다. 설치 시 `files/`가 없다면 생성할 수 있도록 설치 경로에 쓰기 권한이 필요합니다. |

<!-- 현재 MySQL 테스트는 MySQL 5.7에서만 수행합니다. -->

MySQL 테이블 생성에는 선택한 드라이버에 맞는 MyISAM 또는 InnoDB와 `utf8` / `utf8_general_ci` 지원이 필요합니다.

MySQL 8.x와 구형 PHP를 함께 사용할 때는 [PHP MySQL 클라이언트의 인증 방식 호환성](https://www.php.net/manual/en/mysqli.requirements.php)을 확인해야 합니다. 또한 기존 회원의 `mysql_old_password` 해시 검증에 사용하는 `OLD_PASSWORD()` 함수는 [MySQL 5.7에서 제거](https://dev.mysql.com/blog-archive/removal-and-deprecation-in-mysql-5-7/)되어, 해당 방식의 비밀번호를 사용하는 회원은 별도의 전환이 필요합니다.

## Contributors
https://github.com/YJSoft/xe-core/graphs/contributors

## License
XpressEngine의 이름 및 로고는 (주)엑스이허브 소유입니다. [브랜드 가이드라인](https://xe1.xpressengine.com/brand)에 따라 XpressEngine의 이름 및 로고를 사용하고 있습니다.

Copyright 2014 XEHub. <https://www.xehub.io>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
