jQuery.fn.moodChooser = function(options) {
	settings = jQuery.extend({
		limit: 100
	}, options);
	
    $(this).find('ul a').click(function() {
        return false;
    });
    
    $(this).find('a.mood').click(function(e) {
        var moodId = this.href.split('#')[1] || 0; 
    	var form = $(this).parents('form');
        $(form).find(':input[name=mood_id]').val(moodId);
        if (moodId > 1) {
        	$(form).find(':input[name=message]').val(this.title || '');
        } else {
        	$(form).find(':input[name=message]').val('');
        }
        $(form).find('.avatar .overlay').hide();
        $(form).find('.avatar #overlay_' + moodId).show();
        return false;
    });
    
    var moodId = this.find(':input[name=mood_id]').val();
    $(this).find('.avatar #overlay_' + moodId).show();

    $(this).find('textarea').limit(settings.limit, $(this).find('.counter .value'));
};