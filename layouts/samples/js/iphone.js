$(document).ready(function(){
	$('#rotate').click(function(){
		if($('#iphoneIframe').height() == '340'){
			$('#iphoneIframe').css('width', '360px').css('height', '245px');
			$('.iframeHolder').css('top', '239px').css('left', '160px');
			$('.iphone').rotate(90);
		} else {
			$('#iphoneIframe').css('width', '245px').css('height', '340px');
			$('.iframeHolder').css('top', '200px').css('left', '226px');
			$('.iphone').rotate(0);
		}
		return false;
	})
	/*$('.layouts a').click(function(){
		$('#iphoneIframe').attr('src', $(this).);
	})*/
})