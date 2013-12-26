function Users(options){
    var myoptions = jQuery.extend( {
        'simple' : false,
        'usersTableId' : 'registeredUsersTable',
        'messageMaxLength' : 140,
        'selectAllButtonClass' : 'usersSelectAll',
        'messegerId' : 'statusMessage',
        'messegerShowDelay' : 10000,
        'usersActionsContainer' : 'actionsContainer',
        'buttonFollowUser' : 'buttonFollowUser',
        'buttonUnFollowUser' : 'buttonUnFollowUser',
        'messageTextClass' : 'messageText',
        'messageTextCharsCounterClass' : 'messageCharsLeft',
        'moodContainerClass' : 'moodContainer',
        'moods' : {},
        'consts' : {
            MESSAGE_MAX_LENGTH : 140
        },
        'urls' : {
            URL_FOLLOW : '/admin/index/follow/',
            URL_SEND_MESSAGE : '/admin/index/send-message/'
        },
        'messages' : {
            ERROR_FOLLOW   : 'Failed to follow selected people',
            ERROR_UNFOLLOW : 'Failed to unfollow selected people',
            OK_FOLLOW      : 'OK to follow selected people',
            OK_UNFOLLOW    : 'OK to unfollow selected people',
            ERROR_SEND_MESSAGE : 'Failed to send message to selected people',
            OK_SEND_MESSAGE    : 'OK to send message to selected people'
        }
    }, options || {});
        
    this.init = function(){
        if (myoptions.simple == false) {
            _initSelectAllButtons();
            _bindUsersSelect();
            _bindFollowUsers();
            _messageBoxChangeHandler();
            _messageTextChangeHandler();
            _sendMessageHandler();
            _resetChoosenMoods();
            _toggleMoodChoose();
            // hide messager
            $('#' + myoptions.messegerId).hide();
            // disable all buttons
            _disableFollowButtons(true);
            _disableSendButton(true);
            //set moods
            if (myoptions.moods) {
                $(myoptions.moods).each(function(i,n){
                    $('#mood_' + parseInt(n)).parent().click();
                });
            }
        }
        // tablesorter initialization
        initTableSorter();
    };
    
    function initTableSorter() {
        $('table.tablesorter th.sortable').addClass("header");
        var sortFormObj = $('#filterForm input[name=sort]');
        var sortOrderFormObj = $('#filterForm input[name=sort_order]');
        var sort = $(sortFormObj).val();
        var sortOrder = $(sortOrderFormObj).val();
        
        if (sort && sortOrder) {
            var elem = $('table.tablesorter th[sort=' + sort + ']');
            if (elem) {
                var className = 'headerSortUp';
                if (sortOrder == 'DESC')
                    className = "headerSortDown";
                $('table.tablesorter th.sortable').removeClass("headerSortUp");
                $('table.tablesorter th.sortable').removeClass("headerSortDown");
                $(elem).addClass(className);
            }
        }
        $.each($('table.tablesorter th.sortable'),function(i,obj){
            $(obj).unbind('click').bind('click',function(){
                var objSort = $(obj).attr('sort');
                if (objSort == sort) {
                    if (sortOrder == 'DESC') {
                        $(sortOrderFormObj).val('ASC');
                    } else {
                        $(sortOrderFormObj).val('DESC');
                    }
                } else {
                    $(sortFormObj).val(objSort);
                    $(sortOrderFormObj).val('ASC');
                }
                $('#filterForm').submit();
            });
        });
    }

    /*
     * enable/disable send message buttons
     * @param disable - true to disable, false to enable
     */
    function _disableSendButton(disable) {
        if (disable) {
            $('.' + myoptions.usersActionsContainer + ' .usersSending .messageSend').attr('disabled','disabled');
        } else {
            $('.' + myoptions.usersActionsContainer + ' .usersSending .messageSend').removeAttr('disabled');
        }
    }

    /*
     * enable/disable follow buttons
     * @param disable - true to disable, false to enable
     */
    function _disableFollowButtons(disable) {
        if (disable) {
            $('.' + myoptions.buttonFollowUser).attr('disabled','disabled');
            $('.' + myoptions.buttonUnFollowUser).attr('disabled','disabled');
        } else {
            $('.' + myoptions.buttonFollowUser).removeAttr('disabled','disabled');
            $('.' + myoptions.buttonUnFollowUser).removeAttr('disabled','disabled');
        }
    }
    
    /*
     * bind change event to users checkboxes
     */
    function _bindUsersSelect(){
        $("#" + myoptions.usersTableId + " input[type='checkbox']").unbind('change').bind('change',function(){
            if ($("#" + myoptions.usersTableId + " :checked").length) {
                _disableFollowButtons(false);
                $('.' + myoptions.selectAllButtonClass).html("Deselect All");
            } else {
                _disableFollowButtons(true);
                $('.' + myoptions.selectAllButtonClass).html("Select All");
            }
        });
    }

    /*
     * select/deselect all the users 
     */
    function _initSelectAllButtons(){
        $("." + myoptions.selectAllButtonClass).toggle(
                function() {
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").attr('checked', true);
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                    return false;
                },
                function() {
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").attr('checked', false);
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                    return false;
                }
        );
    }
    
    function _bindFollowUsers() {
        // bind follow user(s) button click
        $('.' + myoptions.buttonFollowUser).unbind('click').bind('click',function(){
            var data = $("#" + myoptions.usersTableId + " :checked");
            _followUsers(data,false);
        });
        // bind unfollow user(s) button click
        $('.' + myoptions.buttonUnFollowUser).unbind('click').bind('click',function(){
            var data = $("#" + myoptions.usersTableId + " :checked");
            _followUsers(data,true);
        });
    }
    
    /*
     * (un)follow users by system
     * @param data - ids of users
     * @param unfollow - true to unfollow, false to follow
     */
    function _followUsers(data,unfollow) {
        var users = [];
        if (data) {
            $(data).each(function(i,dat){
                users.push($(dat).val());
            });
        }
        if (!users)
            return;
        _disableFollowButtons(true);
        $.post(
            myoptions.urls.URL_FOLLOW,
            {
                "users[]" : users,
                unfollow : unfollow ? 1 : 0
            },
            function(resp){
                _disableFollowButtons(false);
                $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                $("#" + myoptions.usersTableId + " input[type='checkbox']").attr('checked', false);
                if (resp.error && resp.error.length) {
                    $(resp.error).each(function(i,v){
                        $("#" + myoptions.usersTableId + " input[value='" + parseInt(v) + "']").attr('checked', true);
                    });
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                    if (unfollow)
                        _showMessage(myoptions.messages.ERROR_UNFOLLOW, 'error');
                    else
                        _showMessage(myoptions.messages.ERROR_FOLLOW, 'error');
                } else {
                    if (unfollow)
                        _showMessage(myoptions.messages.OK_UNFOLLOW, 'message');
                    else
                        _showMessage(myoptions.messages.OK_FOLLOW, 'message');
                }
            },
            "json"
        );
    }
    
    function _messageBoxChangeHandler(){
        // message box change handler
        $('.' + myoptions.usersActionsContainer + ' .usersSending .' + myoptions.messageTextClass)
            .limit(myoptions.consts.MESSAGE_MAX_LENGTH, '.actionsContainer .usersSending .' + myoptions.messageTextCharsCounterClass);
    }
    
    function _messageTextChangeHandler(){
        $('.' + myoptions.usersActionsContainer + ' .usersSending .' + myoptions.messageTextClass)
            .unbind('keyup').bind('keyup',function(){
            if ($("#" + myoptions.usersTableId + " :checked").length)
                _disableSendButton(false);
            else
                _disableSendButton(true);
        });
    }
    
    function _sendMessageHandler(){
        // send message handler
        $('.' + myoptions.usersActionsContainer + ' .usersSending .messageSend')
            .unbind('click').bind('click',function(){
            var message = $.trim($('.' + myoptions.usersActionsContainer + ' .usersSending .messageText').val());
            var data = $("#" + myoptions.usersTableId + " :checked");
            if (message && data.length && $("#" + myoptions.usersTableId + " :checked").length) {
                _sendMessage(message, data);
            }
        });
    }
    
    /*
     * send message to selected people
     * @param message - text of message
     * @param data - ids of users
     */
    function _sendMessage(messageText,data) {
        var users = new Array();
        var names = new Array();
        if (data) {
            $(data).each(function(i,dat){
                users.push($(dat).val());
                names.push($(dat).attr('name'));
            });
        }
        _disableSendButton(true);
        $.post(
            myoptions.urls.URL_SEND_MESSAGE,
            {
                "users[]" : users,
                "names[]" : names,
                message   : messageText
            },
            function(resp){
                _disableSendButton(false);
                $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                $("#" + myoptions.usersTableId + " input[type='checkbox']").attr('checked', false);
                if (resp.error && resp.error.length) {
                    $(resp.error).each(function(i,v){
                        $("#" + myoptions.usersTableId + " input[value='" + parseInt(v) + "']").attr('checked', true);
                    });
                    $("#" + myoptions.usersTableId + " input[type='checkbox']").trigger('change');
                    _showMessage(myoptions.messages.ERROR_SEND_MESSAGE, 'error');
                } else {
                    _showMessage(myoptions.messages.OK_SEND_MESSAGE, 'message');
                    $('.' + myoptions.usersActionsContainer + ' .usersSending .messageText').val('');
                }
            },
            "json"
        );
    }
    
    function _resetChoosenMoods(){
        // reset choosen moods
        $('#' + myoptions.moodContainerClass + ' .resetMoods')
            .unbind('click').bind('click',function(){
            $('#' + myoptions.moodContainerClass + ' .mood').each(function(){
                $(this).removeClass("mood_shown");
                $(this).parent().find('input').attr('checked', false);
            });
        });
    }
    
    function _toggleMoodChoose(){
        // toggle mood choose
        $('#' + myoptions.moodContainerClass + ' .mood').each(function(){
            $(this).toggle(
                function(){
                    $(this).addClass("mood_shown");
                    $(this).parent().find('input').attr('checked', true);
                },
                function(){
                    $(this).removeClass("mood_shown");
                    $(this).parent().find('input').attr('checked', false);
                }
            );
        });
    }
    
    /*
     * show system messages
     * @param message - message text
     * @param type - message | error
     */
    function _showMessage(message,type) {
        var messenger = new Messenger();
        messenger.show(message,type);
    }
};