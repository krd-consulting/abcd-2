$(function() {                
                $( ".birthdaypicker" ).datepicker({
			changeMonth: true,
                        changeYear: true,
                        yearRange: "-100,+0",
                        minDate: "-101Y",
                        maxDate: "-1D",
                        showButtonPanel: true,
                        dateFormat : "yy-mm-dd"
		});
                
                $( ".entrydaypicker").datepicker({
                        changeMonth:    true,
                        changeYear:     true,
                        yearRange:      "-1,+1",
                        minDate:        "-1Y",
                        maxDate:        "today",
                        showButtonPanel:true,
                        dateFormat:     "yy-mm-dd"
                });
                
                $( ".dynamicdatepicker").datepicker({
                        changeMonth:    true,
                        changeYear:     true,
                        //yearRange:      "-100,+100",
                        minDate:        "-1Y",
                        maxDate:        "+10Y",
                        showButtonPanel:true,
                        dateFormat:     "yy-mm-dd"
                });
                
                if ($(".refersToSchedule")[0]) {
                    setID = $(".refersToSchedule").data('scheduleid');
                    $.post(
                            "/ajax/gettimeboundaries",
                            {sid:setID},
                            function(data) {
                                var startDate = new Date(data.startDate);
                                var endDate = new Date(data.endDate);
                                startDate.setDate(startDate.getDate() + 1);
                                endDate.setDate(endDate.getDate() + 1);
                              $(".refersToSchedule").datepicker({
                                    changeMonth:        true,
                                    changeYear:         true,
                                    dateFormat:         "yy-mm-dd",
                                    showButtonPanel:    true,
                                    minDate:            startDate,
                                    maxDate:            endDate
                                })  
                            }
                    );
                }
                
                
                
                
	});


