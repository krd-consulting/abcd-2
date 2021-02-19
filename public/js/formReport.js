$(function(){
    
    function getReport(){
        formID = $(":input#formList").val();
        var fromDate = $("input#fromDate").val();
        var toDate = $("input#toDate").val();
        reportFormat = $("input:radio[name='formatSelect']:checked").val();
        
        $.post(
            "/ajax/formreport",
            {id: formID, from: fromDate, to: toDate, format: reportFormat},
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

