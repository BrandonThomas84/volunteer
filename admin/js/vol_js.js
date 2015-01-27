/* 
* @Author: Brandon Thomas
* @Date:   2014-08-19 13:48:27
* @Last Modified by:   Brandon Thomas
* @Last Modified time: 2014-11-10 23:08:48
*/

function verifyProfilePassMatch(pass1, pass2){
    console.log("triggered");
    console.log("pass1: "+$("#"+pass1).val());
    console.log("pass2: "+$("#"+pass2).val());
    
    if( $("#"+pass1).val().length > 0 ){

        $("#"+pass1).attr("required","required");
        $("#"+pass2).attr("required","required");

        if( $("#"+pass2).val() == $("#"+pass1).val() ){
            $("#"+pass2).parents(".form-group").addClass('has-success');
            $("#"+pass2).parents(".form-group").removeClass('has-error');
        } else {
            $("#"+pass2).parents(".form-group").addClass('has-error');
            $("#"+pass2).parents(".form-group").removeClass('has-success');
        }

    } else {
        $("#"+pass1).attr("required",null);
        $("#"+pass2).attr("required",null);
        $("#"+pass2).parents(".form-group").removeClass('has-error');
        $("#"+pass2).parents(".form-group").removeClass('has-success');
    }   
}

function verifyPassMatch( pass1, pass2 ){

    if( $("#"+pass1).val() == $("#"+pass2).val() ){

        $("#"+pass1).parents("form").submit();

    } else {
        console.log( $("#"+pass1).val() );
        console.log( $("#"+pass2).val() );

        var alert = '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><strong>Password Mismatch:</strong>Whoops! It looks like your passwords do not match. Please check your entry and try again.</div>';

        $("#main-body-inner").prepend( alert );

    }
}

//notifications
function removeNotifications(n_id,button_id){

    //hide the notification
    $("#"+button_id).parents(".modal-input-group").animate({opacity:0}, function(){ 
        $(this).remove(); 
        var existing = $.cookie('notif-remv');    
        var newVal = ";"+n_id;

        if( existing !== typeof undefined && existing !== false && existing !== '' ){

            //set cookie
            $.cookie("notif-remv", existing+newVal, {path: '/',expires: 30});

        } else {
            
            //set cookie
            $.cookie("notif-remv", newVal, {path: '/'});
        }   
    });    
}

function removeAttributeOption(atr_elem,DBVal,DisplayVal){

    var replaceVal = "'"+DBVal+"':'"+DisplayVal+"'";

    $("input[name='attr-options-updated']").val("true");
    $("input[name='attr-options-compiled']").val( $("input[name='attr-options-compiled']").val().replace( replaceVal+",", "") );
    $("input[name='attr-options-compiled']").val( $("input[name='attr-options-compiled']").val().replace( replaceVal, "") );
    
    $(atr_elem).parents("tr").remove();
}

