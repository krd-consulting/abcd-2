$(function() {
		
            var fname       =   $( "#fname" ),
                lname       =   $( "#lname"),
                dob         =   $( "#dob"),
                dept        =   $( "#dept"),
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
            
            function deptAdd (f,l,d,list,pid) {                   
                var fname = f.val();
                var lname = l.val();
                
                f.hide(); l.hide(); d.hide();
                $('label').hide();
                
                $.each(list, function(index,department){
                    $("#dept").append('<option value="' + department.id + '">' + department.deptName + '</option>');
                });
                
                $("#dept").show();
                
                updateTips(fname + ' ' + lname + ' is in the database. \n Please choose a department to add record to:');
                
                $("#dialog-form").dialog({
                    buttons: {
                        "Add to Department": function(){
                                                $.get(
                                                    "/participants/deptadd",
                                                    {pid: pid, did: $("#dept").val()},
                                                    function(data){
                                                        if (data.success == 'yes') {
                                                            window.location.reload();
                                                            updateTips('Adding to database, please wait...');
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
            
            function checkDuplicate(f,l,d) {
                var fname=f.val();
                var lname=l.val();
                var dob=d.val();
                
                $.get(
                      "/verify/duplicate",
                      {type: 'participant', fname: fname, lname: lname, dob: dob},
                      function(data){
                          if (data.success == 'yes' || data.success == 'maybe') {
                              updateTips ('Record found. Loading department list...');
                              deptAdd (f,l,d, data.deptlist, data.pid);
                          } else {    
                              updateTips (fname + ' ' + lname + data.msg);
                              f.addClass("ui-state-error");
                              l.addClass("ui-state-error");
                              d.addClass("ui-state-error");     
                          }
                      }
                );
                
            }
                  

            $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 290,
                    width: 400,
                    modal: true,
                    buttons: {
                            "Add Record!": function() {
                                    var bValid = true;
                                    fname.removeClass( "ui-state-error" );
                                    lname.removeClass( "ui-state-error" );
                                    dob.removeClass( "ui-state-error" );

                                    bValid = bValid && checkLength( fname, "Each name", 2, 20 );
                                    bValid = bValid && checkLength( lname, "Each name", 2, 20 );
                                    bValid = bValid && checkRegexp( fname, /^[a-z]([a-z-' ])+$/i, "Names can only contain letters, dashes, apostrophes and spaces." );
                                    bValid = bValid && checkRegexp( lname, /^[a-z]([a-z-' ])+$/i, "Names can only contain letters, dashes, apostrophes and spaces." );
                                    
                                    bValid = bValid && checkRegexp (dob, /^[0-9]{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])+$/i, "Date of Birth must be in YYYY-MM-DD format.");

                                    if ( bValid ) {
                                            updateTips('Format Valid - checking for duplicates...');
                                            checkDuplicate(fname,lname,dob);
                                    }
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            fname.val( "" ).removeClass( "ui-state-error" );
                            lname.val( "" ).removeClass( "ui-state-error" );
                            dob.val( "" ).removeClass( "ui-state-error" );
                    },
                    open: function(e,ui) {
                        updateTips('Please fill out all the fields');
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {
                                
                                $('.ui-dialog-buttonset > button:first').trigger('click');
                            }
                        });
                    }
            });

            $( "#addPtcp" )
                    .button()
                    .click(function() {
                            $( "#dialog-form" ).dialog( "open" );
                    });
                    
                    
function toggleHomeOnlySwitch() {
    home = $("tr.homerecord").length;
    total = $("tr").length;
    
    if ($("input#home-only-switch").is(':checked')) {
        $("#record-count").text(total);
        $("tr.default").removeClass('hidden').removeClass('noSearch').show();
    } else {
        $("#record-count").text(home);
        $("tr.default").addClass('hidden').addClass('noSearch').hide();
    }
}

function toggleActiveOnlySwitch() {
    active = $("tr.active").length;
    total = $("tr").length;
    
    // is checked should show only active participants
    if ($("input#active-only-switch").is(':checked')) {
        $("#record-count").text(active);
        $("tr.archived").addClass('hidden').addClass('noSearch').hide();
    } else {
        $("#record-count").text(total);
        $("tr.archived").removeClass('hidden').removeClass('noSearch').show();
    }
}

$("input#home-only-switch").click(function(){
    toggleHomeOnlySwitch();
});

$("input#active-only-switch").click(function(){
    toggleActiveOnlySwitch();
});
    
total = $("tr").length;
$("#record-count").text(total);
});
