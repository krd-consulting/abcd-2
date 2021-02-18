$(function(){
    
    var chartPtcp;
    var optionsDiv = $("#options-div-ptcp");
    var dataDiv = $("#reportDiv-ptcp");
    
    function addLineGraph(divName,gName,dates,dataLabels,dataValues,maxVal) 
    {
        dataDiv.append("<div class='graphContainer'>" +
                            "<div id=" + divName + ">" +
                            "</div>" +
                        "</div>");
        valueArray = new Array();
        
        //passed values being treated as strings,
        //convert to integers and store in new array
        $.map(dataValues, function(value, index){
            valueArray[index] = parseFloat(value);
        });
        
        newChart = new Highcharts.Chart({
            chart: {
                renderTo:              divName,
                borderWidth:           1,
                borderColor:           '#333333',
                defaultSeriesType:     'areaspline'
                
            },
            title: {
                text: formName + " data for " + ptcpName
            },
            subtitle: {
                text: gName
            },
            xAxis: {
                categories: dates,
                title: {
                    enabled: false
                },
                reversed: true
            },
            yAxis: {
                title: {
                    text: ''
                },
                allowDecimals: false,
                categories: dataLabels,
                min: 0,
                max: maxVal
            },
            legend: {
                backgroundColor: '#FFFFFF',
                reversed: true
            },
            tooltip: {
                enabled: false
            },           
            series: [{
                name: gName,
                data: valueArray
            }],
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
    }
    
    function makeFormGraphs(data) {
        optionsDiv.slideUp(500);
        ptcpName = data.pName;
        formName = data.fName;
        numCharts = data.number;
        dataArray = $.makeArray(data.charts);
        $.map(dataArray, function(val, i) {
                name = dataArray[i]['name'];
                divName = 'chart_' + i;
                dataValues = val['values'];
                dates = data.dates;
                dataLabels = val['labels'];
                maxVal = val['max'];
                addLineGraph(divName,name,dates,dataLabels, dataValues, maxVal);
                $("#" + divName).slideDown(500);
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
    
    function makeTable(data) {
        optionsDiv.slideUp(500);
        dataDiv.html('<table cellpadding="0" cellspacing="0" border="0" style="width: 100%" class="display" id="reportTable"></table>\n\
                                <button id="resetButton">Build another report</button>');
        $("#reportTable").dataTable( {
            "aaData": data.aaData,
            "aoColumns" : data.aoColumns,
            "bJQueryUI" : true,
            "bAutoWidth" : true,
            "bDestroy" : true
        });
        
        $("#resetButton").button()
                            .click(function(){
                            dataDiv.html(''); 
                            optionsDiv.slideDown(500);
                            });
    }
    
    function makeRoleGraph(data) {
    optionsDiv.slideUp(500);
    dataDiv.css('width','100%')
            .css('height', '400px');
    chartPtcp = new Highcharts.Chart({
        chart: {
            renderTo:              'reportDiv-ptcp',
            defaultSeriesType:     'areaspline'
        },
        title: {
            text: data.title
        },
        subtitle: {
            text: "Engagement level over time"
        },
        xAxis: {
            categories: data.dates,
            title: {
                enabled: false
            },
            reversed: false
        },
        yAxis: {
            title: {
                text: 'Level of Participation'
            },
            categories: ['','In Attendance', 'Active Participant', 'Leadership Role', ''],
            max: 4
        },
        legend: {
            backgroundColor: '#FFFFFF',
            reversed: true
        },
        tooltip: {
            enabled: false
        },           
        plotOptions: {
            area: {
                marker: {
                    enabled: false
                }
            }
        },
        series: [{
           name: data.levels.name,
           data: data.levels.data
        }],
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
    
    function getReport(){
        ptcpID = $("#targetID").val();
        if (ptcpID.length < 1) {
            alert("Error - no participant selected.");
            return false;
        }
        var fromDate = $("input#fromDatePtcp").val();
        var toDate = $("input#toDatePtcp").val();
        var reportFormat = $("input:radio[name='formatSelectPtcp']:checked").val();
        var entity = $("input#scopeType").val();
        var entityID = $("#scopeDropdown").val();
        var formFields = $("input[name='formFields[]']").serialize();
        
        $.post(
            "/ajax/ptcpreport",
            {id: ptcpID, from: fromDate, to: toDate, format: reportFormat, entity: entity, eid: entityID, formFields: formFields},
            function(data) {
                if (reportFormat == 'table') {
                    makeTable(data);
                } else if (reportFormat == 'excel') {
                    document.location = '/files/' + data.file;
                } else if (reportFormat == 'graph') {
                    makeRoleGraph(data);
                } else if (reportFormat == 'formGraph') {
                    makeFormGraphs(data);
                }
            }
        );
    }
    
    function getScopeList(t) {
        var type = t;
        var ptcpID = $("#targetID").val();
        
        $.post(
            "/ajax/ptcpscope",
            {id: ptcpID, type: type},
            function(data) {
                $("#scopeHolder").html("<h3>" + data.scopelist + "</h3>");
                $("#tempdiv").remove();
                $("#formatOptions-ptcp").append("<div id='tempdiv'>" + data.reportTypes + "</div>");
                $("#scopeOptions-ptcp").fadeIn(500);
                
                $("#scopeDropdown").click(function(){    
                    if (type == 'form') {
                        $.post(
                            "/ajax/formfields",
                            {formID: $(this).val()},
                            function(data) {
                                $("#formFieldSelect").html(data).slideDown(500);
                                $("#formFieldSelect :checkbox").each(function(){
                                    $(this).attr('checked','checked');
                                });
                                $("#formFieldSelect").addClass('field-select');
                                
                            }
                        );
                    }
                    if ($(this).val().length > 0) {
                        $("#formatOptions-ptcp").fadeIn(500); 
                    }
                })
                
            }
        );
    }
    
    $(":input[name='repType']").click(function(){
       getScopeList($(this).val());
       $("#formFieldSelect").removeClass('field-select').slideUp(500).html('');
    });
    
    $("#targetID").change(function(){
       $(":input[name='repType']").prop('checked', false);
       $("#scopeOptions-ptcp").fadeOut();
       $("#formatOptions-ptcp").fadeOut();
    });
    
    
    $("#buildPtcpReport").button()
                         .css('position', 'absolute')
                         .css('bottom', '5px')
                         .css('left', '120px')
                         .click(function(){
                            getReport(); 
                         });
    
});

