$(function() {
		
    var 
        volName         =   $( "#addVolActivityForm #volName" ),
        date            =   $( "#addVolActivityForm #date" ),
        note            =   $( "#addVolActivityForm #note" ),
        userID          =   $( "#addVolActivityForm #userID" ),
        volID           =   $( "#addVolActivityForm #volunteerID" ),
        program         =   $( "#addVolActivityForm #program"),
        fromTime        =   $( "#addVolActivityForm #fromTime"),
        toTime          =   $( "#addVolActivityForm #toTime"),
        volBeneficiary  =   $( "#addVolActivityForm #volBenef"),
        tips            =   $( "#addAct-dialog p" ),
        times           = $("#fromTime, #toTime"),
        deleteActID     = '',
        
        secondaryInputs =   $('#toTime-label, #toTime-element, #note-label, #note-element');

    function nonZeroTime(from,to) {
        if (from >= to) {
            times.addClass("ui-state-error");
            updateTips("End time has to be after start time!");
            fromTime.focus();
        } else {
            addActivity();
        }
    }

    function addActivity () {
        $.post(
                "/ajax/addactvol",
                {   date: date.val(), 
                    from: fromTime.val(), 
                    to: toTime.val(), 
                    note: note.val(), 
                    userID: userID.val(), 
                    volID: volID.val(), 
                    programID: program.val(),
                    targetID: volBeneficiary.data("targetid"),
                    targetType: volBeneficiary.data("vtype")
                },
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

    $( "#removeActivity-dialog").dialog({
        autoOpen: false,
        height: 170,
        width: 270,
        resize: false,
        modal: true,
        buttons: {
            "Delete": function() {
                $.post(
                    "/ajax/deleteactivity",
                    {id : deleteActID},
                    function(data) {
                            window.location.reload();
                            $(this).dialog("close");
                    }
                )
            },
            "Nevermind": function() {
                $(this).dialog("close");
            }
        }
    })

    $( "#addAct-dialog" ).dialog({
            autoOpen: false,
            height: 400,
            width: 675,
            resize: true,
            modal: true,
            buttons: {
                    "Create It!": function() {
                            var bValid = true;
                            date.removeClass( "ui-state-error" );
                            fromTime.removeClass( "ui-state-error" );
                            toTime.removeClass( "ui-state-error" );
                            bValid = bValid && checkLength( date, "Date", 1);
                            bValid = bValid && checkLength( program, "Program", 1);
                            bValid = bValid && checkLength( fromTime, "Start time", 1);
                            bValid = bValid && checkLength( toTime, "End time", 1);
                            
                            if ( bValid ) {
                                    nonZeroTime(fromTime.val(),toTime.val());
                            } else {
                                    toTime.focus();
                            }
                    },
                    "Nevermind": function() {
                            $( this ).dialog( "close" );
                    }
            },
            close: function() {
                    date.val( "" ).removeClass( "ui-state-error" );
                    fromTime.val( "" ).removeClass( "ui-state-error" );
                    toTime.val( "" ).removeClass( "ui-state-error" );
                    note.val( "" ).removeClass( "ui-state-error" );
            },
            open: function(e,ui) {
                program.focus();
                $(this).keyup(function(e) {
                    if (e.keyCode == 13) {
                        $('.ui-dialog-buttonset > button:first').trigger('click');
                    }
                });
            }
    });

    
            
    function launchToTime(tX) {
        $('.timepicker.end').timepicker({
            timeFormat: 'HH:mm',
            dynamic: true,
            dropdown: false,
            scrollbar: false
        });
        secondaryInputs.show();        
        $('.timepicker.end').focus();
        
    }

    $('.timepicker.start').timepicker({
        timeFormat: 'HH:mm',
        defaultTime: '',
        startTime: '',
        dynamic: true,
        dropdown: false,
        scrollbar: false,        
    });
    
    $('input#fromTime').change(function() {
            textTime = $(this).val();
            launchToTime(textTime);  
    });
            
    
    program.change(function() {
        progID = $(this).val();
        if (progID > 0) {
            $.post(
                "/ajax/getprogvolinfo",
                {progid: progID},
                function(d) {
                if (d.success == 'yes') {
                    volType = d.volType;
                    displayType = d.displayType;
                    progID = d.progID;
                    $("#volBenef-label label").text(displayType + " helped:");
                    $("#volBenef").prop('disabled',false)
                                  .removeData( "progid" )
                                  .removeData( "vtype" )
                                  .val("")
                                  .attr("data-progid",progID)
                                  .attr("data-vtype",displayType)
                                  .focus();
                    
                    
                } else {
                    updateTips('Invalid program selection: ' + data.errormessage);
                }
                }
            )
        } else {
            updateTips('Please select a program.');ß
        }
    })
    
    $( "#add-activities" )
            .button()
            .click(function() {
                    volName.prop('disabled',true);
                    volBeneficiary.prop('disabled',true);
                    //secondaryInputs.hide();
                    $( "#addAct-dialog" ).dialog( "open" );
            });

    $( "#schedule-upcoming") 
            .button()
            .click(function(){
                window.location.replace('/my/calendar');
            });
            
    $ ( ".progCalendar")
            .button()
            .click(function() {
                newURL = '/programs/calendar/id/' + $(this).data('progid');
                window.location.replace(newURL);
            });
            
            $("#Cal").button()
                            .click(function() {
                            window.location = "/volunteers/calendar/id/" + $(this).data('id');
                    });
                    
    $("div#activities-block .remove-pic")
                    .click(function(){
                        type = $(this).data('type');
                        deleteActID = $(this).data('id');
                        $("#removeActivity-dialog p.dialog-content")
                                .text("You are about to delete this activity. Are you sure?");
                        $("#removeActivity-dialog").dialog("open");
                    });
});