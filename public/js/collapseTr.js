$(function(){
    
    function openMe(openTR) {
        $('tr.collapsible').not(openTR)
                           .each(function() {
                                $(this).nextUntil('tr.collapsible').hide();
                                $(this).children('td').removeClass('open');
                            })
        openTR.nextUntil('tr.collapsible').show();
        openTR.children('td').addClass('open');
    }
    
    var first = $('tr.collapsible:first');
    
    $('tr.collapsible').click(function() {
            openMe($(this));
        });
    
    openMe(first);

});

