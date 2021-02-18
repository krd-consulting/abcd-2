$(function(){
    
    var chartProg;
    var optionsDiv = $("#options-div-prog");
    var dataDiv = $("#reportDiv-prog");
    
    function makeTable(data) {
        optionsDiv.slideUp(500);
        dataDiv.html('<table cellpadding="0" cellspacing="0" border="0" style="width: 100%" class="display" id="reportTable"></table>\n\
                                <button id="resetButton">Build another report</button>');
        $("#reportTable").dataTable( {
            "aaData": data.aaData,
            "aoColumns" : data.aoColumns,
            "aoColumnDefs" : [{"bSearchable" : false, "bVisible" : false, "aTargets" : [0] }],
            "aaSortingFixed" : [[0,'asc']],
            "bJQueryUI" : true,
            "bAutoWidth" : true,
            "sScrollX"  : "100%",
            "sScrollXInner"  : "110%",
            "bScrollCollapse": true,
            "bDestroy" : true
        });
        
        $("#reportTable tr").last().css("font-weight", "bold");
        $("#reportTable tr").last().css("font-size", "10pt");
        
        $("#resetButton").button()
                            .click(function(){
                            dataDiv.html(''); 
                            optionsDiv.slideDown(500);
                            });
    }
    
    function makeAttendanceGraph(data) {
    optionsDiv.slideUp(500);
    dataDiv.css('width','100%')
            .css('height', '400px');
    chartProg = new Highcharts.Chart({
        chart: {
            renderTo:              'reportDiv-prog',
            defaultSeriesType:     'column'
        },
        title: {
            text: data.title
        },
        subtitle: {
            text: "Attendance by Group"
        },
        xAxis: {
            categories: data.months,
            title: {
                enabled: false
            },
            reversed: false
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Participants'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: 'gray'
                }
            }
        },
        legend: {
            backgroundColor: '#FFFFFF',
            reversed: true
        },
        tooltip: {
            formatter: function() {
                return ''+
                this.series.name +': '+ this.y +' participants';
            }
        },           
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: data.groups,
        exporting: {
            buttons: {
                exportButton: {
                    menuItems: [{
                        text: 'Download JPEG (small)',
                        onclick: function() {
                            this.exportChart({
                                width: 250,
                                type: "image/jpeg"
                            });
                        }
                    }, {
                        text: 'Download JPEG (large)',
                        onclick: function() {
                            this.exportChart({
                                type: "image/jpeg"
                            }); // 800px by default
                        }
                    }, {
                        text: 'Download PDF',
                        onclick: function() {
                            this.exportChart({
                                type: "application/pdf"
                            })
                        }
                    },
                    null
                    ]
                }
            }
        }
    });

    dataDiv.append(
            '<button id="resetButton">Build another report</button>'
    );
    $("#resetButton").button()
                        .click(function(){
                        dataDiv.html(''); 
                        optionsDiv.slideDown(500);
                        });    
}
                
    function makeEnrollGraph(data) {
                    optionsDiv.slideUp(500);
                    dataDiv.css('width','100%')
                                         .css('height', '400px');
                    chartProg = new Highcharts.Chart({
                       chart: {
                           renderTo:              'reportDiv-prog',
                           defaultSeriesType:     'area'
                       },
                       title: {
                           text: data.title
                       },
                       subtitle: {
                            text: 'Program Enrollment'
                       },
                       xAxis: {
                           categories: data.months,
                           title: {
                               text: 'Month'
                           },
                           reversed: false
                       },
                       yAxis: {
                           title: {
                               text: 'Participants by status'
                           }
                       },
                       legend: {
                           backgroundColor: '#FFFFFF',
                           reversed: true
                       },
                       tooltip: {
                            /*formatter: function() {
                                return ''+
                                this.series.name +': '+ 
                                Highcharts.numberFormat(this.y, 0, ',') + ' members';
                            }*/
                            enabled: false
                       },           
                       plotOptions: {
                            area: {
                                stacking: 'normal',
                                lineColor: '#666666',
                                lineWidth: 1,
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y == 0) {
                                            return '';
                                        } else {
                                            return this.y + ' ' + this.series.name;
                                        }
                                    }
                                },
                                marker: {
                                    enabled: false
                                }
                            }
                       },
                       series: [
                                {
                                 name: 'Concluded',
                                 data: data.concluded
                                },
                                {
                                 name: 'On Leave',
                                 data: data.leave
                                },
                                {
                                 name: 'Waitlisted',
                                 data: data.waitlist
                                },
                                {
                                 name: 'Active',
                                 data: data.active
                                }
                       ],
                       exporting: {
                            buttons: {
                                exportButton: {
                                    menuItems: [{
                                        text: 'Download JPEG (small)',
                                        onclick: function() {
                                            this.exportChart({
                                                width: 250,
                                                type: "image/jpeg"
                                            });
                                        }
                                    }, {
                                        text: 'Download JPEG (large)',
                                        onclick: function() {
                                            this.exportChart({
                                                type: "image/jpeg"
                                            }); // 800px by default
                                        }
                                    }, {
                                        text: 'Download PDF',
                                        onclick: function() {
                                            this.exportChart({
                                                type: "application/pdf"
                                            })
                                        }
                                    },
                                    null
                                    ]
                                }
                            }
                       }
                    });
                    
                    dataDiv.append(
                            '<button class="resetButton">Build another report</button>'
                    );
                    $(".resetButton").button()
                                     .click(function(){
                                        dataDiv.html(''); 
                                        optionsDiv.slideDown(500);
                                     });    
                }            
    
    function getReport(){
        progID = $(":input#progList").val();
        var fromDate = $("input#fromDateProg").val();
        var toDate = $("input#toDateProg").val();
        reportFormat = $("input:radio[name='formatSelectProg']:checked").val();
        
        $.post(
            "/ajax/progreport",
            {id: progID, from: fromDate, to: toDate, format: reportFormat},
            function(data) {
                if (reportFormat == 'table') {
                    makeTable(data);
                } else if (reportFormat == 'excel') {
                    document.location = '/files/' + data.file;
                } else if (reportFormat == 'attend-graph') {
                    makeAttendanceGraph(data);
                } else if (reportFormat == 'enroll-graph') {
                    makeEnrollGraph(data);
                }
            }
        );
    }
    
    $(":input#progList").change(function(){
       $(this).parents('div').next('div.block').fadeIn(500); 
    });
    
    $(":input[name='dateSelectProg']").change(function(){
        if ($(this).val() == 'all') {
            $("#dateBoxes-prog input").val('');
            $("#dateBoxes-prog").slideUp(500);
        } else if ($(this).val() == 'filter') {
            $("#dateBoxes-prog").slideDown(500);
        }
        
        $(this).parents('div').next('div.block').fadeIn(500); 
        
    })
    
    $("#buildProgReport").button()
                         .css('position', 'absolute')
                         .css('bottom', '5px')
                         .css('left', '120px')
                         .click(function(){
                            getReport(); 
                         });
    
});

