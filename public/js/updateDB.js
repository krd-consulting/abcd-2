$(function()


 {

//Make checkbox update database
$("input.isRequired").click(function() {
        
        parentID = $(this).parents('tr').children('td.hidden').children("input[type='hidden']").val();
        parentType = $(this).parents('table').attr('id');
        myID = $("h1.participantProfile").attr('id');
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
$("span.dbUpdate").addClass('editable')
                  .each(function(){
                  
                  type = $(this).parents('table').attr('id');
                  myID = $("h1.participantProfile").attr('id');
                  parentID = $(this).parents('tr').children('td.hidden').children("input[type='hidden']").val();
                  
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

})
