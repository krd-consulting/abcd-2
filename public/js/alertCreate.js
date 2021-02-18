$(function() {
		
    var 
        alertBody   =   $( "#addAlertForm #alert" ),
        formTarget  =   $( "#addAlertForm #formTarget" ),
        name        =   $( "#addAlertForm #name" ),
        startDate   =   $( "#addAlertForm #startDate" ),
        targetID   =   $( "#addAlertForm #targetID" ),
        tips        =   $( "#addAlert-dialog .validateTips" );

    function addAlert () {
        $.post(
                "/ajax/addalert",
                {alertText: alertBody.val(), target: formTarget.val(), targetID: targetID.val(), startDate: startDate.val()},
                function(data) {
                if (data.success == 'yes') {
                    window.location.reload();
                } else {
                    updateTips('Alert not added. Please try again.');
                }
                }
        );
    }

    function updateTips( t ) {
            tips
                    .text( t )
                    .addClass( "ui-state-highlight" );
            setTimeout(function() {
                    tips.removeClass( "ui-state-highlight", 1500 );
            }, 500 );
    }

    function checkLength( o, n, min ) {
            if ( o.val().length < min ) {
                    o.addClass( "ui-state-error" );
                    updateTips( n + " cannot be empty." );
                    return false;
            } else {
                    return true;
            }
    }

    $( "#addAlert-dialog" ).dialog({
            autoOpen: false,
            height: 365,
            width: 490,
            modal: true,
            buttons: {
                    "Create It!": function() {
                            var bValid = true;
                            alertBody.removeClass( "ui-state-error" );
                            bValid = bValid && checkLength( alertBody, "Alert text", 1);
                            
                            if ( bValid ) {
                                    addAlert();
                            } else {
                                    name.focus();
                            }
                    },
                    "Nevermind": function() {
                            $( this ).dialog( "close" );
                    }
            },
            close: function() {
                    alertBody.val( "" ).removeClass( "ui-state-error" );
            },
            open: function(e,ui) {
                $(this).keyup(function(e) {
                    if (e.keyCode == 13) {
                        $('.ui-dialog-buttonset > button:first').trigger('click');
                    }
                });
            }
    });

    $( "#add-alerts" )
            .button()
            .click(function() {
                    $( "#addAlert-dialog" ).dialog( "open" );
            });
            
    $("div#alerts-block .remove-pic")
                    .click(function(){
                        li = $(this).parents('li');
                        id = li.attr('id');
                        type = $(this).data('type');
                        
                        pid = $("#mainColumn h1").attr('id');
                        $.post(
                            '/ajax/removealert',
                            {id: id, pid: pid, type: type},
                            function(data) {
                                li.remove();
                            }
                        );
                    });
                    
    formTarget.change(function() {
        name.val('')
            .removeClass('ui-state-error');
    });
});