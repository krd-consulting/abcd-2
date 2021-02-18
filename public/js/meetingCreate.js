$(function() {  

var bValid = 0;
var jobsArray = new Array();
var progid = $("#progid").data('id');

$(".timepicker").prop('disabled',true);

function getJobs() {
    $.post(
              "/ajax/getprogjobs",
               {progid: progid},
               function(data) {
                   jobsArray = data;  
               }
    );
}

function switchList() {
    if ($("input#list-switch").is(':checked')) {
        $("#prog-vols").addClass('hidden').hide();
        $("#group-vols").removeClass('hidden').show();
    } else {
        $("#group-vols").addClass('hidden').hide();
        $("#prog-vols").removeClass('hidden').show();
    }
}

function initializeJobs(volid) {
    if (!jobsArray[0]){
        jobsArray[0] = "General";
    }
    dString = JSON.stringify(jobsArray);
    
    $("span.editable-jobs[data-volid='" + volid + "']").editable(function(value,settings) {
            displayjobs = new Array();
            displayjobs = jobsArray;
            $(this).data('jobid',value);
            return displayjobs[value];
    },{
            data: dString,//'{"9":"Nurse","10":"Podiatrist","11":"Interpreter","12":"Pharmacist"}',
            type: 'select',
            onchange: 'submit'
    });
}


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

function validateForm() {
    $("input:text.required").each(function(){
        valLength = $(this).val().length;
        if (valLength == 0) {
            markRequired($(this));
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
        numVols = $('input[name="vol_attendance[]"]').filter(':checked').length;

        if (setGuests == 0) {
            attendance = parseInt(numBoxes) + parseInt(setGuests) + parseInt(numVols);
        } else {
            attendance = parseInt(numBoxes) + parseInt(numGuests) + parseInt(numVols);
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
    
    data = $("form#wrapper-form :not(input.filterinput)").serialize();    
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
       // alert("Ptcp " + myID + ", round " + i);
    });
    
    var vols = new Array();
    var vi = 0;
    $("input[name='vol_attendance[]']").filter(':checked').each(function(){
        myID = $(this).val();
        myJobID = $(this).parents('tr').find("span.editable-jobs").data('jobid');
        myJobName = $(this).parents('tr').find("span.editable-jobs").text();
        myStart = $(this).parents('tr').find("input.volFromTime").val();
        myEnd = $(this).parents('tr').find("input.volToTime").val();
        
        me = new Object();
        me.id = myID;
        me.jobid = myJobID;
        me.note = "Performed " + myJobName + " duties.";
        me.fromTime = myStart;
        me.toTime = myEnd;
        
        vols[vi] = me;
        vi++;
        //alert("Vol " + myID + ", round " + vi);
    });
        
    $.post(
        '/ajax/addgroupmeeting',
        {id: groupID, data: data, ptcps: ptcps, vols: vols},
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
    
    $("a#checkAllVols").click(function(){
        $("input[name='vol_attendance[]']").not(':checked').trigger('click');
    });
    
    $("a#uncheckAllVols").click(function(){
        $("input[name='vol_attendance[]']").attr('checked','checked').trigger('click'); 
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

    $("input[name='vol_attendance[]']").change(function()
        {            
            var myID = $(this).val();
            var timepickers = $(this).parents('tr').find("input.timepicker");
            
            if ($(this).is(':checked')) {
                initializeJobs(myID);
                $("span.editable-jobs[data-volid='" + myID + "']").text('General').data('jobid','0');
                timepickers.prop('disabled',false);
            } else {
                $("span.editable-jobs[data-volid='" + myID + "']").text('--').data('jobid','').editable("destroy");
                timepickers.prop('disabled',true);
            } 
        }
    )



    $(".savebutton").button().click(function(){
        validateForm();
    });
    
    if (!jobsArray[0]) {
        getJobs();
    };
    
    $('.timepicker').timepicker({
                timeFormat: 'HH:mm',
                defaultTime: '',
                startTime: '06:00',
                dynamic: true,
                dropdown: true,
                scrollbar: false       
            });
    
    $("input#list-switch").click(function(){
        switchList();
    });
});
