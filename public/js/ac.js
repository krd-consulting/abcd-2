$(document).ready(function() {
    $("#searchkey").autocomplete(
        { 
            source: function(request, response) {

            $.ajax({
                url: "/dash/autocomplete",
                dataType: "json",
                data:   {
                        term: request.term,
                        type: $("input[@name=acType]:checked").val()
                        },
                success: function(data) {
                        response(data);
                        }
                });
            },                        
            focus: function( event, ui ) {
				$( "#searchkey" ).val( ui.item.label );
				return false;
            },
            select: function( event, ui ) {
		 		$( "#searchkey" ).val( ui.item.label );
                                return false;
            },
            minLength: 2,
            delay: 100
       });
});

