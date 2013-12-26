jQuery.fn.loginPopup = function(disabled, options) {
    settings = jQuery.extend({
        autoOpen: false,
        draggable: false,
        modal: true,
        resizable: false,
        minHeight: 0
    }, options);
    
    $(this).dialog(settings);

    jQuery.each(disabled, function(key, value) {
        $(value).addClass('disabled');
        $(value).parent().submit(function() {
            $('#loginPopup').dialog('open');
            return false;
        });
    });
    
    $('.tabs a[href!=/users/everyone]').click(function() {
        $('#loginPopup').dialog('open');
        return false;
    });
    
    $('#homepage .buttons a[href!=/users/everyone]').click(function() {
        $('#loginPopup').dialog('open');
        return false;
    });
    
    $('#homepage .login').click(function() {
        $('#loginPopup').dialog('open');
        return false;
    });
};