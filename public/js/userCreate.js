$(function() {
		
            var fname       =   $( "#fname" ),
                lname       =   $( "#lname"),
                username    =   $( "#username"),
                email       =   $( "#email"),
                password    =   $( "#pwd"),
                role        =   $( "#role"),
                dept        =   $( "#dept"),
                tips        =   $( ".validateTips" );



            function updateTips( t, append ) {
                    if (append == 1) {
                    tips
                            .append('<li>' + t + '</li>' )
                            .addClass( "ui-state-highlight" );
                    } else {
                        tips.text( t ).addClass( "ui-state-highlight");
                    }
                    
                    setTimeout(function() {
                            tips.removeClass( "ui-state-highlight", 1500 );
                    }, 500 );
            }

            function checkLength( o, n, min, max ) {
                    if ( o.val().length > max || o.val().length < min ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n + " must be between " +
                                    min + " and " + max + " characters." , 1);
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error");
                            return true;
                    }
            }
            
            function checkDup( o, n ) {
                    var value = o.val();
                    result = 'maybe';
                    var check;
                    updateTips('Checking availability of ' + n + '. Please wait...', 0);
                    
                    $.get(
                        "/verify/" + n,
                        {value: value},
                        function(data){
                            result = data.success;

                               if (result == 'no') {
                                   o.addClass( "ui-state-error");
                                   updateTips( "The " + n + " '" + o.val() + "' is already in use.", 1);
                                   o.focus();
                                   return false;
                               } else {
                                   o.removeClass( "ui-state-error");
                                   if (n == 'username') {
                                       checkDup(email,'email');
                                   } else if (n =='email') {
                                       checkDuplicate();
                                   }
                               }
                    });
            }

            function checkRegexp( o, regexp, n ) {
                    if ( !( regexp.test( o.val() ) ) ) {
                            o.addClass( "ui-state-error" );
                            updateTips( n , 1);
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error" );
                            return true;
                    }
            }
            
            function deptAdd (list,pid) {                   
                                
                $('input').hide();
                $('select').hide();
                $('label').hide();
                
                $.each(list, function(index,department){
                    $("#dept").append('<option value="' + department.id + '">' + department.deptName + '</option>');
                });
                
                $("#dept").show();
                
                updateTips(fname.val() + ' ' + lname.val() + ' is in the database. \n Please choose a department to add record to:', 0);
                
                $("#dialog-form").dialog({
                    buttons: {
                        "Add to Department": function(){
                                                $.get(
                                                    "/users/deptadd",
                                                    {uid: pid, did: $("#dept").val()},
                                                    function(data){
                                                        if (data.success == 'yes') {
                                                            window.location.reload();
                                                            updateTips('Adding to database, please wait...', 0);
                                                            $(this).dialog("close");
                                                        }
                                                    }
                                                );
                                            },
                        "No!" :             function(){
                                                $(this).dialog("close");
                                            }
                    }
                });
}
            
            function checkDuplicate() {
                var uname   =username.val(),
                    firstname=fname.val(),
                    lastname=lname.val(),
                    emailval=email.val(),
                    pwd     =password.val(),
                    roleval =role.val();
                
                $.get(
                      "/verify/duplicate",
                      {type: 'user', uname: uname, fname: firstname, lname: lastname, email: emailval, pwd: pwd, role: roleval},
                      function(data){
                          if (data.success == 'yes' || data.success == 'maybe') {
                              deptAdd (data.deptlist, data.pid);
                          } else {    
                              updateTips (fname + ' ' + lname + data.msg);
                              u.addClass("ui-state-error");     
                         }
                      }
                );
                
            }
                  

            $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 390,
                    width: 400,
                    modal: true,
                    buttons: {
                            "Add Record!": function() {
                                    var bValid = true;
                                    username.removeClass( "ui-state-error");
                                    fname.removeClass( "ui-state-error" );
                                    lname.removeClass( "ui-state-error" );
                                    email.removeClass( "ui-state-error" );
                                    password.removeClass( "ui-state-error");

                                    bValid = bValid && checkLength( username, "Username", 4, 12 );
                                    bValid = bValid && checkRegexp( username, /^[a-z]([a-z])+$/i, "Username can only contain letters." );
                                    
                                    bValid = bValid && checkLength( fname, "Each name", 2, 20 );
                                    bValid = bValid && checkRegexp( fname, /^[a-z]([a-z-' ])+$/i, "Names can only contain letters, dashes and spaces." );
                                    
                                    bValid = bValid && checkLength( lname, "Each name", 2, 20 );
                                    bValid = bValid && checkRegexp( lname, /^[a-z]([a-z-' ])+$/i, "Names can only contain letters, dashes and spaces." );
                                    
                                    bValid = bValid && checkRegexp( email, /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/i, "Please check e-mail format.");
                                 
                                    bValid = bValid && checkLength( password, "Password", 6, 12 );
                                                                        
                                    if ( bValid ) {
                                            updateTips('Format Valid - checking for duplicates...', 0);
                                            checkDup(username,'username');
                                    };
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            fname.val( "" ).removeClass( "ui-state-error" );
                            lname.val( "" ).removeClass( "ui-state-error" );
                            email.val( "" ).removeClass( "ui-state-error" );
                            pwd.val( "" ).removeClass( "ui-state-error" );
                            uname.val( "" ).removeClass( "ui-state-error" );
                    },
                    open: function(e,ui) {
                        updateTips('Please fill out all the fields', 0);
                        
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                
                                $('.ui-dialog-buttonset > button:first').trigger('click');
                            }
                        });
                    }
            });

            $( "#addUser" )
                    .button()
                    .click(function() {
                            if ($("#admin").html() == '.') {
                                $("#role").removeClass("hidden");
                            } else {
                            $("label[for='role']").hide();
                            }
                            $( "#dialog-form" ).dialog( "open" );
                    });
});
