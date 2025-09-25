$(function() {
    var readOnly = $("#readonly").data("readonly");
    
    var setInfo = $("#setinfo");
    var dateFormatFunc = scheduler.date.date_to_str("%Y-%m-%d %H:%i");
    var str2dateFunc = scheduler.date.str_to_date("%Y-%m-%d");
    
    
    var fromTime = setInfo.data("starttime").split(':')[0];
    var toTime = setInfo.data("endtime").split(':')[0];
    var fromDate = str2dateFunc(setInfo.data("startdate"));    
    var toDate = str2dateFunc(setInfo.data("enddate"));    
    
    //stupid hacky solution to stupid plugin problem where it expects a 0-11 month array
    var fromYear = setInfo.data("startdate").split('-')[0];
    var fromMonth = parseInt(setInfo.data("startdate").split('-')[1]); fromMonth--; fromMonth.toString();
    var fromD = parseInt(setInfo.data("startdate").split('-')[2]); fromD.toString();

    var toYear = setInfo.data("enddate").split('-')[0];
    var toMonth = parseInt(setInfo.data("enddate").split('-')[1]); toMonth--; toMonth.toString();
    var toD = parseInt(setInfo.data("enddate").split('-')[2]); toD++; toD.toString();
    var limitD = parseInt(toD); limitD++;
    
    var setid = setInfo.data("setid");
    
    var pList = setInfo.data("list");
    
    scheduler.createUnitsView({
        name:"unit",
        property:"unit_id", //the mapped data property
        list:pList,
        days:5
    });
    
    scheduler.date.unit_start = function(date) {
        date = scheduler.date.add(date, 2, 'day');
        return scheduler.date.week_start(date);
    }
    
    scheduler.locale.labels.unit_tab = "Resources";
    
    /*scheduler.ignore_unit = function(date){
    if (date.getDay() == 6 || date.getDay() == 0) //hides Saturdays and Sundays
        return true;
    };*/
    
    scheduler.config.first_hour = fromTime;
    scheduler.config.last_hour = toTime;
    scheduler.config.start_on_monday = true;
    scheduler.config.hour_size_px = 176;
    //scheduler.date.unit_start = scheduler.date.week_start;
    
    scheduler.config.details_on_create = false;
    scheduler.config.limit_start = new Date(fromYear,fromMonth,fromD);
    scheduler.config.limit_end = new Date(toYear,toMonth,toD);
    scheduler.config.limit_time_select = true;
    
    //limit what displays in the calendar if today is within its scope
    //if today is not within its scope (calendar is future or past), out-of-scope dates will not be editable but will display.
    //if (fromDate < new Date() || toDate > new Date()) {scheduler.config.limit_view = true};
    
    scheduler.config.time_step = 15;
    scheduler.config.fix_tab_position = true;
    
    scheduler.addMarkedTimespan({
        type: "dhx_time_block",
        start_date: new Date(2016,0,1),
        end_date: new Date(fromYear,fromMonth,fromD),
        zones:"fullday"
    })
    
    scheduler.addMarkedTimespan({
        type: "dhx_time_block",
        start_date: new Date(toYear,toMonth,toD),
        end_date: new Date(2100,0,1),
        zones:"fullday"
    })
    
    scheduler.init('scheduler_here', new Date(), "unit");
    
    $.post(
        ("/ajax/scheduleevent"),
        {task:'getevents', setid: setid},
        function(data){
                scheduler.parse(data, "json");
            }

        );

    function fireAjax(id,ev,mode) {
        from_date = dateFormatFunc(ev.start_date);
        to_date = dateFormatFunc(ev.end_date);
        resID = ev.unit_id;
        text = ev.text;
        oldID = ev.id;
                
        $.post(
                ("/ajax/eventsave"),
                {task: mode, type: 'set', setid: setid, startdate: from_date, enddate: to_date, unit_id: resID, text: text, eventid: id},
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

    scheduler.attachEvent("onEventAdded", function(id,ev){
        fireAjax(id,ev,'insert');
    })
    
    scheduler.attachEvent("onEventChanged", function(id,ev){
        fireAjax(id,ev,'update');
    })
    
    scheduler.attachEvent("onEventDeleted", function(id,ev){
        $.post(
                ("/ajax/eventarchive"),
                {type: 'set', id: id},
                function(data) {
                    //alert(data['status']);
                }
                        
        )
    })
    

});