$(document).ready(function(){
	$.fn.createTabs = function(){
		var tabs = '<div class="tabHolder">';
		var count = 0;
		
		$(this).find('.tabContentHolder').each(function(){
			var title = $(this).find('h2').html();
			$(this).find('h2').remove();
			tabs += '<a href="" ref="'+count+'">'+title+'</a>';
			$(this).addClass('tab'+count);
			$(this).css('display', 'none');
			++count;
		})
		
		count = 0;
		tabs += '</div>';
		$(this).parent().prepend(tabs);
		$(this).parent().find('.tabHolder a').each(function(){
			$(this).click(function(){
				$(this).parent().find('a').each(function(){
					$(this).removeClass('selected');
				})
				$(this).parent().parent().find('.tabContentHolder').each(function(){
					$(this).stop();
					$(this).animate({'opacity':'0'}, 500, function(){ $(this).css('display', 'none'); });
				})
				$('.tab'+$(this).attr('ref')).stop();
				$('.tab'+$(this).attr('ref')).css('display', 'block');
				$('.tab'+$(this).attr('ref')).animate({'opacity':'1'}, 500, function(){ $(this).css('display', 'block') });
				$(this).addClass('selected');
				return false;
			})
		})
	}
})