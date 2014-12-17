	</body>
	<footer>
		<div class="modal fade" id="volunteerModal" tabindex="-1" role="dialog" aria-labelledby="VolunteerModal" aria-hidden="true" active-state="hidden">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		        <h4 class="modal-title">{TITLE}</h4>
		      </div>
		      <div id="modal-position-details" class="">
		      	<div id="modal-position-details-inner"></div>
		      	<div id="modal-position-controls" class="col-xs-12">
		      	<?php
		      		//create the button link urls
		      		$sign_up_url = create_return_url( _ROOTURL_, array('y','m','s'), array('user_type'=>'new_vol') );
		      		$sign_in_url = create_return_url( _ROOTURL_, array('y','m','s'), array('ret'=>'home') );

	      			//check if signed in
	      			if( checkForSession() ){
	      				//if user is signed in then give them a sign up button
	      				echo '<span class="col-xs-6 col-md-4 text-center">';
		    			echo '	<a href="" id="position-sign-up" type="button" class="btn btn-success">Volunteer!</a>';
		    			echo '</span>';
		      		} else {
		      			echo '<span class="col-xs-4 text-center">';
		      			echo '	<a href="' . _PROTOCOL_ . $sign_in_url . '" id="position-sign-in" class="btn btn-primary">Sign In</a><br>';
		      			echo '	<a href="' . _PROTOCOL_ . $sign_up_url . '" title="Don\'t have an account? Signing up is simple!">Create Account</a>';
		      			echo '</span>';
		      			echo '<span class="col-xs-4 text-center">';
		    			echo '	<a id="position-sign-up" type="button" class="disabled btn btn-success">Volunteer!</a>';
		    			echo '</span>';
		      		}

		      	?>	
		      		<span class="col-xs-4  text-center">
		        		<button id="hide-position-info" onClick="hidePositionInfo()" type="button" class="btn btn-danger">No Thanks</button>
		        	</span>
		        </div>
		        <div class="clearfix"></div>
		      </div>
		      <div class="modal-body">
		        <p>{SOME CONTENT ABOUT EACH POSITION}&hellip;</p>
		      </div>
		      <div class="modal-footer">
		        <button id="close-vol-modal" type="button" class="btn btn-default" data-dismiss="modal" data-target="#volunteerModal">Close</button>
		      </div>
		      <div id="position-form-container" class="hidden">
		      	<form id="position-sign-up" method="post">
		      		<input type="hidden" id="signup-form-user-id" name="user_ID" value="">
		      		<input type="hidden" id="signup-form-position-id" name="position_ID" value="">
		      	</form>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		<div class="darken-screen hidden"></div>
	</footer>
	<script src="//<?php echo _FRONTEND_URL_; ?>/js/fe_js.js" type="text/javascript"></script>
</html>