### 적극적 유지보수 중단 안내.
본 저장소는 더이상 적극적인 보안 패치가 이루어지지 않을 예정입니다. 취약점이 있거나 발생할 수 있으나 패치되지 않은 취약점은 이슈로 올려두겠습니다. 이 부분을 수정하여 기여하는 것은 받으나 그 이외 적극적으로 보안 취약점 패치를 진행할 예정은 없습니다.

YJSoft XE Fork
============

[![License](http://img.shields.io/badge/license-GNU%20LGPL-brightgreen.svg)](http://www.gnu.org/licenses/gpl.html)
[![Latest release](http://img.shields.io/github/release/YJSoft/xe-core.svg)](https://github.com/YJSoft/xe-core/releases)

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
* PHP version greater than 5.3.0 and less than 8.0.0 (But recommend PHP >= 5.5.0)
* MYSQL version 4.1 or greater (But recommend MYSQL >= 5.x)
* XML Library
* GD Library
* Fileinfo Extension
* ICONV
* session.auto_start = Off (php.ini)

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
