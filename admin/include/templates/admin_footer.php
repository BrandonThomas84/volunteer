			</div>
		</div>
	</body>
	<footer>
		<div class="modal fade" id="volunteerModal" tabindex="-1" role="dialog" aria-labelledby="VolunteerModal" aria-hidden="true">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		        <h4 class="modal-title">{TITLE}</h4>
		      </div>
		      <div class="modal-body">
		        <p>{SOME CONTENT ABOUT EACH POSITION}&hellip;</p>
		      </div>
		      <div class="modal-footer">
		        <button id="close-vol-modal" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		        <button id="sub-vol-modal" type="button" class="btn btn-primary">Save changes</button>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		<div class="modal fade" id="navModal" tabindex="-1" role="dialog" aria-labelledby="navModal" aria-hidden="true">
		  <div class="modal-dialog">
		    <div class="modal-content">
				<div class="modal-header bg-info">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<h4 class="modal-title">{TITLE}</h4>
				</div>
			   	<form id="nav-modal-form" action="" method="post">
			      <div class="modal-body">
			        <p>{SOME CONTENT ABOUT EDITING THE MENU ITEM}&hellip;</p>
			      </div>
			      <div class="modal-footer">
			        <button id="close-nav-modal" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			        <button id="sub-nav-modal" type="button" class="btn btn-primary">Save changes</button>
			      </div>
			  	</form>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		<div class="darken-screen hidden"></div>
	</footer>
	<?php include_once( __ROOTPATH__ . '/js/php_js.php' ); ?>
</html>