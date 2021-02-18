$(function() {
		
            var setName     =   $( "#setName" ),
                startDate   =   $( "#startDate"),
                endDate     =   $( "#endDate"),
                fromTime    =   $( "#fromTime"),
                toTime      =   $( "#toTime"),
                rType       =   $( "#resourceType"),
                userID      =   $( "#userID"),
                tips        =   $( ".validateTips" ),
                list        =   '';
                
                

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
                    
                    if (max > 0) {
                        message = n + " must be between " + min + " and " + max + " characters.";
                    } else {
                        message = n + " must not be empty.";
                    }
                    
                    updateTips( message , 1);
                            o.focus();
                            return false;
                    } else {
                            o.removeClass( "ui-state-error");
                            return true;
                    }
            }
            
            function checkDuplicateName() {
                
                $.post(
                      "/ajax/checkifexists",
                      {   type: 'scheduleset', 
                          col: 'name',
                          token: setName.val()
                      },
                      function(data){
                          if (data.duplicate == "no") {
                              insertRecord();
                          } else {
                              updateTips (setName.val() + " cannot be added.");
                              setName.addClass("ui-state-error");
                          }
                });
            }
            
            function addResource(rsrc,type,label) {
                resource = rsrc.replace(/ /g,"_");
                $("#resource-list").show(); 
                $("#resource-list-items").append(
                        "<div class='draggable small' data-resourceid=" 
                        + resource 
                        + " data-resourcetype=" 
                        + type 
                        +">" 
                        + "<span>" + label + "</span>"
                        + "<img class='sprite-pic remove-pic' src='/skins/default/images/blank.gif' align='left'></div>"
                        );
                $(".remove-pic").click(function(){
                    $(this).parents(".draggable").remove();
                })
            }
                    
            function insertRecord() {
                $.post(
                        "/ajax/addscheduleset",
                        {
                          setName: setName.val(),
                          startDate: startDate.val(), 
                          endDate: endDate.val(),
                          fromTime: fromTime.val(),
                          toTime: toTime.val(),
                          resources: list,
                          createdBy: userID.val()          
                      },
                      function(data){
                          if (data.result > 1) {
                              deptAdd (data.deptlist, data.pid);
                          } else {    
                              updateTips (setName + data.msg);
                              setName.addClass("ui-state-error");     
                         }
                      }
                );
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
            
            function deptAdd (dlist,sid) {                   
                                
                $('input').hide();
                $('select').hide();
                $('label').hide();
                $("#resource-list").hide();
                $("#resource-list-items").hide();
                
                $.each(dlist, function(index,department){
                    $("#dept").append('<option value="' + department.id + '">' + department.deptName + '</option>');
                });
                
                $("#dept").show();
                
                updateTips(setName.val() + ' is in the database. \n Please choose a department to add record to:', 0);
                
                $("#dialog-form-set").dialog({
                    buttons: {
                        "Add to Department": function(){
                                                $.post(
                                                    "/schedule/deptadd",
                                                    {sid: sid, did: $("#dept").val()},
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
            
            function collectResources() {
                list = $("div#resource-list-items .draggable").map(
                        function() {
                            return('{"' + $(this).data('resourcetype') + '":"' + $(this).data('resourceid')) + '"}';
                        }).get().join();
                if (list.length > 0) {
                    return true;
                } else {
                    updateTips('You must add at least one resource to the calendar.', 0);
                    return false;
                }
            }
            
            
            $( "#dialog-form-set" ).dialog({
                    autoOpen: false,
                    height: 450,
                    width: 500,
                    modal: true,
                    buttons: {
                            "Create!": function() {
                                    var bValid = true;
                                    setName.removeClass( "ui-state-error");
                                    startDate.removeClass( "ui-state-error" );
                                    endDate.removeClass( "ui-state-error" );
                                    fromTime.removeClass( "ui-state-error" );
                                    toTime.removeClass( "ui-state-error");

                                    bValid = bValid && checkLength( setName, "Set Name", 3, 28 );
                                    bValid = bValid && checkRegexp( setName, /^[a-z]([a-z0-9-': ])+$/i, "Set name can only contain alphanumerics, dashes, colons and spaces." );

                                    bValid = bValid && checkLength( startDate, "Date", 1);
                                    bValid = bValid && checkLength( endDate, "Date", 1);
                                    bValid = bValid && checkLength( fromTime, "Time", 1);
                                    bValid = bValid && checkLength( toTime, "Time", 1);
                                
                                    bValid = bValid && collectResources();
                                    //alert ("List is " + list);
                                    if ( bValid ) {
                                            updateTips('Format Valid - checking for duplicates...', 0);
                                            checkDuplicateName();
                                    };
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    close: function() {
                            $("#addSetForm input, #addSetForm select").each(
                                    function(index){
                                        $(this).val( "" ).removeClass( "ui-state-error" );
                                    }
                                    );
                    },
                    open: function(e,ui) {
                        updateTips('Please fill out all the fields', 0);
                        $("input,select").bind("keydown", function (e) {
                            var keyCode = e.keyCode || e.which;
                            if(keyCode === 13) {
                                e.preventDefault();
                                $('input, select, textarea')
                                [$('input,select,textarea').index(this)+1].focus();
                            }
                        });

                    }
            });

            $( "#addSet" )
                    .button()
                    .click(function() {
                            $( "#dialog-form-set" ).dialog( "open" );
                    });
                    
            $('.timepicker').timepicker({
                timeFormat: 'HH:mm',
                defaultTime: '',
                startTime: '',
                dynamic: true,
                dropdown: false,
                scrollbar: false,        
            });
            
            rType.focus(function(){
                $("#resource").show().val("");
            })
            
            rType.change(function() {
                type = rType.val();
                if (type === 'adhoc') {
                    //alert ("Not connected to database, free entry.");
                    $("#resource").autocomplete("destroy");
                    $("#resource").keyup(function(e){
                        if(e.which === 13) {
                            if ($("#resource").val().length > 0) {
                               addResource($("#resource").val(),"adhoc",$("#resource").val());
                            } else {
                                alert ("You need to name your resource.");    
                            }    
                        }
                    });
                    
                    
                } else {
                    $("#resource")
                            .off("keyup")
                            .autocomplete("destroy")
                            .autocomplete({
                                source: function(request,response) {
                                    $.ajax({
                                        url: "/dash/autocomplete",
                                        dataType: "json",
                                        data: {
                                            term: request.term,
                                            type: type
                                        },
                                        success: function(data) {
                                            response(data);
                                        }
                                    });
                                },
                                focus: function(event,ui) {
                                    $(this).val(ui.item.label);
                                    return false;
                                },
                                select: function(event,ui) {
                                    $(this).val(ui.item.label);
                                    $("#resource-list").show();
                                    addResource(ui.item.value,type,ui.item.label);
                                    return false;
                                },
                                minlength: 2,
                                delay: 50
                            });
                } //end else
            })
});
