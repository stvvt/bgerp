function slidebars(){
    initElements();
	openSubmenus();
	userMenuActions();
    sidebarsActions();
    calculateSize();

}

/**
 * Създава лентите и задава необходините опции спрямо ширината на страницата
 */
function initElements() {
    if ($('#main-container > .tab-control > .tab-row').length == 0) {
        $('#framecontentTop').css('border-bottom', '1px solid #ccc');
    }
}
/*
	var viewportWidth = $(window).width();
	if(viewportWidth > 600){
		 $('.btn-sidemenu').jPushMenu({closeOnClickOutside: false, closeOnClickInside: false});
	} else {
		$('.btn-sidemenu').jPushMenu();
	}
	if(getCookie('menuInformation') == null && viewportWidth > 1264 && !isTouchDevice()) {
		$('.btn-menu-left ').click();
		if(viewportWidth > 1604){
			$('.btn-menu-right ').click();
		}
	}

	$('.sidemenu,  #main-container,  .narrow #packWrapper , #framecontentTop, .tab-row').addClass('transition');

	if($('body').hasClass('narrow') && viewportWidth <= 800){
        setViewportWidth(viewportWidth);
        $(window).resize( function() {
            viewportWidth = $(window).width();
            setViewportWidth(viewportWidth);
        });
	}
	*/

function calculateSize(){
    $('.tableHolder').css('height', $(window).height());
    $('.tableHolder .relativeHolder').css('height', $(window).height());

}

function closeSidebar(side){
   if(side != "left" && side != "right") return;
    $( ".sidebar-" + side).animate({
        width: "0"
    }, 500, function() {
        $( ".sidebar-" + side).addClass('nonVisible');
        $( ".sidebar-" + side).removeClass('menu-active');
        setMenuCookie();
    });
}

function openSidebar(side){
    if(side !== 'left' &&  side !== 'right') return;
    $( ".sidebar-" + side).removeClass('nonVisible');
    $( ".sidebar-" + side).animate({
        width: "200px"
    }, 500, function() {
        $( ".sidebar-" + side).addClass('menu-active');
        setMenuCookie();
    });
}

function sidebarsActions() {
    $('body').on('click', function(e){
        var target = e.target;
        if(!$(target).is('a')) target = $(target).parent();


        if($(window).width() < 600 &&  !$(target).is('.btn-menu-left') && !$(target).is('.btn-menu-right')) {
            //closeSidebar('left');
            //closeSidebar('right');
        }


            if($(target).is('.btn-menu-left')){
            if ( $(".sidebar-left").hasClass('menu-active')) {
                closeSidebar('left');
            } else {
                openSidebar('left');
                if($(window).width() < 1200) {
                    closeSidebar('right');
                }
            }
            if($('#debug_info').css('display') == 'block'){
                $('#debug_info').fadeOut();
            }
        }
        if($(target).is('.btn-menu-right')){
            if ( $(".sidebar-right").hasClass('menu-active')) {
                closeSidebar('right');
            } else {
                openSidebar('right');
                if($(window).width() < 1200) {
                    closeSidebar('left');
                }
            }
            if($('#debug_info').css('display') == 'block'){
                $('#debug_info').fadeOut();
            }
            changePinIcon();
        }
        calculateSize()
    });
}



function openDebugInfo() {
    var sidebarWidth= 200 * $('.sidebars.menu-active').length;
    $('.debugHolder').css('width', $(window).width() - sidebarWidth - 25);
    toggleDisplay('debug_info');
    scrollToElem('debug_info');
}


/**
 * Записваме информацията за състоянието на менютата в бисквитка
 */
function setMenuCookie(){
	if ($(window).width() < 700) return;
	
	var menuState = "";
	if($('.sidebar-left').hasClass('menu-active')){
		menuState += "l";

	}
	if($('.sidebar-right').hasClass('menu-active')){
        menuState += "r";
	}
	
	var openMenus = '';
	$('#nav-panel > ul > li.open').each(function() {
		if ($(this).attr('data-menuid') != 'undefined')
			openMenus += $(this).attr('data-menuId') + ",";
	});
	menuState += " " + openMenus;
	setCookie('menuInfo', menuState);
}


/**
 * кои подменюта трябва да са отворени след зареждане
 */
function openSubmenus() {
	if ($(window).width() < 700) return;
	
	var menuInfo = getCookie('menuInfo');
    if (menuInfo!==null && menuInfo.length > 1) {
    	var startPos = menuInfo.indexOf(' ');
    	var endPos = menuInfo.length ;
    	menuInfo = menuInfo.substring(startPos, endPos);
    	
    	menuArray = menuInfo.split(',');
        
        $.each(menuArray, function( index, value ) {
        	value = parseInt(value);
        	$("li[data-menuid='" + value + "']").addClass('open');
        	$("li[data-menuid='" + value + "']").find('ul').css('display', 'block');
        });
    }
}


/**
 * състояние на иконката за пинването
 */
