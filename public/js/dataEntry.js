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


function fillForm({ userID, userName, recordID, formID }) {
    doNotEditID = recordID;
 
    const $form = $("form#" + formID);

    const $name = $form.find("input#name");
    const $target = $form.find("input#targetID");

    $name.val(userName).prop("disabled", true);
    $target.val(userID);


  $.post('/ajax/getformdata', { formID, recordID }, function (data) {
      $.each(data, function (key, value) {
      const $input = $form.find("[name='" + key + "']");

      //check and skip broken keys
      if (!$input.length) {
          console.warn('[ABCD] No input found for key:', key);
          return; //skip this field
      }

      if ($input.hasClass("multiselect")) {
        $input.parent().find(".tag-for-multi").remove();
        if (value) {
          $.each(value.split(","), function (_, item) {
            item = $.trim(item);
            const $tag = $(
              '<span class="tag-for-multi">' +
                item +
                '<span class="remove" style="margin-left:5px;cursor:pointer;">x</span></span>'
            );
            $tag.insertBefore($input);
            $tag.find(".remove").on("click", function () {
              $(this).parent().remove();
            });
          });
        }
        return; // done with this field
      }

      let type = $input.attr("type") || "";
      if (!type && $input.is("textarea")) {
        type = "textarea";
      }
      if (!type) {
        type = "checkbox"; // fallback
      }

      switch (type) {
        case "text":
        case "textarea":
          $input.val(value);
          break;
        case "radio":
          $form
            .find(":input[name='" + key + "'][value='" + value + "']")
            .prop("checked", true);
          break;
        case "checkbox":
          if (value != null) {
            $.each(value.split(","), function (_, box) {
              $form.find(":input[value='" + $.trim(box) + "']").prop("checked", true);
            });
          }
          break;
        default:
          alert("Unknown datatype " + type + " detected.");
      }
    });
    getDepartments();
  });
}



  // Cookie bug fix Sept 2025
  const editID     = $.cookie('formEdit');
  const editIDComp = (editID || '').replace(/^0+/, '');
  const formIDFull = $("form.dataEntry").attr('id') || '';
  const formIDComp = (formIDFull.split("_")[1] || '').replace(/^0+/, '');

  const staffID = $.cookie('staffID') || '';
  const staffName = $.cookie('staffName') || '';

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
  if (editIDComp === formIDComp) {
    console.log('[ABCD] filling with', editCtx);
    try { fillForm(editCtx); }
    catch (e) { console.error('[ABCD] fillForm error:', e); }
  } else {
      console.log('[ABCD] no match: not filling');
  }
  // end cookie bug fix

    
if ($("#formTarget").val() === "staff") {
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
                    .click(function()
                        {
                            validateForm();
                        }
                    );
});
 
