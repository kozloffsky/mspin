jQuery.fn.network = function(options) {
	settings = jQuery.extend({
		URL_GET_RESULTS    : '/my-network/results/',
		maskMessage        : 'Loading',
		useTooltips        : false,
		tooltipWrapper     : '.results',
        tooltipTargetClass : '.hasTooltip'
	}, options);
	
	var thisObj = this;
	thisObj.settings = settings;
	
	init();
	
	function init() {
		submitFormHandler();
		bindPrevButton();
		bindNextButton();
		bindShowAllButton();
		initTooltips();
	}
	
	function initTooltips() {
		if (thisObj.settings.useTooltips) {
			$(thisObj.settings.tooltipWrapper).tooltips({
	            targetClass : thisObj.settings.tooltipTargetClass
	        });
		}
	}
	
	function submitFormHandler() {
		$(thisObj).submit(function(event){
			event.preventDefault();
			disableSubmitButtons(true);
			$(thisObj).submit();
			return true;
		});
	}
	
	/**
	 * bind click to 'previous button'
	 */
	function bindPrevButton(){
		$(thisObj).find('.results .prev').unbind('click').bind('click',function(){
			if ($(this).hasClass('disabled')) {
				return false;
			}
			var moodId = $(this).parent().attr('rel');
			var page = $(this).parent().attr('rev');
			getPage(moodId,-1,page,this);
			return false;
		});
	}
	
	/**
	 * bind click to 'next button'
	 */
	function bindNextButton() {
		$(thisObj).find('.results .next').unbind('click').bind('click',function(){
			if ($(this).hasClass('disabled')) {
				return false;
			}
			var moodId = $(this).parent().attr('rel');
			var page = $(this).parent().attr('rev');
			getPage(moodId,1,page,this);
			return false;
		});
	}
	
	/**
	 * bind click to 'show more button'
	 */
	function bindShowAllButton() {
		$(thisObj).find('.results .more').unbind('click').bind('click',function(){
			var moodId = $(this).parent().attr('rel');
			showAllForMood(moodId,this);
			return false;
		});
	}
	
	/**
	 * bind click to 'show less button'
	 */
	function bindShowLessButton(url) {
		$(thisObj).find('.results .less')
			.unbind('click')
			.bind('click',function(){
				gotoResultPage(url);
				return false;
			});
	}
	
	/**
	 * get page of users with moodId
	 * 
	 * @param int moodId
	 * @param int dir (-1|1)
	 * @param page
	 * @param target
	 */
	function getPage(moodId,dir,page,target) {
		$('body').mask(thisObj.settings.maskMessage);
		$.post(
			settings.URL_GET_RESULTS,
			{
				'mood_id[]'  : moodId,
				results_type : 'showResultsPage',
				page_dir     : dir,
				offset       : page
			},
			function(resp){
				$('body').unmask();
				if (resp) { 
					$(target).parent()
						.after('' + resp)
						.remove();
					bindNextButton();
					bindPrevButton();
					bindShowAllButton();
					initTooltips();
				} else {
					disableButton('next');
				}
			},
			"html"
		);
	}
	
	/**
	 * gets all friends for moodId
	 * 
	 * @param int moodId
	 * @param target - object where event whappend
	 */
	function showAllForMood(moodId,target) {
		$('body').mask(thisObj.settings.maskMessage);
		$.post(
			settings.URL_GET_RESULTS,
			{
				'mood_id[]'   : moodId,
				results_type : 'showMoreResultsForMood'
			},
			function(resp){
				$('body').unmask();
				$(target).parents('ul.results').html('' + resp);
				bindShowLessButton(thisObj.settings.URL_GET_RESULTS);
				initTooltips();
			},
			"html"
		);
	}
	
	/**
	 * goto page with results
	 */
	function gotoResultPage(url) {
		if (url)
			document.location.href = url;
		else
			return false;
	}
	
	/**
	 * set disabled class to the object with the class 'button'
	 * 
	 * @param string button - name of the class of the object
	 */
	function disableButton(button) {
		$(thisObj).find('.results .' + button).addClass('disabled');
	}
	
	/**
	 * remove disabled class from the object with the class 'button'
	 * 
	 * @param string button - name of the class of the object
	 */
	function enableButton(button) {
		$(thisObj).find('.results .' + button).removeClass('disabled');
	}
	
	function disableSubmitButtons(disable) {
		if (disable) {
			$(thisObj).find('.save').disabled = true;
			$(thisObj).find('.cancel').disabled = true;
		} else {
			$(thisObj).find('.save').disabled = false;
			$(thisObj).find('.cancel').disabled = false;
		}
	}
};