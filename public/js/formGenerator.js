$(function() {

jQuery.event.add(window, "load", makeRequired);

var id = 0;
var listOfElements = new Array();
var formInfo = new Object();
listOfElements[0] = formInfo;
var resourcelist = '';

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

function makeRequired() {
    $(":input.required").prev().addClass('hasRequired')
                                 .after('<span class="reqtext">Required</span>');
}

function makeSortable(){
    $("#dynamicForm ul").sortable("destroy")
			.sortable(
        {
            containment: 'parent',
            handle: $(".moveElement"),
            axis: 'y',
            items: 'li.dynamicElement',
            revert: true
        }
    );
}

function safeEnter() {
    $("#formOptions").bind("keypress", function(e) {
          if (e.keyCode == 13) return false;
    });
}

function removeElement(t) {
    delete listOfElements[t];
    $("li#" + t).slideUp(300);
    setTimeout(function(){$("li#" + t).remove()},310);
}

function createTimeHTML(type) {
    //type should be 'from' or 'to
    //used to create first layer of additional fields for calendar reference
    id = listOfElements.length;
    idTag = 'field_' + id;
    switch (type) {
        case 'from' : title = "From"; cssClass = 'start'; break;
        case 'to'   : title = "To"; cssClass = 'end'; break;
        default     : alert ('Invalid Timepicker type passed to HTML creator.'); return false;    
    }
    
    label = "<label class='elementName' for='" + idTag + "'>" + title + "</label>\n";
    
    //create time field (text, timepicker)
    
    input = "<input id='" + idTag + "' type='text' class='timepicker " + cssClass + "' value='' name='" + title + "'/>\n";
    html = label + input;
    
    thisElement = new Object();
    thisElement.id = id;
    thisElement.type = 'text';
    thisElement.name = title;
    listOfElements[id] = thisElement;

    return html;
}

function createResourceHTML() {
    id = listOfElements.length;
    idTag = 'field_' + id;
    title = "Appointment with";
    
    label = "<label class='elementName' for='" + idTag + "'>" + title + "</label>\n";
    input = "<select id='" + idTag + "' class='resourceSelect' value='' name='" + title + "'/>\n";
    html = label + input;
    
    thisElement = new Object();
    thisElement.id = id;
    thisElement.type = 'text';
    thisElement.name = title;
        
    listOfElements[id] = thisElement;
    
    return html;
}

function createElementHTML () 
{
    var elType = $("#elementType").val();
    title  = $("#fieldTitle").val();
    isReq  = $("#isRequired").val();
    
    //field Reference Options
    
    refOpts = $("#referenceOpts").val();
    refForm = $("#formList").val();
    refField = $("#fieldList").val();
    scheduleID = $("#scheduleList").val();
    editable = "editable";
    
    switch(refOpts) {
        case "standAlone"   : extraAtt = ''; break;
        case "refersToForm" : 
            if (refForm === undefined || refField === undefined) {
                extraAtt = ''; 
                alert ("You must specify both a form and a field to make this valid.");
                return false;
            } else {
                extraAtt = " class='reference' data-refType='reference' data-refField='" + refField + "' data-refForm='" + refForm + "' ";
            }
            break;
        case "refersToPtcp" : 
                extraAtt = " class = 'reference' data-refType='participant' "; break;
        case "refersToStaff": 
                extraAtt = " class = 'reference' data-refType='staff' "; break;
        case "refersToVol":
                extraAtt = " class = 'reference' data-refType='volunteer' "; break;
        case "refersToSchedule":
                extraAtt = " class = 'refersToSchedule' data-scheduleid='" + scheduleID + "' ";
                title = "Appointment Date";
                editable = "";
                break;
        default: alert ("Something went wrong with reference field creator: " + refOpts + ". "); break;
    }
    
    id = listOfElements.length;
    idTag = 'field_' + id;
        
    label = "<label class='" + editable + " elementName' for='" + idTag + "'>" + title + "</label>\n";
    
    
    thisElement = new Object();
    
    switch (elType)
    {
        case 'text' : 
            input = "<input id='" + idTag + "' type='text' value=''" + extraAtt + "name='" + title + "'/>\n";
            html = label + input;
            
            thisElement.id = id;
            thisElement.type = 'text';
            thisElement.name = title;
            
            listOfElements[id] = thisElement;
            
            break;
            
        case 'num' : 
            input = "<input id='" + idTag + "' type='text' class='numeric' value='' name='" + title + "'/>";
            html = label + input;
            
            thisElement.id          = id;
            thisElement.type        = 'num';
            thisElement.CSSclass    = 'numeric';
            thisElement.name        = title;
            listOfElements[id]      = thisElement;
            
            break;
        
        case 'dropdown' :
            input = "<input id='" + idTag + "' type='select' value='' name='" + title + "'/>";
            html = label + input;
            thisElement.id          = id;
            thisElement.type        = 'select';
            thisElement.CSSclass    = 'dropdown';
            thisElement.name        = title;
            listOfElements[id]      = thisElement;
            
            break;
        
        case 'date' : 
            if (refOpts == 'refersToSchedule') {
                input = "<input id='" + idTag + "' type='text' value=''" + extraAtt + "name='" + title + "'/>";
            } else {
                input = "<input id='" + idTag + "' type='text' class='dynamicdatepicker' value='' name='" + title + "'/>";
            }
            html = label + input;
            
            thisElement.id          = id;
            thisElement.type        = 'date';
            thisElement.CSSclass    = 'datepicker';
            thisElement.name        = title;
            thisElement.schedulerID = scheduleID;
            listOfElements[id]      = thisElement;
                        
            break;
            
        case 'radio' : 
            label = ""; 
            input = "";
            html = "<div class='external-title editable elementName'>" + title + "</div>\n\
                    <div class='radio-buttons-list'>\n";
            numBoxes= $("#numBoxes").val();
            
            thisElement.id          = id;
            thisElement.name        = title;
            thisElement.type        = 'radio';
            thisElement.numoptions  = numBoxes;
            thisElement.options     = new Array();
            
            for (var i=1; i<=numBoxes; i++) {
                label = "<label class='invert editable' for='" + id + "'> Option " + i + "</label>\n";
                input = "<input id='" + id + "_field_" + i + "' type='radio' class='form-radio' value='Option" + i + "' name='" + title + "'/>\n";
                listItem = "<span class='box-list'>" + input + label + "</span>\n";
                html += listItem;
                
                thisElement.options[i-1] = 'Option ' + i;
                
            }
            
            html += "</div>\n";
            
            listOfElements[id] = thisElement;
                       
            break;
    
        case 'check' : 
            label = ""; 
            input = "";
            html = "<div class='external-title editable elementName'>" + title + "</div>\n\
                    <div class='checkbox-list'>\n";
            numBoxes = $("#numBoxes").val();
            
            thisElement.id          = id;
            thisElement.name        = title;
            thisElement.type        = 'checkbox';
            thisElement.numoptions  = numBoxes;
            thisElement.options     = new Array();
            
            for (var i=1; i<=numBoxes; i++) {
                label = "<label class='invert editable' for='" + id + "'> Option " + i + "</label>\n";
                input = "<input id='" + id + "_field_" + i + "' type='checkbox' class='form-checkbox' value='Option" + i + "' name='" + title + "[]'/>\n";
                listItem = "<span class='box-list'>" + input + label + "</span>\n";
                html += listItem;
                
                thisElement.options[i-1] = 'Option ' + i;
                
            }
            html += "</div>\n";
            
            listOfElements[id] = thisElement;
            
            break;
    
        case 'matrix' : 
            label= "";
            nRows = $("#numRows").val();
            nCols = $("#numCols").val();
            
            thisElement.id = id;
            thisElement.name = title;
            thisElement.type = 'matrix';
            thisElement.numRows = nRows;
            thisElement.numCols = nCols;
            
            thisElement.rows = new Array();
            thisElement.cols = new Array();
            
            matrixTitle = "<div class='matrix-title editable elementName'>" + title + "</div>\n";
            matrixTableTop = "<table class='matrix-table'>\n";
            matrixTableBot = "</table>\n";
            
            matrixTHead = "<tr>";
            matrixTHead += "<td class='noborder'></td>\n";
            for(var i=1;i<=nCols;i++) {
                tCell = "<td id='" + id + "_column_" + i + "' class='head'> <span class='editable column' id='" + i + "'>Option " + i + "</span></td>";
                matrixTHead += tCell;
                
                thisElement.cols[i-1] = "Option " + i;
            }
            matrixTHead += "</tr>";
            
            matrixTable = matrixTableTop + matrixTHead;
            
            for(var j=1;j<=nRows;j++) {
                rowTop = "<tr >";
                
                    rowMeat = "<td id='" + id + "_row_" + j + "' class='question head'><span id='" + j + "' class='editable row'>Question" + j + "</span></td>";
                    for (var k=1; k<=nCols; k++) {        
                        td = "<td><input type='radio' class='" + j + "' name='" + j + "' value='Option " + k + "'/></td>";
                        rowMeat += td;
                    }
                    
                rowBottom = "</tr>";
                row = rowTop + rowMeat + rowBottom;
                matrixTable += row;
                
                thisElement.rows[j-1] = "Question " + j;
            }
            
            matrixTable += matrixTableBot;
            html = matrixTitle + matrixTable;
            
            listOfElements[id] = thisElement;
            
            break;
            
        case 'textarea' : 
            input = "<textarea id='" + id + "' rows='3' cols='35' value='' name='" + title + "'/>\n";
            html = label + input;
            
            thisElement.id = id;
            thisElement.name = title;
            thisElement.type = 'textarea';
            
            listOfElements[id] = thisElement;
            break;
            
        default: alert('Trying to create invalid element type: ' + $elType); break;   
    }
    
    return html;
    
}

function addElementtoCanvas(e,t)
{
    wrapperTop = "<li id='" + id + "' class='dynamicElement'>\n";
    if (t == 'special') {
        dragLink = '';
        removeLink = '';
    } else {
        dragLink = "<div class='elementButtons moveElement'>\n\
                    <img src='/skins/default/images/blank.gif' align='left' class='sprite-pic drag-pic'/>\n\
                </div>\n";    
        removeLink = "<div class='elementButtons removeElement'>\n\
                    <img src='/skins/default/images/blank.gif' align='left' class='sprite-pic remove-pic'/>\n\
                </div>\n";
    }
    
    wrapperBottom = "</li>\n";
    
    entry = wrapperTop + e + dragLink + removeLink + wrapperBottom;
    
    /*
     * Append the full element and highlight it briefly
     */
    
    $("#dynamicForm ul").append(entry);
    $("#" + id).addClass('ui-state-highlight');
    setTimeout(function(){
            $("#" + id).removeClass('ui-state-highlight', 500)
    }, 50);
    
    //if required, add class
    if ($("#isRequired").val() == 'true') {
        $("#" + id + " .elementName").addClass('hasRequired');
        $("#" + id + " .hasRequired").after('<span class="reqtext">Required</span>');
	if ($("#elementType").val() != 'matrix') {
            $("#" + id + " input:first").addClass('required');
        } else {
            $("#" + id + " tr").each(function()
            {
                $(this).find("input:first").addClass('required');
            });
        }
    }

    
    /*
     * Assign the 'Delete' functionality to the proper div
     */
    $("#" + id + " .removeElement").click(function(){
        removeID = $(this).parents('li').attr('id');
        removeElement(removeID);
    });
    
    /*
     * Make element draggable
     */
    makeSortable();
    
    
    /*
     * Make labels editable
     */
    
    $(".editable.elementName")
                              .not('.matrix-title')
                              .editable(function(value,settings)
                                {
                                 myElement = $(this).parent().attr('id');
                                 listOfElements[myElement].name = value;
                                 $(this).parent().find(':input').attr('name',value);
                                 return(value);
                                },{
                                  select: true,
                                  submit: 'OK'
                                }
                                 );
    
    $(".editable.matrix-title").editable(function(value,settings)
                                {
                                 return(value);
                                },{
                                  select: true,
                                  submit: 'OK'
                                }
                                 );
    
    $(".editable.invert").editable(function(value,settings){
                                elementID = $(this).prev().attr('id');
                                //myID = elementID[elementID.length-1];
                                myID = elementID.replace(/.*\_field\_/,'');
                                myElement = $(this).attr('for');
                                arrayID = parseInt(myID) - 1;
                                listOfElements[myElement].options[arrayID] = value;
                                //alert ('Got elementID ' + elementID  + ', myID ' + myID + ', replacing field ' + arrayID);
                                $(this).prev().val(value); 
                                return(value)
                            }, {
                                select: true,
                                submit: 'OK'
                            });
    
    $(".editable.row").editable(function(value,settings){
                                myID = $(this).attr('id');
                                myElement = $(this).parents('li').attr('id');
                                listOfElements[myElement].rows[myID-1] = value;
                                
                                $(this).parents('li').find("input:radio." + myID).attr('name', value);
                                return(value)
                            }, {
                                select: true,
                                submit: 'OK'
                            });
                            
    $(".editable.column").editable(function(value,settings){
                                myID = $(this).attr('id');
                                childID = parseInt(myID); childID++;
                                myElement = $(this).parents('li').attr('id');
                                listOfElements[myElement].cols[myID-1] = value;
                                $("form table tr td:nth-child(" + childID + ")").find('input:radio').each(
                                        function(){
                                            $(this).attr('value', value);
                                        }
                                    );
                                return(value)
                            }, {
                                select: true,
                                submit: 'OK'
                            });
                            
    /*
     * Show datepicker functionality
     */
    
    
    $(".datepicker").datepicker({
                        changeMonth: true,
                        changeYear: true,
                        yearRange: "-2,+2",
                        minDate: "-2Y",
                        maxDate: "+2Y",
                        showButtonPanel: true,
                        dateFormat : "yy-mm-dd"
    });
    
}

function cleanHTML() {
    $("li.skeletonElements").removeClass("skeletonElements");
    $("li.dynamicElement").addClass("customElement").removeClass("dynamicElement");
    $("input.hasDatepicker").removeClass("hasDatepicker");
    $(".editable").removeClass("editable");
    $("input#formType").parent().remove();
    $("input#formDept").parent().remove();
    $(".elementButtons").remove();
    cleanHTML = $("#dynamicForm").html();
    return cleanHTML;
}

function saveForm() {
    //Visual Signals that dynamic is now static.
    $("#controls").slideUp(1000);
    $(".editable").unbind('click.editable');
    $("#saveForm").unbind('click').slideUp(300);
    
    //Collect Final Variables
    formName    = $("#canvas h2").text();
    formType    = $("input[name=formType]").val();
    formDept    = $("input[name=formDept]").val();
    formTarget  = $("input[name=formTarget]").val();
    formDesc    = $(".formDesc").text();
    editableHTML= $("#dynamicForm").html();
    cleanHTML   = cleanHTML();
    
    listOfElements[0].name      = formName;
    listOfElements[0].type      = formType;
    listOfElements[0].dept      = formDept;
    listOfElements[0].target    = formTarget;
    listOfElements[0].desc      = formDesc;
    listOfElements[0].cleanHtml = cleanHTML;
    listOfElements[0].editHtml  = editableHTML;

    $.post(
        "/forms/ajax",
        {task: 'processform', data: listOfElements},
        function(data){
            setTimeout(function(){alert(data.message);},500);
            window.location='/forms/list';
        }

    );

}

function setReferenceOptions(o) {
    switch (o) {
        case 'standAlone'           : $("#refDiv").slideUp().remove(); break;
        case 'isReference'          : $("#refDiv").slideUp().remove(); break;
        case 'refersToSchedule'     : $("#referenceOpts").after("<div id='refDiv'></div>");
                                        $.post(
                                                "/forms/ajax",
                                                {task: 'referenceList', type: 'schedulelist'},
                                                function(data) {
                                                    $("#refDiv").html(data.form)
                                                                .slideDown(1000)
                                                                .addClass("ui-state-highlight");
                                                }
                                        );
                                        break;
        case 'refersToForm'         : $("#referenceOpts").after("<div id='refDiv'></div>");
                                        $.post(
                                            "/forms/ajax",
                                            {task: 'referenceList', type: 'formlist'},
                                            function(data) {
                                                $("#refDiv").html(data.form)
                                                            .slideDown(1000)
                                                            .addClass("ui-state-highlight");
                                                $("#formList").change(function(){
                                                    var formID = $(this).val();
                                                    $.post(
                                                        "/forms/ajax",
                                                        {task: 'referenceList', type: 'fieldList', formID: formID},
                                                        function(data) {
                                                            $("#refFieldDiv").remove();
                                                            $("#refDiv").append("<div id='refFieldDiv'</div>");
                                                            $("#refFieldDiv").html(data.form).slideDown();
                                                        }
                                                    );
                                                });
                                            }

                                        );
                                        break; 
    }
}

$("#elementList").click(function(){
    $("ul.formElements").slideDown();
    $("#elementOptions").slideUp();
});

$("li.elementList").click(function(){
    $("ul.formElements").slideUp();
    $("#elementOptions").html('');
    
    var type = $(this).attr('id');
    var humanType = $(this).text();
    
    $.post(
        "/forms/ajax",
        {task: 'optionset', type: type},
        function(data){
            $("#elementOptions").html("<h3 align=center>" + humanType + " options:</h3>");
            $("#elementOptions").append(data.form)
                                .slideDown(500);
            setTimeout(function(){
                safeEnter();
                },1200);
            setTimeout(function(){
                $("#referenceOpts").change(function(){
                    setReferenceOptions($(this).val());
                });
            },1300)
        }
    );
    
});


$("#addElement").button()
                .click(function()
                    {
                     var newElement = createElementHTML();
                     if (!newElement) {
                         return false;
                         exit;
                     } else {
                        type = 'normal';
                        addElementtoCanvas(newElement,type);
                        refOpts = $("#referenceOpts").val();
                        if (refOpts == 'refersToSchedule') {
                            type = 'special';
                            var fromElement = createTimeHTML('from');
                            addElementtoCanvas(fromElement,type);
                            var toElement = createTimeHTML('to');
                            addElementtoCanvas(toElement,type);
                            var refElement = createResourceHTML();
                            addElementtoCanvas(refElement,type);
                            //get scheduleID from element
                            sID = $("#scheduleList").val();
                            //get list of resources for schedule set from server
                                    $.post(
                                        ("/ajax/getresourcelist"),
                                        {scheduleSet:sID},
                                        function(data) {
                                            $.each(data,function(index,resource){
                                                $('select.resourceSelect').append(
                                                      '<option value="' + resource.resourceID + '" data-resourcetype="' + resource.resourceType + '">' + resource.resourceName + '</option>'
                                                );
                                            })
                                        }
                                    );
                        }

                        $("#elementOptions").html('').slideUp(100);
                        $("ul.formElements").slideDown(500);
                    }
                }
                );

$("#saveForm").button().click(function()
                    {
                    saveForm();
                    }
                );

$(".editable.textonly").editable(function(value,settings){
                                    return (value)
                                }, {
                                    select: true,
                                    submit: 'OK'
                                });

}); //final line
