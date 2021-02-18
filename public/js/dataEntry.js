$(function() {  

bValid = 0;
editLatest = 0;
doNotEditID = 0;
isSchedule = false;
eventID = '';
recordID = '';
formID = '';
checkScheduleConflict = true;

$("input[type=radio]").mouseup(function() {
    this.__chk = this.checked;
  }).click(function() {
    if (this.__chk) this.checked = false;
  });

function getDepartments() {
    
    var pid = $('input#targetID').val();
    var pType = $('input#formTarget').val();


    $('select#ptcpDept').find('option').remove(); //select has legacy name 
                                                  //ptcpDept but works on staff forms too

        $.post(
            ("/forms/ajax"),
            {task:'getdepts', type: pType, pid: pid},
            function(data){
                $.each(data.deptlist,function(index,dept){
                    $('select#ptcpDept').append(
                        '<option value="' + dept.id + '">' + dept.name + '</option>'
                    );
                });
            }

        );
}    

function markRequired(n, name) {
   n.parents("li").css("border", "2px red solid");
}

function unmark(n, name) {
    n.parents("li").css("border", "none");
}

function validateForm() 
{
    if ($(".refersToSchedule")[0]) {
        isSchedule = true;
    } else {
        isSchedule = false;
    }
    
    var incompletes = 0;
    $("input.required").each(function(){
        var inputType = $(this).attr('type');
        var inputName = $(this).attr('name');
        switch (inputType) {
            case 'text':
                var valLength = $(this).val().length;
                break;
            case 'radio':
            case 'checkbox':
                buttonSet = $("input[name='" + inputName +"']");
                var valLength = buttonSet.filter(':checked').length;
                break;
            default: break;
        }
        if (valLength == 0) {
            markRequired($(this), inputName); 
            incompletes++;
        } else {
            unmark($(this), inputName);
        }
        bValid = bValid && valid;
    })
    
    if (incompletes > 0) {
        alert('Please complete all required fields. (' + incompletes + ' incomplete field found.)');
        bValid = false;
    } else {
        if (isSchedule) {
            checkAndEnterCalendarEvent();
        } else {
            submitData();
        }   
    }
    
}

function checkAndEnterCalendarEvent() {
    calendarID = $(".refersToSchedule").data('scheduleid');
    schedDate = $(".refersToSchedule").val();
    schedStartTime = $(".timepicker.start").val();
    schedEndTime = $(".timepicker.end").val();
    schedResourceID = $(".resourceSelect option:selected").val();
    schedResourceType = $(".resourceSelect option:selected").data('resourcetype');
    
    if (checkScheduleConflict) {
    
        $.post(
          ("/ajax/getbookedresources"),
          {sid:calendarID,date:schedDate,from:schedStartTime,to:schedEndTime,rType:schedResourceType,rID:schedResourceID},
          function(data) {
              if (data.length == 0) {
                    $.post(
                      ("/ajax/saveeventfromform"),
                      {   scheduleID:calendarID,
                          date:schedDate,
                          from:schedStartTime,
                          to:schedEndTime,
                          rType:schedResourceType,
                          rID:schedResourceID,
                          name:$("input#name").val(),
                          target:$("#formTarget").val(),
                          targetID:$("#targetID").val()
                      },
                      function(d2) {
                          if (d2.success == true) {
                              eventID = d2.eventid;
                              submitData();
                          } else {
                              alert("Couldn't save event: " + d2.message);
                          }
                      }
                    )
              } else {
                  alert("A conflict exists for resource '" + schedResourceID + "' at the requested time. Please try again.");
                  $(".refersToSchedule").val('').trigger('change').focus();
              }
          }
        );
    
    } else {
        submitData();
    }
    
}

function deleteEventFromCal() {
            tempArr = $('form.dataEntry').prop("id").split("_");
            formID = tempArr[1];
            $.post(
                    "/ajax/formeventdelete",
                    {formid:formID,entryid:recordID},
                    function() {
                        $(".refersToSchedule").prop("disabled",false)
                                              .val("")
                                              .trigger('change');
                        $("#button-release").hide();
                        checkScheduleConflict = true;
                    }
            );
}

function setScheduleRelease() {
    checkScheduleConflict = false;
    dateRefField = $(".refersToSchedule");
    releaseBtn = "<span id='button-release'>Release</span>";
    dateRefField.attr('disabled',true)
                .parents('li').append(releaseBtn);
    $("#button-release").button()
              .click(function()
                        {
                           sDate = dateRefField.val();
                           $("#mainColumn").append("<div id='confirmRelease'><span class='releaseMessage'></span></div>");
                           $(".releaseMessage").html("<b>Are you sure you want to release this appointment?");
                           $("#confirmRelease").dialog({
                               modal: true,
                               buttons: {
                                   OK: function() {
                                       deleteEventFromCal();
                                       $(this).dialog("close");
                                   },
                                   Nevermind: function() {
                                       $(this).dialog("close");
                                   }
                               }
                           }
                                   );
                        }
                    );
}

function submitData()
{
    formID   = $("form.dataEntry").attr('id');
    
    
    disabled = $("form.dataEntry").find(':input:disabled').removeAttr('disabled');
    formData = $("form.dataEntry").serialize();
    disabled.attr('disabled','disabled');
    
    $.post(
      '/ajax/isFormFcss',
      {id: formID},
      function(data) {
         
         if (data.fcss == 'yes') {
            $("p#msg").html("\
               Submitting to FCSS...\n\
               <br><br><center><img src='/skins/default/images/ajax-loader.gif'></center>\n\
            ");
         } else {
            $("p#msg").html("Saving to ABCD database...");
         }
         
         $("#dialog-message").dialog({
               modal: true
            });
         
         $.post(
             '/forms/ajax',
             {task: 'submit', id: formID, data: formData, oldVersion: doNotEditID, isSchedule: isSchedule, eventID: eventID},
             function(data){
                  $("#dialog-message").dialog({
                     buttons: {
                        OK: function() {
                           $(this).dialog("close");
                        }
                     }
                  });
                  if (data.success == 'yes') {
//                     if ($(".refersToSchedule")[0]) {
//                         var entryID = data.formEntryID;
//                         $.post(
//                           "/ajax/linkformtoevent",
//                            {formID: formID, eventID: eventID, formEntryID: entryID},
//                            function(d3) {
//                                $("p#msg").html("Event entry linked to form entry.<br>");
//                            }
//                         );
//                     }  
                     $("p#msg").append("Your data was saved successfully.");
                       //$('form.dataEntry')[0].reset();
                       history.back();
                  } else {
                       $("p#msg").html(data.success);
                  }
            }
        );          
    });    
}

function fillForm(){
    userID = $.cookie('formEditUserID', {path: "/"});
    userName = $.cookie('formEditUserName', {path: "/"});
    recordID = $.cookie('formEditRecordID', {path: "/"});
    
    setScheduleRelease();
    
    doNotEditID = recordID;
    
    $("form#" + formID + " input#name").val(userName).attr("disabled","disabled");
    $("form#" + formID + " input#targetID").val(userID);
    
    //alert("formID is " + formID + ", recordID is " + recordID);
    
    $.post(
        '/ajax/getformdata',
        {formID: formID, recordID: recordID},
        function(data) {
            //alert(data.toSource());
            frm = $("form#" + formID);
            
            
            $.each(data, function(key, value){  
                //alert(key + " is " + value);
                fInput = $("[name='" + key + "']");

                myType = fInput.attr('type');
                myID = fInput.attr('id');

                if (myID == undefined) {
                    myType = 'checkbox';
                } else if (myType == undefined) {
                    myType = fInput.prop('type');
                }

                //alert ("Working with " + myType + " (will use " + value + " for ID " + fInput.attr('id') + ")");
                switch (myType) {
                    case 'text': 
                        fInput.val(value);
                        break;
                    case 'textarea':
                         //alert ("Trying to set " + myType + " to " + fieldVal);
                          fInput.html(value);
                        break;
                    case 'radio':
                        $("form#" + formID + ", :input[name='"+key+"'][value='"+value+"']")
                              .attr('checked', true);
                        break;
                    case 'checkbox':
                        if (value == null) {
                            break;
                        }

                        boxes = new Array();
                        boxes=value.split(' , ');

                        for (q=0;q<boxes.length;q++) {
                            $("form#" + formID + ", :input[value='" + boxes[q] + "']").attr('checked', true);
                        }
                        break;
                    case 'select-one':
                        $("[name='" + key + "'] select").val(value);
                        break;
                    default:
                        alert ('Unknown datatype ' + myType + ' detected.');
                }
            }); 
            
            
            getDepartments();
        
        }
    );
    
    
    
    $.removeCookie('formEdit', {path: "/"});
    $.removeCookie('formEditUserID', {path: "/"});
    $.removeCookie('formEditUserName', {path: "/"});
    $.removeCookie('formEditRecordID', {path: "/"});
}
    
    editID     = $.cookie('formEdit');
    formID     = $("form.dataEntry").attr('id');
    formIDComp = formID.split("_")[1]; //need only the numeric id in form_###
    formIDComp = formIDComp.replace(/^0+/, '');

    if (editID == formIDComp) {
        fillForm();  
    }
    
    
    ptcpID = $.cookie('ptcpID');
    ptcpName = $.cookie('ptcpName');
    
    if (ptcpID) {
        $("input#name").val(ptcpName);
        $("input#targetID").val(ptcpID);
        $.removeCookie('ptcpID', {path: "/"});
        $.removeCookie('ptcpName', {path: "/"});
    }

    $("input[type='hidden']").parent().hide();

    $(".numeric").numeric({allow:"."});
    $(".hasDatepicker").numeric({allow:"-"});

    //$("input[type='text']").not('.numeric').alphanumeric({allow:"#- @."});

    $("input#responseDate").blur(function() {
        getDepartments();
    });
    
    $(".addForm").button()
                    .click(function()
                        {
                            validateForm();
                        }
                    );
    
    $.post(
                ("/ajax/gettimeboundaries"),
                {sid:$(".refersToSchedule").data('scheduleid')},
                function(data) {
                    $('.timepicker').timepicker({
                        timeFormat: 'HH:mm',
                        defaultTime: '',
                        minTime: data.startTime,
                        maxTime: data.endTime,
                        dynamic: true,
                        dropdown: true,
                        scrollbar: true,
                        interval: 15,
                        change: function(time) {
                            $(this).trigger('change');
                        }
                    });
                }
            );
    
    
    
});
 