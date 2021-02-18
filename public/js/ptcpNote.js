$(function() {
		
    var status      =    $( ":input#status"),
        sNote       =    $( ":input#note"),
        tips        =    $( ".validateTips" ),
        ptcpName, ptcpID, progID;

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
            "/ajax/programstatus",
            {ptcpID: ptcpID, programID: progID, status: newStatus, note: newNote},
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



    
$( "a.changeStatus" ).not("a.off")
    .click(function() {
        ptcpName = $(this).parents('tr').children('td.nameTD').children('span.name').text();
        ptcpID = $(this).data('ptcpid');
        progID = $(this).data('progid');
        dTitle = "Update program status for " + ptcpName;    
            $("#dialog-form")
                        .dialog("option", "title", dTitle)
                        .dialog("open");
    });
    
$( "a.off").click(function() {
    trParent = $(this).parents('tr');
    tdParent = $(this).parents('td');
    trParentHTML = trParent.html();
    assignedTo = tdParent.find('span.ac-extra').text();
    
    trParent.html("<td colspan=3><h3>" + assignedTo + "</h3></td>");
    trParent.addClass('ui-state-highlight');
    
    setTimeout(function(){
        trParent.removeClass('ui-state-highlight');
        trParent.html(trParentHTML);
    },2500);
})    
});
