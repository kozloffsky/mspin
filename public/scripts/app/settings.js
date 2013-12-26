jQuery.fn.settings = function(options) {
    var settings = $.extend({
    	alertsCheckbox: '',
    	alertsContainer: ''
    }, options || {});
    
	var checkbox = $(this).find(settings.alertsCheckbox);
	var container = $(this).find(settings.alertsContainer);
	if (checkbox.is(':checked')) {
		container.show();
	} else {
		container.hide();
	}
    
	checkbox.unbind('click').bind('click', function(){
		if (checkbox.is(':checked')) {
			container.show();
		} else {
			container.hide();
		}
	});
};