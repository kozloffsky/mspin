jQuery.fn.moodline = function(options) {
	var settings = jQuery.extend({
		cell:  {width: 49, height: 37},
		mood:  {width: 32, height: 32},
		color: {height: 23},
		dot:   {width: 12, height: 12}
	}, options);	
	
	var url = settings.serviceUrl;
	var user = settings.user;
	var page = 0;
	
	var getData = function (_page, _url, _user){
		$.post(_url,{page:_page, username:_user},
				function(data){
					onData(data);
				}
			);
	};
	
	var onData = function(data){
		data = eval(data);
		$('#moodline .date .date-interval').text(data[1].date);
		$('#moodline .markers').html('');
		$.each(data[0],moodsIterator);
		$('#moodline .date .prev').unbind('click');
		$('#moodline .date .next').unbind('click');
		var page = data[1].page;
		var current = data[1].current+1;
		
		$('#moodline tr span').removeClass('selected');
		if (current > 0){
			$('#moodline tr:eq('+current+') span').addClass('selected');
		}
		
		if (page == 0){
			$('#moodline .date .next').addClass('disabled');
		} else {
			$('#moodline .date .next').removeClass('disabled');
			$('#moodline .date .next').click(function(evtObject){
				evtObject.preventDefault();
				evtObject.stopPropagation();
				getData(data[1].page-1, url, user);
			});
		}
		
		$('#moodline .date .prev').click(function(evtObject){
			evtObject.preventDefault();
			evtObject.stopPropagation();
			getData(data[1].page+1, url, user);
		});
		
	};
	
	var moodsIterator = function(key, value) {
		var element = document.createElement('span');
		element.className = value.type;
		switch (value.type) {
			case 'mood':
				element.style.top = (value.day * settings.cell.height + (settings.cell.height - settings.mood.height) / 2) + 'px';
				element.style.left = (value.hours * settings.cell.width / 2 + (settings.cell.width - settings.mood.width) / 2) + 'px';
				element.style.backgroundImage = 'url(/images/moods/mood_' + value.moodId + '.png)';
				break;
			case 'color':
				element.style.top = (value.day * settings.cell.height + (settings.cell.height - settings.color.height) / 2) + 'px';
				element.style.left = (value.hours * settings.cell.width / 2 + settings.cell.width / 2) + 'px';
				if(value.duration * settings.cell.width / 2 > 0)
					element.style.width = (value.duration * settings.cell.width / 2) + 'px';
				element.style.backgroundImage = 'url(/images/moods/moodline_' + value.moodId + '.png)';
				break;
			case 'dot':
				element.style.top = (value.day * settings.cell.height + (settings.cell.height - settings.dot.height) / 2) + 'px';
				element.style.left = (value.hours * settings.cell.width / 2 + (settings.cell.width - settings.dot.width) / 2) + 'px';
				break;
			default:
				break;
		}
		
		$('#moodline .markers').append(element);
		
		var isTooltip = false;
		
		if (undefined != value.messages && value.messages) {
			var content = $("<div></div>");
			$.each(value.messages,function(i,val){
				if (val && (val.text || val.mood_id > 1)) {
					isTooltip = true;
					var tpl = getTooltipTemplate(user,val.text, val.mood_id);
					if (val.mood_id > 1) {
						$(tpl).find('p').addClass('icon').css('background-image','url(../images/moods/mood_' + val.mood_id + '.png)');
					}
					$(content).append(
						tpl
					);
				}
			});
			
			if (isTooltip) {
				$(element).addClass('hasTooltip');
				
				$(element).tooltips({
					setContent     : $(content).html(),
					element        : element,
			        targetClass    : '.hasTooltip'
				});
			}
		}
		
	};
	
	getData(0,url, user);
	
	function getTooltipTemplate(_name,_text,_moodId) {
		var t = $.template(
			'<p>${text}</p><ul>' +
			'<li><a target="_blank" href="http://twitter.com/${name}">Follow</a></li>' +
			'<li><a href="#RT@${name}">Re-Tweet</a></li>' +
	    	'<li><a href="#@${name}">Reply</a></li></ul>');
		return $("<div>").append(t, {name:_name,text:_text, mood:_moodId});
	}
	
};