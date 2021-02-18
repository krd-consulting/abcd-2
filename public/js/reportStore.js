$(function() {
    
    var optionsDiv = $("#controls");
    var recipsDiv = $("#top");
    var dataDiv = $("#bottom");
    
    
    
    function createRecipEntry(event, elm, filterType) {
        groupingID         = elm.item.data('id');
        groupingName       = elm.item.data('name');
        groupingType       = filterType; 
        
        var htmlID = groupingID + "_" + groupingType;
        
        var displayName = "<span class='recipName'> in " + groupingName + "</span>";
        
        var filterOptions = new Array();
        filterOptions[0] = 'All staff';
        filterOptions[1] = 'Managers';
        
        filterSelect = "<select id='" + htmlID + "-selector' data-type='" + groupingType + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";
        
        var content = filterSelect + displayName;
        
        if (elm.sender.attr("id") == 'recipList') {
            filterText=elm.item.find("span.recipName").text();
            content = filterText.substr(filterText.indexOf(" ") + 3);//hacky way to split the "in ".
        }
        
        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);
    }
    
    function createDataEntry(event, elm, filterType) {
        switch (filterType) {
            case 'prgs': 
                var descrip=' of status changes in '; 
                var readableType=' program.'; 
                break;
            case 'grps': 
                var descrip=' of attendance in '; 
                var readableType=' group.';
                break;
            case 'forms': 
                var descrip=' of activity for '; 
                var readableType=' form.';
                break;
        }
         
        
        dataID         = elm.item.data('id');
        dataName       = elm.item.data('name');
        dataType       = filterType; 
        
        var htmlID = dataID + "_" + dataType;
        
        var displayName = "<span class='dataName'>" + dataName + "</span>";
        
        var filterOptions = new Array();
        filterOptions[0] = 'Summary';
        
        if (filterType != 'forms') {
            filterOptions[1] = 'Details';
        }
        filterSelect = "<select name='filterSelect' id='" + htmlID + "-selector' data-type='" + dataType + "'>";
                $.map(filterOptions, function(value, index){
                    filterSelect += "<option value='" + value + "'>" + value + "</option>";
                });
        filterSelect += "</select>";
        
        var content = filterSelect + descrip + displayName + readableType;
        
        if (elm.sender.attr("id") == 'dataList') {
            filterText=elm.item.find("span.dataName").text();
            content = filterText;
        }
        
        elm.item.html(content);
        elm.item.addClass('ui-state-highlight');
        elm.item.removeClass('ui-state-highlight', 500);
    }
    
    function makeDraggable(filterType) {
        $("#recipFieldList, #recipList").sortable({
            connectWith: ".connectedRecip",
            receive: function(event,element){
                switch (filterType) { //single, grps, prgs, depts
                    case 'single': break;
                    case 'grps': 
                    case 'prgs': 
                    case 'depts': createRecipEntry(event,element,filterType); break;
                }
            },
            items: "li:not(.unsortable)",
            placeholder: "placeholder"
        });
    }
    
    function makeDraggableData(filterType) {
        $("#dataFieldList, #dataList").sortable({
           connectWith: ".connectedData",
           receive: function(event,element) {
               createDataEntry(event,element,filterType)
           },
           placeholder: "placeholder"
        });
    }

    function checkReqs() {
        var numRecips = $("#recipList li").length;
        var numDataIncludes = $("#dataList li").length;
        
        if (!$("input#reportName").val()) {
            alert ("Please specify a name for your report.");
        } else if (numRecips == 0 || numDataIncludes == 0) {
                alert ("Please select at least 1 recipient and at least 1 piece of data to include.");
        } else {
            saveReport();
            $("#options").tabs("option","selected",0);
        }
        
    }

    function loadFields(kto) {
        var listToParse;
        switch (kto) {
            case 'recip' : 
                listToParse = $("ul#recipList li:not(.unsortable)");
                break;
            case 'data' : listToParse = $("ul#dataList li");
                break;
        }
        
        var fields = new Array();
        var i = 0;
        listToParse.each(function() {
           var myField = new Object();
           myField.typeID       = $(this).data('id');
           myField.subtype      = $(this).data('type');
           
           var compare = $(this).children().not('span').first();
           myField.level = compare.val();
           myField.name = $(this).children('span').text();
           
            fields[i] = myField;
           i++;
        });
        
        return fields;
    }

    function saveReport() {
        //gather my data
            var Name = $("#reportName").val();
            var Recipients = loadFields('recip');
            var DataIncludes = loadFields('data');
            var Frequency = $("select#freqDropDown").val();

        //pass to server for saving
        $.post(
            "/ajax/savestoredreport",
            {
                name: Name,
                recips: Recipients,
                includes: DataIncludes,
                freq: Frequency
            },
            function(data){
                window.location='/reports';
            }
        );
    }
    
    $("#recipsDropDown").click(function(){
        var id = $(this).val();
        $.post(
            "/ajax/srbuilder",
            {id: id, type: 'recip'},
            function(data) {
                $("#fieldList").html(data)
                               .slideDown(300);
                makeDraggable(id);
            }
        );
    });

    $("#typeDropDown").click(function(){
        var id = $(this).val();
        $.post(
            "/ajax/srbuilder",
            {id: id, type: 'data'},
            function(data) {
                $("#dataFieldList").html(data)
                               .slideDown(300);
                makeDraggableData(id);
            }
        );
    });



    $("#saveStoredReport").button()
                       .click(function(){
                          checkReqs(); 
                       });
    
}); 
