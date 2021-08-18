$(function(){
    
    function getReport(){
        formID = $(":input#formList").val();
        var fromDate = $("input#fromDate").val();
        var toDate = $("input#toDate").val();
        var deptList = $("#deptChoice").val();
        reportFormat = $("input:radio[name='formatSelect']:checked").val();
        
        $.post(
            "/ajax/formreport",
            {id: formID, from: fromDate, to: toDate, deptlist: deptList, format: reportFormat},
            function(data) {
                if (reportFormat == 'table') {
                    $("#options-div").hide();
                    $("#reportDiv").html('<table cellpadding="0" cellspacing="0" border="0" class="display" id="reportTable"></table>\n\
                                          <button id="resetButton">Build another report</button>');
                    $("#reportTable").dataTable( {
                        "aaData": data.aaData,
                        "aoColumns" : data.aoColumns,
                        "bJQueryUI" : true,
                        "sScrollX"  : "100%",
                        "sScrollXInner"  : "110%",
                        "bScrollCollapse": true,
                        "order" : [[2, "desc"]]
                    });


                    $("#reportTable tr").last().css("font-weight", "bold");
                    $("#reportTable tr").last().css("font-size", "10pt");



                    $("#resetButton").button()
                                     .click(function(){
                                        $("#reportDiv").html(''); 
                                        $("#options-div").slideDown(500);
                                     });
                } else if (reportFormat == 'excel') {
                    document.location = '/files/' + data.file;
                }
            }
        );
    }
    
    $(":input#formList").change(function(){
       $(this).parents('div').next('div.block').fadeIn(500); 
    });
    
    $(":input[name='deptToggle']").change(function(){
        if ($(this).val() == 'all') {
            $("#deptChoice option:selected").removeAttr("selected");
            $("#deptSelector").slideUp(500);
        } else if ($(this).val() == 'filter') {
            $.post(
                "/ajax/getuserdepts",
                function(data){
                    $.each(data.deptlist, function(index,department){
                        $("#deptChoice").append('<option value="' + department.id + '">' + department.deptName + '</option>');
                        $("#deptSelector").slideDown(500);
                    });
                }
            );
        }
        
        $(this).parents('div').next('div.block').fadeIn(500);
        
    })
    
    $(":input[name='dateSelect']").change(function(){
        if ($(this).val() == 'all') {
            $("#dateBoxes input").val('');
            $("#dateBoxes").slideUp(500);
        } else if ($(this).val() == 'filter') {
            $("#dateBoxes").slideDown(500);
        }
        
        $(this).parents('div').next('div.block').fadeIn(500); 
        
    })
    
    $("#buildFormReport").button()
                         .css('position', 'absolute')
                         .css('bottom', '5px')
                         .css('left', '120px')
                         .click(function(){
                            getReport(); 
                         });
    
});

