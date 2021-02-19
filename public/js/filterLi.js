
  jQuery.expr[':'].Contains = function(a,i,m){
      return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
  };

  function filterList(header, list) {
    var form = $("<form>").attr({"class":"filterform","action":"#"}),
        input = $("<input>").attr({"class":"filterinput","type":"text","value":"Search..."});
    $(form).append(input).appendTo(header);
    
    
    var numberOrig = $("#record-count").text();
    
    $(input).focus(function()
                    {
                        if ($(this).val() == 'Search...') {$(this).val('');}
                    }
                  );
                      
    $(input).focusout(function()
                    {
                        if ($(this).val() == '') {$(this).val('Search...');}                      
                        
                    }
                  );
                      
    
    $(input)
      .change( function () {
        var filter = $(this).val();
        
        if(filter) {
            $matches = $(list).find('li:Contains(' + filter + ')');
            $('li', list).not($matches)
                        .hide();
            $matches.show();
            //var numberGood = $matches.length;
            //$("#record-count").text(numberGood);
        } else {
          $(list).find("li").show();
          //$("#record-count").text(numberOrig);
        }
        //sortableList.sortable('refresh');
        return false;
      })
    .keyup( function () {
        $(this).change();
    });
  }
(function ($) {
  $(function () {
    filterList($("#filterleft"), $("#current-users"));
    filterList($("#filterright"), $("#other-users"));
  });
}(jQuery));
