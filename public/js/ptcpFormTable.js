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

    $( "button.download-file").button()
            .click(function() {
                    let id = $(this).data('id');
                    let $form = $('<form>', {
                        method: 'POST',
                        action: '/ajax/downloadfile'
                    });
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: 'id',
                        value: id
                    }));
                    $(document.body).append($form);
                    $form.trigger('submit');
            })

    $("button.addRecordForm").button()
                             .click(function(){
                                 goToForm = $(this).data('path');
                                 window.location = goToForm;
                             });

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
                                            $(this).slideDown(500)
                                                    .removeClass('hidden');
                                        } else {
                                            $(this).slideUp(500)
                                                    .addClass('hidden');
                                        }
                                    });
                            });

});

