$(function() {
	
    function updateHome(i,v) {
        did = $(i).data('id');
        uid = $("#content h1").attr('id');
            $.post (
                    "/ajax/sethome",
                    {deptID : did, userID : uid, value: v},
                    function(data) {
                        window.location.reload();
                    }
                    );

    
    }    
    
    $("tr.default .setHome a").click(function(){
            updateHome(this,'1'); //set value in table to "1" (true)
        });
    
    $("tr.homedept .setHome a").click(function(){
            updateHome(this,'0'); //set value in table to "0" (false)
    })
            
});