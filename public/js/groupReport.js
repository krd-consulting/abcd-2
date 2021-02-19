$(function(){
    
    var chart;
    
    function makeTable(data) {
        $("#options-div-group").slideUp(500);
                    $("#reportDiv-group").html('<table cellpadding="0" cellspacing="0" border="0" style="width: 100%" class="display" id="reportTable"></table>\n\
                                          <button id="resetButton">Build another report</button>');
                    $("#reportTable").dataTable( {
                        "aaData": data.aaData,
                        "aoColumns" : data.aoColumns,
                        "bJQueryUI" : true,
                        //"sScrollX"  : "100%",
                        //"sScrollXInner"  : "110%",
                        //"bScrollCollapse": false,
                        "bAutoWidth" : true
                    });
                    $("#resetButton").button()
                                     .click(function(){
                                        $("#reportDiv-group").html(''); 
                                        $("#options-div-group").slideDown(500);
                                     });
    }
    
    function makeAttendanceGraph(data) {
                    $("#options-div-group").slideUp(500);
                    $("#reportDiv-group").css('width','100%')
                                         .css('height', '400px');
                    chart = new Highcharts.Chart({
                       chart: {
                           renderTo:              'reportDiv-group',
                           defaultSeriesType:     'column'
                       },
                       title: {
                           text: data.title
                       },
                       xAxis: {
                           categories: data.mtgDates,
                           title: {
                               text: 'Meetings'
                           },
                           reversed: true
                       },
                       yAxis: {
                           min: 0,
                           title: {
                               text: 'Participant Count'
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
                                return '<b>'+ this.x +'</b><br/>'+
                                    this.series.name +': '+ this.y +'<br/>'+
                                    'Total: '+ this.point.stackTotal;
                            }
                       },           
                       plotOptions: {
                            series: {
                                stacking: 'normal'
                            }
                       },
                       series: [
                                {
                                 name: 'Volunteers',
                                 data: data.volNumbers
                                },
                                {
                                 name: 'Guests',
                                 data: data.guestNumbers
                                },
                                {
                                 name: 'Members',
                                 data: data.memberNumbers
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
                    
                    $("#reportDiv-group").append(
                            '<button id="resetButton">Build another report</button>'
                    );
                    $("#resetButton").button()
                                     .click(function(){
                                        $("#reportDiv-group").html(''); 
                                        $("#options-div-group").slideDown(500);
                                     });    
                }
                
    function makeRoleGraph(data) {
                    $("#options-div-group").slideUp(500);
                    $("#reportDiv-group").css('width','100%')
                                         .css('height', '400px');
                    chart = new Highcharts.Chart({
                       chart: {
                           renderTo:              'reportDiv-group',
                           defaultSeriesType:     'area'
                       },
                       title: {
                           text: data.title
                       },
                       subtitle: {
                            text: 'Participants\' Engagement'
                       },
                       xAxis: {
                           categories: data.mtgDates,
                           title: {
                               text: 'Meetings'
                           },
                           reversed: true
                       },
                       yAxis: {
                           title: {
                               text: 'Participants'
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
                                this.series.name +': '+ Highcharts.numberFormat(this.percentage, 1) +'% ('+
                                Highcharts.numberFormat(this.y, 0, ',') + ' members)';
                            }
                       },           
                       plotOptions: {
                            area: {
                                stacking: 'normal',
                                lineColor: '#666666',
                                lineWidth: 1,
                                marker: {
                                    lineWidth: 1,
                                    lineColor: '#ffffff'
                                }
                            }
                       },
                       series: [
                                {
                                 name: 'Leadership Role',
                                 data: data.leaders
                                },
                                {
                                 name: 'Active Participation',
                                 data: data.active
                                },
                                {
                                 name: 'In Attendance',
                                 data: data.passive
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
                    
                    $("#reportDiv-group").append(
                            '<button id="resetButton">Build another report</button>'
                    );
                    $("#resetButton").button()
                                     .click(function(){
                                        $("#reportDiv-group").html(''); 
                                        $("#options-div-group").slideDown(500);
                                     });    
                }            
    
    function getReport(){
        var groupID = $(":input#groupList").val();
        var fromDate = $("input#fromDateGroup").val();
        var toDate = $("input#toDateGroup").val();
        var reportFormat = $("input:radio[name='formatSelectGroup']:checked").val();
        
        $.post(
            "/ajax/groupreport",
            {id: groupID, from: fromDate, to: toDate, format: reportFormat},
            function(data) {
                if (reportFormat == 'table') {
                    makeTable(data);
                } else if (reportFormat == 'excel') {
                    document.location = '/files/' + data.file;
                } else if (reportFormat == 'attend-graph') {
                    makeAttendanceGraph(data);
                } else if (reportFormat == 'role-graph') {
                    makeRoleGraph(data);
                }
            }
        );
    }
    
    $(":input#groupList").change(function(){
       $(this).parents('div').next('div.block').fadeIn(500); 
    });
    
    $(":input[name='dateSelectGroup']").change(function(){
        if ($(this).val() == 'all') {
            $("#dateBoxes-group input").val('');
            $("#dateBoxes-group").slideUp(500);
        } else if ($(this).val() == 'filter') {
            $("#dateBoxes-group").slideDown(500);
        }
        
        $(this).parents('div').next('div.block').fadeIn(500); 
        
    })
    
    $("#buildGroupReport").button()
                         .css('position', 'absolute')
                         .css('bottom', '5px')
                         .css('left', '120px')
                         .click(function(){
                            getReport(); 
                         });
    
});

