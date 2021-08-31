$(function() {
    var pathname = window.location.pathname;
    if (pathname == '/schedule/profile/id/9') {
        //alert ('Will block dates.');
        scheduler.addMarkedTimespan({
            days: new Date(2020, 04, 10),
            zones: "fullday",
            type: "dhx_time_block",
            css: "red_section"
        });
    }
     
    var readOnly = $("#readonly").data("readonly");
     
    var setInfo = $("#setinfo");
    var dateFormatFunc = scheduler.date.date_to_str("%Y-%m-%d %H:%i");
    var str2dateFunc = scheduler.date.str_to_date("%Y-%m-%d");
    
    
    var fromTime = setInfo.data("starttime").split(':')[0];
    var toTime = setInfo.data("endtime").split(':')[0];
    var fromDate = str2dateFunc(setInfo.data("startdate"));    
    var toDate = str2dateFunc(setInfo.data("enddate"));    
    
    //alert ("Start Time is " + fromTime + " and End Time is " + toTime); 
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
        days:1
    });
    
    scheduler.templates.day_date = function(date){
    var formatFunc = scheduler.date.date_to_str("%j %M %Y - %l");
    return formatFunc(date);
};
    
//    scheduler.date.unit_start = function(date) {
//        date = scheduler.date.add(date, 2, 'day');
//        return scheduler.date.week_start(date);
//    }
    
    scheduler.locale.labels.unit_tab = "Schedules";
    
    /*scheduler.ignore_unit = function(date){
    if (date.getDay() == 6 || date.getDay() == 0) //hides Saturdays and Sundays
        return true;
    };*/
    
    scheduler.config.first_hour = fromTime;
    scheduler.config.last_hour = toTime;
    scheduler.config.start_on_monday = true;
    scheduler.config.hour_size_px = 132; //176;
    //scheduler.date.unit_start = scheduler.date.week_start;
    
    scheduler.config.details_on_create = false;
    scheduler.config.limit_start = new Date(fromYear,fromMonth,fromD);
    scheduler.config.limit_end = new Date(toYear,toMonth,toD);
    scheduler.config.limit_time_select = true;
    
    if (new Date(fromDate) < new Date()) {
        calStartDate = new Date();
    } else {
        calStartDate = new Date(fromDate);
    }
    
    scheduler.config.time_step = 15;
    scheduler.config.fix_tab_position = true;
    scheduler.config.minicalendar.mark_events = false; 

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
    
    scheduler.config.readonly = true;

    
    //initialize calendar
    scheduler.init('scheduler_here', calStartDate, "day");
    
    // show calendar's "unit" view, since it doesn't autoload any
    $("div[name='unit_tab']").trigger('click')
                             .addClass('hidden');
    
    $.post(
        ("/ajax/scheduleevent"),
        {task:'getevents', setid: setid},
        function(data){
                scheduler.parse(data, "json"); //add events                
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
    
    function show_minical(){
        if (scheduler.isCalendarVisible()){
            scheduler.destroyCalendar();
        } else {
            scheduler.renderCalendar({
                position:"dhx_minical_icon",
                date:scheduler._date,
                navigation:true,
                handler:function(date,calendar){
                    scheduler.setCurrentView(date);
                    scheduler.destroyCalendar();
                }
            });
        }
    }
    
    $("#dhx_minical_icon").click(function() {show_minical();});
    
    
    setTimeout(function() {
            $("div[name='unit_tab']").trigger('click');
        },100);
    
});
