var slideCount = 0;
var currSlide = 0;
	
function changeSlider(){
	layout = null;
	$('.slide'+currSlide).animate({'opacity':0}, 1000);
	++currSlide;
	
	if(currSlide == slideCount){
		currSlide = 0;
	}
	$('.slide'+currSlide).animate({'opacity':1}, 1000);
	layout = setTimeout('changeSlider()', 5000);
}

$(document).ready(function(){
	$.fn.slider = function(){
		$('.slider').find('.slideHolder').each(function(){
			$(this).addClass('slide'+slideCount);
			$(this).animate({'opacity':0}, 0);
			++slideCount;
		})
		var layout = setTimeout('changeSlider()', 0);
	}
})