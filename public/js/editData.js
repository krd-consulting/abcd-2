$(function() {

//validateForm() to mark things and set 'bValid variable'
//submitData to submit and clear the form

var formValues = new Array();
var bValid = 0;

function markRequired(n, name) {
   n.parents("li").css("border", "2px red solid");
}

function unmark(n, name) {
    n.parents("li").css("border", "none");
}

function validateForm() 
{
    $("input.required").each(function(){
        var inputType = $(this).attr('type');
        var inputName = $(this).attr('name');
        bValid = 1;
        switch (inputType) {
            case 'text':
                length = $(this).val().length;
                break;
            case 'radio':
            case 'checkbox':
                buttonSet = $("input[name='" + inputName +"']");
                length = buttonSet.filter(':checked').length;
                break;
            default: break;
        }
        if (length == 0) {
            markRequired($(this), inputName); 
            valid=0;
        } else {
            unmark($(this), inputName);
            valid = 1;
        }
        bValid = bValid && valid;
    })
    if (!bValid) {alert('Please fill out all required fields.');}
}

function submitData()
{
    $("input#name").attr('disabled', false);
    formID   = $("form.dataEntry").attr('id');
    formData = $("form.dataEntry").serialize();
    
    $.post(
        '/forms/ajax',
        {task: 'submit', id: formID, data: formData},
        function(data){
             if (data.success == 'yes') {
                 //alert ('Success!');
                 window.location.reload();
             } else {
                 alert ('Problem detected - your data was not submitted.');
             }
        }
    );
}

function getFormData(tabID) {
        i = 1;
    $("div#" + tabID).children('div.content-data').children('div.form-display-item').each(function(){
        inputID = $(this).children('span.value').attr('id');
        inputValue = $(this).children('span.value').text();
        inputName = $(this).children('span.title').text();
        
        thisElement = new Object();
        thisElement.id = inputID;
        thisElement.value = inputValue;
        thisElement.name = inputName;
        formValues[i] = thisElement;
        i++;
    }
    );
    
}

function buttonReset(bttn) {
   bttn.removeClass('edit').addClass('saveForm').button('destroy').unbind('click');
   
   bttn.fadeOut(500)
       .button({label: 'Save'})
            .click(function()
                {
                        validateForm();
                        if (bValid) {
                            submitData();
                        }
                }
            )
       .fadeIn(500);
            
}

function fillForm(f) {
    //alert(formValues.toSource());
    //set the name, because that's not passed
    uName = $("h1 span.p-name").text().trim();
    $("form#" + f + " input#name").val(uName);
    $("form#" + f + " input#name").attr("disabled", "disabled");
    
    //set the targetID (uID)
    $("form#" + f + " input#targetID").val(formValues[2].value);
    $("form#" + f + " input#responseDate").val(formValues[5].value);
    $("form#" + f + " select#ptcpDept").val(formValues[6].value);
                                                     
    //set the targetForm (formID)
    
  for (k=7; k < formValues.length; k++) {
      thisField = formValues[k];
      
      fieldID   = thisField.id;          //offset to match the hidden and skeleton fields
      fieldVal  = thisField.value;
      fieldName = thisField.name;
      
      fInput = $("form#" + f + ", :input[name='" + fieldName + "']");
      //alert (fieldID + "/" + fieldVal + "/" + fieldName);
      myType = fInput.attr('type');
      myID = fInput.attr('id');
      
      if (myID == undefined) {
          myType = 'checkbox';
      } else if (myType == undefined) {
          myType = 'textarea';
      }
            
      //alert ("Working with " + myType + " (will use " + fieldVal + " for ID " + fInput.attr('id') + ")");
      switch (myType) {
          case 'text': 
              fInput.val(fieldVal);
              break;
          case 'textarea':
               //alert ("Trying to set " + myType + " to " + fieldVal);
                fInput.html(fieldVal);
              break;
          case 'radio':
              $("form#" + f + ", :input[name='"+fieldName+"'][value='"+fieldVal+"']")
                    .attr('checked', true);
              break;
          case 'checkbox':
              boxes = new Array();
              boxes=fieldVal.split(' , ');
              
              for (q=0;q<boxes.length;q++) {
                  $("form#" + f + ", :input[value='" + boxes[q] + "']").attr('checked', true);
              }
              break;
          default:
              alert ('Unknown datatype ' + myType + ' detected.');
      }
  }    
}

function getForm(bttn) {
    formID = bttn.attr('id');
    myDiv = bttn.parents('div').children('div.content-data');
    table = myDiv.attr('id');
    $.post(
        "/forms/ajax",
        {task: 'getform', fid: formID},
        function(data){
            myDiv.html();
            myDiv.html("<form id='" + table + "' class='dataEntry'>" + data.content + "</form>");                  
            $.getScript('/js/jquery.alphanumeric.js');
            $.getScript('/js/datePicker.js');
            $.getScript('/js/dataEntry.js');
            buttonReset(bttn);
	    fillForm(table);
            makeAutocomplete();
            //ptcpDepts();
        }
    ) 
}

$(".edit").button()
          .click(function(){
                 divID = $(this).parent().attr('id');
                 getFormData(divID);
                 getForm($(this));
                 });
});


