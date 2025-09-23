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
        var $input = $(this);
        var inputType = $input.attr('type');
        var inputName = $input.attr('name');
        var valLength = 0;
        
        if ($input.hasClass('multiselect')) {
            valLength = $input.parent().find('.tag-for-multi').length;
        } else {
            switch (inputType) {
                case 'text':
                    valLength = $input.val().length;
                    break;
                case 'radio':
                case 'checkbox':
                    buttonSet = $("input[name='" + inputName +"']");
                    valLength = buttonSet.filter(':checked').length;
                    break;
                default: break;
            }
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

function submitData() {
    var formID = $("form.dataEntry").attr('id');

    // Temporarily enable disabled inputs for serialization
    var disabled = $("form.dataEntry").find(':input:disabled').removeAttr('disabled');

    // Serialize the form data
    var formData = $("form.dataEntry").serialize();

    // Collect values from .multi fields and append them to formData
    $('.reference.multiselect').each(function() {
        var $container = $(this).parent();
        var fieldName = $(this).attr('name'); // Assuming .multiselect inputs have a name attribute
        var selectedItems = $container.find('.tag-for-multi').map(function() {
            return $(this).clone()    // Clone the element
                        .children()  // Select all the children
                        .remove()    // Remove all the children
                        .end()       // Again go back to selected element
                        .text().trim(); // Get the text and trim spaces
        }).get().join(','); // Join the values with a comma

        // Append the collected values to formData, ensure fieldName is unique or handled properly
        formData += '&' + encodeURIComponent(fieldName) + '=' + encodeURIComponent(selectedItems);
    });

    addlFormDataArray = new Array();
    
    $("input.dynamic-upload").each(function(){
        uploadData = new Object;
        uploadData.fileUploadID = $(this).data("fileid");
        uploadData.fieldID = $(this).attr('id');
        addlFormDataArray.push(uploadData);
    });

    addlFormData = JSON.stringify(addlFormDataArray);

    // Re-disable the previously disabled inputs
    disabled.attr('disabled', 'disabled');

    $.post('/ajax/isFormFcss', {id: formID}, function(data) {
        if (data.fcss == 'yes') {
            $("p#msg").html("Submitting to FCSS...<br><br><center><img src='/skins/default/images/ajax-loader.gif'></center>");
        } else {
            $("p#msg").html("Saving to ABCD database...");
        }

        $("#dialog-message").dialog({
            modal: true
        });

        $.post('/forms/ajax', {
            task: 'submit',
            id: formID,
            data: formData,
            oldVersion: doNotEditID
        }, function(data) {
            $("#dialog-message").dialog({
                buttons: {
                    OK: function() {
                        $(this).dialog("close");
                    }
                }
            });
            if (data.success == 'yes') {
                $("p#msg").html("Your data was saved successfully.");
                // $('form.dataEntry')[0].reset();
                history.back();
            } else {
                $("p#msg").html(data.success);
            }
        });
    });
}

function fillForm() {
    var userID = $.cookie('formEditUserID', {path: "/"});
    var userName = $.cookie('formEditUserName', {path: "/"});
    var recordID = $.cookie('formEditRecordID', {path: "/"});

    doNotEditID = recordID;

    $("form#" + formID + " input#name").val(userName).attr("disabled", "disabled");
    $("form#" + formID + " input#targetID").val(userID);

    $.post('/ajax/getformdata', {formID: formID, recordID: recordID}, function(data) {
        var frm = $("form#" + formID);

        $.each(data, function(key, value) {
            var fInput = $("[name='" + key + "']");
            var myType = fInput.attr('type');
            var myID = fInput.attr('id');

            if (myType == undefined && myID == undefined) {
                myType = 'checkbox';
            } else if (myType == undefined) {
                myType = 'textarea';
            }

            // Handling for multi-select inputs
            if (fInput.hasClass('multiselect')) {
                // Clear any existing tags
                fInput.parent().find('.tag-for-multi').remove();

                // Split the value by comma and create tags for each item
                if (value) {
                    var items = value.split(',');
                    $.each(items, function(index, item) {
                        // Trim the item to remove any leading/trailing spaces
                        item = $.trim(item);
                        var $tag = $('<span class="tag-for-multi">' + item + '<span class="remove" style="margin-left: 5px; cursor: pointer;">x</span></span>');
                        // Insert the tag before the input
                        $tag.insertBefore(fInput);
                        // Add click event on 'x' to remove tag
                        $tag.find('.remove').click(function() {
                            $(this).parent().remove();
                        });
                    });
                }
                return; // Skip further processing for multi-select inputs
            }

            switch (myType) {
                case 'text':
                    fInput.val(value);
                    break;
                case 'textarea':
                    fInput.html(value);
                    break;
                case 'radio':
                    $("form#" + formID + " :input[name='" + key + "'][value='" + value + "']").attr('checked', true);
                    break;
                case 'checkbox':
                    if (value == null) {
                        break;
                    }
                    var boxes = value.split(' , ');
                    $.each(boxes, function(index, box) {
                        $("form#" + formID + " :input[value='" + box + "']").attr('checked', true);
                    });
                    break;
                default:
                    alert('Unknown datatype ' + myType + ' detected.');
            }
        });

        // getDepartments is called after filling the form
        getDepartments();
    });
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
    
    
    if ($("#formTarget").val() === "staff") {
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
                    .click(function(){
                            validateForm();
                        });

    function prepareUpload(column) {
        var formTargetType = $("form.dataEntry input#formTarget"),
            formTargetID = $("form.dataEntry input#targetID"),
            uploadFormID = $("form.dataEntry").prop("id");
            myTargetType = $("form#uploadFileForm input#targetType"),
            myTargetID = $("form#uploadFileForm input#targetID"),
            myFormID = $("form#uploadFileForm input#formID"),
            myColumn = $("form#uploadFileForm input#column"),
            uploadField = $("#fileUpload");
         
       $('#fileupload').fileupload({
        dataType: 'json',
        replaceFileInput: false,
       add: function (e,data) {
            data.context = 
                   $("#uploadButton").remove();
                   $('<button id="uploadButton" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">').html('<span class="ui-button-text">Upload</span>')
                    .prependTo('.ui-dialog-buttonset')
                    .click(function() {
                        //updateTipsUpload('Uploading file...');
                        myTargetType.val(formTargetType.val());
                        myTargetID.val(formTargetID.val());
                        myFormID.val(uploadFormID);
                        myColumn.val(column);
                        data.submit();
            });

        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .bar')
                .css('width', progress + '%')
                .text(progress + '% complete');
        },
        done: function (e, data) {
            $('#progress').hide();
            uploadField.val('');
            fileID = data.result.fileEntryID;
            fileName = data.result.fileName;
            
            $(".uploadInProgress").val(fileName)
                    .attr("data-fileid",fileID)
                    .removeClass('uploadInProgress')
                    .prop("disabled",true);
            $('#uploadFile-dialog').dialog("close");
            
        }
     });
    };
    
    $( "#uploadFile-dialog" ).dialog({
        autoOpen: false,
        height: 365,
        width: 490,
        modal: true,
        buttons: {
                "Nevermind": function() {
                        $( this ).dialog( "close" );
                }
        },
            
        open: function(e,ui) {
            $("#uploadButton").remove();
            uploadField.empty();
            $(this).keyup(function(e) {
                if (e.keyCode == 13) {
                    $('.ui-dialog-buttonset > button:first').trigger('click');
                }
            });
        }
    });
    
    $( ".upload-button" )
        .button()
        .click(function() {
                $(this).parents('li').children('input.dynamic-upload').addClass('uploadInProgress');
                prepareUpload($(this).data('id'));
                $( "#uploadFile-dialog" ).dialog( "open" );
        });
});


 