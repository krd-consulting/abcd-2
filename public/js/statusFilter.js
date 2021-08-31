$(function(){
    
    function hideOrShow(myVal){
        filterVal = myVal;
        
        if (filterVal == 'All') {
            $('tr').show();
        } else {
            $('tr').show();
            $('table#ptcp tr, table#sr tr, table#caseload tr').not($('tr:contains("' + filterVal + '")'))
                   .not($('tr#headTR'))
                   .hide();
        }
    }
    
    function purge() {
       toDelete = $('table#ptcp, table#prog, table#caseload').find('tr:contains("Concluded")')
                       .not('tr#headTR');
       
       toDelete.each(function(){
           
           //id = $(this).find('span.name').attr('id');
           //pid = $("#mainColumn h1").attr('id');
           rtype = $(this).find('span.name').data('rtype');
           rid = $(this).find('span.name').data('rid');
           ptype = $("#purge").data('ptype');
           pid = $("#purge").data('pid');
           addlprogid = $(this).find('a.changeStatus').data('statusprogid');
           
           $(this).remove();
                      
           $.post(
                "/ajax/associaterecords",
                {what: 'remove', rtype: rtype, rid: rid, ptype: ptype, pid: pid, addlprogid: addlprogid},
                function(data) {
                });
        });
        
        numR = $('table#ptcp tr, table#prog tr, table#caseload tr').not('tr#headTR').length;
        return numR;
        
    }
    
    function purgeConfirm(){
        toDelete = $('table#ptcp, table#caseload').find('tr:contains("Concluded")')
                       .not('tr#headTR');
        if (toDelete.length == 0) {
            toDelete = $('table#prog').find('tr:contains("Concluded")')
                                      .not('tr#headTR');
        } if (toDelete.length == 0) {
            alert ('No entry with that status.');
        } else {
            $("ul#deleteLise").html('');
            toDelete.each(function() {
                                name = $(this).find('span.name').text();
                                $("ul#deleteList").append('<li>' + name + '</li>');
                            });

            $("#dialog-confirm").dialog('open');                
        }
    }
    
    $("#dialog-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            "Clear All": function() {
                c = purge();
                $("#record-count").text(c);
                $(this).dialog('close');
            },
            "Nevermind": function() {
                $(this).dialog('close');
            }
        }
   });
    
    
    $(":input#filter").change(function(){
        hideOrShow($(this).val());
    });
    
    $("a#purge").click(function(){
        purgeConfirm();
    })
});

