$(function() {
		
    var 
        description     =   $( "#uploadFileForm #fileDescription" ),
        targetType      =   $( "#uploadFileForm #targetType" ),
        targetID        =   $( "#uploadFileForm #targetID" ),
        uploadedFile    =   $( "#uploadFileForm #uploadedFile" ),
        uploadtips      =   $( "#uploadFile-dialog .validateTips" );

    function updateTipsUpload( t ) {
            uploadtips
                    .text( t )
                    .addClass( "ui-state-highlight" );
            setTimeout(function() {
                    uploadtips.removeClass( "ui-state-highlight", 1500 );
            }, 500 );
    }

    function checkLength( o, n, min ) {
            if ( o.val().length < min ) {
                    o.addClass( "ui-state-error" );
                    updateTipsUpload( n + " cannot be empty." );
                    return false;
            } else {
                    return true;
            }
    }

   $(function () {
    $('#fileupload').fileupload({
        dataType: 'json',
        replaceFileInput: false,
       add: function (e,data) {
            data.context = $('<button class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">').html('<span class="ui-button-text">Upload</span>')
                    .prependTo('.ui-dialog-buttonset')
                    .click(function() {
                        updateTipsUpload('Uploading file...');
                        data.submit();
            });

        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .bar')
                .css('width', progress + '%')
                .text(progress + '% complete');
        },
        done: function (e, data) {
            $('#progress').hide();
            $('#uploadFile-dialog').dialog("close");
            window.location.reload();
        }
    });
});

    $( "#uploadFile-dialog" ).dialog({
            autoOpen: false,
            height: 365,
            width: 490,
            modal: true,
            buttons: {
                    //"Upload": function() {
                    //        var bValid = true;
                            //fileName.removeClass( "ui-state-error" );
                            
                    //        if ( bValid ) {
                    //                uploadFile();
                    //        } else {
                    //                name.focus();
                    //        }
                    //},
                    "Nevermind": function() {
                            $( this ).dialog( "close" );
                    }
            },
            
            open: function(e,ui) {
                $(this).keyup(function(e) {
                    if (e.keyCode == 13) {
                        $('.ui-dialog-buttonset > button:first').trigger('click');
                    }
                });
            }
    });

    $( "#add-files" )
            .button()
            .click(function() {
                    $( "#uploadFile-dialog" ).dialog( "open" );
            });
            
    $( ".download-file")
            .button()
            .click(function() {
                    var id = $(this).data('id');
                    $.post(
                            '/ajax/downloadfile',
                            {id: id},
                            function(data) {
                                window.location.href = data.url;
                            }
                        );
            })
            
    $(".archive-file")
            .button()
            .click(function() {
                var id = $(this).data('id');
                $.post(
                        '/ajax/archivefile',
                        {id: id},
                        function(data) {
                            window.location.reload();
                        }
                )
    })
            
    //$('input[type=file]').on('change', prepareUpload);
});