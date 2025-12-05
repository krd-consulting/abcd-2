$(function() {
		
    var unlockForm  =    $("#unlock-form"),
        username    =    unlockForm.find( ":input#username"),
        password    =    unlockForm.find( ":input#password"),
        tips        =    unlockForm.find( ".validateTips" ),
        uid         =    unlockForm.find( ":input#userID");
        

    function updateTips( t ) {
        tips
            .text( t )
            .addClass( "ui-state-highlight" );
        setTimeout(function() {
                tips.removeClass( "ui-state-highlight", 1500 );
        }, 500 );
    }
    
    function checkLength( o, n, min, max ) {
                    if ( o.val().length > max || o.val().length < min ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n + " must be between " +
                                    min + " and " + max + " characters." );
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error");
                            return true;
                    }
            }

    function unLock() {
        id = uid.val();
        pwd = password.val();
        
        $.post(
            "/users/unlock",
            {uid: id, pwd: pwd},
            
            function(data) {  
                window.location.reload();
                $(".dialog-form").dialog("close");
            }
        );
        
    }

    $( "#unlock-form" ).dialog({
                    autoOpen: false,
                    height: 260,
                    width: 430,
                    modal: true,
                    buttons: {
                            "Unlock User": function() {
                                    var bValid = true;
                                    password.removeClass( "ui-state-error" );
                                    
                                    bValid = bValid && checkLength( password, "Password", 6, 20 );
                                    
                                    if ( bValid ) {
                                            updateTips('Valid Password - unlocking...');
                                            unLock();
                                    }
                                    
                             },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                             }
                    },
                    close: function() {
                            username.val( "" );
                            password.val( "" );
                    },
                    open: function(e,ui) {
                        updateTips('Please enter a new login password for this account:');
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                return false;
                            }
                        });
                    }
            });

   

$("a:contains('unlock')")
    .click(function() {
        myName = $(this).parents('tr').find('div.pName').text();
        uid.val($(this).parents('tr').attr('id'));
        username.val(myName).attr('disabled','disabled');
        dTitle = "Unlock ABCD access for " + myName;    
            $("#unlock-form")
                        .dialog("option", "title", dTitle)
                        .dialog("open");
    });

    // for users/profile.phtml unlock button
    $("#unlock-button")
        .click(function() {
            let name = $('span.p-name').text().trim();
            uid.val($(this).data('id'));
            username.val(name).attr('disabled','disabled');
            dTitle = "Unlock ABCD access for " + name;    
                $("#unlock-form")
                            .dialog("option", "title", dTitle)
                            .dialog("open");
        });
});
