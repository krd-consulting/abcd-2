$(function(){
    
    //set links
    $(".sideLinks a.showNotes").click(function(){
        $(this).hide();
        $(this).next().show();
        notes = $(this).parents('li').find('div.notesDiv');
        if (notes.text().length == 0) {
            notes.text('No notes yet. Click here to add some!');
        }
        notes.slideDown(500)
             .editable('/ajax/updategroupnotes',
                        {
                            type: 'textarea',
                            cancel: 'Nevermind',
                            submit: 'Save',
                            tooltip: 'Click to edit',
                            height: '100px',
                            data: function(value, settings) {
                                    /* Convert <br> to newline. */
                                    var retval = value.replace(/<br\s*\/?>\n?/gi, '\n');
                                    return retval;
                            }
                        }
                      );
    });
    
    $(".sideLinks a.hideNotes").click(function(){
       $(this).hide();
       $(this).prev().show();
       $(this).parents('li').find('div.notesDiv').slideUp(500);
    });
    
    $("#addMeeting").button()
                    
                    .css('position', 'absolute')
                    .css('right', '5px')
                    .css('top', '50px')
                    .click(function()
                    {
                        var id = $("#content h1").attr('id');
                        address = '/groups/meetings/id/' + id;
                        window.location = address;
                    })
    
    filterList($(".groupfilter"), $(".meeting-list"));
   
});

