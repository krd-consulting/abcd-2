$(function() {
		sortableList = $( "#add, #remove" ).sortable({
			connectWith: ".connectedSortable",
                        items: "li:not(.exclude).sortingInitialize", //doesn't add any items to the list on page load
                        placeholder: "placeholder",
                        receive: handleSort
		}).disableSelection();
                
                sortableList.find("li").one("mouseenter",function(){ // add one at a time at mouseover
                    $(this).addClass("sortingInitialize");
                    sortableList.sortable('refresh');
                });
});

function handleSort(event, ui) {
  var action     = $(this).attr('id');          //'add' or 'remove'
  var recordID   = ui.item.attr('id');          //id of li being dragged
  var parentID   = $("#parentID").text().trim();       //id of parent
  var recordType = $("#recordType").text().trim();     //type of record
  var parentType = $("#parentType").text().trim();     //type of parent
  
  if (recordType == 'ptcp' && parentType == 'user') {
      var progID = ui.item.data('progid');
  } else {
      var progID = '';
  }
  
  $.post(
      "/ajax/associaterecords",
      {     what : action, 
            rid : recordID, 
            rtype : recordType, 
            pid : parentID, 
            ptype : parentType, 
            addlprogid : progID},
      function(data) {
          $("#helparea").html(data);
      }
  );

  $("#" + recordID).toggleClass('in-list');
  $("#" + recordID).toggleClass('out-list');

};





