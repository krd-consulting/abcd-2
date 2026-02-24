$(function() {  

bValid = 0;
editLatest = 0;
doNotEditID = 0;
recordID = '';
formID = '';

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
            oldVersion: doNotEditID,
            addlData: addlFormData
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


function fillForm({ userID, userName, recordID, formID }) {
  doNotEditID = recordID;

  const $form = $("form#" + formID);

  const $name = $form.find("input#name");
  const $target = $form.find("input#targetID");

  $name.val(userName).prop("disabled", true);
  $target.val(userID);

  $.post('/ajax/getformdata', { formID, recordID }, function (data) {
    $.each(data, function (key, value) {
      // match both name="Field" and name="Field[]"
      const $inputs = $form.find("[name='" + key + "'], [name='" + key + "[]']");
      const $first  = $inputs.first();

      // check and skip broken keys
      if (!$inputs.length) {
        console.warn('[ABCD] No input found for key:', key);
        return; // skip this field
      }

      // multiselect handling (uses first input in the group)
      if ($first.hasClass("multiselect")) {
        $first.parent().find(".tag-for-multi").remove();
        if (value) {
          $.each(String(value).split(","), function (_, item) {
            item = $.trim(item);
            const $tag = $(
              '<span class="tag-for-multi">' +
                item +
                '<span class="remove" style="margin-left:5px;cursor:pointer;">x</span></span>'
            );
            $tag.insertBefore($first);
            $tag.find(".remove").on("click", function () {
              $(this).parent().remove();
            });
          });
        }
        return; // done with this field
      }

      // detect type based on first input in the group
      let type = $first.attr("type") || "";
      if (!type && $first.is("textarea")) {
        type = "textarea";
      }
      if (!type) {
        type = "checkbox"; // fallback
      }

      switch (type) {
        case "text":
        case "textarea":
          // set value on all matching inputs with this name
          $inputs.val(value);
          break;

        case "radio":
          // check only radios in this name group
          $inputs
            .filter("[value='" + value + "']")
            .prop("checked", true);
          break;

        case "checkbox":
          if (value != null) {
            const values = String(value)
              .split(",")
              .map(v => $.trim(v))
              .filter(Boolean);

            const $group = $inputs.filter(":checkbox");

            $.each(values, function (_, box) {
              // basic escape for single quotes in value
              const v = box.replace(/'/g, "\\'");
              $group
                .filter("[value='" + v + "']")
                .prop("checked", true);
            });
          }
          break;

        default:
          alert("Unknown datatype " + type + " detected.");
      }

      if ($first.hasClass("dynamic-upload")) {
        $first.prop("disabled", true);
        $first.attr("data-fileid", value['file_id']);
        $first.val(value['filename']);
      }
    });

    getDepartments();
  });
}


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

  // Cookie bug fix Sept 2025
  const editID     = $.cookie('formEdit');
  const editIDComp = (editID || '').replace(/^0+/, '');
  const formIDFull = $("form.dataEntry").attr('id') || '';
  const formIDComp = (formIDFull.split("_")[1] || '').replace(/^0+/, '');

  // 1) Read values BEFORE deletion
  const editCtx = {
    userID:   $.cookie('formEditUserID')   || '',
    userName: $.cookie('formEditUserName') || '',
    recordID: $.cookie('formEditRecordID') || '',
    formID:   formIDFull
  };

  console.log('[ABCD] pre-delete', { editID, formIDComp, editCtx });

  // 2) Delete all edit-related cookies right away
  ['formEdit','formEditRecordID','formEditUserID','formEditUserName']
    .forEach(n => $.removeCookie(n, { path: '/' }));

  // 3) If this form matches, populate it
  // otherwise, try to check if we're passing in a 'uid' for the form
  // otherwise, don't fill the form with anything
  let searchParams = new URLSearchParams(window.location.search);
  if (editIDComp === formIDComp) {
    console.log('[ABCD] filling with', editCtx);
    try { fillForm(editCtx); }
    catch (e) { console.error('[ABCD] fillForm error:', e); }
  } else if(searchParams.has('uid')) {
    const $form = $("form#" + formIDFull);

    const $name = $form.find("input#name");
    const $target = $form.find("input#targetID");

    $form.find('button.addForm').prop('disabled', true);
    $.get( "/participants/profile/id/" + searchParams.get('uid'))
      .done(function( htmlData ) {
        const username = $(htmlData).find('.participantProfile .p-name').text().trim();

        $form.find('button.addForm').prop('disabled', false);

        $name.val(username).prop("disabled", true);
        $target.val(searchParams.get('uid'));
      })
      .fail(function(jqXHR, textStatus, errorThrown) {
        // Just show the error from the request.
        document.open('text/html', 'replace');
        document.write(jqXHR.responseText);
        document.close();
      });
  } else {
      console.log('[ABCD] no match: not filling');
  }
  // end cookie bug fix

    
if ($("#formTarget").val() === "staff") {
    const staffID = editCtx.userID || '';
    const staffName = editCtx.userName || '';

    console.log('[ABCD] working with ' + staffName + ', id ' + staffID);

    $("input#name").val(staffName.replace(/\+/g," "));
    $("input#targetID").val(staffID);
    getDepartments();
}

    $("input[type='hidden']").parent().hide();

    $(".numeric").numeric({allow:"."});
    $(".hasDatepicker").numeric({allow:"-"});

    $("input#responseDate").blur(function() {
        getDepartments();
    });
    
    $(".addForm").button()
                    .click(function(){
                            validateForm();
                        });

    
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
    
    // Handle remove file button on click
    $('.remove-file-upload').click(function() {
      $(this).siblings('input.dynamic-upload').val('');
     });
});
