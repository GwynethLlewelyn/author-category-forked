<?php
/**
 * SimplePanel is a class to be used in WordPress to create option panels for themes and plugins
 * using the native settings api
 *
 * @version 0.1
 * @author Ohad Raz <admin@bainternet.info>
 * @copyright 2013 Ohad Raz
 * 
 */
if (!class_exists('SimplePanel')){
	/**
	* SimplePanel
	*/
	class SimplePanel{
		public $title = '';
		public $name = '';
		public $capability = 'manage_options';
		public $option = '';
		public $fields = array();
		public $sections = array();
		public $has_errors = false;
		public $slug = '';
		public $help_tabs = array();
		function __construct($args=array()){
			$this->setProperties($args);
			$this->hooks();
		}

		public function setProperties($args = array(), $properties = false){
			if (!is_array($properties))
				$properties = array_keys(get_object_vars($this));

			foreach ($properties as $key ) {
			  $this->$key = (isset($args[$key]) ? $args[$key] : $this->$key);
			}
		}

		public function hooks(){
			//admin page
			add_action('admin_menu', array($this,'admin_menu'));

			//register settings
			add_action( 'admin_init', array($this,'register_settings') );
		}

		public function _help_tab(){
			$screen = get_current_screen();

		    /*
		     * Check if current screen is My Admin Page
		     * Don't add help tab if it's not
		     */
		    if ( $screen->id != $this->slug )
		        return;

		    // Add my_help_tab if current screen is My Admin Page
		    foreach ($this->help_tabs as $value) {
		    	$screen->add_help_tab($value);	
		    }
		}

		public function register_settings(){
			foreach ($this->sections as $s) {
				add_settings_section( $s['id'], $s['title'], array($this,'section_callback') , __FILE__ );
				register_setting( $s['option_group'], $this->option, array($this,'sanitize_callback') );
				
			}
			foreach ($this->fields as $f) {
				add_settings_field( $f['id'], $f['label'], array($this,'show_field'), __FILE__, $f['section'], $f ); 
			}
		}

		public function admin_menu(){
			$this->slug = add_options_page(
				$this->title, 
				$this->name, 
				$this->capability,
				__CLASS__, 
				array($this,'show_page')
			);

			//help tabs
			add_action('load-'.$this->slug, array($this,'_help_tab'));
		}

		public function show_page(){
			?>
		    <div class="wrap">
		        <h2><?php echo $this->name; ?></h2>
		         <form action="options.php" method="POST">
		            <?php 
		            	do_settings_sections( __FILE__ );
		            	foreach ($this->sections as $s) {
		            		settings_fields($s['option_group']);
		            	}
		            	submit_button(); 
		            ?>
		        </form>
		    </div>
		    <?php
		}

		public function sanitize_callback($input){
			//sanitize
			$input = apply_filters('SimplePanel_sanitize',$input,$this->option,$this);

			//get all options
		    //$options = get_option($this->option);

		    //update only the neede options
		    foreach ($input as $key => $value){
		        $options[$key] = $value;
		    }
		    //return all options
		    return $options;
		}

		public function show_field($args){
			if (method_exists($this, '_setting_'.$args['type'])){
				call_user_func( array( $this, '_setting_'.$args['type']),$args);
				$this->_settings_field_desc($args);
			}
		}

		public function get_value($key = '',$def = ''){
			$options = get_option($this->option);
			if (isset($options[$key]))
				return $options[$key];
			return $def;
		}

		function _settings_field_desc($args){
			if (isset($args['desc']))
				echo '<p class="description">'.$args['desc'].'</p>';
		}

		function _setting_editor( $args ) {
			$std = isset($args['std'])? $args['std'] : '';
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
		    wp_editor( $value, $name);
		}

		function _setting_text( $args ) {
			$std = isset($args['std'])? $args['std'] : '';
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
		    echo "<input type='text' name='$name' value='$value' />";
		}

		function  _setting_select($args) {
			$std = isset($args['std'])? $args['std'] : '';
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
			$items = $args['options'];
			echo "<select name='$name'>";
			foreach($items as $l => $v) {
				$selected = ($value == $v) ? 'selected="selected"' : '';
				echo "<option value='$v' $selected>$l</option>";
			}
			echo "</select>";
		}

		function _setting_textarea($args) {
			$std = isset($args['std'])? $args['std'] : '';
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
			echo "<textarea  name='$name' rows='7' cols='50' type='textarea'>$value</textarea>";
		}

		function _setting_checkbox($args) {
			$std = isset($args['std'])? $args['std'] : false;
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
			$checked = ($value!= false)? ' checked="checked" ' : '';
			echo "<input ".$checked." name='$name' type='checkbox' />";
		}

		function _setting_radio($args) {
			$std = isset($args['std'])? $args['std'] : '';
		    $name = esc_attr( $args['name'] );
		    $value = esc_attr( $this->get_value($args['id'],$std));
			$items = $args['options'];
			foreach($items as $l => $v) {
				$checked = ($value==$v) ? ' checked="checked" ' : '';
				echo "<label><input ".$checked." value='$v' name='$name' type='radio' /> $l</label><br />";
			}
		}

		function section_callback() {
		    
		    //to do
		}

		public function add_field($f){
			$f['name'] = $this->option .'[' .$f['id'].']';
			$this->fields[] = $f;
		}

		public function add_section($f){
			$this->sections[] = $f;
			return $f['id'];
		}

		public function addError($e){
			$setting = _CLASS__ . esc_attr($this->option);
			$code = $e['code']; 
			$message = $e['message']; 
			$type = isset($e['type'])? $e['type']: 'error';
			add_settings_error( $setting, $code, $message, $type );
			$this->has_errors = true;
		}

		/**
		 * add_help_tab 
		 * @param array $args 
		 *      'id'	=> 'my_help_tab',
		 *      'title'	=> __('My Help Tab'),
		 *      'content'	=> '<p>' . __( 'Descriptive content that will show in My Help Tab-body goes here.' ) . '</p>',
		 *      callback' => callback function
		 */
		public function add_help_tab($args = array()){

			$this->help_tabs[] = $args;
		}

	}//end class
}//end if 