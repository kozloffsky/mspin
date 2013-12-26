jQuery.fn.moodSelector = function() {
	
	var thisObj = this;
	
	updateChoosenMoods();
	
	// stupid IE hack
	$(this).find('.moods :checkbox').siblings('label').unbind('click').bind('click',function(){
		if ($.browser.msie) {
			$(this).siblings("input[type='checkbox']").trigger('click');
			$(this).siblings("input[type='checkbox']").trigger('change');
		}
	});
	
	$(this).find('.moods :checkbox').unbind('change').bind('change',function(){
        $(this).parents('li').toggleClass('selected');
        updateChoosenMoods();
    });
	
	function updateChoosenMoods() {
		var choosenMoods = $(thisObj).find('p');
        
        if (choosenMoods) {
            var content = '';
            var checked = $(thisObj).find(':checkbox[checked]').siblings('label');
            jQuery.each(checked, function(key, value) {
                content += value.title + ', ';
            });
            content = content.substr(0, content.length - 2);
            choosenMoods.html(content);
        }
	}
};