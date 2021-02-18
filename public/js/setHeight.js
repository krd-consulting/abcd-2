jQuery.event.add(window, "load", resizeHeight);
jQuery.event.add(window, "load", tabSelect);
jQuery.event.add(window, "resize", resizeHeight);
jQuery.event.add(window, "load", checkBrowser);

if (window.location.pathname != "/auth") {
 jQuery.event.add(window, "load", showTimeOut);
}

function showTimeOut() {
    $.timeoutDialog(
            {
                'timeout': 3600, //60 minutes
                'countdown': 300,
                'title': "Auto-Logout Warning",
                'question': "Please click below to renew your session.",
                'keep_alive_button_text': "Renew",
                'sign_out_button_text': "Log out",
                'keep_alive_url': "/ajax/sessionrenew",
                'logout_url': "/auth/logout",
                'logout_redirect_url': "/auth/logout?q=timeout"
            });
}

function resizeHeight() {
var fullHeight = $(window).height();
    var headerHeight = $("#topWrapper").height();
    var footerHeight = $("#desktopFooterWrapper").height();
    
    var pageHeight = fullHeight - (headerHeight + footerHeight);
    
    var titleHeight = $("#mainColumn h1").height();
    var buttonRowHeight = $("#content-top").height();
    
    var contentHeight = pageHeight - (titleHeight + buttonRowHeight) - 60;
    
    
    $("#content-main").height(contentHeight);
    $("#formBuilder").height(contentHeight);
    $("ul.connectedSortable").height(contentHeight - 83);
    $(".ui-tabs").css('min-height', contentHeight-15);    
}

function tabSelect() {
    var tabID = $('.ui-tabs').attr('id');
    var cookieName = 'stickyTab-' + tabID;
    var selected = ($.cookie(cookieName) || 0);
    if (selected > 0) {
        var active = selected++;
        $('.ui-tabs').tabs("select", active);
    }
    
    $('.ui-tabs').bind("tabsselect", function(event, ui) {
        $.cookie(cookieName, ui.index);
    });
    
    $('ul.navigation a').click(function(){
        $.cookie('stickyTab-ptcp', 0);
        $.cookie('stickyTab-prog', 0);
        $.cookie('stickyTab-group', 0);
        $.cookie('stickyTab-dept', 0);
        $.cookie('stickyTab-funders', 0);
        $.cookie('stickyTab-form', 0);
    })
   
}

function checkBrowser() {
   br = $.browser;
   ver = parseInt(br.version.slice(0,3));

   if ($.browser.msie && ver < 8) {
        $("#mainColumn").html('<h2>ABCD supports all modern browsers:</h2>\n\
                            <ul>\n\
                                <li>Internet Explorer 8 and above,\n\
                                <li>Mozilla Firefox 3 and above,\n\
                                <li>Google Chrome 9 and above,\n\
                                <li>Safari 3 and above,\n\
                                <li>Opera 10 and above.\n\
                            </ul> \n\
\n\
                             <div class="ui-state-error" style="font-size: 11pt">Your browser, Internet Explorer ' + $.browser.version + ', is not supported.<br>\n\
                             <i>Please join <a href="http://www.w3schools.com/browsers/browsers_explorer.asp"> 95.6% of worldwide Internet users</a> and upgrade your browser.</i></div>')
                      .fadeIn(2000);
    }
}
