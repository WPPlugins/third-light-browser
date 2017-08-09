<?php

//
// This class deals with displaying and saving the Third Light Browser admin settings.
//
class ThirdLightBrowserSettings
{

	const NAME = "ThirdLightBrowserSettings";


	//array of default options:
	public static function defaultOptions(){
		return array(

			"global_options" => array(

				"site" => "http://SITENAME.thirdlight.com",
				"apikey" => false,
				"automaticLogin" => "disabled", //or "username" or "email",
				"userRef" => false,
				"theme" => "light",
				"showRevisions" => "yes",
				"showMetadata" => "yes"

			),

			"output_formats" => array(

				array(
					"key"=> "banner",
					"name" => "Banner",
					"width" => 400,
					"height" => 75,
					"format" => "JPG", //or "PNG" or "GIF"
					"class" => "" //CSS class to add to images of this class.
				),

				array(
					"key" => "landscape",
					"name" => "Landscape",
					"width" => 200,
					"height" => 150,
					"format" => "JPG", //or "PNG" or "GIF"
					"class" => "" //CSS class to add to images of this class.
				),

				array(
					"key" => "portrait",
					"name" => "Portrait",
					"width" => 150,
					"height" => 200,
					"format" => "JPG", //or "PNG" or "GIF"
					"class" => "" //CSS class to add to images of this class.
				)

			)

		);
	}

