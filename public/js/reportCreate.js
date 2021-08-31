$(function() {
    var filterType  =   $( "#filterType" ),
        dataFrom    =   $( "#dataFrom")
        tips        =   $( ".validateTips" );
        reportID    =   '';
        reportName  =   '';
        
    function startGenerator() {
        values = $("#reportOptions").serialize();
        window.location = '/reports/build?'+values;
        $(this).dialog("close");
    }

    function updateTips( t ) {
            tips
                    .text( t )
                    .addClass( "ui-state-highlight" );
            setTimeout(function() {
                    tips.removeClass( "ui-state-highlight", 1500 );
            }, 500 );
    }

    function deleteReport() {
        $.post(
                "/ajax/deletestoredreport",
                {rid: reportID},
                function(data) {
                if (data.success == 'yes') {
                    reportID='';
                    $("#deleteConfirm-container").dialog("close");
                    window.location.reload();
                } else {
                    updateTips('Report not deleted. The server responded: ' + data.success);
                }
                }
        );
    }

    $( "#reportOptions-container" ).dialog({
            autoOpen: false,
            height: 275,
            width: 380,
            modal: true,
            buttons: {
                    "Launch Generator": function() {
                            startGenerator();
                    },
                    "Nevermind": function() {
                            $( this ).dialog( "close" );
                    }
            },
            close: function() {
                    
            },
            open: function(e,ui) {
                $(this).keyup(function(e) {
                    if (e.keyCode == 13) {
                        $('.ui-dialog-buttonset > button:first').trigger('click');
                    }
                });
                updateTips('Please select report options.');
            }
    });

    $( "#deleteConfirm-container" ).dialog({
        autoOpen: false,
        height: 175,
        width: 380,
        modal: true,
        buttons: {
            "Delete": function() {
                deleteReport();
            },
            "Nevermind": function() {
                $(this).dialog("close");
            }
        },
        open: function() {
            var tipText = "Are you sure you want to delete '" + reportName + "'? This cannot be undone.";
            updateTips(tipText);
        }
    });

    $(".changeFreq").click(function() {
        reportID = $(this).data('reportid');
        dropDownForm = "<select id='newFreq'>\n\
                            <option>Choose new...</option>\n\
                            <option label='Daily' value='daily'>Daily</option>\n\
                            <option label='Weekly' value='weekly'>Weekly</option>\n\
                            <option label='monthly' value='monthly'>Monthly</option>\n\
                        </select>";
        $(this).html(dropDownForm);
        $ ("#newFreq").on('change',function(){
            var newFreq = this.value;
            $.post(
                "/ajax/updatefrequency",
                {rid: reportID, freq: newFreq},
                function(data) {
                    if (data.success == 'yes') {
                        reportID = '';
                        newContent = "<a class='changeFreq' data-reportID=" + reportID + "href='#'> " + newFreq + "</a>";
                        $("#newFreq").parent().html(newContent).css('textTransform', 'capitalize');
                    } else {
                        alert('Could not change frequency. Please try again.');
                    }
                }
            );
        });
    });

    $( "#addReport" )
            .button() 
            .click(function() {
                 $( "#reportOptions-container" ).dialog( "open" );
            });
            
    $( ".deleteButton")
            .button()
            .click(function() {
                reportID = $(this).attr("id");
                reportName = $(this).parents("tr").children("td.nameTD").children("span.name").html();
                $( "#deleteConfirm-container").dialog("open");
            });
    
    
    $( "#addStoredReport")
            .button()
            .click(function() {
                window.location = '/reports/addstored';
            });
    
    $("#filterTarget").click(function() {
        if ($(this).val() == 'staff') {
            $("#filterType").val('form');
            $("#filterType option[value='group']").attr("disabled","disabled");
            $("#filterType option[value='prog']").attr("disabled","disabled");
        } else {
            $("#filterType option[value='group']").removeAttr("disabled");
            $("#filterType option[value='prog']").removeAttr("disabled");
        }
    })
}); 
