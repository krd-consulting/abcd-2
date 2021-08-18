$(document).ready(function() {

if ($("#formTarget").val() !== "other") {

$("#name").autocomplete(
        { 
            source: function(request, response) {
            $("#name").addClass('ui-autocomplete-loading');
            $.ajax({
                url: "/dash/autocomplete",
                dataType: "json",
                data:   {
                        term: request.term,
                        type: $("#formTarget").val()
                        },
                success: function(data) {
                        $("#name").removeClass('ui-autocomplete-loading');
                        response(data);
                        }
            });
            },                        
            focus: function( event, ui ) {
				$( "#name" ).val( ui.item.label )
                                            .removeClass('ui-state-error');
				$("span#ui-error").hide();
				return false;
            },
            select: function( event, ui ) {
		 		$( "#name" ).val( ui.item.label );
                                $( "#targetID").val( ui.item.value)
                                               .trigger('change');
                                return false;
            },
	    change: function(event, ui) {
	  	if ((!ui.item) || ($("#name").val() == 'No valid matches found')) {
			$(this).val('')
                               .addClass('ui-state-error');
			$("input#targetID").val('')
                                           .trigger('change');
			$("span#ui-error").show();
		}
	    },
            minLength: 0,
            delay: 100
    
       })
	.data( "autocomplete" )._renderItem = function( ul, item ) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a>" + item.label + "<br>" + item.extra + "</a>" )
				.appendTo( ul );
		};

$("input#name").parents('li').append(
	"<span id='ui-error' class='ui-state-highlight hidden'>Please select one of the auto-suggested names.</span>"
);

}

});

