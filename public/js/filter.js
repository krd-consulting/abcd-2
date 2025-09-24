function displayRecordsShown($) {
  const count = $('.p-list-table tr:visible').length;
  $("#record-count").text(count);
}

$(document).ready(function() {
  // Code here will execute once the DOM is ready
  displayRecordsShown($);
});

(function ($) {
  jQuery.expr[':'].Contains = function(a,i,m){
      return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
  };

  function filterList(header, list) {
    var form = $("<form>").attr({"class":"filterform","action":"#"}),
        input = $("<input>").attr({"class":"filterinput","type":"text","value":"Search..."});
    $(form).append(input).appendTo(header);
    
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
        const filter = $(this).val();
        
        if(filter) {
            $matches = $(list).find('td.nameLink:Contains(' + filter + ')').parents('tr');
            $('tr', list).not($matches)
                         .not('tr.collapsible')
                         .not('tr#headTR')
                         .hide();
            $matches.not('tr.noSearch').show();
        } else {
            $(list).find("tr").not('tr.noSearch').show();
            $("#lock-unlock-count").show();
        }

        displayRecordsShown($);

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
