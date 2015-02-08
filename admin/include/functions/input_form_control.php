<?php

/**
CREATE FORM INPUT FIELDS
Input field default settings. Some of these values MUST be overwritten
* @var string 	option_name 				REQUIRED 			| calls the database option_name value
* @var string 	input_value					DEFAULT null 		| the display value for the field
* @var string 	input_type 					DEFAULT "text" 		| standard html input types
* @var string 	label 						DEFAULT null 		| adds label above the field
* @var string 	label_class					DEFAULT null 		| adds class to the label
* @var string 	placeholder 				DEFAULT null 		| acts as placeholder text
* @var string 	class 						DEFAULT null 		| list classes as they wil appear
* @var boolean 	disable 					DEFAULT false 		| whether or not the field is enabled
* @var boolean	required 					DEFAULT false 		| whether or not the field is required
* @var array 	option 						DEFAULT array() 	| options array for select types {value=>display,...}
* @var string 	check_value 				DEFAULT null 		| checks for selected value (checkboxes,radios,selects)
* @var string 	input_addon_start 			DEFAULT false 		| add bootstrap input addo to beginning
* @var string 	input_addon_start_type 		DEFAULT "default" 	| start addon color 
* @var string 	input_addon_end 			DEFAULT false 		| add bootstrap input addon to end
* @var string 	input_addon_end_type 		DEFAULT "default" 	| end addon color
* @var string 	input_button_start 			DEFAULT false 		| add bootstrap input button addon to beginning
* @var string 	input_button_start_type 	DEFAULT "default" 	| start button addon color
* @var string 	input_button_end 			DEFAULT false 		| add bootstrap input button addon to end
* @var string 	input_button_end_type 		DEFAULT "default" 	| end button addon color
* @var string 	help_title	 				DEFAULT null 		| if supplied renders as bubble title
* @var string 	help_text 					DEFAULT null 		| required to display help bubble
* @var array 	field_wrap 					DEFAULT (null,null)	| array([0]=>START,[1]=>END)
* @var array 	inline_style				DEFAULT null		| array([0]=>'height:100px',[1]=>'width:100px',...)
**/
function createFormInput( $option_name, $field_options ){

	//check for submitted vlues and add to the new input array in not set values are set as default values
	$newInput['label'] = ( isset( $field_options['label'] ) ? $field_options['label'] : false );
	$newInput['label_class'] = ( isset( $field_options['label_class'] ) ? $field_options['label_class'] : null );
	$newInput['input_type'] = ( isset( $field_options['input_type'] ) ? $field_options['input_type'] : 'text' );
	$newInput['placeholder'] = ( isset( $field_options['placeholder'] ) ? $field_options['placeholder'] : null );
	$newInput['class'] = ( isset( $field_options['class'] ) ? $field_options['class'] : null );
	$newInput['disabled'] = ( isset( $field_options['disabled'] ) && $field_options['disabled'] ? 'disabled' : false );
	$newInput['required'] = ( isset( $field_options['required'] ) && $field_options['required'] ? 'required' : false );
	$newInput['options'] = ( isset( $field_options['options'] ) ? $field_options['options'] : array() );
	$newInput['check_value'] = ( isset( $field_options['check_value'] ) ? $field_options['check_value'] : null );
	$newInput['input_addon_start'] = ( isset( $field_options['input_addon_start'] ) ? $field_options['input_addon_start'] : false );
	$newInput['input_addon_start_type'] = ( isset( $field_options['input_addon_start_type'] ) ? $field_options['input_addon_start_type'] : 'default' );
	$newInput['input_addon_end'] = ( isset( $field_options['input_addon_end'] ) ? $field_options['input_addon_end'] : false );
	$newInput['input_addon_end_type'] = ( isset( $field_options['input_addon_end_type'] ) ? $field_options['input_addon_end_type'] : 'default' );
	$newInput['input_button_start'] = ( isset( $field_options['input_button_start'] ) ? $field_options['input_button_start'] : false );
	$newInput['input_button_start_type'] = ( isset( $field_options['input_button_start_type'] ) ? $field_options['input_button_start_type'] : 'default' );
	$newInput['input_button_end'] = ( isset( $field_options['input_button_end'] ) ? $field_options['input_button_end'] : false );
	$newInput['input_button_end_type'] = ( isset( $field_options['input_button_end_type'] ) ? $field_options['input_button_end_type'] : 'default' );
	$newInput['help_title'] = ( isset( $field_options['help_title'] ) ? $field_options['help_title'] : 'What is this?' );
	$newInput['help_text'] = ( isset( $field_options['help_text'] ) ? $field_options['help_text'] : null );
	$newInput['min_value'] = ( isset( $field_options['min_value'] ) ? $field_options['min_value'] : null );
	$newInput['max_value'] = ( isset( $field_options['max_value'] ) ? $field_options['max_value'] : null );
	$newInput['pattern'] = ( isset( $field_options['pattern'] ) ? ' pattern="' . $field_options['pattern'] . '" ' : null );
	$newInput['allow_blank'] = ( isset( $field_options['allow_blank'] ) ? $field_options['allow_blank'] : false );
	$newInput['on_change'] = ( isset( $field_options['on_change'] ) ? ' onchange="' . $field_options['on_change'] . '" ' : null );
	$newInput['display_value'] = ( isset( $field_options['display_value'] ) ? $field_options['display_value'] : null );
	$newInput['inline_style'] = ( isset( $field_options['inline_style'] ) ? ' style="' . implode(';',$field_options['inline_style'] ) . '"' : null );

	//set field wraps
	$field_wrapS = ( isset( $field_options['field_wrap'] ) ? $field_options['field_wrap'][0] : null );
	$field_wrapE = ( isset( $field_options['field_wrap'] ) ? $field_options['field_wrap'][1] : null );

	//check for existing database option and set value and ID (ONLY APPLIES TO GLOBAL OPTIONS)
	$db_option = get_option_by_name( $option_name );
	$newInput['id'] = ( $db_option ? $db_option['id'] : null );

	//check for supplied field_value
	if( !empty( $field_options['input_value'] ) ){
		$newInput['option_value'] = $field_options['input_value'];
	} else {
		//if there is no input value supplied check the database for a given option value
		$newInput['option_value'] = ( $db_option ? $db_option['option_value'] : null );
	}	

	//check for help text
	$fieldHelp = null;
	if( isset( $newInput['help_text' ] ) ){

		//set the popover data
		$fieldHelp = '<span class="popover-toggle-help" data-toggle="popover" data-placement="auto" data-content="' . $newInput['help_text'] . '" data-trigger="focus click" title="' . $newInput['help_title'] . '">?</span>';

	} 

	//blank html retrun value
	$html ='';

	//check for label
	if( $newInput['label'] ){
		$html .= '<label for="settings-' . $option_name . '" class="control-label ' . $newInput['label_class'] . '">' . $newInput['label'];

		//check for wrap label option
		if( empty( $field_options['wrap_label'] ) || $field_options['wrap_label'] == false ){
			$html .= '</label>';
		}
	}

	//add field wrapping
	$html .=  $field_wrapS;

	//check for form addon
	if( $newInput['input_addon_start'] ){	
		$html .= '<span class="input-group-addon">' . $newInput['input_addon_start'] . '</span>';
	}

	//check for form button addon
		if( $newInput['input_button_start'] ){	
			$html .= '<span class="input-group-btn">';
			$html .= '	<button class="btn btn-' . $newInput['input_button_start_type'] . '" type="button">' . $newInput['input_button_start'] . '</button>';
			$html .= '</span>';
		}

	//compile basic field
	if( in_array( $newInput['input_type'], array('text','checkbox','date','year','month','datetime','email','password','time','tel','color','number','time','submit','hidden','reset','button') ) ){ 		

		//add form control class
		if(empty( $field_options['no_form_control'] ) ){
			$newInput['class'] .= ' form-control';
		}

		//check for field min and max
		$max = null;
		$min = null;
		if( !empty( $newInput['max_value'] ) || $newInput['max_value'] == '0' ){
			$max = ' max="' . $newInput['max_value'] . '" ';
		}
		if( !empty( $newInput['min_value'] ) || $newInput['min_value'] == '0'  ){
			$min = ' min="' . $newInput['min_value'] . '" ';
		}

		$html .= '<input type="' . $newInput['input_type'] . '" id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="' . $newInput['class'] . '" placeholder="' . $newInput['placeholder'] . '"' . $min . $max . $newInput['disabled'] . $newInput['required'] . $newInput['pattern'];

		//if checkbox check for checked attribute
		if( $newInput['input_type'] == 'checkbox' && $newInput['option_value'] !== null ){

			$html .= returnChecked( $newInput['option_value'], $newInput['check_value'] );
		} else {
			//add value
			$html .= ' value="' . $newInput['option_value']  . '" ';
		}

		//check for inline style
		if( !empty( $newInput['inline_style'] ) ){
			$html .= $newInput['inline_style'];
		}

		//close the input tag
		$html .= $newInput['on_change'] . '>';

		//check for a value to display after the input. This is used for checkboxes, radios, etc
		if( $newInput['input_type'] == 'checkbox' ){

			//check for display value
			if( !empty( $newInput['display_value'] ) ){
				$html .= $newInput['display_value'] . '<br>';
			} 
		}

		$html .= '<!--END INPUT-->';

	} elseif( $newInput['input_type'] == 'select' ){

			//cehck if blanks are allowed
			if( !$newInput['allow_blank'] ){ $disabled_option = ' disabled'; } else { $disabled_option = null; }

			//create first option
			$html .= '<select id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="form-control ' . $newInput['class'] . '"' . $newInput['disabled'] . $newInput['required'] . $newInput['on_change'] . '><option' . $disabled_option . '>' . $newInput['placeholder']  . '</option>';

			foreach($newInput['options'] as $value=>$display){
				$html .= '<option value="' . $value . '"' . returnSelected($newInput['check_value'],$value) . '>' . $display . '</option>';
			}
			$html .= '</select><!--END SELECT-->';

	} elseif( $newInput['input_type'] == 'textarea' ){


			//create first option
			$html .= '<textarea id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="form-control ' . $newInput['class'] . '"' . $newInput['disabled'] . $newInput['required'] . $newInput['on_change'] . '>' . $newInput['check_value']  . '</textarea>';

			$html .= '<!--END TEXTAREA-->';
	}

	//check for form addon
	if( $newInput['input_addon_end'] ){	
		$html .= '<span class="input-group-addon">' . $newInput['input_addon_end'] . '</span>';
	}

	//check for form button addon	
	if( $newInput['input_button_end'] ){	
		$html .= '<span class="input-group-btn">';
		$html .= '	<button class="btn btn-' . $newInput['input_button_end_type'] . '" type="button">' . $newInput['input_button_end'] . '</button>';
		$html .= '</span>';
	}

	//check for wrap label option
	if( !empty( $field_options['wrap_label'] ) && $field_options['wrap_label'] == true ){
		$html .= '</label>';
	}

	//add field wrapping
	$html .= $field_wrapE;

	return $html;
}


?>