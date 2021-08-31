$(function() {
		
            var name        =   $( "#nametoadd" ),
                tips        =   $( ".validateTips" );

            function addFunder( n ) {
                $.get(
                      "/funders/add",
                      {name : n},
                      function(data) {
                        var row = '';
                        var url = '/funders/profile/id/' + data.id;
                        var dname = data.name;
                                                                     
                        /**/
                         row = '<tr><td class="nameLink"><a href=' + 
                                url + 
                                '><div class="table-link"><div class="pName">' +
                                dname +
                                '</div></div></a></td></tr>' ;
                        /**/
                        $( "#funders-table tbody" ).append( row );
                        numRows = $("#funders-table tr").length;
                        $("#record-count").text(numRows);
                      }
                );
            }

            function updateTips( t ) {
                    tips
                            .text( t )
                            .addClass( "ui-state-highlight" );
                    setTimeout(function() {
                            tips.removeClass( "ui-state-highlight", 1500 );
                    }, 500 );
            }

            function checkLength( o, n, min, max ) {
                    if ( o.val().length > max || o.val().length < min ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n + " must be between " +
                                    min + " and " + max + " characters." );
                            return false;
                    } else {
                            return true;
                    }
            }

            function checkRegexp( o, regexp, n ) {
                    if ( !( regexp.test( o.val() ) ) ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n );
                            return false;
                    } else {
                            return true;
                    }
            }

            $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 300,
                    width: 350,
                    modal: true,
                    buttons: {
                            "Create It!": function() {
                                    var bValid = true;
                                    name.removeClass( "ui-state-error" );

                                    bValid = bValid && checkLength( name, "Funder Name", 3, 20 );

                                    bValid = bValid && checkRegexp( name, /^[a-z]([a-z- ])+$/i, "Funder names should contain letters, dashes and spaces only." );

                                    if ( bValid ) {
                                            addFunder(name.val());
                                            $( this ).dialog( "close" );
                                    } else {
                                        name.focus();
                                    }
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            name.val( "" ).removeClass( "ui-state-error" );
                    },
                    open: function(e,ui) {
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                
                                $('.ui-dialog-buttonset > button:first').trigger('click');
                            }
                        });
                    }
            });

            $( "#addFunder" )
                    .button()
                    .click(function() {
                            $( "#dialog-form" ).dialog( "open" );
                    });
});