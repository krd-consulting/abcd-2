$(function(){

var fromBox = $(".timepicker.start");
var toBox = $(".timepicker.end");
var resourceBox = $(".resourceSelect");
var dateBox = $(".refersToSchedule");

var scheduleID = dateBox.data('scheduleid');
var fromTime = fromBox.val();
var toTime = toBox.val();

function turnOffBox(b) {
    b.val('')
     .prop('disabled',true);
}

function turnOnBox(b) {
    b.prop('disabled',false);
}

fromBox.prop('disabled',true);
toBox.prop('disabled',true);
resourceBox.prop('disabled',true);



dateBox.change(function() {
    turnOffBox(fromBox);
        turnOffBox(toBox);
        turnOffBox(resourceBox);
    if (dateBox.val() == '') {
        $(this);
    } else {
        turnOnBox(fromBox);
    }
})

fromBox.change(function() {
    if (fromBox.val() == '') {
        turnOffBox(toBox);
        turnOffBox(resourceBox);
    } else {
        turnOffBox(toBox);
        turnOnBox(toBox);
    }
})

toBox.change(function() {
    turnOffBox(resourceBox);
    if (toBox.val() == '') {    
        exit;
    } else {
        turnOnBox(resourceBox);
        $(".resourceSelect option").each(
                        function(i){
                            $(this).prop('disabled',false)
                        });
        $.post(
            "/ajax/getbookedresources",
            {'sid':scheduleID,'date':dateBox.val(),'from':fromBox.val(),'to':toBox.val()},
            function(data){
                $.each(data, function(index,item) {
                      rid = item.resourceID;
                      rtype = item.resourceType;
                      $(".resourceSelect option[value='" + rid + "']").prop('disabled',true);
                    })
            }
        );
    }
})

});