/*
 * show system messages
 * @param message - message text
 * @param type - message | error
 */
function Messenger(options){
	var myoptions = jQuery.extend( {
		'messegerId' : 'statusMessage',
		'messegerShowDelay' : 10000
	}, options);
	
	this.show = function(message,type) {
		var icon,css;
	    if ('message' == type) {
	    	icon = 'ui-icon-info';
	        css  = 'ui-state-highlight';
	    } else {
	    	icon = 'ui-icon-alert';
	        css  = 'ui-state-error';
	    }
	    
	    $('#' + myoptions.messegerId + ' .ui-corner-all').addClass(css);
	    $('#' + myoptions.messegerId + ' .ui-icon').addClass(icon);
	    $('#' + myoptions.messegerId + ' .message').html(message);
	    
	    setTimeout(function() {
	        $('#' + myoptions.messegerId).slideDown();
	        setTimeout(function() {
	            $('#' + myoptions.messegerId).slideUp();
	            $('#' + myoptions.messegerId + ' .ui-corner-all').removeClass(css);
	            $('#' + myoptions.messegerId + ' .ui-icon').removeClass(icon);
	            $('#' + myoptions.messegerId + ' .message').html('');
	        }, myoptions.messegerShowDelay);
	    }, 1);
	};
}