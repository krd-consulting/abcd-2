$(function() {
		
    var status      =    $( ":input#status"),
        sNote       =    $( ":input#note"),
        tips        =    $( ".validateTips" ),
        ptcpName, ptcpID, userID;

    function updateTips( t ) {
        tips
            .text( t )
            .addClass( "ui-state-highlight" );
        setTimeout(function() {
                tips.removeClass( "ui-state-highlight", 1500 );
        }, 500 );
    }

    

    function updateStatus() {
        newStatus = status.val();
        newNote   = sNote.val();
        
        $.post(
            "/ajax/casestatus",
            {ptcpID: ptcpID, userID: userID, progID: progID, status: newStatus, note: newNote},
            function(data) {
                //ptcpURL = window.location.pathname + '#prog-frag-4';
                //window.location = ptcpURL;
                window.location.reload();
                $("#dialog-form").dialog("close");
               
            }
        );
        
    }

    $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 260,
                    width: 430,
                    modal: true,
                    buttons: {
                            "Update Status": function() {
                                    updateStatus();
                             },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                             }
                    },
                    close: function() {
                            sNote.val( "" );
                    },
                    open: function(e,ui) {
                        updateTips('Please select a new status. Notes are optional.');
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                return false;
                            }
                        });
                    }
            });



    
$( "a.changeStatus" )
    .click(function() {
        ptcpName = $(this).parents('tr').children('td.nameTD').children('span.name').text();
        progID = $(this).data('statusprogid');
        ptcpID = $(this).data('ptcpid');
        userID = $(this).data('progid'); //form being created with "data-progid" for legacy reasons, but correctly stores userID
        dTitle = "Update file status for " + ptcpName;    
            $("#dialog-form")
                        .dialog("option", "title", dTitle)
                        .dialog("open");
    });
    
    
});
