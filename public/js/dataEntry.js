$(function() {  

bValid = 0;
editLatest = 0;
doNotEditID = 0;

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
    } else {
        submitData();
    }
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
             {task: 'submit', id: formID, data: formData, oldVersion: doNotEditID},
             function(data){
                  $("#dialog-message").dialog({
                     buttons: {
                        OK: function() {
                           $(this).dialog("close");
                        }
                     }
                  });
                  if (data.success == 'yes') {
                       $("p#msg").html("Your data was saved successfully.");
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
                
                fInput = $("[name='" + key + "']");
                
                myType = fInput.attr('type');
                myID = fInput.attr('id');



                if (myType == undefined && myID == undefined) {
                    myType = 'checkbox';
                } else if (myType == undefined) {
                    myType = 'textarea';
                }
                
                //alert(key + " is " + value + ", type is " + myType);

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
                        //alert (key + " is " + value + " in this radiobox. Setting.");
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

    staffID = $.cookie('staffID');
    staffName = $.cookie('staffName');
    
    
    if ($("#formTarget").val() == "staff") {
        $("input#name").val(staffName.replace(/\+/g," "));
        $("input#targetID").val(staffID);
        getDepartments();
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
});
 