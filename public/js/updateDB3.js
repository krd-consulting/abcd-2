$(function()
{
//Disable checkboxes we don't want
$("input:checkbox.disabled").attr('disabled','disabled');

//Make checkbox update database
$("input.isRequired").not(".disabled")
        .click(function() {
        myID = $(this).parents('tr').children('td.hidden').children("input[type='hidden']").val();
        parentType = 'progs';
        parentID = $("#mainColumn h1").attr('id');
        me = $(this);
        
    if ($(this).is(':checked')) {   
        action = 'add';        
    } else {
        action = 'remove';
    }
    
    $.post(
        "/forms/ajax",
        {task: 'required', action: action, type : parentType, to : parentID, who : myID},
        function(data) {
            if (data.success >= 0) {
                alert("Setting Saved.\n" + data.success + " participants affected.");
            } else { 
                alert("Update failed.\n Please contact your ABCD support team.");
            }
        }
    )
})

//Set frequency field as editable
$("span.dbUpdate").not(".disabled")
                  .addClass('editable')
                  .each(function(){
                  
                  type = 'progs';
                  parentID = $("#mainColumn h1").attr('id');
                  myID = $(this).parents('tr').children('td.hidden').children("input[type='hidden']").val();
                  
                  $(this).editable('/forms/ajax',
                  {    
                      submitdata : {
                          task: 'reminder',
                          myID : myID,
                          type : type,
                          parentID : parentID
                      },
                      data  :   "{'null': '-None-', 'monthly':'Monthly', 'quarterly':'Quarterly', 'semi-annual':'Semi-Annual', 'annual':'Annual'}",
                      type  :   "select",
                      onchange : "submit",
                      indicator: '<img src="/skins/default/images/ajax-loader.gif">',
                      tooltip: "Click to change"
                  }
                  
                  );
                  })
                  
                  $("#Cal").button()
                            .click(function() {
                            window.location = "/programs/calendar/id/" + $(this).data('id');
                    });

})
