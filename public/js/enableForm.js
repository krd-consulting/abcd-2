$(function() {
		
function enable (f) {
        
        $.post(
            "/forms/enable",
            {id: f},
            
            function(data) {  
                window.location.reload();
            }
        );
        
    }

$("a:contains('enable')")
    .click(function() {
        var formid = ($(this).parents('tr').attr('id'));
        enable(formid); 
    });
});
