jQuery.event.add(window, "load", showLogin);
jQuery.event.add(window, "resize", resizeHeight);


function resizeHeight() {
var fullHeight = $(window).height();
var headerHeight = $("#topWrapper").height();
var footerHeight = $("#desktopFooterWrapper").height();
var pageHeight = fullHeight - (headerHeight + footerHeight);
var availHeight = pageHeight - $("div#login").height() - 200;
var topmargin = (availHeight / 2);
$("div#login").css('marginTop', topmargin);
}


function showLogin() {
   br = $.browser;
   ver = parseInt(br.version.slice(0,3));

    if ($.browser.msie && ver < 8) {
        $("div#login").html('<h2>ABCD supports all modern browsers:</h2>\n\
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
    } else {
        resizeHeight();
        $("div#login").fadeIn(2000);
    }
}

