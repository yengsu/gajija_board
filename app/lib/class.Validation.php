<?php
/*
 Title: PHP Validation Class v1
 Author: Roy Anonuevo - www.royanonuevo.com
 User Guide: http://www.royanonuevo.com/blog/php-validation-class/
 User Guide: https://github.com/royanonuevo/PHP-Validation-Class
 */

class Validation{
	public  $_debug=false;
	private $_debug_errors=array(),
	$_passed=false,
	$_errors=array(),
	$_field_message=array(),
	$_field_message_error=array(),
	$_error_open_tags='<div>',
	$_error_closed_tags='</div>',
	$_error_msgs=array(
			//'required'				=> "%s is required!",
			//'matches'				=> "%s must match to %s.",
			'matches'				=> "%s",
			//'equal'					=> "%s must be exactly equivalent to %s.",
			'equal'					=> "%s 정확히 동일해야 합니다. %s.",
			//'email'					=> "%s must be a valid email format.",
			'email'					=> "%s Email형식에 맞게 입력해주세요.",
			'emails'				=> "%s must contain all valid email addresses.",
			'date'					=> "%s must be a valid date format.",
			'date_exact'			=> "%s must be exactly equivalent to %s.",
			'date_before'			=> "%s must contain a date before %s.",
			'date_after'			=> "%s must contain a date after %s.",
			'username'				=> "%s must contain only alpha-numeric characters and underscores. Underscore should be place between of any alpha-numeric characters.",
			'alpha'					=> "%s must contain only alphabetical characters.",
			'alpha_space'			=> "%s must contain only alphabetical characters and spaces.",
			//'alpha_numeric'			=> "%s must contain only alpha-numeric characters.",
			'alpha_numeric'			=> "%s 영어(a-z)과 숫자(0-9) 조합으로 입력해주세요.",
			'alpha_numeric_space'	=> "%s must contain only alpha-numeric characters and spaces.",
			//'numeric'				=> "%s must contain only numbers.",
			'numeric'				=> "%s 숫자로 입력해주세요.",
			'natural'				=> "%s must contain only positive numbers.",
			'natural_no_zero'		=> "%s must contain only number greater than zero.",
			'integer'				=> "%s must contain only integer numbers.",
			'decimal'				=> "%s must contain only decimal numbers.",
			'exact_char'			=> "%s must be exactly %s %s in length.",
			//'max_char'				=> "%s must be a maximum only of %s %s.",
			'max_char'				=> "%s 최대 %s %s 까지 입력해주세요.",
			//'min_char'				=> "%s must be a minimum of %s %s.",
			'min_char'				=> "%s 최소 %s %s이상 입력해주세요.",
			'greater_than'			=> "%s must contain a number greater than %s.",
			'less_than'				=> "%s must contain a number less than than %s.",
			'hexa_color'			=> "%s must be a valid hexa color.",
			'ip_address'			=> "%s must be a valid ip address.",
			'url'					=> "%s must be a valid url.",
			'exact_file_size'		=> "%s must be exactly %s size.",
			'min_file_size'			=> "%s must be a minimum of %s size.",
			'max_file_size'			=> "%s must be a maximum only of %s size.",
			'pdf'					=> "%s must be a valid PDF format.",
			'exact_dimension'		=> "%s must be exactly %s.",
			'min_dimension'			=> "%s must be a minimum of %s.",
			'max_dimension'			=> "%s must be a maximum only of %s.",
			'image'					=> "%s must be a valid image format. Allowed format: %s.",
			'required'				=> "%s 입력해주세요.",
			//'required'				=> "%s Please enter it correctly.",
			'hangul'					=> "%s 정확히 입력해주세요.",
			'hangul_alpha_numeric' => "%s 정확히 입력해주세요.",
			'empty'			=> "%s 정확히 입력해주세요.",
			'whitespace'			=> "%s 정확히 입력해주세요.") ;
			//'whitespace'			=> "%s Please enter it correctly.") ;
	
	
	# VALID IMAGES FORMAT
	private static $_valid_jpeg_mimes=array('image/jpe', 'image/jpg', 'image/jpeg', 'image/pjpeg'),
	$_valid_png_mimes=array('image/x-png','image/png'),
	$_valid_bmp_mimes=array('image/x-windows-bmp','image/bmp'),
	$_valid_img_mimes=array('image/gif', 'image/jpeg', 'image/png', 'image/bmp');
	
