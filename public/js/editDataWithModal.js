$(function(){
    
    var tips = $(".validateTips"),
        editForm = $("#dialog-form-edit"),
        originalName,
        type,
        id;
        
    function updateTips( t ) 
    {
        tips
                .text( t )
                .addClass( "ui-state-highlight" );
        setTimeout(function() {
                tips.removeClass( "ui-state-highlight", 1500 );
        }, 500 );
    }

    function checkLength( o, n, min, max ) 
    {
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

    function checkRegexp( o, regexp, n ) 
    {
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
    
    function validate() {
        valid = true;
        
        editForm.find("input:text")
                .not("#myDate")
                .each(function()
            {
                var element = $(this);
                valid = valid && checkLength(element, "Highlighted fields", 2, 40);
                valid = valid && checkRegexp(element, /^[a-z]([0-9a-z-'@. ])+$/i, "Fields can only contain alpha-numerics, spaces and dashes." );
            }
        );
            
        editForm.find("input:password").each(function()
            {
                var element = $(this);
                valid = valid && checkLength(element, "Password", 6, 12);
            }
        );    
        
        myName = editForm.find("input:text").first();
        if (type == 'participants') {
            fName = editForm.find("#fname");
            lName = editForm.find("#lname");
            dob   = editForm.find("#myDate");
            if (valid) checkThreeFieldDuplicates(fName,lName, dob);
        } else {
            if (valid) checkDuplicates(myName);
        }
    }
    
    function submitModalForm() {
        data = editForm.find('form').serialize();
        $.post(
            "/ajax/updateform",
            {type: type, data:data},
            function(data) {
                if (data.success == 'yes') {
                    editForm.dialog("close");
                    window.location.reload();
                }
            }
        );
    }
    
    function addDate() {
        editForm.find(".birthdaypicker")
                .removeClass(".birthdaypicker")
                .attr('id', 'myDate');
                
          $("#myDate").datepicker({
			changeMonth: true,
                        changeYear: true,
                        yearRange: "-100,+0",
                        minDate: "-101Y",
                        maxDate: "-1D",
                        showButtonPanel: true,
                        dateFormat : "yy-mm-dd"
		});
    }
    
    function checkDuplicates(n) {
        if (n.val() != originalName) {
            $.post(
                        "/ajax/checkduplicates",
                        {table: type, column: 'name', val: n.val()},
                        function(data){
                            if (data.unique == 'yes') {
                                updateTips('OK');
                                submitModalForm();
                            } else {
                                updateTips ('This name is already in use. Please choose another.');
                                n.addClass("ui-state-error").focus();
                            }
                        }
                    );
        } else {
            updateTips('Name unchanged, proceeding.');
            submitModalForm();
        }
    }
    
    function checkThreeFieldDuplicates(f,l,d) {
            $.post(
                        "/ajax/checkduplicates",
                        {table: type, column: 'firstName', val: f.val(), 
                                      col2: 'lastName', val2: l.val(),
                                      col3: 'dateOfBirth', val3: d.val()},
                        function(data){
                            if (data.unique == 'yes') {
                                updateTips('OK');
                                submitModalForm();
                            } else {
                                updateTips ('This name is already in use. Please choose another.');
                                f.addClass("ui-state-error").focus();
                                l.addClass("ui-state-error");
                                d.addClass("ui-state-error");
                            }
                        }
                    );
    }
    
    function getModalForm() {
        //ajax request to get form with values filled in
        
        $.post(
            "/ajax/editform",
            {type: type, id: id},
            function(data) {
                if (data.success == 'yes') {
                    editForm.append(data.form);
                    editForm.dialog("open");
                    addDate();
                    originalName = editForm.find("input:text").first().val();
                } else {
                    alert ("Something went wrong. Please check logs.");
                }
            }
        );        
    }
    
    $('a:contains("edit")').click(function(){
        id = $(this).data('id');
        type = $(this).data('type');
        //fix conflict with 'locked' mode for users
        weirdType = type.split(" ");
        if (weirdType) type = weirdType[0];
        
        getModalForm();
    })
    
    editForm.dialog({
                    autoOpen: false,
                    height: 340,
                    width: 450,
                    modal: true,
                    buttons: {
                            "Save": function() {
                                    validate($(this));
                            },
                            "Nevermind": function() {
                                    $( this ).dialog( "close" );
                            }
                    },
                    open: function(e,ui) {
                        $(this).keyup(function(e) {
                            if (e.keyCode == 13) {     
                                $('.ui-dialog-buttonset > button:first').trigger('click');
                                return false;
                            }
                        });
                    },
                    close: function() {
                        editForm.find('form').remove();
                    }
            });
});

