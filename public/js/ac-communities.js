jQuery.expr[':'].iContains = function(a,i,m) {
    return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
}


function makeAutocomplete() {
    $("label:iContains('Community'), label:iContains('Neighbourhood')")
            .siblings('input')
            .each(function() {
                var e = this;
                $(e).autocomplete(
                { 
                    source: function(request, response) {
                            $.ajax({
                            url: "/dash/autocomplete",
                            dataType: "json",
                            data:   {
                                    term: request.term,
                                    type: 'community'
                                    },
                            success: function(data) {
                                    response(data);
                                    }
                    });
                },                        
                
                focus: function( event, ui ) {
				$(e).val( ui.item.label );
				return false;
                },
            
                select: function( event, ui ) {
		 		$(e).val( ui.item.label );
                                return false;
                },
                minLength: 1,
                delay: 100
                })
                .data( "autocomplete" )._renderItem = function( ul, item ) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a>" + item.label + "<br>" + item.extra + "</a>" )
				.appendTo( ul );
		};
            })
}

$(document).ready(function() {
    makeAutocomplete();
});