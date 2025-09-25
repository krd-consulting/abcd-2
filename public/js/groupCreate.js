$(function() {
		
            var groupname       =   $( "#groupName" ),
                description     =   $( "#groupDesc"),
                program         =   $( "#progField"),
                tips            =   $( ".validateTips" );

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

    $.get(
        "/verify/" + n,
        {value: value},
        function(data){
            result = data.success;
            if (result == 'no') {
                o.addClass( "ui-state-error");
                updateTips( "The " + n + " '" + value + "' is already in use.", 1);
                o.focus();
                return false;
            } else {
                o.removeClass( "ui-state-error");
                updateTips(value + " is a unique name: adding to database.");
                addGroup(groupname,description,program);
            }
        }
        );

    updateTips('Checking availability of ' + n + '. Please wait...', 0);

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

function addGroup(g,d,p) {
    var gname   =   g.val(),
        pid     =   p.val(),
        desc    =   d.val();

    $.post(
          "/groups/add",
          {gname: gname, pid: pid, desc: desc},
          function(data){
              if (data.success) {
                  $('.dialog-form').dialog('close');
                  window.location.reload();
              } else {
                  updateTips('Could not add group. Please try again.');
              }
              
          }
    );

}
                  

            $( ".dialog-form" ).dialog({
                    autoOpen: false,
                    height: 390,
                    width: 450,
                    modal: true,
                    buttons: {
                            "Add Group!": function() {
                                    var bValid = true;
                                    
                                    groupname.removeClass( "ui-state-error");

                                    bValid = bValid && checkLength( groupname, "Group name", 3, 30 );
                                    bValid = bValid && checkRegexp( groupname, /^[a-z]([0-9a-z- '])+$/i, "Group names can only contain alpha-numerics, spaces, dashes and apostrophes." );
                                    
                                    bValid = bValid && checkLength( description, "Description", 0, 140);
                                    
                                    if ( bValid ) {
                                        updateTips('Format Valid - checking for duplicates...', 0);
                                        checkDup(groupname, "groupname");
                                    }
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            groupname.val( "" ).removeClass( "ui-state-error" );
                    },
                    open: function(e,ui) {
                        updateTips('Please fill out all the fields', 0);
                        
                        $(this).bind("keypress", function (e) {
                            if (e.keyCode == 13) return false;
                            });
                    }
            });

            $( "#addGroup" )
                    .button()
                    .click(function() {
                            $( "#dialog-form-add" ).dialog( "open" );
                    });
});
