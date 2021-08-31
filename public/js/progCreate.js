$(function() {
		
            var name        =   $( "#pname" ),
                dept        =   $( "#deptField"),
                voltype     =   $( "#voltype"),
                tips        =   $( ".validateTips" );



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
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error");
                            return true;
                    }
            }

            function checkRegexp( o, regexp, n ) {
                    if ( !( regexp.test( o.val() ) ) ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n );
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error" );
                            return true;
                    }
            }
            
            function showDept () {
                var list;
                $("#deptField>option").remove();
                                
                $.get(
                    "/ajax/deptlist",
                    function(data){
                        list = data.deptlist;
                        $.each(list, function(index,department){
                        $("#deptField").append('<option value="' + department.id + '">' + department.deptName + '</option>');
                    });
                    }
                );             
            }
            
            function checkDuplicate() {
                
                $.get(
                      "/programs/add",
                      {name: name.val(), voltype: voltype.val(), dept: dept.val()},
                      function(data){
                          if (data.success == 'yes') {
                             $(this).parent().dialog( "close" );
                             window.location.reload();
                          } else {    
                              updateTips (data.msg + name);
                              n.addClass("ui-state-error");
                              d.addClass("ui-state-error");
                          }
                      }
                );
                
            }
                  

            $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 290,
                    width: 500,
                    modal: true,
                    buttons: {
                            "Create It!": function() {
                                    var bValid = true;
                                    name.removeClass( "ui-state-error" );
                                    dept.removeClass( "ui-state-error" );

                                    bValid = bValid && checkLength( name, "Program Name", 2, 30 );
                                    bValid = bValid && checkRegexp( name, /^[a-z]([a-z- ])+$/i, "Names can only contain letters, dashes and spaces." );
                                                                      
                                    if ( bValid ) {
                                            updateTips('Format Valid - checking for duplicates...');
                                            checkDuplicate(name,dept);
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
                        updateTips('Please fill out all the fields.');
                        showDept();
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                
                                $('.ui-dialog-buttonset > button:first').trigger('click');
                            }
                        });
                    }
            });

            $( "#addProg" )
                    .button()
                    .click(function() {
                            $( "#dialog-form" ).dialog( "open" );
                            //window.location.hash = "#dept-frag-3";
                    });
});
