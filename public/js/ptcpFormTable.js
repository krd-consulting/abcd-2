$(function(){
    
    
    $("a.toggleRecord").click(function(){
        dataDiv = $(this).parents('td').find("div.content-data");
        if (dataDiv.hasClass('hidden')) {
            dataDiv.slideDown(500)
                   .removeClass('hidden');
        } else {
            dataDiv.slideUp(500)
                   .addClass('hidden');
        }
    })

    $("button.addRecord").button()
                         .click(function(){
                             var pID = $("h1.participantProfile").attr('id');
                             var hText = $("h1.participantProfile").text();
                             var pName = hText.split(': ')[1].trim();
                             
                             $.cookie("ptcpID",pID,{path: '/'});
                             $.cookie("ptcpName",pName,{path: '/'});
                             
                             goTo = $(this).data('path');
                             window.location = goTo;
                             
    });

    $("button.editLatest, button.editProfile").button()
                          .click(function(){
                              var formID = $(this).data('formid');
                              var entryID = $(this).data('entryid');
                              var userID = $(this).data('userid');
                              var userName = $(this).data('username'); 
//var userID = $(this).parents('div#content').find('h1').attr('id');
                              //var userName = $(this).parents('div#content').find('h1').children('span').html().trim();
                              
                              //alert("Settings a cookie for " + userName + " (ID #" + userID + ") for form " + formID);
                              
                              $.cookie.raw = true;
                              $.cookie("formEdit",formID,{path: '/'});
                              $.cookie("formEditUserID",userID,{path: "/"});
                              $.cookie("formEditUserName",userName,{path: "/"});
                              $.cookie("formEditRecordID",entryID,{path: "/"});
                              
                              window.location = '/forms/dataentry/id/' + formID;
    });
    
    $("button.deleteDynamicForm").button()
                          .click(function() {
                              var formID = $(this).data('formid');
                              var entryID = $(this).data('entryid');
                              
                              $("#mainColumn").append("<div id='confirmRelease'><span class='releaseMessage'></span></div>");
                              $(".releaseMessage").html("<b>Are you sure you want to delete the entry? <br> Data will be archived.");
                                $("#confirmRelease").dialog({
                                    modal: true,
                                    buttons: {
                                        OK: function() {
                                            $.post(
                                              "/ajax/archiveformentry",
                                              {formid:formID,entryid:entryID},
                                              function(data){
                                                  window.location.reload();
                                              })
                                            $(this).dialog("close");
                                        },
                                        Nevermind: function() {
                                            $(this).dialog("close");
                                        }
                                    }
                                });
                            });
    
    $("button.showRecords").button()
                           .click(function(){
                                btn = $(this);
                                showLabel = "Show Entries";
                                hideLabel = "Hide Entries";
                                curLabel = btn.button("option", "label");
                                
                                if (curLabel == showLabel) {
                                    btn.button("option", "label", hideLabel);
                                } else {
                                    btn.button("option", "label", showLabel);
                                }
                                
                                btn.parents('tr')
                                    .nextUntil('tr.descriptor')
                                    .each(function() {
                                        if ($(this).hasClass('hidden')) {
                                            $(this).show().removeClass('hidden');
                                        } else {
                                            $(this).hide().addClass('hidden');
                                        }
                                    });
                            });

});

