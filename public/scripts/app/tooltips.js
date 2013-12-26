jQuery.fn.tooltips = function(options) {
    settings = jQuery.extend({
        setContent     : '',
        element        : null,
        defaultContent : 'loading...',
        targetClass    : '.hasTooltip',
        tooltipContent : '.tooltips',
        bindLinks      : true,
        tooltipFixed   : true
    }, options || {});

    var thisObj = this;
    var tooltipElement;

    createTooltips();

    function createTooltips() {
        if (settings.element) {
            tooltipElement = $(settings.element);
        } else {
            tooltipElement = $(thisObj).find(settings.targetClass);
        }
        $(tooltipElement).qtip({
            content : {
        		text      : settings.setContent ? settings.setContent : settings.defaultContent,
				prerender : false
        	},
            position : {
                corner : {
                    target  : 'bottomMiddle',
                    tooltip : 'topMiddle'
                },
                adjust : {
                    scroll : true,
                    resize : true,
                    screen : true
                }
            },
            style : {
                background: '#FFCC5C',
                width : {
                    max : 200,
                    min : 200
                },
                border: {
                    width: 3,
                    radius: 3,
                    color: '#1B1B1B'
                },
                tip : {
                    corner : 'topMiddle',
                    size : {
                       x : 9,
                       y : 5
                    }
                }
            },
            show : {
            	ready : false,
            	solo  : true
            },
            hide : {
                fixed : settings.tooltipFixed
            },
            api : {
                beforeShow : function () {
                    if (!settings.setContent) {
                        var content;
                        content = $(this.elements.target)
                            .parents()
                            .find(settings.tooltipContent).html();
                        content = jQuery.trim(content);
                        if (!content) {
                            return false;
                        }
                        this.updateContent(
                            content,
                            true
                        );
                    }
                    if (settings.bindLinks == true) {
                    	bindEvents(this.elements.tooltip);
                    }
                    return true;
                }
            }
        });
    }

    function bindEvents(tooltip) {
        $(tooltip).find('ul a').click(function(e) {
            var unescapeHtml = function (html) {
                var temp = document.createElement("div");
                temp.innerHTML = html;
                var result = temp.childNodes[0].nodeValue;
                temp.removeChild(temp.firstChild)
                return result;
            };

            var href = this.href.split('#')[1];
            if (href) {
                var textarea = $('#moodChooser textarea');
                if (href.substr(0, 2) == 'RT') {
                    href = href.substr(2, href.length - 2);
                    textarea.val(unescapeHtml('RT ' + href + ' ' + $('p', this.parentNode.parentNode.parentNode).html()));
                } else {
                    textarea.val(href + ' ');
                }
                textarea.focus();
                return false;
            }
        });
    }

};