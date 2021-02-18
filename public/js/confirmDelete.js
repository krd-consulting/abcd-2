$(function(){
    var archiveType;
    var archiveID;
    proceed = true;
    
    function archive() {
           $.post(
                ("/ajax/archive"),
                {type: archiveType, id: archiveID},
                function(data) {
                    if (data['success'] == "yes") {
                        $(this).parent().dialog( "close" );
                        window.location.reload();
                    } else {
                        $("span#list").html("Archive unsuccessful:<br>" + data['message'] + "<br><br> Please try again.");
                        $("button:contains('Archive')").hide();
                        $("button:contains('Nevermind')").children().text('OK');
                         }
                });
    }
    
    function archiveConfirm(){
        $.post(
                ('/ajax/appointmentsinschedule'),
                {id: archiveID},
                function(data){
                  var numberActive = parseInt(data['num']);
                  
                  if (numberActive == 0) {
                      $("span#list").text("There seem to be no active appointments in this calendar set. You will not lose anything by archiving it.");
                  } else if (numberActive > 0) {
                      $("span#activenum").text(" " + numberActive + " ");
                  } else {
                      $("span#list").text("There was an error trying to parse this request. Please try again.");
                      $("button:contains('Archive')").hide();
                  }
                }
                );
        $("#dialog-confirm").dialog('open');
    }
    
    $("#dialog-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            "Archive": function() {
                archive();
            },
            "Nevermind": function() {
                $(this).dialog('close');
            }
        }
   });
    
    

    $(".view-link.delete a")
            .removeAttr("href")
            .click(function(){
                archiveType = $(this).data("type");
                archiveID   = $(this).data("id");
                archiveConfirm();
            })
});

