<?php
/**
 * @Author: Brandon Thomas
 * @Date:  2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 00:13:38
 */

class settings {

	public function __construct(){

		$this->title = 'Settings';
		$this->subtitle = 'Control overall system settings from this panel';
		$this->pageMessage = null;

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Settings',
			'url' => '/settings',
			'meta_title' => 'VMS System Settings',
			'meta_description' => 'Make changes to system settings from this screen.',
		);

		//check if attribute is set
		if( isset( $_GET['function'] ) ){

			//get the attribute
			$attr = get_attribute_by_id( $_GET['function'] );

			//set object level vars
			$this->attr_id = $attr['id'];
			$this->attr_name = $attr['name'];
			$this->attr_dom_id = $attr['dom_id'];
			$this->attr_type = $attr['type'];
			$this->attr_default = $attr['default'];
			$this->attr_placeholder = $attr['placeholder'];
			$this->attr_options_option_id = $attr['options'];
			$this->attr_data_option_id = $attr['data'];
			$this->attr_created = $attr['created'];

			//set required
			if( $attr['required'] ){
				$this->attr_required = true;
			} else {
				$this->attr_required = false;
			}

			//set page values
			$this->title = $this->attr_name . ' - Attribute Settings';
			$this->subtitle = 'Change options for the ' . $this->attr_name . ' position attribute';

			//page meta and breadcrumb information
			$this->pageMeta[1] = array(
				'name' => $this->attr_name . ' - Attribute Settings',
				'url' => '/settings/' . $_GET['function'],
				'meta_title' => 'VMS ' . $this->attr_name . ' - Atttribute Settings',
				'meta_description' => 'Change options for the ' . $this->attr_name . ' position attribute',
			);
		}

