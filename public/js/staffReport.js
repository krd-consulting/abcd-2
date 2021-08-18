$(function(){
    
    var chartStaff;
    var optionsDiv  = $("#options-div-staff");
    var dataDiv     = $("#reportDiv-staff");
    
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
        
        //$("#reportTable tr").last().css("font-weight", "bold");
        //$("#reportTable tr").last().css("font-size", "10pt");
        $("#reportTable").css("margin-bottom", "7px");
        
        $("#resetButton").button()
                            .click(function(){
                            dataDiv.html(''); 
                            optionsDiv.slideDown(500);
                            })
                          .css("margin-top", "10px");
    }
                
    function makeCaseloadGraph(data) {
                    optionsDiv.slideUp(500);
                    dataDiv.css('width','100%')
                                         .css('height', '400px');
                    chartStaff = new Highcharts.Chart({
                       chart: {
                           renderTo:              'reportDiv-staff',
                           defaultSeriesType:     'area'
                       },
                       title: {
                           text: data.title
                       },
                       subtitle: {
                            text: 'Caseload Overview'
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
        staffID = $(":input#userList").val();
        var fromDate = $("input#fromDateStaff").val();
        var toDate = $("input#toDateStaff").val();
        reportFormat = $("input:radio[name='formatSelectStaff']:checked").val();
        
        $.post(
            "/ajax/staffreport",
            {id: staffID, from: fromDate, to: toDate, format: reportFormat},
            function(data) {
                if (reportFormat == 'table' || reportFormat == 'caseload-snap') {
                    makeTable(data);
                } else if (reportFormat == 'excel') {
                    document.location = '/files/' + data.file;
                } else if (reportFormat == 'caseload-graph') {
                    makeCaseloadGraph(data);
                }
            }
        );
    }
    
    $(":input#userList").change(function(){
       $(this).parents('div').next('div.block').fadeIn(500); 
    });
    
    $(":input[name='dateSelectStaff']").change(function(){
        if ($(this).val() == 'all') {
            $("#dateBoxes-staff input").val('');
            $("#dateBoxes-staff").slideUp(500);
        } else if ($(this).val() == 'filter') {
            $("#dateBoxes-staff").slideDown(500);
        }
        
        $(this).parents('div').next('div.block').fadeIn(500); 
        
    })
    
    $("#buildStaffReport").button()
                         .css('position', 'absolute')
                         .css('bottom', '5px')
                         .css('left', '120px')
                         .click(function(){
                            getReport(); 
                         });
    
});

