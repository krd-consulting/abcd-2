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
                        yearRange:      "-100,+100",
                        minDate:        "-100Y",
                        maxDate:        "+100Y",
                        showButtonPanel:true,
                        dateFormat:     "yy-mm-dd"
                });
                
                
                
	});