function changePinIcon(){
    	if ($('.sidebar-right').hasClass('menu-active')) {
            $('.pinned').addClass('hidden');
            $('.pin').removeClass('hidden');
    	} else {

            $('.pin').addClass('hidden');
            $('.pinned').removeClass('hidden');
    	}
}


/**
 * действия за потребителското меню
 */
function userMenuActions() {
	$('body').on('click', function(e){
    	if($(e.target).is('.menu-options') || $(e.target).is('.menu-options > img') ) {
            var element = $(e.target).parent().find('.menu-holder');
            if ( $(element).css('display') == 'none' ){
                $('.menu-holder').css('display', 'none');
                $(element).css('display', 'table');
            } else {
                $(element).css('display', 'none');
            }

            // При отваряне да се фокусира input полето
            var input = $(e.target).parent().find('.menu-holder > input');
            if (input) {
            	input.focus();
            }
    	}
    	else{
            if (!($(e.target).is('.menu-holder > input')) ) {
                $('.menu-holder').hide();
            }
    	}
    });
}

/**
 * Създава бисквитка
 */
function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}


/**
 * Чете информацията от дадена бисквитка
 */
function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
}

/**
 * Действия на акордеона в меюто
 */
function sidebarAccordeonActions() {
	$('#nav-panel li:not(.selected) ul').css('display', 'none');
	$('#nav-panel li.selected').addClass('open');

	$("#nav-panel li div").click( function() {
		$(this).parent().toggleClass('open');
		$(this).parent().find('ul').slideToggle(
            function () {
                if($(this).parent().hasClass('open')) {
                    var scrollTo = $(this).parent().find('ul li:last');
                    if (scrollTo.offset().top > $(window).height()) {
                        var position = $(this).parent().offset().top - $(window).height() + $(this).parent().outerHeight()
                        $('#nav-panel').animate({
                            scrollTop:  position
                        }, 500)
                    }
                }
            }
        );
		setMenuCookie();
	});
}

/**
 * Задава максиналната височина на опаковката и основното съдържание
 */
function setMinHeight() {
	 if($('.inner-framecontentTop').length){
		 var menuHeight = $('.tab-control > .tab-row').first().height();
		 var headerHeight = parseInt($('.inner-framecontentTop').height(), 10);
		 var calcMargin = headerHeight + menuHeight;
		 if ($('body').hasClass('narrow')){
			 $(window).scrollTop(0);
			 $('#maincontent').css('margin-top', calcMargin - 12);
		 }
		 var clientHeight = parseInt(document.documentElement.clientHeight,10);
		 $('#packWrapper').css('min-height', clientHeight - headerHeight - 68);
		 $('#maincontent').css('min-height', clientHeight - headerHeight - 38);
	 }
}

/**
 * Скролира listTable, ако е необходимо
 */
function scrollLongListTable() {
    if ($('body').hasClass('wide') && !$('.listBlock').hasClass('doc_Containers')) {
        var winWidth = parseInt($('#maincontent').width()) - 45;
        var tableWidth = parseInt($('.listBlock .listTable').width());
        if (winWidth < tableWidth) {
            $('.listBlock .listRows').addClass('overflow-scroll');
            $('.main-container').css('display', 'block');
            $('.listBlock').css('display', 'block');
        }
    }
}


function scrollToElem(docId) {
	$('html, body').animate({
        scrollTop: $("#" + docId).offset().top - $(window).height() + $(this).height() - 75
    }, 500);
}

/**
 * Скролиране до елемента
 * */
function scrollToHash(){
	var hash = window.location.hash;
	if($(hash).length) {
        setTimeout(function() {
			var scrollTo = $(hash).offset().top - 70;
			if (scrollTo < 400) {
				scrollTo = 0;
			}
			$('html, body').scrollTop(scrollTo, 0);
		}, 1);
	}
}


function disableScale() {
    if (isTouchDevice()) {
        $('meta[name=viewport]').remove();
        $('head').append('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">');
    }
}


/**
 * 
 * @param obj
 * @param inputClassName
 * @param fieldName
 */
function searchInLink(obj, inputClassName, fieldName, haveGet)
{
	var inputVal = $('.' + inputClassName).val();
	if (inputVal) {
		
		var amp = '&';
		if (!haveGet) {
			amp = '?';
		}
		
		window.location.href = obj.href = obj.href + amp + fieldName + '=' + encodeURIComponent(inputVal);
	}
	
	window.location.href = obj.href;
}


/**
 * При натискане на ентер симулира натискане на линка
 * 
 * @param obj
 */
function onSearchEnter(obj, id, inp)
{
	if (obj.keyCode == 13) {
		if (!inp || (inp && $(inp).val().trim())) {
			$('#' + id).click();
		}
    }
}

function stopScalingOnTouch(){
    if (isTouchDevice()) {
        $('meta[name=viewport]').remove();
        $('head').append('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">/>');
    }
}