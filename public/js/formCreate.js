$(function() {
    		
            var name        =   $( "#formName" ),
                type        =   $( "#formType" ),
                whom        =   $( "#formTarget"),
                desc        =   $( "#description"),
                tips        =   $( ".validateTips" );

            function addForm() {
                values = $("#formCreator").serialize();
                window.location = '/forms/add?'+values;
                $(this).dialog("close");
            }
            
            function checkDuplicates (n) {
                $.post(
                    "/forms/ajax",
                    {task:'dupcheck',name:n.val()},
                    function(data){
                        if (data.success == 'yes') {
                            updateTips('OK');
                            addForm();
                        } else {
                            updateTips ('A form with this name already exists. Please choose another.');
                            name.addClass("ui-state-error").focus();
                        }
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
                    height: 370,
                    width: 450,
                    modal: true,
                    buttons: {
                            "Create It!": function() {
                                    var bValid = true;
                                    name.removeClass( "ui-state-error" );

                                    bValid = bValid && checkLength( name, "Form Name", 3, 40 );

                                    bValid = bValid && checkRegexp( name, /^[a-z]([a-z0-9- ])+$/i, "Form names begin with letters and contain letters, numbers, dashes and spaces." );

                                    if ( bValid ) {
                                            checkDuplicates(name);
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
                        updateTips('Please fill out all fields.');
                    }
            });


    
    
    
    $( "#addForm" )
            .button() 
            .click(function() {
                 $( "#dialog-form" ).dialog( "open" );
            });
});
