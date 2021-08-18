$(function(){
    //disable checkboxes
    //2018 -- WHY ARE WE DOING THIS?
    //changed it to look for class. previously was disabling all checkboxes. -rk
    $("input:checkbox.disabled").attr('disabled','disabled');
    
    //set link buttons
    $("button.link").button()
                    .click(function(){
                       goTo = $(this).data('path');
                       window.location = goTo;
                    });
});