function removeAttributeData(atr_elem,DBVal,DisplayVal){

    var replaceVal = "'"+DBVal+"':'"+DisplayVal+"'";

    $("input[name='attr-data-updated']").val("true");
    $("input[name='attr-data-compiled']").val( $("input[name='attr-data-compiled']").val().replace( replaceVal+",", "") );
    $("input[name='attr-data-compiled']").val( $("input[name='attr-data-compiled']").val().replace( replaceVal, "") );
    
    $(atr_elem).parents("tr").remove();
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

function generateRandomString(length){
    var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz!@#$%^&*()?";
    var randomstring = '';
    for (var i=0; i<length; i++) {
        var rnum = Math.floor(Math.random() * chars.length);
        randomstring += chars.substring(rnum,rnum+1);
    }

    return randomstring;
}

function removePageMessage( key ){

    var existingRemove = $.cookie("msg_remove");
    console.log( existingRemove );
    $.cookie("msg_remove", existingRemove+','+key, {path: '/'});
}

function unsetPageMessage( key ){

    //get the current messages
    var messages = $.parseJSON( $.cookie('page_message') );

    //remove the keyed message
    delete messages.key
    
    //reset the messages
    $.cookie( 'page_message', messages, {path: '/'} );
    
}


$(document).ready(function(){

	//enable tooltips
    $('.avail-positions').tooltip();
    $(".tooltips").tooltip();

    //scroll to top button display
    $("#scroll-to-top").css("display","none");
    $( window ).scroll(function() {
        if( $(window).scrollTop() > 200 ){
            $("#scroll-to-top").css("display","block");
        } else {
            $("#scroll-to-top").css("display","none");
        }
    });

    //disable click function on active menu items
    $(".navbar-nav > li.active > a ").click(function(){
    	event.preventDefault();
    });

    //popovers
    $(".popover-toggle-help").popover();

    //nav menu functions

    //function to add content to the nav modal
    function navModalContent(elem, event){
        //prevent click function from going anywhere
        event.preventDefault();

        //set the form action
        var formAction = $(elem).data("modalFormAction");
        $("#nav-modal-form").prop('action', formAction );
        
        //set the form action
        var title = $(elem).data("title");
        console.log( title );
        
        //replace the modal title
        $("#navModal").find(".modal-title").empty();
        $("#navModal").find(".modal-title").text(title);

        //set the container and content vars
        var modalContainer = $(elem).data("modalBody");
        var modalContent = $("body").find( "#"+modalContainer ).html();

        //replace the content for the modal
        $("#navModal").find(".modal-body").empty();
        $("#navModal").find(".modal-body").prepend( modalContent );

        //check for footer buttons
        var saveBtn = $(elem).data("saveButton");
        var closeBtn = $(elem).data("closeButton");
        if( closeBtn == 'hide' ){
          $("#close-nav-modal").addClass("hidden");
        } 
        if( saveBtn == 'hide' ){
          $("#sub-nav-modal").addClass("hidden");  
        } 

        //check for save button override
        var saveBtnOR = $(elem).data("saveButtonText");
        if( saveBtnOR.length > 0 ){
          $("#sub-nav-modal").empty();
          $("#sub-nav-modal").text( saveBtnOR );
        } 

        //fill in user-type form field on create user button click
        if( $(elem).hasClass("new-user-panel-button") ){
            var fieldVal = $(elem).data("userType");
            $("#nav-modal-form").find("input[name='user-type']").val( fieldVal );
        }
    };

    //activate the modal content replacement
    $(".modal-operator").click(function(event){
        navModalContent( $(this), event );
    });
    $(".duplicateNavItem").click(function(event){ 
        navModalContent( $(this), event ); 
    });

    //duplicate any link from the nav menu functionality
    $(".duplicateNavItem").each(function(){

        //set vars from nav link
        var navItem = $(this).data("navId");
        var origClasses = $(this).attr("class").split(/\s+/);
        var linkHref = $("#"+navItem).attr("href");
        var linkToggle = $("#"+navItem).data("toggle");
        var linkTarget = $("#"+navItem).data("target");
        var linkjsTitle = $("#"+navItem).data("title");
        var linkTitle = $("#"+navItem).attr("title");
        var linkBody = $("#"+navItem).data("modalBody");
        var linkClass = $("#"+navItem).attr("class");
        var linkFormAction = $("#"+navItem).data("modalFormAction");

        //assign attributes
        $(this).attr("href",linkHref);
        $(this).attr("data-toggle",linkToggle);
        $(this).attr("data-target",linkTarget);
        $(this).attr("data-title",linkjsTitle);
        $(this).attr("title",linkTitle);
        $(this).attr("data-modal-body",linkBody);
        $(this).attr("class",linkClass);
        $(this).attr("data-modal-form-action",linkFormAction);


        for (var i = 0; i < origClasses.length; i++) {
           $(this).addClass(origClasses[i])
        }

    });

    $(".vol-modal-trigger").click(function(event){
        
        //prevent click function from going anywhere
        event.preventDefault();
        
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

    //modal form submit
    $("#sub-nav-modal").click(function(){
        $("#nav-modal-form").submit();
    });

    //form submit through data
    $(".form-submit").click(function(){
        var form = $(this).data('formId');
        $("#"+form).submit();
    });

    //breadcrumb toggle
    $("#breadcrumb-toggle").click(function(){

        var duration = 1000;
        var easing = 'easeOutCubic';

        if( $(this).hasClass("open") ){

            $("#breadcrumbs-container").slideUp(duration, easing);

            $("#breadcrumb-toggle").toggleClass("closed");
            $("#breadcrumb-toggle").animate({"top":"0"},function(){
                $("#breadcrumb-toggle").toggleClass("open");
            });

            $("#main-body").addClass('border-top');

            $.cookie("breadcrumb_show", 0, {path: '/'});

        } else {

            $("#breadcrumbs-container").slideDown(duration, easing, function(){
                
                $("#breadcrumb-toggle").toggleClass("closed");
                
                $("#breadcrumb-toggle").animate({"top":"-20px"},function(){
                    $("#breadcrumb-toggle").toggleClass("open");
                });

                $("#main-body").removeClass('border-top');
                $.cookie("breadcrumb_show", 1, {path: '/'});
            });            
        }
    });

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

    function triggerFormUpdateNonce(form){
        var inputToggle = $(form).data("controllerId");
        $("#"+inputToggle).val("true");
    }

    //event status change trigger
    $(".modify-form-controller input").change(function(){
        var form = $(this).parents(".modify-form-controller");
        triggerFormUpdateNonce( form );
    });
    $(".modify-form-controller select").change(function(){
        var form = $(this).parents(".modify-form-controller");
        triggerFormUpdateNonce( form );
    });
    $(".modify-form-controller input").keyup(function(){
        var form = $(this).parents(".modify-form-controller");
        triggerFormUpdateNonce( form );
    });
    $(".modify-form-controller textarea").keyup(function(){
        var form = $(this).parents(".modify-form-controller");
        triggerFormUpdateNonce( form );
    });

    //settings form triggers
    $("#settings-controls input").change(function(){
        triggerFormUpdateNonce( $("#settings-controls") );
    });
    $("#settings-controls select").change(function(){
        triggerFormUpdateNonce( $("#settings-controls") );
    });
    $("#settings-controls input").keyup(function(){
        triggerFormUpdateNonce( $("#settings-controls") );
    });
    $("#settings-controls textarea").keyup(function(){
        triggerFormUpdateNonce( $("#settings-controls") );
    });

    //settings atribute creation form triggers
    $("#attribute-add-from-settings input").change(function(){
        triggerFormUpdateNonce( $("#attribute-add-from-settings") );
    });
    $("#attribute-add-from-settings select").change(function(){
        triggerFormUpdateNonce( $("#attribute-add-from-settings") );
    });
    $("#attribute-add-from-settings input").keyup(function(){
        triggerFormUpdateNonce( $("#attribute-add-from-settings") );
    });
    $("#attribute-add-from-settings textarea").keyup(function(){
        triggerFormUpdateNonce( $("#attribute-add-from-settings") );
    });

    //attribute settings nonce
    $("#attr-settings input").change(function(){
        triggerFormUpdateNonce( $("#attr-settings") );
    });
    $("#attr-settings select").change(function(){
        triggerFormUpdateNonce( $("#attr-settings") );
    });
    $("#attr-settings input").keyup(function(){
        triggerFormUpdateNonce( $("#attr-settings") );
    });
    $("#attr-settings textarea").keyup(function(){
        triggerFormUpdateNonce( $("#attr-settings") );
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

    //volunteer narrow change trigger
    $("#volunteer-settings input").change(function(){
        triggerFormUpdateNonce( $("#volunteer-settings") );
    });
    $("#volunteer-settings select").change(function(){
        triggerFormUpdateNonce( $("#volunteer-settings") );
    });
    $("#volunteer-settings input").keyup(function(){
        triggerFormUpdateNonce( $("#volunteer-settings") );
    });
    $("#volunteer-settings textarea").keyup(function(){
        triggerFormUpdateNonce( $("#volunteer-settings") );
    });

    //form has nonce class trigger
    $(".form-has-nonce input").change(function(){
        if( !$(this).hasClass('nonce-ignore') ){
            triggerFormUpdateNonce( $(this).parents(".form-has-nonce ") );
        }
    });
    $(".form-has-nonce select").change(function(){
        if( !$(this).hasClass('nonce-ignore') ){
            triggerFormUpdateNonce( $(this).parents(".form-has-nonce ") );
        }
    });
    $(".form-has-nonce input").keyup(function(){
        if( !$(this).hasClass('nonce-ignore') ){
            triggerFormUpdateNonce( $(this).parents(".form-has-nonce ") );
        }
    });
    $(".form-has-nonce textarea").keyup(function(){
        if( !$(this).hasClass('nonce-ignore') ){
            triggerFormUpdateNonce( $(this).parents(".form-has-nonce ") );
        }
    });
    


    //confirm box
    $(".trigger-confirm").click(function(event){
        var msg = $(this).data('confirmMessage');
        var func = $(this).data('confirmFunction');
        var r = confirm(msg);
        if (r == false) {
            event.preventDefault();
        } else {
            console.log(func);
            window[func]();
        }
    });

    //update user button
    $(".update-user-control").change(function(){

        var button = $(this).parent().siblings(".update-user");
        var type = $(this).val();
        
        if( type !== typeof undefined && type !== false && type !== ''){

            var user_id = $(button).data('userId');

            $(button).prop("href","/admin/manage-users/?approve="+user_id+"&utype="+type);
            $(button).removeClass("disabled");
        } else {
            $(button).prop("href","javascript:void(0)");
            $(button).addClass("disabled");
        }
    }); 

    //user collapse causing min size adjustment to users table
    $("#manage-users-primary >.panel").on("shown.bs.collapse", function () {
        if( $(window).width() < 600 ){
            $(this).animate({"width":"750px"});
            $(this).addClass("active-animation");
        }
    }); 
    $("#manage-users-primary >.panel").on('hidden.bs.collapse', function () {
        if( $(window).width() < 800 &&  $(this).hasClass("active-animation") ){
            $(this).animate({"width":"100%"});
            $(this).removeClass("active-animation");
        }
    }); 

    //add new attribute button verification (settings page)
    $("#settings-new_attr_add_btn").click(function(){

        //check that type is selected
        var name = $("#settings-new_attr_name").val();
        var id = $("#settings-new_attr_id").val();
        var type = $("#settings-new_attr_type").val();
        var cookie_value = {"id":id,"name":name,"type":type,"options":""};
        var attrID = $.now();

        //check if values are set
        if( ( name !== typeof undefined && name !== false && name !== '' ) && ( id !== typeof undefined && id !== false && id !== '' ) && ( type !== typeof undefined && type !== false && type !== '' ) ){
            
            $("#settings-controls").submit();

        } else {
      
            console.log("Missing Options");
            alert('You must fill in all the fields before you can save the values');
            
        }
    });    

    //add new attribute multiple choice option
    $("#new-attr-option-btn").click(function(){
        var DBVal = $("#settings-attr-option-key-new").val().toLowerCase().replace(/ /g, '_').replace(/'/g, '');
        var DisplayVal = $("#settings-attr-option-value-new").val().replace(/'/g, '');
        
        //verify they are filled in
        if( ( DisplayVal !== typeof undefined && DisplayVal !== false && DisplayVal !== '' )  && ( DBVal !== typeof undefined && DBVal !== false && DBVal !== '' ) ){

            var newVal = "'"+DBVal+"':'"+DisplayVal+"',";

            //update the trigger field
            $("input[name='attr-options-updated']").val("true");

            //add new option to compiled field
            $("input[name='attr-options-compiled']").val( $("input[name='attr-options-compiled']").val()+newVal);

            //prevent a new page from loading
            event.preventDefault();

            //add to table
            $("#attr-options-display-table tr:last").after("<tr><td>"+DBVal+"</td><td>"+DisplayVal+"</td><td><a href='#' class='btn btn-danger remove-attr-option-button' onclick='removeAttributeOption(this,\""+DBVal+"\",\""+DisplayVal+"\")'>Remove</a></td></tr>");

            $("#settings-attr-option-key-new").val("");
            $("#settings-attr-option-value-new").val("");

        } else {
            event.preventDefault();
            alert('You must supply both a database value and a display value to add a new option.');
        }
    }); 

    //add new attribute data tag
    $("#new-attr-data-btn").click(function(){
        var DBVal = $("#settings-attr-data-key-new").val().toLowerCase().replace(/ /g, '-');
        var DisplayVal = $("#settings-attr-data-value-new").val();
        
        //verify they are filled in
        if( ( DisplayVal !== typeof undefined && DisplayVal !== false && DisplayVal !== '' )  && ( DBVal !== typeof undefined && DBVal !== false && DBVal !== '' ) ){

            var newVal = "'"+DBVal+"':'"+DisplayVal+"',";

            //update the trigger field
            $("input[name='attr-data-updated']").val("true");

            //add new option to compiled field
            $("input[name='attr-data-compiled']").val( $("input[name='attr-data-compiled']").val()+newVal);

            //prevent a new page from loading
            event.preventDefault();

            //add to table
            $("#attr-data-display-table tr:last").after("<tr><td>"+DBVal+"</td><td>"+DisplayVal+"</td><td><a href='#' class='btn btn-danger remove-attr-data-button' onclick='removeAttributeData(this,\""+DBVal+"\",\""+DisplayVal+"\")'>Remove</a></td></tr>");

            $("#settings-attr-data-key-new").val("");
            $("#settings-attr-data-value-new").val("");

        } else {
            event.preventDefault();
            alert('You must supply both a data key and value to add a new data attribute.');
        }
    });

    //javascript buttons
    $(".javascript-button").click(function(){
        window.location.href = $(this).data('href');
    });

    //auto expand the positons table by events
    $("#positions-by-events-primary >.panel").on('hidden.bs.collapse', function () {
        if( $(window).width() < 800 &&  $(this).hasClass("active-animation") && !$(this).hasClass("no-slide") ){
            $(this).animate({"width":"100%"});
            $(this).removeClass("active-animation");
        }
    }); 
    $("#positions-by-events-primary >.panel").on("shown.bs.collapse", function () {
        if( $(window).width() < 600  && !$(this).hasClass("no-slide") ){
            $(this).animate({"width":"750px"});
            $(this).addClass("active-animation");
        }
    }); 

    //verify double passwords match
    $("#settings-update_pass").click(function(){
        event.preventDefault();
        verifyPassMatch( 'settings-new_password1', 'settings-new_password2' );
    });

    //new profile image trigger
    $("#new-profile-img-trigger").click(function(){
        event.preventDefault();
        console.log('triggered new profile image container');

        if( $("#new-profile-img-upload").hasClass("exposed") ){
            $("#new-profile-img-upload").slideUp('slow', function() {
               $(this).removeClass("exposed");
               $("#new-profile-img-trigger").empty();
               $("#new-profile-img-trigger").prepend("Upload New Photo");
               $("#new-profile-img-trigger").removeClass('btn-danger');
               $("#new-profile-img-trigger").addClass('btn-primary');
            });
        } else {
            $("#new-profile-img-upload").slideDown('slow', function() {
               $(this).addClass("exposed");
               $("#new-profile-img-trigger").empty();
               $("#new-profile-img-trigger").prepend("Cancel");
               $("#new-profile-img-trigger").removeClass('btn-primary');
               $("#new-profile-img-trigger").addClass('btn-danger');
            });
        }
    });

    //require password 2 on password 1 entry
    $("#settings-profile-password1").keyup(function() {
        verifyProfilePassMatch("settings-profile-password1","settings-profile-password2")
    });
    $("#settings-profile-password2").keyup(function() {
        verifyProfilePassMatch("settings-profile-password1","settings-profile-password2")
    });
    $("#settings-profile-password1").change(function() {
        verifyProfilePassMatch("settings-profile-password1","settings-profile-password2")
    });
    $("#settings-profile-password2").change(function() {
        verifyProfilePassMatch("settings-profile-password1","settings-profile-password2")
    });

    //require password 2 on password 1 entry
    $("#settings-password").keyup(function() {
        verifyProfilePassMatch("settings-password","settings-password_repeat")
    });
    $("#settings-password_repeat").keyup(function() {
        verifyProfilePassMatch("settings-password","settings-password_repeat")
    });
    $("#settings-password").change(function() {
        verifyProfilePassMatch("settings-password","settings-password_repeat")
    });
    $("#settings-password_repeat").change(function() {
        verifyProfilePassMatch("settings-password","settings-password_repeat")
    });

    //random string generation for volunteer creation
    $("#generate-random-pass").click(function(){

        $("#settings-password").empty();
        $("#settings-password_repeat").empty();

        var password = generateRandomString(12);
        var message = '<div class="col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3"><div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button><strong>Generated Password:</strong> '+password+'</div></div>';
        
        $("#settings-password").val( password );
        $("#settings-password_repeat").val( password );

        $("#main-body-inner").prepend( message );

        $("#volunteer_update").val("true");

    });

    //auto size the progress bars
    $(".progress-container").each(function(){
        //get size
        var size = $(this).parent().innerWidth() - 20;
        $(this).find(".c100").css("font-size",size+"px");
    });

    //add new position notification
    $("#add-custom-notification").click(function(){

        //get the variables
        var date = $("#new-notification-date").find("select").val();
        var message = $("#new-notification-msg").find("select").val();
        var recipients = $("#new-notification-recip").find("select").val();

        //add form classes to let user know there are missing values
        if( date == '' ){
            $("#new-notification-date").find(".form-group").removeClass("has-success");
            $("#new-notification-date").find(".form-group").addClass("has-error");
        } else {
            $("#new-notification-date").find(".form-group").removeClass("has-error");
            $("#new-notification-date").find(".form-group").addClass("has-success");
        }
        if( message == '' ){
            $("#new-notification-msg").find(".form-group").removeClass("has-success");
            $("#new-notification-msg").find(".form-group").addClass("has-error");
        } else {
            $("#new-notification-msg").find(".form-group").removeClass("has-error");
            $("#new-notification-msg").find(".form-group").addClass("has-success");
        }
        if( recipients == '' ){
            $("#new-notification-recip").find(".form-group").removeClass("has-success");
            $("#new-notification-recip").find(".form-group").addClass("has-error");
        } else {
            $("#new-notification-recip").find(".form-group").removeClass("has-error");
            $("#new-notification-recip").find(".form-group").addClass("has-success");
        }

        //check that values are filled in
        if( date == '' || message == '' || recipients == '' ){

            alert('You must enter all fields');

        } else {

            //unset form classes
            $("#new-notification-date").val('');
            $("#new-notification-date").find(".form-group").removeClass("has-error");
            $("#new-notification-date").find(".form-group").removeClass("has-success");

            $("#new-notification-msg").val('');
            $("#new-notification-msg").find(".form-group").removeClass("has-error");
            $("#new-notification-msg").find(".form-group").removeClass("has-success");

            $("#new-notification-recip").val('');
            $("#new-notification-recip").find(".form-group").removeClass("has-error");
            $("#new-notification-recip").find(".form-group").removeClass("has-success");

            //set the object tools variables            
            var key_val = $("#add-custom-notification").data("nextId");
            var next_num = key_val + 1;
            var date_f = $("#new-notification-date").find("select option:selected").text();
            var message_f = $("#new-notification-msg").find("select option:selected").text();
            var recipients_f = $("#new-notification-recip").find("select option:selected").text();
            if( $("#new-require-confirm").find("input[type='checkbox']").is(':checked') ){
                var req_conf = 'true';
                var req_conf_f = 'REQUIRED';
                var req_class = 'danger';
            } else {
                var req_conf = 'false';
                var req_conf_f = 'NOT REQUIRED';
                var req_class = 'success';
            }
            var row = "<tr><td>"+next_num+"</td><td>"+date_f+"</td><td>"+message_f+"</td><td>"+recipients_f+"</td><td class='"+req_class+"'>"+req_conf_f+"</td><td><span class='btn btn-danger rmv-parent-notif'>Remove</span></tr>";
            var existing = $("#custom-notifications-json").val();
            var obj_val = "{'time':'"+date+"','msg':'"+message+"','rec':'"+recipients+"','req_conf':'"+req_conf+"'}";

            console.log( existing.toLowerCase().indexOf( obj_val ));

            //check for existing in table already
            if( existing.toLowerCase().indexOf(obj_val) <= 0){

                //add the row to the table
                $("#position-custom-notifications > tbody").append( row );

                //update the next number trigger
                $("#add-custom-notification").data('nextId',next_num);

                //add new value to existing
                new_value = existing.substring(1, existing.length-1)+","+obj_val;
                
                //set the new value
                $("#custom-notifications-json").val( "["+new_value+"]" );

                //update nonce
                $("#position-form-nonce").val("true");
            }               
            
        }

    });

    //remove notification
    $(".rmv-parent-notif").click(function(){
        var string = $(this).data('string');
        var input_value = $("#custom-notifications-json").val().replace( ","+string, '' ).replace( string+",", '');
        $("#custom-notifications-json").val( input_value );
        $(this).parents("tr").remove();
        $("#position-form-nonce").val("true");
    });

    //change the end date on start date change
    $("#settings-position-start-date").change(function(){
        var setVal = $(this).val();
        $("#settings-position-end-date").val( setVal );
    });

    //page scroller
    $(".page-scroll").click(function(){
        event.preventDefault();
        var element = $(this).data('element');
        $.scrollTo(element,800);
    });

    $(".remove-volunteer-from-role").click(function(){

        var msg = $(this).data('confirmMessage');
        var r = confirm(msg);

        //if user approved removal
        if (r == true) {

            $(this).addClass("disabled");
            $("#position-form-nonce").val("true");

            //get the role ID
            var input = $(this).siblings("input");

            //update the input field to trigger DB update
            $(input).val( $(this).data("roleId") );

            //empty the table row
            $(this).parents("tr").find(".role-name").empty();
            $(this).parents("tr").find(".role-name").prepend('No Volunteer <span class="label label-danger">unfilled</span>');
        }
    });

    $("#duplicate_position").click(function(){
        $("#sub-vol-modal").attr("onclick","submitDuplicatePositionForm()");
    });

    //highlight the required fields on the profile page
    $("#profile-settings input").each(function(){
        if( $(this).prop('required') && !$(this).val() ){
            $(this).addClass('required-glow');
        }
    });

});

function cancelPositionDuplication(){
    $("#duplicate-position-trigger").val("false");
    $("#sub-vol-modal").removeAttr("onclick",'');
    console.log("cancelled duplication");
}

function duplicatePositionFormField(id){
    $("#duplicate-position-trigger").val("true");
    $("#position-form-nonce").val("true");
    $("#close-vol-modal").attr("onclick","cancelPositionDuplication()");

    if( $("#volunteerModal").find("#settings-"+id).is(':checkbox') ){
        //controls chekboxes
        $("#position-settings").find("#settings-"+id).prop("checked", $("#volunteerModal").find("#settings-"+id).prop("checked") );
    } else {
        //get value for input
        var val = $("#volunteerModal").find("#settings-"+id).val();
        //apply value
        $("#position-settings").find("#settings-"+id).val( val );
    }
}

function submitDuplicatePositionForm(){
    $('#volunteerModal').modal('hide')
    $("#position-settings").submit();
}