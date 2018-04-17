$(function () {

	function setupAjaxMore() {
		$('.tao-ajax-more-button').click(function () {
			runAjaxMore($(this));
			return false;
		});
		checkMarkers();
	}

	function runAjaxMore(elem) {
		var url = elem.attr('data-url');
		var after = elem.attr('data-after');
		var code = elem.attr('data-infoblock');
		var loader = $('<div>').addClass('tao-ajax-elements-loader').addClass('infoblock-' + code + '-elements-loader');
		elem.replaceWith(loader);
		$.get(url, function (data) {
			loader.replaceWith(data);
			if (after !== undefined) {
				var func = window[after];
				if (typeof func == 'function') {
					func();
				}
			}
			setupAjaxMore();
		});
	}

	function isVisible(elem) {
		return $(window).scrollTop() + $(window).height() >= elem.offset().top;
	}

	function checkMarkers() {
		$('.tao-ajax-more-marker').each(function () {
			if (isVisible($(this))) {
				runAjaxMore($(this));
			}
		});
	}

	$(window).scroll(function () {
		checkMarkers();
	});

	$('.tao-elements-ajax').each(function () {
		var div = $(this);
		var url = div.attr('data-url');
		var after = div.attr('data-after');
		$.get(url, function (data) {
			div.append(data);
			setupAjaxMore();
			if (after !== undefined) {
				var func = window[after];
				if (typeof func == 'function') {
					func();
				}
			}
		});
	});
});