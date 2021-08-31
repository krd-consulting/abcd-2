$(function(){
    //disable checkboxes
    $("input:checkbox").attr('disabled','disabled');
    
    //set link buttons
    $("button.link").button()
                    .click(function(){
                       goTo = $(this).data('path');
                       window.location = goTo;
                    });
});

