$(function(){
    
    //set links
    $("a.moreRecords").click(function(){
        hiddenRecs = $(this).parents('.block').find('li.hidden');
        visibleRecs = $(this).parents('.block').find('li').not('li.hidden');
        totalRecs = $(this).parents('.block').find('li').length;
        
        if (hiddenRecs.length > 0) {
            for(i=0;i<3;i++){
                rec = hiddenRecs.eq(i);
                rec.slideDown(350)
                   .removeClass('hidden');
                if(rec.next().length == 0) {
                    $(this).text('Show fewer records');    
                }
            }
        } else {
            for (i=visibleRecs.length;i>2;i--){
                    visibleRecs.eq(i).slideUp(350)
                                     .addClass('hidden');
                    if (i == 3) {
                        $(this).text('Show more records');
                    }
            }
        }
    });
    
    $("a.showDetails").click(function(){
        $(this).hide();
        $(this).next().show();
        details = $(this).parent().find('ul');
        details.slideDown(100);
    });
    
    $("a.hideDetails").click(function() {
        $(this).hide();
        $(this).prev().show();
        details = $(this).parent().find('ul');
        details.slideUp(100);
    })
    
    $("a.showNotes").click(function(){
        $(this).hide();
        $(this).next().show();
        notes = $(this).parent().find('div.notesDiv');
        if (notes.text().length == 0) {
            notes.text('No notes yet. Click here to add some!');
        }
        notes.slideDown(100)
             .editable('/ajax/updatevolnotes',
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
    
    $("a.hideNotes").click(function(){
       $(this).hide();
       $(this).prev().show();
       $(this).parent().find('div.notesDiv').slideUp(100);
    });
       
});

