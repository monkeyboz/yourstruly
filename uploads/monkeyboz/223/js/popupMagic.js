$(document).ready(function(){
	$.fn.popupMagic = function(){
		$(this).prepend('<div class="imageOverlayHolder"><div class="imageRollover"></div><div class="iconHolder"><img src="images/hover_image.png"/></div></div>');
		$(this).find('.imageRollover').animate({opacity:0}, 0);
		$(this).click(function(){
			var image = $(this).find('a').attr('href');
			if($('body').find('.overlay')){
			   $('body').find('.overlay').remove();
			   $('body').append('<div class="overlay"></div><div class="displayImage"><img src="'+image+'" class="overlayImage" /></div>');
			} else {
			   $('body').append('<div class="overlay"></div><div class="displayImage"><img src="'+image+'" class="overlayImage" /></div>');
			}
			$('.overlayImage').chooseCorrectly();
			
			$('.overlay').css('opacity', 0);
			$('.displayImage').css('opacity', 0);
			$('.overlay').animate({'opacity':.5}, 1000);
			$('.displayImage').animate({'opacity':1}, 1000);
			$('.overlay').closeLayout();
			$('.displayImage').closeLayout();
			
			return false;
	   })
	   $(this).find('.imageOverlayHolder').hover(function(){
			$(this).find('.iconHolder').animate({'top':'-320px', opacity:1}, 300);
			$(this).find('.imageRollover').animate({opacity:0}, 0);
			$(this).find('.imageRollover').animate({opacity:.5}, 300);
	   }, function(){
			$(this).find('.imageRollover').animate({opacity:0}, 300);
			$(this).find('.iconHolder').animate({'top':'-450px', opacity:0}, 300, function(){ $(this).html(''); })
	   })
	   $.fn.chooseCorrectly = function(){ 
			$(this).load(function(){
				var overlay = $(this);
				$(this).height();
				$(this).data('height', $(this).height());
				$(this).data('width', $(this).width());
				
				if($(this).height() > $(window).height()){
					$(this).removeAttr('width');
					$(this).attr('height',$(window).height()-100);
				}
				if($(this).width() > $(window).width()){
					$(this).removeAttr('height');
					$(this).attr('width',$(window).width()-100);
				}
			});
	   }
	   
	   $(window).resize(function(){
			var overlayImage = $('.overlayImage');
			overlayImage.attr('height', $(window).height()-100);
						 
			if(overlayImage.width() > $(window).width()){
				overlayImage.removeAttr('height');
				overlayImage.attr('width', $(window).width()-100);
			}
			if(overlayImage.data('width') > overlayImage.width()){
				overlayImage.removeAttr('height');
				overlayImage.attr('width', $(window).width()-100);
			}
			if(overlayImage.height() > $(window).height()){
				overlayImage.removeAttr('width');
				overlayImage.attr('height', $(window).height()-100);
			}
		})
	   $.fn.closeLayout = function(){
			$(this).click(function(){
				var overlay = $('.overlay');
				var displayImage = $('.displayImage');
				var closeImage = $('.close');
				overlay.animate({'opacity': 0}, 500, function(){ $(this).remove(); });
				displayImage.animate({'opacity': 0}, 500, function(){ $(this).remove(); });
				closeImage.animate({'opacity': 0}, 500, function(){ $(this).remove(); });
			})
	   }
	}
})