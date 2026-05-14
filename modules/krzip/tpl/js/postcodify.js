/* Copyright (C) XEHub <https://www.xehub.io> */

(function ($) {
	"use strict";

	$.fn.Krzip = function () {
		var $this = $(this);

		var values = {
			postcode      : $this.find(".krzip-hidden-postcode"),
			roadAddress   : $this.find(".krzip-hidden-roadAddress"),
			jibunAddress  : $this.find(".krzip-hidden-jibunAddress"),
			detailAddress : $this.find(".krzip-hidden-detailAddress"),
			extraAddress  : $this.find(".krzip-hidden-extraAddress")
		};

		var ui = {
			postcode      : $this.find(".krzip-postcode"),
			roadAddress   : $this.find(".krzip-roadAddress"),
			jibunAddress  : $this.find(".krzip-jibunAddress"),
			detailAddress : $this.find(".krzip-detailAddress"),
			extraAddress  : $this.find(".krzip-extraAddress"),
			search        : $this.find(".krzip-search"),
			guide         : $this.find(".krzip-guide")
		};

		/* 검색 버튼에 Postcodify 팝업 연결 */
		ui.search.postcodifyPopUp({
			container : $this,
			onSelect  : function () {
				/* 우편번호 표시 업데이트 */
				ui.postcode.val(values.postcode.val()).trigger("change");

				/* 도로명 주소 표시 업데이트 */
				ui.roadAddress.val(values.roadAddress.val()).trigger("change");

				/* 지번 주소: 괄호 처리 후 hidden 및 표시 필드 업데이트 */
				var jibunVal = values.jibunAddress.val();
				var jibunDisplay = jibunVal ? "(" + jibunVal + ")" : "";
				values.jibunAddress.val(jibunDisplay);
				ui.jibunAddress.val(jibunDisplay).trigger("change");

				/* 참고항목 표시 업데이트 */
				ui.extraAddress.val(values.extraAddress.val()).trigger("change");

				/* 안내 문구 초기화 */
				ui.guide.hide().html("");
			}
		});

		/* 상세 주소 변경 시 hidden 필드 동기화 */
		ui.detailAddress.on("change", function () {
			values.detailAddress.val($(this).val());
		});

		/* 우편번호·주소 표시 필드 클릭 시에도 팝업 열기 */
		var i, val, key = ["postcode", "roadAddress", "jibunAddress", "extraAddress"];
		for (i = 0; i < key.length; i++) {
			val = key[i];
			ui[val].on("click", function () {
				ui.search.trigger("click");
			});
		}
	};
})(jQuery);

/* End of file postcodify.js */
/* Location: ./modules/krzip/tpl/js/postcodify.js */
