$(function() {  

var bValid = 0;

function markRequired(n, name) {
   n.addClass('ui-state-error');
}

function unmark(n, name) {
    n.removeClass('ui-state-error');
}

function checkDuplicates() {
    groupID = $("#content h1").attr('id');
    date = $("input#date").val();
    $.post(
        "/ajax/checkduplicates",
        {table: 'groupMeetings', column: 'groupID', val: groupID, col2: 'date', val2: date},
        function(data){
            if (data.unique == 'no') {
                $("input#date").addClass('ui-state-error');
                alert ("A group meeting on that date already exists. Please choose another.");
                return false;
            } else {
                $("input#date").removeClass('ui-state-error');
                submitData();
            }
        }
    );
}

function validateForm() 
{
    $("input:text.required").each(function(){
        valLength = $(this).val().length;
        if (valLength == 0) {
            markRequired($(this));
            alert ("Please fill out required fields.");
            return false;
        } else {
            unmark($(this));
            return true;
        }
    });    
    
    if (valLength != 0) {
        numBoxes = $('input[name="attendance[]"]').filter(':checked').length;
        setGuests = $('input#unenrolled').val().length;
        numGuests = $('input#unenrolled').val();

        if (setGuests == 0) {
            attendance = parseInt(numBoxes) + parseInt(setGuests);
        } else {
            attendance = parseInt(numBoxes) + parseInt(numGuests);
        }

        if (attendance == 0) {
            alert ("You haven't indicated anyone's attendance.");
        }
    }
    
    bValid = attendance && valLength;
    if (bValid) {
        checkDuplicates();
    } else {
        return false;
    }
}

function submitData()
{
    groupID = $("#content h1").attr('id');
    optionalNums = $(":input.numeric").not(".required");
    optionalNums.each(function() {
        if ($(this).val().length == 0) {
            $(this).val('0');
        }
    })
    
    data = $("#content form").serialize();
    var ptcps = new Array();
    var i = 0;
    $("input[name='attendance[]']").filter(':checked').each(function(){
        myID = $(this).val();
        myLevel = $(this).parents('tr').find("span.editable").text();
        myVol = $(this).parents('tr').find("input[name='volunteers[]']").filter(':checked').length;
        
        me = new Object();
        me.id = myID;
        me.level = myLevel;
        me.vol = myVol;
        
        ptcps[i] = me;
        
        i++;
    });
        
    $.post(
        '/ajax/addgroupmeeting',
        {id: groupID, data: data, ptcps: ptcps},
        function(data){
             if (data.success == 'no') {
                 alert ('Problem detected - your data was not submitted.');
             } else {
                 var gProfile = '/groups/profile/id/' + groupID;
                 window.location = gProfile; 
             }                 
        }
    );
}

    $("input[type='hidden']").parent().hide();

    $(".numeric").numeric({allow:"."});
    $(".datepicker").numeric({allow:"-"});

    $("input[type='text']").not('.numeric').alphanumeric({allow:"#- "});
    
    $("span.editable").editable(function(value,settings) {
                                    display = new Array();
                                    display['passive'] = 'In Attendance';
                                    display['contrib'] = 'Active Contributor';
                                    display['leadrole'] = 'Leadership Role';
                                    return display[value];
                                }, {
                                    data: "{'passive':'In Attendance','contrib':'Active Contributor','leadrole':'Leadership Role'}",
                                    type: 'select',
                                    onchange: 'submit' 
                                });
                                
    $("a#checkAll").click(function(){
        $("input[name='attendance[]']").not(':checked').trigger('click');
    });
    
    $("a#uncheckAll").click(function(){
        $("input[name='attendance[]']").attr('checked','checked').trigger('click'); 
    });
    
    $("input[name='attendance[]']").change(function()
        {
            
            var myID = $(this).val();
            if ($(this).is(':checked')) {
                $("span.editable[data-id='" + myID + "']").text('In Attendance');
            } else {
                $("span.editable[data-id='" + myID + "']").text('--');
            } 
        }
    )
                                    .first().addClass('required');

    $(".savebutton").button().click(function(){
        validateForm();
    });

});
