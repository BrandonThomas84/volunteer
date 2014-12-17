/* 
* @Author: Brandon Thomas
* @Date:   2014-10-08 01:06:41
* @Last Modified by:   Brandon Thomas
* @Last Modified time: 2014-10-09 14:33:04
*/

function triggerFormUpdateNonce(form){
    var inputToggle = $(form).data("controllerId");
    $("#"+inputToggle).val("true");
}

function volunteerModalTrigger(){
    $(".vol-modal-trigger").click(function(event){
                
        //prevent click function from going anywhere
        event.preventDefault();

        $('#volunteerModal').modal('show');
        
        //set the form action
        var title = $(this).data("title");
        
        //replace the modal title
        $("#volunteerModal").find(".modal-title").empty();
        $("#volunteerModal").find(".modal-title").text(title);

        //set the container and content vars
        var modalContainer = $(this).data("modalBody");
        var modalContent = $(this).siblings( "."+modalContainer ).html();

        //replace the content for the modal
        $("#volunteerModal").find(".modal-body").empty();
        $("#volunteerModal").find(".modal-body").prepend( modalContent );

        //check for footer buttons
        var saveBtn = $(this).data("saveButton");
        var closeBtn = $(this).data("closeButton");
        if( closeBtn == 'hide' ){
          $("#close-nav-modal").addClass("hidden");
          $("#close-vol-modal").addClass("hidden");
        } 
        if( saveBtn == 'hide' ){
          $("#sub-nav-modal").addClass("hidden");  
          $("#sub-vol-modal").addClass("hidden");  
        } 

        //check for save button override
        if( $(this).data("saveButtonText") ){
            var saveBtnOR = $(this).data("saveButtonText");
            $("#sub-vol-modal").empty();
            $("#sub-vol-modal").text( saveBtnOR );
        } 

        //check for save button color
        if( $(this).data("saveButtonColor") ){
            var saveButtonColor = $(this).data("saveButtonColor");
            $("#sub-vol-modal").addClass(saveButtonColor);
        } 

        //check for close button override
        if( $(this).data("closeButtonText") ){
            var closeBtnOR = $(this).data("closeButtonText");
            $("#close-vol-modal").empty();
            $("#close-vol-modal").text( closeBtnOR );
        } 

        //check for close button color
        if( $(this).data("closeButtonColor") ){
            var closeBtnColor = $(this).data("closeButtonColor");
            $("#close-vol-modal").addClass(closeBtnColor);
        } 

        //fill in user-type form field on create user button click
        if( $(this).hasClass("new-user-panel-button") ){
            var fieldVal = $(this).data("userType");
            $("#nav-modal-form").find("input[name='user-type']").val( fieldVal );
        }
    });
}

function showPositions(elem){
    var state = $("#volunteerModal").attr('active-state');
    if( state == "hidden"){

        //change state
        $("#volunteerModal").attr('active-state',"pos-view");

        //set the form action
        var title = $(elem).data("title");
        
        //replace the modal title
        $("#volunteerModal").find(".modal-title").empty();
        $("#volunteerModal").find(".modal-title").text(title);

        //set the container and content vars
        var modalContainer = $(elem).data("modalBody");
        var modalContent = $(elem).siblings( "."+modalContainer ).html();

        //replace the content for the modal
        $("#volunteerModal").find(".modal-body").empty();
        $("#volunteerModal").find(".modal-body").prepend( modalContent );

        //check for footer buttons
        var closeBtn = $(elem).data("closeButton");
        if( closeBtn == 'hide' ){
          $("#close-nav-modal").addClass("hidden");
          $("#close-vol-modal").addClass("hidden");
        } 
    }   
}
//unset a js page message
function unsetPageMessage( key ){

    //get the current messages
    var messages = $.parseJSON( $.cookie('page_message') );

    //remove the keyed message
    delete messages.key
    
    //reset the messages
    $.cookie( 'page_message', messages, {path: '/'} );
    
}

//trigger detail info pop up on volunteer modal
function showPositionInfo(elem){

    console.log("show: "+elem);

    //set the content container
    var positionID = $(elem).data("posId");
    var content = "#position-detail-"+positionID;
    var SUURL = $(elem).data("suUrl");
    console.log( SUURL);

    //empty the existing content
    $("#modal-position-details-inner").empty();
    //add the details
    $("#modal-position-details-inner").prepend( $(content) );
    //slide the content down
    $("#modal-position-details").slideDown(400,function(){
        //fade in the content
        $(content).fadeIn();

        //change the sign in url to contain the return value
        $("#position-sign-in").attr( "href", $("#position-sign-in").attr("href")+"&pos_id="+positionID );

        //update the volunteer button
        $("#position-sign-up").attr( "href", SUURL);
    });
   
}

function hidePositionInfo(){
    console.log("hide: position info");

    var content = $("#modal-position-details-inner > .fe-position-detail");
    var positionID = $(content).data("positionId");

    //move the content back to its home
    $(content).fadeOut();
    $("#modal-position-details").slideUp();
    $("#modal-position-details-inner").empty();  

    $("#position-sign-up").attr( "href", "");  
}

function signUpForPosition(){

}

function clearAdminPosNarrows(){
    console.log("triggered");
    event.preventDefault();
    $("#admin-position-narrow").find("input").each(function(){
        if( $(this).attr("type") !== "checkbox" && $(this).attr("type") !== "submit" ){
            $(this).val('');
        }
    });
    $("#admin-position-narrow").find("select").each(function(){
        $(this).val('');
    });
    $("#position-narrow-change-nonce").val("true");
    $("#admin-position-narrow").submit();
}

function triggerFormUpdateNonce(form){
    var inputToggle = $(form).data("controllerId");
    $("#"+inputToggle).val("true");
}
$(document).ready(function(){
    $(".tooltips").tooltip();

    //calendar functions
    $("#goToDate").click(function(){

        var url = $(this).data('url').split("?");
        var year = $("#goToDateInputY").val();
        var month = $("#goToDateInputM").val();

        if( year !== false && year !== typeof undefined &&  year !== '' && month !== false && month !== typeof undefined &&  month !== ''){
            window.location = url[0] + "?y=" + year + "&m=" + month;
            //console.log("ready");
        } else {

            alert("You must select a date first");
        }       
    });

    //modal form submit
    $("#sub-nav-modal").click(function(){
        $("#nav-modal-form").submit();
    });

    //form submit through data
    $(".form-submit").click(function(){
        var form = $(this).data('formId');
        $("#"+form).submit();
    });
    
    //positions narrow change trigger
    $("#admin-position-narrow input").change(function(){
        triggerFormUpdateNonce( $("#admin-position-narrow") );
    });
    $("#admin-position-narrow select").change(function(){
        triggerFormUpdateNonce( $("#admin-position-narrow") );
    });
    $("#admin-position-narrow input").keyup(function(){
        triggerFormUpdateNonce( $("#admin-position-narrow") );
    });
    $("#admin-position-narrow textarea").keyup(function(){
        triggerFormUpdateNonce( $("#admin-position-narrow") );
    });
    
    $(".vol-modal-trigger").click(function(){ volunteerModalTrigger(); } );

    //highlight narrows with values
    $('#volunteer-signup-narrows input').each(function(){
        if( $(this).val() && $(this).attr('type') !== 'submit' ){
            $(this).addClass('active-narrow');
        }
    });
    $('#volunteer-signup-narrows select').each(function(){
        if( $(this).val() ){
            $(this).addClass('active-narrow');
        }
    });

});