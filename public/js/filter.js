(function ($) {
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
            $matches = $(list).find('td.nameLink:Contains(' + filter + ')').parents('tr');
            $('tr', list).not($matches)
                         .not('tr.collapsible')
                         .not('tr#headTR')
                         .hide();
            $matches.not('tr.noSearch').show();
            var numberGood = $matches.not('tr.noSearch').length;
            $("#record-count").text(numberGood);
        } else {
            $(list).find("tr").not('tr.noSearch').show();
            $("#record-count").text(numberOrig);
            $("#lock-unlock-count").show();
        }
        return false;
      })
    .keyup( function () {
        $(this).change();
    });
  }

  $(function () {
    filterList($("#searchform"), $(".p-list-table"));
    filterList($("#programPtcpSearch"), $(".ptcpTable"));
  });
}(jQuery));
