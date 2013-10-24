//scrolling navigation jquery function
$.fn.scrollNav = function(scrollOffset){
	$(this).click(function(){
		$('html, body').stop();
		var offset = $('a[name="'+$(this).attr('ref')+'"]').offset().top-scrollOffset;
		$('html, body').animate({
				scrollTop: offset
			}, 1000);
		return false;
	})
}