	public function set_rules($items){
		$_SOURCES=$_REQUEST;
		# If have no request or files, return false
		if(count($_SOURCES)==0 && count($_FILES)==0){
			if($this->_debug==true){ // if debug is enable, prompt the error
				$this->addDebugError("Validation Error: No input found!");
			}
			return $this;
		}
		
		# If set_rules() parameter is not an array, return false
		if(!is_array($items)){
			if($this->_debug==true){ // if debug is enable, prompt the error
				$this->addDebugError("Validation Error: Invalid set_rules() parameter. It must be an array!");
			}
			return $this;
		}
		
		foreach($items as $item=>$field)
		{
			# If label is not set, get the fieldname
			$field_label=(isset($field['label']) && !$this->is_empty($field['label'])? $field['label'] : ucwords(strtolower($item)));
			
			# Check if rules is set, then continue the script
			if(isset($field['rules']))
			{
				# Get the field rules
				$field_rules=explode("|",trim($field['rules']));
				$field_rules=str_replace(" ","",$field_rules);
				
				# Get The Field Value
				if(isset($_SOURCES[$item])){
					# Get the field value when fieldname found on $_REQUEST
					$field_value=$_SOURCES[$item];
					$field_initial_value=$_SOURCES[$item];
				}else if(isset($_FILES[$item])){
					# Get the field value when fieldname found on $_FILES
					$field_value=$_FILES[$item];
					$field_initial_value=$_FILES[$item]['name'];
				}else{
					# If fieldname not found on $_REQUEST or $_FILES, set empty string
					$field_value="";
					$field_initial_value="";
				}
				
				foreach($field_rules as $field_rule)
				{
					$field_rule=strtolower($field_rule); // specific rule
					$has_field_parameters=false;
					
					# Get the parameter on the rule
					if(strpos($field_rule, '[')!==FALSE){
						$bracket_pos_start=strpos($field_rule, '[')+1;
						$bracket_pos_end=((strlen($field_rule))-$bracket_pos_start)-1;
						$field_rule_parameters=substr($field_rule,$bracket_pos_start,$bracket_pos_end);
						$param=explode(',',$field_rule_parameters);
						$field_rule=substr($field_rule,0,$bracket_pos_start-1);
						$has_field_parameters=true; // parameter is found then set it to true
					}
					
					if($field_rule==="required" && $this->is_empty($field_initial_value))
					{
						# If field rule is required and field value is empty
						$this->addError($field_rule,$field_label,$item);
					}
					else if(!$this->is_empty($field_initial_value))
					{
						switch($field_rule)
						{
							case 'required':
								# Important to include this rule here to prevent error on file
								break;
							case 'matches':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Field name parameter is required!");
									}
									break;
								}
								$param1=$param[0];
								$matches_to=(isset($items[$param1]['label']) && !$this->is_empty($items[$param1]['label'])? $items[$param1]['label'] : $param1);
								if(!isset($_SOURCES[$param1])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! Field name not found!");
									}
									break;
								}
								if($field_value !== $_SOURCES[$param1]){
									$labels=array($field_label, $matches_to);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'equal':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the equivalent value!");
									}
									break;
								}
								if($field_value!==$param[0]){
									$labels=array($field_label, $param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'email':
								if(!$this->is_email($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'emails':
								if(!$this->is_emails($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'date':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the date delimiter!");
									}
									break;
								}
								if(!$this->is_date($field_value,$param[0])){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'date_exact':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the date!");
									}
									break;
								}
								if(!$this->is_date($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! Date format must be mm/dd/yyyy!");
									}
									break;
								}
								if(!$this->is_date_exact($field_value,$param[0])){
									$labels=array($field_label,$param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'date_before':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the date!");
									}
									break;
								}
								if(!$this->is_date($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! Date format must be mm/dd/yyyy!");
									}
									break;
								}
								if(!$this->is_date_before($field_value,$param[0])){
									$labels=array($field_label,$param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'date_after':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the date!");
									}
									break;
								}
								if(!$this->is_date($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! Date format must be mm/dd/yyyy!");
									}
									break;
								}
								if(!$this->is_date_after($field_value,$param[0])){
									$labels=array($field_label,$param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'username':
								if(!$this->is_username($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'alpha':
								if(!$this->is_alpha($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'alpha_space':
								if(!$this->is_alpha_space($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'alpha_numeric':
								if(!$this->is_alpha_numeric($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'alpha_numeric_space':
								if(!$this->is_alpha_numeric_space($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'numeric':
								if(!$this->is_num($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'natural':
								if(!$this->is_natural($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'natural_no_zero':
								if(!$this->is_natural_no_zero($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'integer':
								if(!$this->is_integer_number($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'decimal':
								if(!$this->is_decimal($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'exact_char':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the number of character!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! It must be a natural number!");
									}
									break;
								}
								$param1=(int)$param[0];
								
								$char_word="character";
								if($param1==0 || $param1>1){
									$char_word="characters";
								}
								if(!$this->is_exact_char($field_value,$param1)){
									$labels=array($field_label,$param1,$char_word);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'max_char':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the number of character!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! It must be a natural number!");
									}
									break;
								}
								$param1=(int)$param[0];
								
								//$char_word="character";
								$char_word="자리";
								if($param1==0 || $param1>1){
									//$char_word="characters";
									$char_word="자리";
								}
								if($this->is_max_char($field_value,$param1)){
									$labels=array($field_label,$param1,$char_word);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'min_char':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the number of character!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Invalid parameter! It must be a natural number!");
									}
									break;
								}
								$param1=(int)$param[0];
								
								//$char_word="character";
								$char_word="자리";
								if($param1==0 || $param1>1){
									//$char_word="characters";
									$char_word="자리";
								}
								if($this->is_min_char($field_value,$param1)){
									$labels=array($field_label,$param1,$char_word);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'greater_than':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the parameter value!");
									}
									break;
								}
								if(!$this->is_num($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameter must be number!");
									}
									break;
								}
								if(!$this->is_greater_than($field_value,$param[0])){
									$labels=array($field_label,$param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'less_than':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the parameter value!");
									}
									break;
								}
								if(!$this->is_num($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameter must be number!");
									}
									break;
								}
								if(!$this->is_less_than($field_value,$param[0])){
									$labels=array($field_label,$param[0]);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'hexa_color':
								if(!$this->is_hexa_color($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'ip_address':
								if(!$this->is_ip_address($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'url':
								if(!$this->is_url($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'exact_file_size':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the file size!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameter must be a natural number!");
									}
									break;
								}
								if(!$this->is_exact_size($item,$param[0])){
									$file_size=$param[0]/1024;
									if($file_size>=1){
										$file_size_mb=floor($file_size);
										$file_size_kb=($param[0]-($file_size_mb*1024));
										
										$file_size=($file_size_kb>0)? $file_size_mb."mb and ".$file_size_kb."kb": $file_size_mb."mb";
									}else{
										$file_size=($param[0])."kb";
									}
									$labels=array($field_label,$file_size);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'min_file_size':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the file size!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameter must be a natural number!");
									}
									break;
								}
								if(!$this->is_min_size($item,$param[0])){
									$file_size=$param[0]/1024;
									if($file_size>=1){
										$file_size_mb=floor($file_size);
										$file_size_kb=($param[0]-($file_size_mb*1024));
										
										$file_size=($file_size_kb>0)? $file_size_mb."mb and ".$file_size_kb."kb": $file_size_mb."mb";
									}else{
										$file_size=($param[0])."kb";
									}
									$labels=array($field_label,$file_size);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'max_file_size':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the file size!");
									}
									break;
								}
								if(!$this->is_natural($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameter must be a natural number!");
									}
									break;
								}
								if($this->is_max_size($item,$param[0])){
									$file_size=$param[0]/1024;
									if($file_size>=1){
										$file_size_mb=floor($file_size);
										$file_size_kb=($param[0]-($file_size_mb*1024));
										
										$file_size=($file_size_kb>0)? $file_size_mb."mb and ".$file_size_kb."kb": $file_size_mb."mb";
									}else{
										$file_size=($param[0])."kb";
									}
									$labels=array($field_label,$file_size);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'exact_dimension':
								if(count($param)!=2){
									$this->addError("Validation Error: {$field_label} - Please specify the width and height in pixels!");
									break;
								}
								if(!$this->is_natural($param[0]) || !$this->is_natural($param[1])){
									$this->addError("Validation Error: {$field_label} - Invalid parameter!");
									break;
								}
								if(!$this->is_exact_dimension($item,$param[0],$param[1])){
									$label="{$param[0]}px width and {$param[1]}px height";
									
									$labels=array($field_label,$label);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'min_dimension':
								if(count($param)!=2){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the width and height in pixels!");
									}
									break;
								}
								if(!$this->is_natural($param[0]) || !$this->is_natural($param[1])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameters must be a natural number!");
									}
									break;
								}
								if(!$this->is_min_dimension($item,$param[0],$param[1])){
									if($param[0]>0 && $param[1]>0){
										$label="{$param[0]}px width and {$param[1]}px height";
									}else if($param[0]>0 && $param[1]==0){
										$label="{$param[0]}px width";
									}else if($param[0]==0 && $param[1]>0){
										$label="{$param[1]}px height";
									}else{
									}
									
									$labels=array($field_label,$label);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'max_dimension':
								if(count($param)!=2){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the width and height in pixels!");
									}
									break;
								}
								if(!$this->is_natural($param[0]) || !$this->is_natural($param[1])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Parameters must be a natural number!");
									}
									break;
								}
								if(!$this->is_max_dimension($item,$param[0],$param[1])){
									if($param[0]>0 && $param[1]>0){
										$label="{$param[0]}px width and {$param[1]}px height";
									}else if($param[0]>0 && $param[1]==0){
										$label="{$param[0]}px width";
									}else if($param[0]==0 && $param[1]>0){
										$label="{$param[1]}px height";
									}else{
									}
									
									$labels=array($field_label,$label);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'pdf':
								if(!$this->is_pdf($item)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'image':
								if(!$has_field_parameters || $this->is_empty($param[0])){
									if($this->_debug==true){ // if debug is enable, prompt the error
										$this->addDebugError("Validation Error: {$item} ({$field_rule}) - Specify the image format!");
									}
									break;
								}
								
								if(!$this->is_image($item,$param)){
									
									if(count($param)>1){
										$last_param=$param[count($param)-1];
										unset($param[count($param)-1]);
										$formats=implode(", ",$param)." and ".$last_param;
									}else{
										$formats=$param[0];
									}
									
									$labels=array($field_label,$formats);
									$this->addError($field_rule,$labels,$item);
								}
								break;
							case 'hangul':
								if(!$this->is_hangul($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'hangul_alpha_numeric':
								if(!$this->is_hangulAlphaNum($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							case 'whitespace':
								if(!$this->is_whitespace($field_value)){
									$this->addError($field_rule,$field_label,$item);
								}
								break;
							default:
								if($this->_debug==true){ // if debug is enable, prompt the error
									$this->addDebugError("Validation Error: {$field_rule} - Invalid rules!");
								}
						}//end switch($field_rule)
					}//end else if(!$this->is_empty($field_value))
				}//end foreach($field_rules as $field_rule)
			}//end if(isset($field['rules']))
		}//end foreach($items as $item=>$field)
		
		if($this->is_empty($this->_errors)){
			$this->_passed=true;
		}
		return $this;
		
	}//end set_rules
	
	private function addDebugError($error=""){
		$this->_debug_errors[]=$error; // add new error
	}
	public function debugErrors(){
		return $this->_debug_errors; // return array of errors
	}
	private function addError($field_rule,$labels="",$field=""){
		if($field!="" && isset($this->_field_message[$field][$field_rule])){
			$error_msgs=$this->_field_message[$field][$field_rule];
		}else{
			if(isset($this->_error_msgs[$field_rule])){
				if(is_array($labels)){
					if(count($labels)==2){
						$error_msgs=sprintf($this->_error_msgs[$field_rule], $labels[0], $labels[1]);
					}else if(count($labels)>=3){
						$error_msgs=sprintf($this->_error_msgs[$field_rule], $labels[0], $labels[1], $labels[2]);
					}else{
						$error_msgs=sprintf($this->_error_msgs[$field_rule], $labels[0]);
					}
				}else{
					$error_msgs=sprintf($this->_error_msgs[$field_rule], $labels);
				}
			}else{
				$error_msgs=$field_rule;
			}
		}
		$this->_field_message_error[$field][]=$this->_error_open_tags.$error_msgs.$this->_error_closed_tags;
		$this->_errors[]=$this->_error_open_tags.$error_msgs.$this->_error_closed_tags;
		//echo '<pre>';print_r($this->_error_msgs);
		//echo '<pre>';print_r($this->_field_message_error);
	}
	
	public function errors($assoc=""){
		if($assoc=="assoc"){
			return $this->_field_message_error; // return array of errors (associative)
		}else{
			//return $this->_errors; // return array of errors
			return $this->_field_message_error ;
		}
	}
	
	public function set_field_message($msgs){
		if(is_array($msgs)){
			// replace the default error message of a rule for specific field name
			$this->_field_message=$msgs;
		}
	}
	
	public function set_error_tags($open_tag='<div>',$close_tag='<div>'){
		$this->_error_open_tags=$open_tag; // set the open html tag
		$this->_error_closed_tags=$close_tag; // set the closing html tag
	}
	
	public function get_field_error($field,$show_all=true){
		$errors="";
		
		if($show_all==false){
			// return one error at a time
			$errors=isset($this->_field_message_error[$field])? $this->_field_message_error[$field][0] : '';
		}else{
			// return all errors
			if(isset($this->_field_message_error[$field])){
				foreach($this->_field_message_error[$field] as $error){
					$errors.=$error;
				}
			}
		}
		
		return $errors;
	}
	
	public function set_message($rules,$msgs=""){
		// Replace the default error message for specific rule
		if(is_array($rules)){
			foreach($rules as $rule => $rule_value){
				if(isset($this->_error_msgs[$rule])){
					$rule_value=$this->sanitize_error_msgs($rule_value);
					$this->_error_msgs[$rule]=$rule_value;
				}
			}
		}else if(!$this->is_empty($msgs)){
			if(isset($this->_error_msgs[$rules])){
				$msgs=$this->sanitize_error_msgs($msgs);
				$this->_error_msgs[$rules]=$msgs;
			}
		}
	}
	
	private function sanitize_error_msgs($str){
		// Sanitize the custom error message, make sure that only :label is allowed for sprintf
		$search=array('%s','%d','%b','%c','%f','%o','%x','%X');
		$str=str_replace($search,'',$str);
		$str=str_replace(':label','%s',$str);
		
		if(strpos($str,'%s')!==FALSE){
			$str_pos=strpos($str,'%s');
			$str=str_replace('%s','',$str);
			$str_pos_start=substr($str,0,$str_pos);
			$str_pos_end=substr($str,$str_pos);
			$str=$str_pos_start."%s".$str_pos_end;
		}
		return $str;
	}
	
	public function passed(){
		return $this->_passed; // return if the form is valid or not
	}
	
	/**
	 * check if the value is empty string, object or array
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_empty($val){
		return in_array($val, array(null, false, '', array(),(object)array()), true) && empty($val);
	}
	
	/**
	 * check if the value is a valid email address
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_email($val){
		return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $val);
	}
	
	/**
	 * check if the value is contain only a valid email addresses
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_emails($val){
		if(strpos($val, ',')===false){
			return self::is_email(trim($val));
		}
		foreach(explode(',', $val) as $email){
			if (trim($email)!='' && !self::is_email(trim($email))){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * check if the value is a valid date format(mm/dd/yyyy) based on the delimiter specified
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_date($date,$delimiter="/"){
		if(strlen($date)>10){
			return false;
		}
		else{
			$pieces = explode($delimiter, $date);
			if(count($pieces)!=3){
				return false;
			}
			else{
				$month = $pieces[0];
				$day = $pieces[1];
				$year = $pieces[2];
				return checkdate($month,$day,$year);
			}
		}
	}
	
	/**
	 * check if the date is exactly equivalent to the given date specified (mm/dd/yyyy format)
	 * @access	public, static
	 * @param   string, date format (mm/dd/yyyy)
	 * @return  boolean
	 */
	public static function is_date_exact($val,$date_exact){
		if(!self::is_date($val) || !self::is_date($date_exact)){
			return false;
		}
		$value=strtotime($val);
		$date_exact=strtotime($date_exact);
		
		return ($value==$date_exact);
	}
	
	/**
	 * check if the date is preceding to the given date specified (mm/dd/yyyy format)
	 * @access	public, static
	 * @param   string, date format (mm/dd/yyyy)
	 * @return  boolean
	 */
	public static function is_date_before($val,$date_before){
		if(!self::is_date($val) || !self::is_date($date_before)){
			return false;
		}
		$value=strtotime($val);
		$date_before=strtotime($date_before);
		
		return ($value<$date_before);
	}
	
	/**
	 * check if the date is after to the given date specified (mm/dd/yyyy format)
	 * @access	public, static
	 * @param   string, date format (mm/dd/yyyy)
	 * @return  boolean
	 */
	public static function is_date_after($val,$date_after){
		if(!self::is_date($val) || !self::is_date($date_after)){
			return false;
		}
		$value=strtotime($val);
		$date_after=strtotime($date_after);
		
		return ($value>$date_after);
	}
	
	/**
	 * check if the value is valid username, underscore must be place between of any alpha-numeric characters
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_username($val){
		return preg_match('/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/', $val);
	}
	
	/**
	 * check if the value is contain only alphabetical characters
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_alpha($val){
		//return (preg_match('/^[a-zA-ZñÑ]+$/', $val));
		return (preg_match('/^[a-zA-Z]+$/', $val));
	}
	
	/**
	 * check if the value is contain only alphabetical characters and spaces
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_alpha_space($val){
		#return preg_match('/^[A-Za-zñÑ][A-Za-zñÑ]*(?:\s[A-Za-zñÑ]+)*$/', $val);
		//return (preg_match('/^[a-zA-ZñÑ\s]+$/', $val));
		return (preg_match('/^[a-zA-Z\s]+$/', $val));
	}
	
	/**
	 * check if the value is contain only alpha-numeric characters
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_alpha_numeric($val){
		//return (preg_match('/^[a-zA-ZñÑ0-9]+$/', $val));
		return (preg_match('/^[a-zA-Z0-9]+$/', $val));
	}
	
	/**
	 * check if the value is contain only alpha-numeric characters and spaces
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_alpha_numeric_space($val){
		#return preg_match('/^[0-9A-Za-zñÑ][0-9A-Za-zñÑ]*(?:\s[0-9A-Za-zñÑ]+)*$/', $val);
		//return (preg_match('/^[a-zA-ZñÑ0-9\s]+$/', $val));
		return (preg_match('/^[a-zA-Z0-9\s]+$/', $val));
	}
	
	/**
	 * check if the value is a valid numerical value
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_num($val){
		return preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $val);
	}
	
	/**
	 * check if the value is a valid natural number
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_natural($val){
		return preg_match('/^[0-9]+$/', $val);
	}
	
	/**
	 * check if the value is a natural number greater than zero
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_natural_no_zero($val){
		if(!preg_match( '/^[0-9]+$/', $val)){
			return false;
		}
		if($val==0){
			return false;
		}
		return true;
	}
	
	/**
	 * check if the value is a valid integer number
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_integer_number($val){
		return preg_match('/^[\-+]?[0-9]+$/', $val);
	}
	
	/**
	 * check if the value is a valid decimal number
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_decimal($val){
		return preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $val);
	}
	
	/**
	 * check if the number of characters is exactly equivalent to the parameter value
	 * @access	public, static
	 * @param   string, positive integer
	 * @return  boolean
	 */
	public static function is_exact_char($val,$num){
		if(preg_match("/[^0-9]/", $num)){
			return false;
		}
		if(function_exists('mb_strlen')){
			return (mb_strlen($val)==$num)? true : false ;
		}
		return (strlen($val)==$num)? true : false ;
	}
	
	/**
	 * check if the number of characters is greater than to the parameter value
	 * @access	public, static
	 * @param   string, positive integer
	 * @return  boolean
	 */
	public static function is_max_char($val,$max){
		if(preg_match("/[^0-9]/", $max)){
			return false;
		}
		if(function_exists('mb_strlen')){
			return (mb_strlen($val)>$max)? true : false ;
		}
		return (strlen($val)>$max)? true : false ;
	}
	
	/**
	 * check if the number of characters is less than to the parameter value
	 * @access	public, static
	 * @param   string, positive integer
	 * @return  boolean
	 */
	public static function is_min_char($val,$min){
		if(preg_match("/[^0-9]/", $min)){
			return false;
		}
		if (function_exists('mb_strlen')){
			return (mb_strlen($val)<$min)? true : false ;
		}
		return (strlen($val)<$min)? true : false ;
	}
	
	/**
	 * check if the value is greater than to the parameter value
	 * @access	public, static
	 * @param   numeric
	 * @return  boolean
	 */
	public static function is_greater_than($val, $min){
		if(!self::is_num($val)){
			return false;
		}
		return ($val>$min)? true : false;
	}
	
	/**
	 * check if the value is less than to the parameter value
	 * @access	public, static
	 * @param   numeric
	 * @return  boolean
	 */
	public static function is_less_than($val,$max){
		if(!self::is_num($val)){
			return false;
		}
		return ($val<$max)? true : false;
	}
	
	/**
	 * check if the value is a valid hexa web color
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_hexa_color($val){
		return preg_match('/^#(?:(?:[a-f0-9]{3}){1,2})$/i', $val);
	}
	
	/**
	 * check if the value is a valid ip address
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_ip_address($val){
		return preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',$val);
	}
	
	/**
	 * check if the value is a valid URL
	 * @access	public, static
	 * @param   string
	 * @return  boolean
	 */
	public static function is_url($url){
		return filter_var($url, FILTER_VALIDATE_URL);
	}
	
	/**
	 * check if the file size is exactly equivalent to the parameter size
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_exact_size($file,$equivalent) {
		$file_size=(int)($_FILES[$file]['size']/1024);
		
		if(!self::is_num($equivalent)){
			return false;
		}
		return ($file_size==$equivalent)? true : false;
	}
	
	/**
	 * check if the file size is less than to the minimum size
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_min_size($file,$min) {
		$file_size=(int)($_FILES[$file]['size']/1024);
		
		if(!self::is_num($min)){
			return false;
		}
		return ($file_size<$min)? false : true;
	}
	
	/**
	 * check if the file size is greater than to the maximum size
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_max_size($file,$max) {
		$file_size=(int)($_FILES[$file]['size']/1024);
		
		if(!self::is_num($max)){
			return false;
		}
		return ($file_size>$max)? true : false;
	}
	
	/**
	 * check if the file is a valid PDF format
	 * @access	public, static
	 * @param   file
	 * @return  boolean
	 */
	public static function is_pdf($file) {
		if(!isset($_FILES[$file])){
			return false;
		}
		
		$file_name=$_FILES[$file]['name'];
		$file_ext=strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		$file_tmp=$_FILES[$file]['tmp_name'];
		$file_type=$_FILES[$file]['type'];
		$file_size=$_FILES[$file]['size'];
		
		if($file_size<=0){
			return false;
		}
		
		if($file_ext=="pdf"){
			$PDF_MAGIC="\x25\x50\x44\x46\x2D";
			return (file_get_contents($file_tmp,false,null,0,strlen($PDF_MAGIC))===$PDF_MAGIC)?true:false;
		}else{
			return false;
		}
	}
	
	/**
	 * check if the image is exactly equivalent to the dimension size specified
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_exact_dimension($file, $allowed_width=0, $allowed_height=0){
		if(!isset($_FILES[$file])){
			return false;
		}
		
		$file_tmp=$_FILES[$file]['tmp_name'];
		$file_type=$_FILES[$file]['type'];
		$file_size=$_FILES[$file]['size'];
		
		if($file_size<=0){
			return false;
		}
		
		list($width, $height) = getimagesize($file_tmp);
		if($width<=0 || $height<=0){
			return false;
		}
		
		if($allowed_width==$width && $allowed_height==$height){
			return true;
		}
		
		return false;
	}
	
	/**
	 * check if the image is not less than to the dimension size specified
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_min_dimension($file,$min_width=0,$min_height=0){
		if(!isset($_FILES[$file])){
			return false;
		}
		
		$file_tmp=$_FILES[$file]['tmp_name'];
		$file_type=$_FILES[$file]['type'];
		$file_size=$_FILES[$file]['size'];
		
		if($file_size<=0){
			return false;
		}
		
		list($width, $height) = getimagesize($file_tmp);
		if($width<=0 || $height<=0){
			return false;
		}
		
		if($min_width>0 && $min_height>0){
			if($width>=$min_width && $height>=$min_height){
				return true;
			}
		}else if($min_width>0 && $min_height==0){
			if($width>=$min_width){
				return true;
			}
		}else if($min_width==0 && $min_height>0){
			if($height>=$min_height){
				return true;
			}
		}else{
			return true;
		}
		
		return false;
	}
	
	/**
	 * check if the image is not greater than to the dimension size specified
	 * @access	public, static
	 * @param   file, positive integer
	 * @return  boolean
	 */
	public static function is_max_dimension($file,$max_width=0,$max_height=0){
		if(!isset($_FILES[$file])){
			return false;
		}
		
		$file_tmp=$_FILES[$file]['tmp_name'];
		$file_type=$_FILES[$file]['type'];
		$file_size=$_FILES[$file]['size'];
		
		if($file_size<=0){
			return false;
		}
		
		list($width, $height) = getimagesize($file_tmp);
		if($width<=0 || $height<=0){
			return false;
		}
		
		if($max_width>0 && $max_height>0){
			if($width<=$max_width && $height<=$max_height){
				return true;
			}
		}else if($max_width>0 && $max_height==0){
			if($width<=$max_width){
				return true;
			}
		}else if($max_width==0 && $max_height>0){
			if($height<=$max_height){
				return true;
			}
		}else{
			return true;
		}
		
		return false;
	}
	
	/**
	 * check if the file is a valid image format
	 * @access	public, static
	 * @param   file
	 * @return  boolean
	 */
	public static function is_image($file,$allowed_format){
		if(!isset($_FILES[$file])){
			return false;
		}
		
		$file_name=$_FILES[$file]['name'];
		$file_ext=strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		$file_tmp=$_FILES[$file]['tmp_name'];
		$file_type=$_FILES[$file]['type'];
		$file_size=$_FILES[$file]['size'];
		
		if($file_size<=0){
			return false;
		}
		
		$allowed_ext=array();
		if(in_array("jpg", $allowed_format)){
			$jpg_ext=array("jpe","jpeg");
			$allowed_ext=array_merge($jpg_ext, $allowed_format);
		}
		
		if(in_array($file_ext,$allowed_ext))
		{
			if(function_exists('getimagesize')){
				if($file_info=@getimagesize($file_tmp)){
					if($file_info[0]>0 && $file_info[1]>0){
						if($file_info<=0){
							return false;
						}
					}else{
						return false;
					}
				}else{
					return false;
				}
			}
			
			if(in_array($file_type, self::$_valid_jpeg_mimes)){
				$file_type='image/jpeg';
			}
			if(in_array($file_type, self::$_valid_png_mimes)){
				$file_type='image/png';
			}
			if(in_array($file_type, self::$_valid_bmp_mimes)){
				$file_type='image/bmp';
			}
			
			return (in_array($file_type, self::$_valid_img_mimes, true))? true : false;
		}
		return false;
	}
	/**
	 * 한글만 입력했는지 체크
	 *
	 * @access	public, static
	 * @param string $str
	 * @return boolean
	 */
	public static function is_hangul($str){
		
		if(preg_match("/^[가-힣]+$/", $str)) return true ;
		return false ;
		/* $cset = array("UTF-8", "EUC-KR", "ASCII") ;
		 $sCharset = mb_detect_encoding($str, $cset);
		 
		 if( $sCharset == "EUC-KR" || $sCharset == "ASCII")
		 {
		 if(preg_match("/[\xA1-\xFE][\xA1-\xFE]/", $str)) return true ;
		 }
		 else if( $sCharset == "UTF-8")
		 {
		 if(preg_match("/[\xE0-\xFF][\x80-\xFF][\x80-\xFF]/", $str)) return true ;
		 }
		 return false ;
		 */
	}
	
	public static function is_hangulAlphaNum($str){
		if(preg_match("/^[가-힣0-9a-zA-Z]+$/", $str)) return true ;
		return false ;
	}
	
	/**
	 * 스페이스만 있는지 체크
	 *
	 * @access	public, static
	 * @param string $str
	 * @return boolean
	 *
	 * return ( strstr($val, ' ') ) ? true : false;
	 */
	public static function is_whitespace($str){
		if( ctype_space($str))  return false ;
		else return true;
	}
	
}