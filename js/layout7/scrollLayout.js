//scrolling navigation jquery function
$.fn.scrollNav = function(){
	$(this).data('containerCount', 0);
	var container = $(this);
	$(this).find('.container').each(function(){
		var containerCount = $(this).data('containerCount');
		container.data('containerCount', ++containerCount);
	});
	
	$(this).css('width', container.data('containerCount')*$(this).css('width'));
	alert(container.data('containerCount')*$(this).css('width'));
	/*$(this).click(function(){
		$('html, body').stop();
		var offset = $('a[name="'+$(this).attr('ref')+'"]').offset().top-50;
		$('html, body').animate({
				scrollLeft: offset
			}, 1000);
		return false;
	})*/
}