		//check for registration key update - must be on instantiation to allow for updates to the field when outside of valid state
		if( isset( $_POST['settings-software_key'] ) ){

			//update the database
			updateOption('software_key',$_POST['settings-software_key']);

			//set the vookie to trigger reverification
			setcookie('pdval','verify',0,'/');
		}
	}

	public function checkSubmission(){

		//verify license
		$verify = verifyLicense();

		//check for delete
		if( isset( $_GET['remv'] ) && $_GET['remv'] == 'true' ){
			removeAtrribute( $_GET['function'] );
			submission_redirect( '/settings#settings-position-attributes' );
		}

		//check for attribute change
		if( isset( $_POST['attribute-settings-updated'] ) && $_POST['attribute-settings-updated'] == 'true' ){

			//set the id
			$values['id'] = $_GET['function'];

			//set the fields to look for
			$fields = array('name','dom_id','type','default','placeholder');

			//set the value array
			foreach( $fields as $field ){
				if( !empty( $_POST[ 'settings-attr-' . $field ] ) && $_POST[ 'settings-attr-' . $field ] !== '' ){
					$values[ $field ] = $_POST[ 'settings-attr-' . $field ];
				} else {
					$values[ $field ] = null;
				}
			}

			//check for required
			if( isset( $_POST['settings-attr-require-onoff'] ) ){
				$values['required'] = 1;
			} else {
				$values['required'] = 0;
			}			
			
			//update the attribute
			updateAttribute( $values );
			submission_redirect( '/settings/' . $_GET['function']);
		}

		//check for new attribute submission from settings page
		if( isset( $_POST['new-attr-from-core'] ) && $_POST['new-attr-from-core'] == 'true' ){

			if( isset( $_POST['settings-new_attr_name'] ) ){
				
				//set the vars
				$name = $_POST['settings-new_attr_name'];
				$id = $_POST['settings-new_attr_id'];
				$type = $_POST['settings-new_attr_type'];

				//set the new value array
				$newAttrVal = array( 'name'=>$name, 'dom_id'=>$id, 'type'=>$type );

				//run insert
				$update = updateAttribute($newAttrVal);

				//reload the page
				submission_redirect( '/settings/' . $update);
			}

			submission_redirect( '/settings/' . $_GET['function'] );
		}

		//check for attribute options addition
		if( isset( $_POST['attr-options-updated'] ) && $_POST['attr-options-updated'] == 'true' ){

			//get the options ID
			$attr = get_attribute_by_id( $_GET['function'] );
			$options_id = $attr['options'];

			//set the options attribute
			$newOptionVal = '{' . rtrim($_POST['attr-options-compiled'], ',') . '}';
			$newOptionVal = str_replace( '\'', '"', $newOptionVal );
			updateOption('attr_options', $newOptionVal, $options_id);

			//redirect after update to prevent resubmission
			submission_redirect( '/settings/' . $_GET['function'] );
		}
		//check for attribute data update
		if( isset( $_POST['attr-data-updated'] ) && $_POST['attr-data-updated'] == 'true' ){

			//get the options ID
			$attr = get_attribute_by_id( $_GET['function'] );
			$data_id = $attr['data'];

			//update the data attribute
			$newOptionVal = '{' . rtrim($_POST['attr-data-compiled'], ',') . '}';
			$newOptionVal = str_replace( '\'', '"', $newOptionVal );
			updateOption('attr_data', $newOptionVal, $data_id);

			//redirect after update to prevent resubmission
			submission_redirect( '/settings/' . $_GET['function'] );
		}

		//check for a settings update
		if( isset( $_POST['settings-changed_settings'] ) && $_POST['settings-changed_settings'] == 'true' ) {

			//lockout duration
			updateOption('lockout_duration',$_POST['settings-lockout_duration']);

			//check for development mode
			if( isset( $_POST['settings-development_mode'] ) ){
				updateOption('development_mode','on');
			} else {
				updateOption('development_mode','off');
			}

			//redirect after update to prevent resubmission
			submission_redirect( '/settings/' );
		}
			
	}

	public function display(){

		//verify license
		$verify = verifyLicense();

		//check if attribute is set
		if( isset( $_GET['function'] ) ){				

			$html = $this->attrSettingsPage();

		} else {

			//ouput the core settings page
			$html = $this->coreSettingsPage();
		}
			

		return $html;
	}

	public function attrSettingsPage(){

		//start html output
		$html = '<div class=" row-fluid">';
		$html .= '<form id="attr-settings" action="' . _ROOT_ . '/settings/' . $_GET['function'] . '" method="post" class="form form-has-nonce " role="form" data-controller-id="attribute-settings-updated">';
		$html .= '<h2 class="h3 bg-info text-info">Core Settings</h2>';

		//created date time
		//$html .= '<p>Created: <code>' . date('l, F jS Y - g:m a T', strtotime( $this->attr_created ) ) . '</code></p>';

		//hidden field to trigger update (nonce)
		$html .= '<input id="attribute-settings-updated" type="hidden" name="attribute-settings-updated" value="false">';

		//name field
		$options = array(
				'input_type'=>'text',
				'input_value'=>$this->attr_name,
				'class'=>'',
				'disable'=>false,
				'required'=>true,
				'input_addon_start'=>'Name',
				'help_title'=>'What is this?',
				'help_text'=>'Enter a name for the field. This should be different than other attribute names you have used and should be formatted in sentence case.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('attr-name', $options ) . '</div></div>';

		//type field
		$options = array(
				'input_type'=>'select',
				'options'=>array('text'=>'Text Input','number'=>'Number Input','date'=>'Date Input','multiple_choice'=>'Multiple Choice'),
				'check_value'=>$this->attr_type,
				'input_addon_start'=>'Type',
				'class'=>'',
				'disable'=>false,
				'required'=>true,
				'help_title'=>'What is this?',
				'help_text'=>'This is a selection of the field type. Different field types will determine how the value is entered by the user and the type of limitations you can invoke on a position.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-type', $options ) . '</div></div>';

		//dom ID field
		$options = array(
				'input_type'=>'text',
				'input_value'=>$this->attr_dom_id,
				'input_addon_start'=>'DOM ID',
				'class'=>'',
				'disable'=>false,
				'required'=>true,
				'help_title'=>'What is this?',
				'help_text'=>'The \'DOM\' or \'Document Object Model\' is a grouping of expressions that enable the traversing of the document or HTML page. These values are used in CSS, JavaScript and more. This ID must be unique to the page it is being displayed on and so it is generally better to make sure it is unique to the entire application. This field should not have any spaces and should be in all lowercase.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-dom_id', $options ) . '</div></div>';

		//clear row
		$html .= '<div class="clearfix hidden-xs hidden-sm" style="height:10px;"></div>';

		//default field
		$options = array(
				'input_type'=>'text',
				'input_value'=>$this->attr_default,
				'input_addon_start'=>'Default Value',
				'placeholder'=>'Default Value',
				'class'=>'',
				'disable'=>false,
				'required'=>false,
				'help_title'=>'What is this?',
				'help_text'=>'If this value is supplied then a default value wll be applied to the sign up and position creation process.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-6 small-top-marg"><div class="input-group">' . createFormInput('attr-default', $options ) . '</div></div>';

		//placeholder field
		$options = array(
				'input_type'=>'text',
				'input_value'=>$this->attr_placeholder,
				'input_addon_start'=>'Placeholder Value',
				'placeholder'=>'This is a Placeholder',
				'class'=>'',
				'disable'=>false,
				'required'=>false,
				'help_title'=>'What is this?',
				'help_text'=>'A placeholder is a value that is inserted into an input field and acts as an example. The value is overwritten as soon as the user beigns to interact with the field and is not included in page submissions if left unchanged.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-6 small-top-marg"><div class="input-group">' . createFormInput('attr-placeholder', $options ) . '</div></div>';

		//clear row
		$html .= '<div class="clearfix"></div>';

		//required
		$options = array(
				'input_type'=>'checkbox',
				'input_value'=>$this->attr_placeholder,
				'input_addon_start'=>'Placeholder Value',
				'placeholder'=>'This is a Placeholder',
				'class'=>'',
				'disable'=>false,
				'required'=>false,
				'help_title'=>'What is this?',
				'help_text'=>'A placeholder is a value that is inserted into an input field and acts as an example. The value is overwritten as soon as the user beigns to interact with the field and is not included in page submissions if left unchanged.'
			);
		
		//on off switch
		$html .= '<div class="col-xs-12 col-md-4 small-top-marg form-group">';
		$html .= '	<label>Required</label>';
		$html .= '	<div class="input-group">';
		$html .= '		<div class="onoffswitch">';
		$html .= '			 <input type="checkbox" id="settings-attr-require-onoff" name="settings-attr-require-onoff" class="onoffswitch-checkbox"  '. returnChecked( $this->attr_required, true ) . '>';
		$html .= '			 <label class="onoffswitch-label" for="settings-attr-require-onoff">';
		$html .= '			 	<span class="onoffswitch-inner"></span>';
		$html .= '		 		<span class="onoffswitch-switch"></span>';
		$html .= '			</label>';
		$html .= '		</div>';
		$html .= '	</div>';
		$html .= '</div>';
		$html .= '<div class="clearfix"></div>';
		//$html .= '<div class="form-group col-xs-12 col-md-6 small-top-marg"><div class="input-group">' . '</div></div>';

		//check for options on multiple choice attributes 
		if( $this->attr_type == 'multiple_choice' ){

			//get the attribute options
			$attr_options_json = get_option_by_id( $this->attr_options_option_id );

			//start html output
			$html .= '<h2 class="h3 bg-info text-info">Options</h2>';

			//new input value field
			$options = array(
					'input_type'=>'text',
					'placeholder'=>'example_value',
					'input_addon_start'=>'DB Value',
					'help_title'=>'What is this?',
					'help_text'=>'This is the value that will be stored in the database. It should be in all lowercase,'
				);
			$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-option-key-new', $options ) . '</div></div>';

			//new input Display value field
			$options = array(
					'input_type'=>'text',
					'placeholder'=>'Example Value',
					'input_addon_start'=>'Display Value',
					'help_title'=>'What is this?',
					'help_text'=>'This is the value that is shown to the user when making a selection and should be formatted in a norma sentence case.'
				);
			$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-option-value-new', $options ) . '</div></div>';

			//Add new option button
			$html .= '<div class="form-group col-xs-6 col-xs-offset-3 col-md-3 col-md small-top-marg-offset-1"><div class="input-group"><button class="btn btn-primary form-control" id="new-attr-option-btn">Add New Option</button></div></div>';

			//update hidden fields
			$options_form_value = $attr_options_json['option_value'];
			$options_form_value = str_replace('"', '\'', $options_form_value );
			$options_form_value = str_replace( array('{','}'), null, $options_form_value );
			if( strpos( $options_form_value, ',' ) ){
				$options_form_value .= ',';
			}

			$html .= '<input type="hidden" name="attr-options-compiled" value="' . $options_form_value . '">';
			$html .= '<input type="hidden" name="attr-options-updated" value="false">';

			//existing options table
			$html .= '<div class="container">';
			$html .= '<table id="attr-options-display-table" class="table table-bordered table-striped table-condensed table-hover">';
			$html .= '<tr><th>Database</th><th>Display</th><th></th></tr>';

			//if there are options set
			if( !empty( $options_form_value ) ){

				//unserialize the options
				$attr_options = json_decode( $attr_options_json['option_value'] );
				foreach( $attr_options as $key => $value ){
					$html .= '<tr><td>' . $key . '</td><td>' . $value . '</td><td><a class="btn btn-danger remove-attr-option-button" onclick="removeAttributeOption(this,\'' . $key . '\',\'' . $value . '\')">Remove</a></td></tr>';

				}
			}

			//close table
			$html .= '</table>';
			$html .= '</div>';

			//clear row
			$html .= '<div class="clearfix" style="height:10px;"></div>';
		}

		//data fields
		$html .= '<h2 class="h3 bg-info text-info">Data Tags</h2>';
		$html .= '<p class="help-block">Data tags are used to trigger javascript events. These should only be edited by an administrator.</p>';

		//new data fields
		$options = array(
				'input_type'=>'text',
				'placeholder'=>'My Data Tag',
				'input_addon_start'=>'Data Key',
				'help_title'=>'What is this?',
				'help_text'=>'This value is used to create an HTML5 data attribute. Values should contain only spaces or hyphens and no special characters.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-data-key-new', $options ) . '</div></div>';

		//new data display value field
		$options = array(
				'input_type'=>'text',
				'placeholder'=>'My Data Value',
				'input_addon_start'=>'Data Value',
				'help_title'=>'What is this?',
				'help_text'=>'This value will be passed to the HTML data attribute.'
			);
		$html .= '<div class="form-group col-xs-12 col-md-4 small-top-marg"><div class="input-group">' . createFormInput('attr-data-value-new', $options ) . '</div></div>';

		//add new data attr button
		$html .= '<div class="form-group col-xs-6 col-xs-offset-3 col-md-4 col-md-offset-0 small-top-marg"><button class="btn btn-primary form-control" id="new-attr-data-btn full-width">Add New Data Attr</button></div>';

		//existing data attribute
		if( !empty( $this->attr_data_option_id ) ){

			//get the attribute data
			$attr_data_json = get_option_by_id( $this->attr_data_option_id );

			//update hidden fields
			$data_form_value = $attr_data_json['option_value'];
			$data_form_value = str_replace('"', '\'', $data_form_value );
			$data_form_value = str_replace( array('{','}'), null, $data_form_value );
			if( strpos( $data_form_value, ',' ) ){
				$data_form_value .= ',';
			}

			$html .= '<input type="hidden" name="attr-data-compiled" value="' . $data_form_value . '">';
			$html .= '<input type="hidden" name="attr-data-updated" value="false">';

			//existing data table
			$html .= '<div class="col-xs-12">';
			$html .= '<p class="h4">Existing Data Tags</p>';
			$html .= '<table id="attr-data-display-table" class="table table-bordered table-striped table-condensed table-hover">';
			$html .= '<tr><th>Database Value</th><th>Display Value</th><th>Control</th></tr>';

			//if there are data set
			if( !empty( $data_form_value ) ){

				//unserialize the data
				$attr_data = json_decode( $attr_data_json['option_value'] );
				foreach( $attr_data as $key => $value ){
					$html .= '<tr><td>' . $key . '</td><td>' . $value . '</td><td><a class="btn btn-danger remove-attr-option-button" onclick="removeAttributeData(this,\'' . $key . '\',\'' . $value . '\')">Remove</a></td></tr>';
				}
			}

			//close table
			$html .= '</table>';
			$html .= '</div>';
		}



		//clear row
		$html .= '<div class="clearfix" style="height:10px;"></div>';

		//save changes
		$html .= '<hr>';
		$html .= '<div class="col-xs-8 col-xs-offset-2 col-md-6 col-md small-top-marg-offset-3 input-group"><button class="btn btn-success form-control form-submit" data-form-id="attr-settings">Save Changes</button></div>';
		

		//close container
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public function coreSettingsPage(){

		//create group end 
		$end_group 	= '</div><!--END GROUP-->';

		//blank $html
		$html = '';

		//start options form
		$html .= '<div class="row-fluid">';
		$html .= '	<form id="settings-controls" class="form-horizontal" data-controller-id="settings-changed_settings" role="form" action="' . _ROOT_ . '/settings" method="post">';

		//add hidden field
		$options = array('input_type'=>'hidden','input_value'=>'false');
		$html .= createFormInput('changed_settings', $options );

		$html .= '	<h2 class="h3 bg-info text-info">Registration Settings</h2>';
		$html .= '	<div class="col-md-12 no-padd">';

			//create site url field
			$html .= '		<div class="form-group col-xs-12 col-md-6 no-padd">';
		$options = array('label'=>'Registered URL:','label_class'=>'col-xs-4','disabled'=>true,'field_wrap'=>array('<div class="col-xs-8">','</div>'));
		$html .= createFormInput('site_url', $options ) . $end_group;

		//registration key
		$html .= '		<div class="form-group col-xs-12 col-md-6 no-padd">';
		$options = array('label'=>'Registration Key:','label_class'=>'col-xs-4','required'=>true,'field_wrap'=>array('<div class="col-xs-8">','</div>'));
		$html .= createFormInput('software_key', $options) . $end_group;

		//column / row shift
		$html .= '	</div><!--CLOSE COLUMN-->';
		$html .= '	<div class="clearfix"></div>';

		//start general settings
		$html .= '	<h2 class="h3 bg-info text-info">General Settings</h2>';
		$html .= '	<div class="col-xs-12 no-padd">';

		//lockout_duration
		$html .= '		<div class="form-group col-xs-12 col-md-6 no-padd">';
		$options = array('input_type'=>'number','label'=>'Password Lockout:','label_class'=>'col-xs-4','field_wrap'=>array('<div class="col-xs-8"><div class="input-group">', '</div></div>'),'input_addon_end'=>'Mins','placeholder'=>'30','help_text'=>'This is the time in minutes that a user will be locked out of their account after 5 failed attempts to login. This should be set to no less than 5 minutes to help prevent brute force attacks','help_title'=>'Failed Password Lockout');
		$html .= createFormInput('lockout_duration', $options) . $end_group;

		//lockout_duration
		$html .= '		<div class="form-group col-xs-12 col-md-6 no-padd">';
		$options = array('input_type'=>'checkbox','label'=>'Development Mode:','label_class'=>'col-xs-4','check_value'=>'on');
		$html .= createFormInput('development_mode', $options) . $end_group;

		//column / row shift
		$html .= '	</div><!--CLOSE COLUMN-->';
		$html .= '	<div class="clearfix"></div>';

		//start the position attributes
		$html .= '	<h2 class="h3 bg-info text-info" id="settings-position-attributes">Position Attributes</h2>';
		$html .= '	<p class="help-block">Add and configure attributes that are available when creating positions to help narrow the applicants.</p>';

		

		//new attribute
		$html .= '	<div class="col-sm-12 no-padd" id="attribute-add-from-settings" data-controller-id="new-attr-from-core">';
		$html .= '		<div class="well">';
		$html .= '			<h3 class="h4">Add New Attribute</h3>';
		$html .= '			<input type="hidden" id="new-attr-from-core" name="new-attr-from-core" value="false">';
		
		//attribute name
		$html .= '		<div class="col-xs-12 col-md-3">';
		$options = array('input_type'=>'text','field_wrap'=>array('<div class="form-group input-group has-error">', '</div>'),'input_addon_start'=>'Name:','placeholder'=>'My Attribute');
		$html .= createFormInput('new_attr_name', $options) . $end_group;

		//attribute ID
		$html .= '		<div class="col-xs-12 col-md-3">';
		$options = array('input_type'=>'text','field_wrap'=>array('<div class="form-group input-group has-error">', '</div>'),'input_addon_start'=>'Unique ID:','placeholder'=>'my_attribute');
		$html .= createFormInput('new_attr_id', $options) . $end_group;

		//attribute type
		$html .= '		<div class="col-xs-12 col-md-3">';
		$inputs = array('text'=>'Text Input','number'=>'Number Input','date'=>'Date Input','multiple_choice'=>'Multiple Choice');
		$options = array('input_type'=>'select','field_wrap'=>array('<div class="form-group input-group has-error">', '</div>'),'options'=>$inputs,'input_addon_start'=>'Type:');
		$html .= createFormInput('new_attr_type', $options) . $end_group;

		//add attribute button
		$html .= '		<div class="col-xs-12 col-md-3">';
		$options = array('input_type'=>'button','input_value'=>'Add Attribute','class'=>'btn btn-info');
		$html .= createFormInput('new_attr_add_btn', $options) . $end_group . '<div class="clearfix"></div>';

		//close add attribute
		$html .= '		</div><!--END NEW ATTRIBUTE WELL-->';
		$html .= '		<div class="clearfix"></div>';

		//existing attributes container
		$html .= '	<div class="col-xs-12">';

		//get attrs
		$attributes = getAttributes();
		foreach( $attributes as $key=>$attr ){

			//set color code
			$html .= '<div class="col-xs-12 col-md-3">';
			$html .= '	<div class="alert alert-info attr-' . $attr['type'] . ' settings-attr">';
			$html .= '		<table>';
			$html .= '			<tr><td>ID #:</td><td>' . $attr['id'] . '</td></tr>';
			$html .= '			<tr><td>Name:</td><td>' . $attr['name'] . '</td></tr>';
			$html .= '			<tr><td>Type:</td><td>' . strtoupper( $attr['type'] ) . '</td></tr>';
			$html .= '		</table>';
			$html .= '		<br>';
			$html .= '		<a href="' . _PROTOCOL_ . _ROOTURL_ . '/settings/' . $attr['id'] . '" class="btn btn-default col-xs-5">Edit</a>';
			$html .= '		<a href="' . _PROTOCOL_ . _ROOTURL_ . '/settings/' . $attr['id'] . '?remv=true" class="trigger-confirm btn btn-danger col-xs-5 col-xs-offset-2" data-confirm-message="Are you sure you would like to remove this attribute? This will not be able to be undone and will als remove all associated settings.">Remove</a>';
			$html .= '		<div class="clearfix"></div>';
			$html .= '	</div>';
			$html .= '</div>';
		}

		$html .= '	</div><!--CLOSE EXISTING ATTRIBUTES-->';

		//column / row shift
		$html .= '</div><!--CLOSE COLUMN-->';
		$html .= '<div class="clearfix"></div>';

		//extra spacing before save button
		$html .= '<br>';

		//submit form
		$options = array('input_type'=>'submit','input_value'=>'Update Settings','class'=>'btn btn-success','field_wrap'=>array('<div class="col-xs-12 text-center col-md-4 col-md-offset-4">', '</div>') );
		$html .= createFormInput('settings_submit_button', $options) . $end_group;	

		//close form and container
		$html .= '	</form>';
		$html .= '</div><!-- END CONTAINER -->';

		return $html;
	}

}
?>