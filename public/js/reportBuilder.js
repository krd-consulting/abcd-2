$(function() {
    
    var optionsDiv = $("#formBuilder");
    var dataDiv = $("#reportDisplay");
    
    var filterType = $("#filterForm").data('filtertype');
    
    var deptsOn = false;
    
    function toggleDepts() {
        if (deptsOn) {
            $("#deptList").remove();
            deptsOn = false;
            //enableTab();
        } else {   
            $.post (
                "/ajax/getuserdepts",
                function(data) {
                    deptFilterLiOpen = "<li id=deptList class=draggable ";
                    dataOptions = '"{';
                    
                    //$.each(data.deptlist, function(index,department) {
                    //    dataOptions = dataOptions + "'" + department.id + "':'" + department.deptName + "',";
                    //});
                    //dataOptions = dataOptions.slice(0,-1);
                    dataOptions += '}"';
                    
                    deptFilterLiOpen = deptFilterLiOpen + "data-options=" + dataOptions + ' data-type="checkbox" data-formname="DPTSELECT" data-formid="999" data-elementid="999" style="">';
                    
                    deptFilterSpan = "<span class='filterName'>Department</span>";
                    deptFilterIncludeSelector = '<select class="middleSelect" \n\
                                                        id="999_field_999-selector" data-type="checkbox">\n\
                                                <option value="includes">includes</option>\n\
                                                <option value="excludes">excludes</option>\n\
                                          </select>';
                    deptFilterSelector = '<select id="999_field_999" multiple="" data-formid="999" data-fieldid="field_999"></select>';
                    deptFilterLiClose = "</li>";
                    
                    deptFilter = deptFilterLiOpen + deptFilterSpan + deptFilterIncludeSelector + deptFilterSelector + deptFilterLiClose;
                    $("ul#filterList").prepend(deptFilter);
                    
                    $.each(data.deptlist, function(index,department) {
                        $("#999_field_999").append('<option value="' + department.id + '">' + department.deptName + '</option>');

                    });
                    //enableTab();
                }
                );
                
            deptsOn = true;
        }
        
    }
    
    function enableTab() {
        var numFilters = $("#filterList li").length;
        var numFields = $("#dataList li").length;
        
        if (numFilters == 0) {
            $("#options").tabs("option", "disabled", [1,2]);
        } 
        
        if ((numFilters > 0) && (numFields == 0)) {
            $("#options").tabs("option", "disabled", [1,2]);
            $("#options").tabs("enable", 1);
        } 
        
        if ((numFilters > 0) && (numFields > 0)) {
            $("#options").tabs("enable", 1);
            $("#options").tabs("enable", 2);
        }
        
    }

    function createFormFilterEntry(event, elm) {

        formID      = elm.item.data('formid');
        formName    = elm.item.data('formname');
        fieldID     = elm.item.data('elementid');
        fieldName   = elm.item.text();
        fieldType   = elm.item.data('type');
        fieldOptions= elm.item.data('options');

        var htmlID = formID + "_" + fieldID;

        var displayName = "<span class='filterName'>" + fieldName +"</span>";

        var filterOptions = new Array();
        switch (fieldType) {
            case 'radio':
                filterOptions[0] = 'is';
                filterOptions[1] = 'is not';

                filterField = "<select id='" + htmlID +
                              "' data-formid='" + formID +
                              "' data-fieldid='" + fieldID + "'>";
                $.map(fieldOptions, function(value, index){
                    filterField += "<option value='" + value + "'>" + value + "</option>";
                });
                filterField += "</select>";
                break;
            case 'checkbox':
                filterOptions[0] = 'includes';
                filterOptions[1] = 'excludes';

                filterField = "<select multiple " +
                              "id='" + htmlID +
                              "' data-formid='" + formID +
                              "' data-fieldid='" + fieldID + "'>";
                $.map(fieldOptions, function(value, index){
                    filterField += "<option value='" + value + "'>" + value + "</option>";
                });
                filterField += "</select>";

                break;
            case 'text':
            case 'textarea':
                filterOptions[0] = 'is';
                filterOptions[1] = 'is not';
                filterOptions[2] = 'includes';
                filterOptions[3] = 'begins with';
                filterOptions[4] = 'ends with';

                filterField = "<input type='text' " +
                              "id='" + htmlID +
                              "' data-formid='" + formID +
                              "' data-fieldid='" + fieldID + "'/>";
                break;

            case 'num':
                filterOptions[0] = 'equals';
                filterOptions[1] = 'does not equal';
                filterOptions[2] = 'is less than';
                filterOptions[3] = 'is greater than';

                filterField = "<input type='text' class='numeric' " +
                              "id='" + htmlID +
                              "' data-formid='" + formID +
                              "' data-fieldid='" + fieldID + "'/>";
                break;
            case 'date':
                filterOptions[0] = 'equals';
                filterOptions[1] = 'does not equal';
                filterOptions[2] = 'is before';
                filterOptions[3] = 'is after';

                filterField = "<input type='text' class='datepicker'" +
                              "id='" + htmlID +
                              "' data-formid='" + formID +
                              "' data-fieldid='" + fieldID + "'/>";
                break;
        }

        filterSelect = "<select class='middleSelect' id='" + htmlID + "-selector' data-type='" + fieldType + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";

        var content = displayName + filterSelect + filterField;

        if (elm.sender.attr("id") == 'filterList') {
            filterText=elm.item.find("span.filterName").text();
            content = filterText;
        }

        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);

        enableTab();
    }

    function createGroupFilterEntry(event, elm) {
        
        groupID      = elm.item.data('groupid');
        groupName    = elm.item.data('groupname');
        fieldType   = elm.item.data('type'); 
        
        var htmlID = groupID + "_" + fieldType;
        
        var displayName = "<span class='filterName'>" + fieldType + " for " + groupName + "</span>";
        
        var filterOptions = new Array();

        filterOptions[0] = 'at least';
        filterOptions[1] = 'no more than';
        filterOptions[2] = 'exactly';

        filterField = "<input type='text' class='numeric' " +
                        "id='" + htmlID + 
                        "' data-groupid='" + groupID + "'/> group meetings";
        
        filterSelect = "<select id='" + htmlID + "-selector' data-type='" + fieldType + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";
        
        var content = displayName + filterSelect + filterField;
        
        if (elm.sender.attr("id") == 'filterList') {
            filterText=elm.item.find("span.filterName").text();
            content = filterText;
        }
        
        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);
        
        enableTab();
    }
    
    function createProgFilterEntry(event, elm) {
        
        progID      = elm.item.data('progid');
        progName    = elm.item.data('progname');
        fieldType   = elm.item.data('type'); 
        
        var htmlID = progID + "_" + fieldType;
        
        var displayName = "<span class='filterName'>" + fieldType + " for " + progName + ": </span>";
        
        var filterStyle = new Array();
        filterStyle[0] = 'became';
        filterStyle[1] = 'was';
        
        var filterOptions = new Array();
        filterOptions[0] = 'active';
        filterOptions[1] = 'waitlisted';
        filterOptions[2] = 'on leave';
        filterOptions[3] = 'concluded';
        
        styleSelect = "<select id='"
                        + htmlID
                        + "-style'>";
                    $.map(filterStyle,function(value, index){
                       styleSelect += "<option value='" + value + "'>" + value + "</option>";
                    });
        styleSelect += "</select>";
        
        filterSelect = "<select id='" 
                        + htmlID 
                        + "-selector' data-type='" 
                        + fieldType
                        + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";
        
        var content = displayName + styleSelect + filterSelect;
        
        if (elm.sender.attr("id") == 'filterList') {
            filterText=elm.item.find("span.filterName").text();
            content = filterText;
        }
        
        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);
        
        enableTab();
    }
    
    function createStaffFilterEntry(event, elm) {
        
        staffID      = elm.item.data('staffid');
        staffName    = elm.item.data('staffname');
        fieldType   = elm.item.data('type'); 
        
        var htmlID = staffID + "_" + fieldType;
        
        var displayName = "<span class='filterName'>" + fieldType + " in " + staffName + "'s caseload: </span>";
        
        var filterStyle = new Array();
        filterStyle[0] = 'became';
        filterStyle[1] = 'was';
        
        var filterOptions = new Array();
        filterOptions[0] = 'active';
        filterOptions[1] = 'waitlisted';
        filterOptions[2] = 'on leave';
        filterOptions[3] = 'concluded';
        
        styleSelect = "<select id='"
                        + htmlID
                        + "-style'>";
                    $.map(filterStyle,function(value, index){
                       styleSelect += "<option value='" + value + "'>" + value + "</option>";
                    });
        styleSelect += "</select>";
        
        filterSelect = "<select id='" 
                        + htmlID 
                        + "-selector' data-type='" 
                        + fieldType
                        + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";
        
        var content = displayName + styleSelect + filterSelect;
        
        if (elm.sender.attr("id") == 'filterList') {
            filterText=elm.item.find("span.filterName").text();
            content = filterText;
        }
        
        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);
        
        enableTab();
    }

    
    function makeDraggable() {
        $("#filterFieldList, #filterList").sortable({
            connectWith: ".connectedFilter",
            receive: function(event,element){
                switch (filterType) { //form, group, or prog
                    case 'form': createFormFilterEntry(event,element); break;
                    case 'group': createGroupFilterEntry(event,element); break;
                    case 'prog': createProgFilterEntry(event,element); break;
                    case 'staff': createStaffFilterEntry(event,element); break;
                }
            },
            placeholder: "placeholder"
        });
    }
    
    function makeDraggableData() {
        $("#dataFieldList, #dataList").sortable({
           connectWith: ".connectedData",
           receive: enableTab,
           placeholder: "placeholder"
        });
    }
    
    function loadFields(kto) {
        var listToParse;
        switch (kto) {
            case 'filter' : listToParse = $("ul#filterList li");
                break;
            case 'data' : listToParse = $("ul#dataList li");
                break;
        }
        
        var fields = new Array();
        var i = 0;
        listToParse.each(function() {
           
           var myField = new Object();
           myField.elementID = $(this).data('elementid');
           
           myField.formID = $(this).data('formid');
           myField.dataType = $(this).data('dataType');
           
           if (kto == 'filter') {
            var compare = $(this).children().not('span').first();
            var match = compare.next();
            myField.fCompare = compare.val();
            myField.match = match.val();
            myField.elementName = $(this).children('span').text();
           } else {
            myField.elementName = $(this).text();
           }
            if ($(this).data('staffid')) {
                myField.filterID = $(this).data('staffid');
            }
           fields[i] = myField;
           i++;
        });
        
        return fields;
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
    
    function addPieGraph(chartName,chartSubtitle,divName,chartData) {
        dataDiv.append("<div class='graphContainer'>" +
                            "<div id=" + divName + ">" +
                            "</div>" +
                        "</div>");
        //go through data and create data array usable as piechart option
        var valueArray = new Array();
        $.map(chartData, function(value, index){
            myArray = new Array();
            myArray.push(index);
            myArray.push(parseInt(value));
            valueArray.push(myArray);
        });
        
        //create chart
        newChart = new Highcharts.Chart({
            chart: {
                renderTo:              divName,
                borderWidth:           1,
                borderColor:           '#333333',
                defaultSeriesType:     'pie'
            },
            title: {
                text: chartName
            },
            subtitle: {
                text: chartSubtitle
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        color: '#000000',
                        connectorColor: '#000000',
                        formatter: function() {
                            return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
                        }
                    }
                }
            },
            series: [{
                type: 'pie',
                name: chartName,
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
    
    function addPercentGraph(chartName,chartSubtitle,divName,chartData) {
        dataDiv.append("<div class='graphContainer'>" +
                            "<div id=" + divName + ">" +
                            "</div>" +
                        "</div>");
        newChart = new Highcharts.Chart({
        chart: {
            renderTo:              divName,
            defaultSeriesType:     'bar'
        },
        title: {
            text: chartName
        },
        subtitle: {
            text: chartSubtitle
        },
        xAxis: {
            categories: ["Pre", "Post"],
            title: {
                text: "Outcome results"
            },
            reversed: true
        },
        yAxis: {
            title: {
                text: 'Number of respondents'
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
                this.series.name +': ' +
                Highcharts.numberFormat(this.y, 0, ',') + ' respondents';
            }
        },           
        plotOptions: {
            bar: {
                //stacking: 'normal',
                lineColor: '#666666',
                lineWidth: 1,
                marker: {
                    lineWidth: 1,
                    lineColor: '#ffffff'
                }
            }
        },
        series: chartData,
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
        
    function makeGraphs(data, dataType) {
        optionsDiv.slideUp(500);
        dataArray = $.makeArray(data.charts);
        i = 0;
        $.map(dataArray, function(val, index) {
                chartName = val['name'];
                chartSubtitle = val['subtitle'];
                divName = 'chart_' + i;
                chartData = val['values'];
                if (dataType == 'singleuse') {
                    addPieGraph(chartName,chartSubtitle,divName,chartData);
                } else if (dataType == 'prepost') {
                    addPercentGraph(chartName,chartSubtitle,divName,chartData);
                }
                $("#" + divName).slideDown(500);
                i++;
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
    
    function buildReport() {
        //get my variables
        filterType   = $("#filterForm").data('filtertype');        //form, group, prog or staff
        var filterTarget = $("#filterForm").data('filtertarget');  //participant or staff
        var dataType     = $("#filterForm").data('datatype');      //singleuse or prepost
        var filterFields = loadFields('filter');
        var dataFields   = loadFields('data');
        var fromDate     = $("#fromDate").val();
        var toDate       = $("#toDate").val();
        var reportType   = $("input:radio[name='formatSelect']:checked").val(); //table, excel or graph
                
        //send ajax request and build report based on result
        $.post(
            "/ajax/dynamicreport",
            {
                fType: filterType,
                fTarget: filterTarget,
                dType: dataType,
                fFields: filterFields,
                dFields: dataFields,
                from: fromDate,
                to: toDate,
                rType: reportType
            },
            function(data){
                if (reportType == 'table') {
                    makeTable(data);
                } else if (reportType == 'excel') {
                    document.location = '/files/' + data.file;
                } else if (reportType == 'graph') {
                    makeGraphs(data, dataType);
                }
            }
        );
    }
    
    $("#options").tabs("option", "disabled", [1,2]);
    
    $("#filterForm").click(function(){
        var type = $(this).data('filtertype');
        var id = $(this).val();
        $.post(
            "/ajax/reportoptions",
            {type: type, id: id, step: 'filter'},
            function(data) {
                $("#fieldList").html(data)
                               .slideDown(300);
                makeDraggable();
            }
        );
    });
    
    $("#dataForm").click(function(){
        var type = 'form';
        var id = $(this).val();
        $.post(
            "/ajax/reportoptions",
            {type: type, id: id, step: 'data'},
            function(data) {
                $("#dataFieldList").html(data)
                               .slideDown(300);
                makeDraggableData();
            }
        );
    });
    
    $("#buildMyReport").button()
                       .click(function(){
                          buildReport(); 
                       });
                       
    $("#deptSelector input").change(function() {
        toggleDepts();
    })
    
}); 
