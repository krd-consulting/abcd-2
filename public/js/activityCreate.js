$(function() {
		
    var 
        ptcpName        =   $( "#addActivityForm #ptcpName" ),
        date            =   $( "#addActivityForm #date" ),
        duration        =   $( "#addActivityForm #duration" ),
        note            =   $( "#addActivityForm #note" ),
        userID          =   $( "#addActivityForm #userID" ),
        participantID   =   $( "#addActivityForm #participantID" ),
        tips            =   $( "#addAct-dialog p" );

    function addActivity () {
        $.post(
                "/ajax/addact",
                {column: 'participantID', date: date.val(), duration: duration.val(), note: note.val(), uid: userID.val(), pid: participantID.val()},
                function(data) {
                if (data.success == 'yes') {
                    window.location.reload();
                } else {
                    updateTips('Activity not added. Please try again.');
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

    $( "#addAct-dialog" ).dialog({
            autoOpen: false,
            height: 365,
            width: 540,
            resize: true,
            modal: true,
            buttons: {
                    "Create It!": function() {
                            var bValid = true;
                            date.removeClass( "ui-state-error" );
                            duration.removeClass( "ui-state-error" );
                            bValid = bValid && checkLength( date, "Date", 1);
                            bValid = bValid && checkLength( duration, "Duration", 1);
                            
                            if ( bValid ) {
                                    addActivity();
                            } else {
                                    duration.focus();
                            }
                    },
                    "Nevermind": function() {
                            $( this ).dialog( "close" );
                    }
            },
            close: function() {
                    date.val( "" ).removeClass( "ui-state-error" );
                    duration.val( "" ).removeClass( "ui-state-error" );
                    note.val( "" ).removeClass( "ui-state-error" );
            },
            open: function(e,ui) {
                duration.focus();
                $(this).keyup(function(e) {
                    if (e.keyCode == 13) {
                        $('.ui-dialog-buttonset > button:first').trigger('click');
                    }
                });
            }
    });

    $( "#add-activities" )
            .button()
            .click(function() {
                    ptcpName.attr('disabled','disabled');
                    $( "#addAct-dialog" ).dialog( "open" );
            });
});