	/**
	 * Uninstall function. Static; no class instacne required for cleanup.
	 */
	public static function uninstall()
	{
		//for a single-site setup:
		if(!is_multisite())
		{
			delete_option(self::NAME);
		} 
		//for a multisite setup:
		else 
		{
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();
			foreach ( $blog_ids as $blog_id ) 
			{
				switch_to_blog( $blog_id );
				delete_option( self::NAME );  
			}
			switch_to_blog( $original_blog_id );
		}
	}

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );

		//get options, or defaults if none present:
		$this->{self::NAME} = get_option(self::NAME, self::defaultOptions());
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			__('Third Light Browser'), 
			__('Third Light Browser'), 
			'manage_options', 
			'third-light-browser-settings', 
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{

		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>My Settings</h2>  

			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'thirdlight-options' );
				// print out the actual option fields:
				do_settings_sections( 'my-setting-admin' );
				// print the submit button:
				submit_button(); 
			?>
			</form>


		</div>
		<?php

	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{        
		register_setting(
			'thirdlight-options', // Option group
			self::NAME, // Option name
			array( $this, 'sanitizeOptions' ) // Sanitize
		);

		$general_settings = "general_settings";
		add_settings_section(
			$general_settings, // ID
			__('General Settings'), // Title
			function(){ _e('General settings for the Third Light Browser:'); },
			'my-setting-admin' // Page
		);  

		$site = "site";
		add_settings_field(
			$site, // ID
			__('IMS Site URL'), // Title 
			$this->input_field_factory(array(self::NAME, "global_options", $site)), // Callback
			'my-setting-admin', // Page
			$general_settings // Section           
		);      

		$autoLogin = "automaticLogin";
		add_settings_field(
			$autoLogin, 
			__('Automatic Login'),
			//this func generates html for userRef and apikey settings as well:
			$this->input_autologin_field($autoLogin), 
			'my-setting-admin', 
			$general_settings
		);

		$theme = "theme";
		add_settings_field(
			$theme, 
			__('Theme'), 
			$this->input_radio_factory(array(self::NAME, "global_options", $theme), array(
				"light" => __("Light"), 
				"dark" => __("Dark")
			)), 
			'my-setting-admin', 
			$general_settings
		);    

		$showRevisions = "showRevisions";
		add_settings_field(
			$showRevisions, 
			__('Show Revisions'), 
			$this->input_radio_factory(array(self::NAME, "global_options", $showRevisions), array(
				"yes" => __("Yes"),
				"no" => __("No")
			)), 
			'my-setting-admin', 
			$general_settings
		); 

		$showMetadata = "showMetadata";
		add_settings_field(
			$showMetadata, 
			__('Show Metadata'), 
			$this->input_radio_factory(array(self::NAME, "global_options", $showMetadata), array(
				"yes" => "Yes", 
				"no" => "No"
			)),
			'my-setting-admin', 
			$general_settings
		); 

		//print out all the output formats + JS to make them changeable:
		$output_formats = "output_formats";
		add_settings_section(
			$output_formats, // ID
			__('Output Formats'), // Title
			$this->print_output_formats(array(self::NAME, "output_formats")),
			'my-setting-admin' // Page
		);  

	}

	/**
	 * Check that the url provided is a valid thirdlight site:
	 */
	public static function checkForValidUrl($site)
	{
		//does site appear to be a valid URL?
		if( filter_var($site, FILTER_VALIDATE_URL) === false)
		{
			return __("The URL provided does not seem to be valid.");
		} 

		//does it point to a suitable third light site?
		elseif(function_exists("curl_init")) 
		{
			$rCurl = curl_init($site."/apps/cmsbrowser/index.html");
	        curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
	        $ret = curl_exec($rCurl);
			if(curl_errno($rCurl) != 0 || false === strpos($ret, "thirdlight_application"))
			{
				return __('The URL provided does not point to a Third Light site supporting the Third Light Browser.');
			}

		} 
		elseif(ini_get("allow_url_fopen"))
		{
			$ret = @file_get_contents($site."/apps/cmsbrowser/index.html");
			if(empty($ret) || (false === strpos($ret, "thirdlight_application")))
			{
				return __('The URL provided does not point to a Third Light site supporting the Third Light Browser.');
			}
		}
		return true;
	}

	/**
	 * Sanitize each setting field as needed
	 */
	public function sanitizeOptions( $options )
	{

		//get previous/default options so we can swap them back in if needed:
		$previous_options = $this->{self::NAME};

		//sanitize general options:
		$global_options = $options["global_options"];
		$options["global_options"] = call_user_func(function() use ($global_options){

			//check that the URL is valid:
			$urlRes = self::checkForValidUrl( $global_options["site"] );
			if($urlRes !== true)
			{
				add_settings_error(self::NAME, null, $urlRes);
			}

			//if there is an apikey, is it valid. If so, set the sessionId from it. Else, complain and set sessionId to false.
			if($global_options["automaticLogin"] !== "disabled" && !empty($global_options["apikey"]))
			{
			    try{
			        $imsAPI = new IMSApiClient($global_options["site"], $global_options["apikey"]);
			        $keyValid = true;
			    }
			    catch(IMSApiActionException $e) {
			        add_settings_error(self::NAME, null, __('The API key was rejected by the Third Light IMS server.'));
			    }
			    catch(IMSApiPrerequisiteException $e) {
			        add_settings_error(self::NAME, null, __("API client prerequisite missing: ").$e->getMessage());
			    }
			    catch(IMSApiClientException $e) {
			        add_settings_error(self::NAME, null, __('The API key could not be validated.'));
			    }
			    if(!$keyValid){
			    	add_settings_error(self::NAME, null, __('Automatic log in requires that the API key be configured correctly.'));
			    	$global_options["automaticLogin"] = "disabled";
			    }
			}
			else
			{
				$global_options["automaticLogin"] = "disabled";
			}

			//if we are trying to auto-login with an email address, make sure it appears valid:
			if($global_options["automaticLogin"] === "email" && filter_var($global_options["userRef"], FILTER_VALIDATE_EMAIL) === false)
			{
				add_settings_error(self::NAME, null, __('Email address for automatic login does not appear to be valid.'));
				$global_options["userRef"] = false;
			}

			//if we try to autologin and don't provide a userref, turn autologin off:
			if(!$global_options["userRef"])
			{
				$global_options["automaticLogin"] = "disabled";
			}

			//give back our sanitized options:
			return $global_options;

		});

		//sanitize output formats:
		$output_formats = $options["output_formats"];
		$previous_formats = $previous_options["output_formats"];
		$options["output_formats"] = call_user_func(function() use($output_formats, $previous_formats){

			$seen_formats = array();
			$new_output_formats = array();
			foreach($output_formats as $val){

				//give the format a key based on its name, must be unique:
				$key = strtolower($val["name"]);
				if(isset($seen_formats[$key])){
					add_settings_error(self::NAME, null, __("Two output formats cannot share the same name."));
					continue;
				}
				$seen_formats[$key] = true;
				$val["key"] = $key;

				//make sure width and height are integers > 0:
				$width = intval($val["width"]);
				if(!$width){ 
					add_settings_error(self::NAME, null, __("The width of an output format is not a valid number."));
					continue;
				}
				$height = intval($val["height"]);
				if(!$height){ 
					add_settings_error(self::NAME, null, __("The height of an output format is not a valid number."));
					continue;
				}

				//format seems legit; let it through:
				$new_output_formats[] = $val;

			}

			//if no formats, use previous ones (not allowed to have none!):
			if(count($new_output_formats) == 0){
				add_settings_error(self::NAME, null, __("Output formats are required, so they have been reset to the last used ones."));
				return $previous_formats;
			}
			return $new_output_formats;

		});

		return $options;
	}


	/**
	 * Factory functions to generate input text/radio buttons/dropdowns
	 * based on the provided parameters:
	 */
	
	private function prettyPrintLocation($location){
		$output = $location[0];
		for($i = 1; $i < count($location); $i++){
			$output = $output.'['.$location[$i].']';
		}
		return $output;
	}

	private function resolveLocation($location){
		if(!is_array($location)) return false;
		$out = $this->{$location[0]};
		for($i = 1; $i < count($location); $i++){
			if(is_array($out)){
				$out = $out[$location[$i]];
			}
			else {
				return false;
			}
		}
		return $out;
	}

	public function input_field_factory($location, $class=false){
		$val = $this->resolveLocation($location);
		$location = $this->prettyPrintLocation($location);
		return function() use($location, $val, $class){
			echo '<input type="text" class="regular-text '.($class? $class : '').'" name="'.$location.'" value="'.$val.'" />';
		};
	}

	public function input_radio_factory($location, $available, $class=false){
		$val = $this->resolveLocation($location);
		$location = $this->prettyPrintLocation($location);
		return function() use ($val, $location, $available, $class){
			foreach ($available as $radioId => $radioText) {
				echo $radioText.' <input type="radio" name="'.$location.'" value="'.$radioId.'" '.($radioId == $val? 'checked=true' : '').($class? ' class="'.$class.'"' : '').' /> ';
			}
		};
	}

	public function input_dropdown_factory($location, $available, $class=false){
		$val = $this->resolveLocation($location);
		$location = $this->prettyPrintLocation($location);
		return function() use ($val, $location, $available, $class){
			echo '<select name="'.$location.'"'.($class? ' class="'.$class.'"' : '').' >';
			foreach ($available as $radioId => $radioText) {
				echo '<option value="'.$radioId.'" '.($radioId == $val? 'selected=true' : '').'>'.$radioText.'</option>';
			}
			echo '</select>';
		};
	}

	public function input_autologin_field($fieldName){
		return function() use ($fieldName){

			//generate a standard input field:
			call_user_func($this->input_dropdown_factory(array(self::NAME, "global_options", $fieldName), array(
				"username" => __("Username"),
				"email" => __("Email Address"),
				"disabled" => __("No Automatic Login")
			), "thirdlight-automaticlogin-field"));

			echo '<div class="thirdlight-automaticlogin-options">';

				//userRef (with dynamic text based on above field):
				echo '<p class="thirdlight-userref-desc"></p>';
				call_user_func($this->input_field_factory(array(self::NAME, "global_options", "userRef"), "thirdlight-userref-field"));

				//apikey:
				echo "<p>"._("An API key is required for automatic login:")."</p>";
				call_user_func($this->input_field_factory(array(self::NAME, "global_options", "apikey"), "thirdlight-apikey-field"));

			echo '</div>';
			//some JS to show/hide a field to provide the user reference if needbe:
			?>
			<script type="text/javascript">
			"use strict";
			document.addEventListener("DOMContentLoaded", function(){

				var $ = jQuery;
				var $autoLoginField = $(".thirdlight-automaticlogin-field");
				var $autoLoginOptions = $(".thirdlight-automaticlogin-options");
				var $userrefDesc = $(".thirdlight-userref-desc");
				var $userrefField = $(".thirdlight-userref-field");

				$autoLoginField.on("change", function(){
					switch(this.value) {
					case "disabled":
						$autoLoginOptions.hide();
					break;
					case "username":
						if($userrefField.val().indexOf("@") != -1) $userrefField.val("");
						$autoLoginOptions.show();
						$userrefDesc.empty().append("<?php _e('Please enter the Third Light username to login as:'); ?>");
					break;
					case "email":
						if($userrefField.val().indexOf("@") == -1) $userrefField.val("");
						$autoLoginOptions.show();
						$userrefDesc.empty().append("<?php _e('Please enter the Third Light email address to login as:'); ?>");
					break;
					}
				});
				$autoLoginField.trigger("change");
			});
			</script>

			<?php

		};
	}

	/**
	 * factory function to generate markup for an output format:
	 */

	public function print_output_formats($location){

		return function() use ($location){
		?>

			<!-- output formats HTML -->
			<p><?php _e("These are the image output formats available in the Third Light Browser:"); ?></p>
			<p id="thirdlight-output-formats-none" style="display:none;"><em><?php _e("No output formats are present. Please add some."); ?></em></p>
			<div id="thirdlight-table-wrap">
				<table id="thirdlight-output-formats-container">
					<tr>
						<th><?php _e("Name"); ?></th>
						<th><?php _e("Width"); ?></th>
						<th><?php _e("Height"); ?></th>
						<th><?php _e("Format"); ?></th>
						<th><?php _e("CSS Class"); ?></th>
					</tr>
				</table>
			</div>
			<button type="button" class="button-secondary" id="thirdlight-output-formats-add"><?php _e("Add New Format"); ?></button>

			<!-- a little styling to control layout -->
			<style type="text/css">
			#thirdlight-table-wrap{
				overflow:auto;
			}
			.thirdlight-number-input{
				width:100px;
			}
			.thirdlight-cssclass-input{
				width:150px;
			}
			#wpfooter{
				display:none !important;
			}
			</style>

			<!-- Javascript to control output formats. delay execution as jQuery is loaded at bottom (IE9+). -->
			<script type="text/javascript">
			"use strict";
			document.addEventListener("DOMContentLoaded", function(){

				var $ = window.jQuery;
				var formatKey = "<?php echo $this->prettyPrintLocation($location); ?>";
				var formats = <?php echo json_encode($this->resolveLocation($location)); ?>;
				var $container = $("#thirdlight-output-formats-container");
				var $noOutputFormatsMessage = $("#thirdlight-output-formats-none");
				var $addButton = $("#thirdlight-output-formats-add");

				var nextIndex = function(start){ 
					var l = start;
					return function(){ return l++; }
				};

				function getName(arr){
					var str = formatKey;
					for(var i = 0; i < arr.length; i++){
						str += "["+arr[i]+"]";
					}
					return str;
				}

				function getValue(arr){
					var out = formats;
					for(var i = 0; i < arr.length-1; i++){
						if(typeof out[arr[i]] != "object"){
							out[arr[i]] = {};
						}
						out = out[arr[i]];
					}
					return out[arr[arr.length-1]];
				}

				function makeInput(arr, defaultValue, className){
					var val = getValue(arr);
					if(typeof val == "undefined") val = defaultValue;
					return '<input type="text" value="'+val+'" name="'+getName(arr)+'" '+(className? 'class="'+className+'"' : '')+'" />';
				}

				function makeDropdown(arr, options){
					var val = getValue(arr);
					var str = '<select name="'+getName(arr)+'">';
					for(var optionValue in options){
						var optionName = options[optionValue];
						str += '<option value="'+optionValue+'" '+(val == optionValue? 'selected=true':'')+'>'+optionName+'</option>';
					}
					str += '</select>';
					return str;
				}

				function onlyNumbersPlease(){
					this.value = this.value.replace(/[^0-9]/g, "");
				}

				function makeMeANumber(){
					if(isNaN(parseInt(this.value))){
						this.value = "0";
					}
				}

				function renderItem(formatIndex){

					var $innerContainer = $('<tr><td class="thirdlight-output-format">'
						+makeInput([formatIndex, "name"], "<?php _e('New Format'); ?> "+(formatIndex+1))+"</td><td>"
						+makeInput([formatIndex, "width"], "400", "thirdlight-number-input")+"</td><td>"
						+makeInput([formatIndex, "height"], "300", "thirdlight-number-input")+"</td><td>"
						+makeDropdown([formatIndex, "format"], { "JPG":"JPG", "GIF":"GIF", "PNG":"PNG" })+"</td><td>"
						+makeInput([formatIndex, "class"], "", "thirdlight-cssclass-input")
						+'</td></tr>');

					$innerContainer.find(".thirdlight-number-input").on("change", onlyNumbersPlease);
					$innerContainer.find(".thirdlight-number-input").on("blur", makeMeANumber);

					//configure the delete button to remove format, and hide table if all removed:
					var $deleteButton = $('<button class="button-secondary" type="button"><?php _e("Remove"); ?></button>');
					var $deleteColumn = $('<td></td>').append($deleteButton);

					$deleteButton.on("click", function(e){
						$innerContainer.remove(); 
						if($container.find("tr").length == 1){
							$container.hide();
							$noOutputFormatsMessage.show();
						}
					});
					$innerContainer.append($deleteColumn);

					$container.append($innerContainer);

				}

				//kick everything off:
				(function(){

					for(var formatIndex in formats){
						renderItem(formatIndex);
					}
					$addButton.on("click", function(e){
						e.preventDefault();
						$container.show();
						$noOutputFormatsMessage.hide();
						var next = nextIndex(formats.length);
						renderItem(next());
					});

				}());
			});
			</script>

		<?php
		};
	}

}