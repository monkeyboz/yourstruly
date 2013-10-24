//scrolling navigation jquery function
$.fn.scrollNav = function(){
	$(this).click(function(){
		$('html, body').stop();
		var offset = $('a[name="'+$(this).attr('ref')+'"]').offset().top-50;
		$('html, body').animate({
				scrollTop: offset
			}, 1000);
		return false;
	})
}

//scrolling navigation jquery
$('#nav a').each(function(){ $(this).scrollNav(); });
$('#top_nav a').each(function(){ $(this).scrollNav(); });