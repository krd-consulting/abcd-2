$(document).ready(function() {
    $("#searchkey, #volBenef").autocomplete(
        { 
            source: function(request, response) {

            $.ajax({
                url: "/dash/autocomplete",
                dataType: "json",
                data:   {
                        term: request.term,
                        type: $("input[@name=acType]:checked").val(),
                        vtype: $("#volBenef").data('vtype'),
                        progid: $("#volBenef").data('progid')
                        },
                success: function(data) {
                        response(data);
                        }
                });
            },                        
            focus: function( event, ui ) {
				$(this).val( ui.item.label );
				return false;
            },
            select: function( event, ui ) {
		 		$(this).val( ui.item.label );
                                $(this).attr('data-targetid', ui.item.value)
                                return false;
            },
            minLength: 0,
            delay: 100
       });
});


