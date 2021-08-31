$(function() {
    var readOnly = $("#readonly").data("readonly");

    //dialog form vars
    var eventElement = $("#dialog-form-enroll #event");
    var jobElement = $("#dialog-form-enroll #job");
    var volElement = $("#dialog-form-enroll #resource");
    var volListArea = $("#resource-list");
    var volList = $("#resource-list-items");
    var tips = $(".validateTips");
    var needsFromServer;
    var list;
    
    var progid = $("#calinfo").data("calid");
    var jobs = JSON.parse($('#programJobs').attr('data-vjobs'));
    
    var jobSections = '';
    
    var dateFormatFunc = scheduler.date.date_to_str("%Y-%m-%d %H:%i");
    var str2dateFunc = scheduler.date.str_to_date("%Y-%m-%d");
    
    function collectResources() {
                list = $("div#resource-list-items .draggable").map(
                        function() {
                            return(eventElement.val() + ':' + jobElement.val() + ':' + $(this).data('resourceid'));
                        }).get().join();
                if (list.length > 0) {
                    return true;
                } else {
                    //updateTips('You must add at least one resource to the calendar.', 0);
                    list = eventElement.val() + ':' + jobElement.val() + ":X";
                    return true;
                }
            }
    
    function enrollVolunteers() {
       collectResources();
       $.post(
                ("/ajax/enrollvolunteers"),
                {info:list},
                function(data) {
                    volElement.val("");
                    volList.html("");
                    $("#dialog-form-enroll").dialog("close");
                    //window.location.reload();
                }
       );
    }
    
    function updateTips( t, append ) {
                    if (append == 1) {
                    tips
                            .append('<li>' + t + '</li>' )
                            .addClass( "ui-state-highlight" );
                    } else {
                        tips.text( t ).addClass( "ui-state-highlight");
                    }
                    
                    setTimeout(function() {
                            tips.removeClass( "ui-state-highlight", 1500 );
                    }, 500 );
            }
    
    function addVolunteers(eventID) {
        var volEvent = scheduler.getEvent(eventID);
        jobElement.html("");
        eventElement.val(eventID);
        $.post(
                "/ajax/geteventneeds",
                {id:eventID},
                function(data) {
                    //set up job options
                    needsFromServer = data;
                    jobList = data.needDetails;
                    $.each(jobList, function(k,v) {
                        if (v.neededCount > 0) jobElement.append('<option value="' + v.jobID + '">' + v.jobName + '</option>');
                    })
                    jobElement.focus();
                    $(".ui-dialog-title").text('Enroll volunteers in ' + volEvent.text);
                    $("#resource-list h3").text('Signing up for ' + $("#job option:selected").text());
                    $("#dialog-form-enroll").dialog("open");
                }
                )
        
    }
    
    
    
jobElement.focus(function() {
        volList.html("");
        volListArea.show(); 
        myID = $(this).val();
        $.each(needsFromServer.needDetails,function(key,val) {            
            if (val.jobID == myID) {
                $.each(val.resources,function(k,v){
                    volData = v.toString().split(",");
                    volID = volData[0];
                    volName = volData[1];
                    
                    if (readOnly) {
                        removeButton = "";
                    } else {
                        removeButton = "<img class='sprite-pic remove-pic' src='/skins/default/images/blank.gif' align='left'>";
                    }
                        
                        volList.append(
                                "<div class='draggable small' data-resourceid='" 
                                + volID 
                                + "' >"  
                                + "<span>" + volName + "</span>"
                                + removeButton 
                                + "</div>"
                                );
                        $(".remove-pic").click(function(){
                            $(this).parents(".draggable").remove();
                        })
                    
                    
                })
            }
        })
    })
    
jobElement.change(function() {
        $("#resource-list h3").text('Signing up for ' + $("#job option:selected").text());
        volList.html("");
        volListArea.show(); 
        myID = $(this).val();
        $.each(needsFromServer.needDetails,function(key,val) {            
            if (val.jobID == myID) {
                $.each(val.resources,function(k,v){
                    volData = v.toString().split(",");
                    volID = volData[0];
                    volName = volData[1];
                        
                    if (readOnly) {
                        removeButton = "";
                    } else {
                        removeButton = "<img class='sprite-pic remove-pic' src='/skins/default/images/blank.gif' align='left'>";
                    }
                        
                        volList.append(
                                "<div class='draggable small' data-resourceid='" 
                                + volID 
                                + "' >"  
                                + "<span>" + volName + "</span>"
                                + removeButton + "</div>"
                                );
                        $(".remove-pic").click(function(){
                            $(this).parents(".draggable").remove();
                        })
                    
                    
                })
            }
        })
    })
    
    
volElement.focus(function() {
                type = 'volunteer';
                volElement
                        .autocomplete("destroy")
                        .autocomplete({
                    source: function(request,response) {
                        $.ajax({
                            url: "/dash/autocomplete",
                            dataType: "json",
                            data: {
                                term: request.term,
                                type: type
                            },
                            success: function(data) {
                                response(data);
                            }
                        });
                    },
                    focus: function(event,ui) {
                        $(this).val(ui.item.label);
                        return false;
                    },
                    select: function(event,ui) {
                        var unique = true;
                        $(this).val(ui.item.label);
                        volList.find("div.draggable").each(function() {
                            existsID=$(this).attr('data-resourceid');
                            if (existsID == ui.item.value) {
                                alert(ui.item.label + " is already enrolled.");
                                unique=false;
                            }
                        })

                        if (unique) {
                            volList.append(
                                "<div class='draggable small' data-resourceid=" 
                                + ui.item.value 
                                + " data-resourcetype=" 
                                + type 
                                +">" 
                                + "<span>" + ui.item.label + "</span>"
                                + "<img class='sprite-pic remove-pic' src='/skins/default/images/blank.gif' align='left'></div>"
                                );
                            $(".remove-pic").click(function(){
                                $(this).parents(".draggable").remove();
                            })
                        }
                    
                        return false;
                    },
                    minlength: 2,
                    delay: 50
                });
            })
    
    
    scheduler.config.first_hour = "07";
    scheduler.config.last_hour = "21";
    scheduler.config.start_on_monday = true;
    
    scheduler.templates.event_bar_date = function(start,end,ev) {
        return scheduler.templates.event_date(start);
    }
    
    scheduler.config.details_on_create = true;    
    scheduler.config.details_on_dblclick = true;    
    scheduler.config.time_step = 15;
    scheduler.config.fix_tab_position = true;
    
    if (!readOnly) {
            scheduler.config.lightbox.sections=[
            {name:"Event Name", height:21, type:"textarea",map_to:"text",focus: true},
            {name:"Description", height:80, map_to:"desc", type:"textarea"},
            {name:"Needs", height:120,map_to:"needs_data",type:"template"},
            {name:"Location", height:21, type:"textarea",map_to:"spot"},
            {name:"time", height:72, type:"time", map_to:"auto"}
        ];

        scheduler.config.buttons_left=["dhx_save_btn","dhx_cancel_btn","enroll_btn"];
        scheduler.locale.labels["enroll_btn"] = "Enroll Volunteers";
    } else {
    
        scheduler.config.lightbox.sections=[
            {name:"Event Name", height:21, type:"textarea",map_to:"text"},
            {name:"Description", height:100, type: "textarea", map_to:"desc"},
            {name:"Location", height:21, type:"textarea",map_to:"spot"}
        ];
        scheduler.config.buttons_left=["enroll_btn"];
        scheduler.locale.labels['enroll_btn'] = "Sign Up";
        scheduler.config.buttons_right=["dhx_cancel_btn"];
    }
    
    scheduler.attachEvent("onLightboxButton", function(button_id, node, e){
        if(button_id == "enroll_btn"){
            var eventID = scheduler.getState().lightbox_id;
            addVolunteers(eventID);
        }
    });
    
    if (!readOnly) {
        scheduler.config.icons_select = [
            "icon_details",
            "icon_enroll",
            "icon_delete"
        ];
    } else {
        scheduler.config.icons_select = ['icon_details','icon_enroll'];
    }
    
    scheduler.locale.labels.icon_enroll = "Enroll Volunteers";
    scheduler._click.buttons.enroll = function(id) {
        addVolunteers(id);
    }
    
    scheduler.attachEvent("onTemplatesReady", function(){
        scheduler.templates.event_text=function(start,end,event){
            var markup = "";
            $.ajax({
                    type: "POST",
                    url: "/ajax/geteventneeds",
                    data: {id:event.id},
                    async: false,
                    success: function(data){
                                var jobsFromServer = data.needDetails;
                                $.each(jobsFromServer, function(k,v) {
                                    if (v.neededCount > 0) {
                                        markup += "<br><b>" + v.jobName + "</b>: (" + v.signedUpCount + " of " + v.neededCount + ")";
                                    }
                                })
                             }
                });
            
                return "<b><font size='2' style='color: yellow'>" + event.text + "</font></b>" 
                        + "<br>Location: " + event.spot
                        + markup ;

            }
            
    }); 
    
    scheduler.attachEvent("onEventCreated", function(id,e) {
        var ev = scheduler.getEvent(id);
        ev.needs_data = "<form id='needsForm'>";
        var rowstate = "even";
        var floatclass;
        $.each(jobs, function (key,value){
            $.each (value, function (k,v){
                if (rowstate == "even") {rowstate = "odd"; floatclass = "float-left"} else {rowstate = "even"; floatclass = "float-right"}
                ev.needs_data += "<div class='half " + floatclass + "'><b><span class='scheduler-span'>" + v + ":</span></b> <input id='job-" + k + "' type=text name='" + k + "' length=3></div>";
            })
        })
        ev.needs_data += "</form>";
    })
    
    scheduler.attachEvent("onBeforeLightbox", function (id){
            
        var ev = scheduler.getEvent(id);
        ev.needs_data = '';
        var rowstate = "even";
        var floatclass;
        ev.needs_data = "<form id='needsForm'>";
        $.each(jobs, function (key,value){
            $.each (value, function (k,v){
                if (rowstate == "even") {rowstate = "odd"; floatclass = "float-left"} else {rowstate = "even"; floatclass = "float-right"}
                ev.needs_data += "<div class='half " + floatclass + "'><b><span class='scheduler-span'>" + v + ":</span></b> <input id='job-" + k + "' type=text name='" + k + "' length=3></div>";
            })
        })
        ev.needs_data += "</form>";
            $.post(
                ("/ajax/geteventneeds"),
                {id:id},
                function(data){
                    var jobsFromServer = data.needDetails;
                    $.each(jobsFromServer, function(k,v){
                        var jid = "job-" + v.jobID;
                        var numN = v.neededCount;
                        $("#" + jid).val(numN);
                        //alert(jid + " value will be " + numN);
                    })
                }
            );
            return true;
        }) 
    
    if (readOnly) {
        scheduler.config.readonly_form = true;
    }
    
    scheduler.init('prog_scheduler', new Date(), "week");
    
    $.post(
        ("/ajax/scheduleevent"),
        {task:'getprogevents', setid: progid},
        function(data){
                scheduler.parse(data, "json");
            }

        );

    function fireAjax(id,ev,mode) {
        from_date = dateFormatFunc(ev.start_date);
        to_date = dateFormatFunc(ev.end_date);
        desc = ev.desc;
        name = ev.text;
        oldID = ev.id;
        spot = ev.spot;
        //needed = ev.num_needed;
        needs = $("#needsForm").serializeArray();
        needsJSON = JSON.stringify(needs);
        
//        needsJSON = needsJSON.replace('name', 'jobID')
//                             .replace('value', 'volNeeds');
//        
        
        
        $.post(
                ("/ajax/eventsave"),
                {task: mode, type: 'program', setid: progid, location: spot, startdate: from_date, enddate: to_date, text: name, desc: desc, volunteersNeeded: needsJSON, eventid: id},
                function(data){
                    if (data['status'] == 'FAIL') {
                        alert ("Event not saved. Please see message:\n" + data['message']);
                        scheduler.deleteEvent(id);
                    }
                    
                    if (data['status'] == 'SUCCESS') {
                        scheduler.changeEventId(oldID,data['eventid']);
                    }
                }
                );
    }
//
    scheduler.attachEvent("onEventAdded", function(id,ev){
        if(!readOnly) { 
            fireAjax(id,ev,'insert');
        } else {
            alert ("You must be logged in with a higher access level to create new events.");
            scheduler.deleteEvent(id);
        }
    })
    
    scheduler.attachEvent("onEventChanged", function(id,ev){
        if (!readOnly) {
            fireAjax(id,ev,'update');
        } else {
            alert ("You must be logged in with a higher access level to change event details.");
            window.location.reload();
        }
    })
    
    scheduler.attachEvent("onEventDeleted", function(id,ev){
        if (!readOnly) {
            $.post(
                    ("/ajax/eventarchive"),
                    {type: 'program', id: id},
                    function(data) {
                        //alert(data['status']);
                    }

            )
        } else {
            alert ("You must be logged in with a higher access level to delete events.");
            
        }
    })
    
    
    $( "#dialog-form-enroll" ).dialog({
                    autoOpen: false,
                    height: 450,
                    width: 500,
                    modal: true,
                    buttons: {
                            "Enroll": function() {
                                        enrollVolunteers();
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            $("#enrollVolForm input, #enrollVolForm select").each(
                                    function(index){
                                        $(this).val( "" ).removeClass( "ui-state-error" );
                                    }
                                    );
                    },
                    open: function(e,ui) {
                        updateTips('Please fill out all the fields', 0);
                        $("input,select").bind("keydown", function (e) {
                            var keyCode = e.keyCode || e.which;
                            if(keyCode === 13) {
                                e.preventDefault();
                                $('input, select, textarea')
                                [$('input,select,textarea').index(this)+1].focus();
                            }
                        });

                    }
            });

});