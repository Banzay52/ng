<?php
namespace sf\ng\classes;

use sf\ng\classes\NasaGallery;

class Options {
	const PLUGIN_OPTIONS = 'sfng_options';
	const PLUGIN_OPTIONS_MENU_ID = 'sfng';
	const SFNG_SLUG_OPTION_NAME = 'sfng_slug';
	private static $sections = array(
		'api_section' => array(
			'id'    => 'sfng_api_section',
			'title' => 'API section'
		)
	);
	public static $options = array(
		'sfng_api_key' => array(
			'type'      => 'text',
			'id'        => 'sfng_api_key',
			'desc'      => 'Enter API key',
			'label_for' => 'sfng_api_key',
			'title'     => 'API key',
			'section'   => 'api_section'
		)
	);

	public function __construct() {}

	public static function init() {
		add_action( 'admin_menu', array(__CLASS__, 'regOptionsPage') );
		add_action( 'admin_init', array(__CLASS__, 'optionSettings') );
	}

	public static function getOption($name, $default = '') {
		$options = get_option(self::PLUGIN_OPTIONS, $default);
		$value = isset($options[$name]) ? $options[$name] : $default;
		return $value ?? $default;
	}
	
	public static function regOptionsPage() {
		add_options_page( 'NASA Gallery Plugin', 'NASA Gallery Plugin', 'edit_posts', self::SFNG_SLUG_OPTION_NAME, array(__CLASS__, 'optionsPage') );
	}

	public static function optionsPage() {
		?><div class="wrap">
			<h2>NASA Gallery options</h2>
			<form method="post" enctype="multipart/form-data" action="options.php">
				<?php 
				settings_fields(self::SFNG_SLUG_OPTION_NAME);
				do_settings_sections( self::SFNG_SLUG_OPTION_NAME );
				?>
				<p class="submit">  
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />  
				</p>
			</form>
		</div><?php
	}

	public static function optionSettings() {
		register_setting( self::SFNG_SLUG_OPTION_NAME, self::PLUGIN_OPTIONS, array(__CLASS__, 'parseValue') );
		foreach ( static::$sections as $name => $atts ) {
			add_settings_section( $atts['id'], $atts['title'], '', self::PLUGIN_OPTIONS_MENU_ID );
		}
			add_settings_section('sfng_api_section', 'API section', '', self::SFNG_SLUG_OPTION_NAME );
		$option_value = array();
		foreach ( static::$options as $name => $option_value ) {
			$option_value['value'] = static::getOption($name);
			if ( isset($option_value['section']) ) {
				$section_id = static::$sections[ $option_value['section'] ]['id'];
			} else {
				$option_value['section'] =  '';
			}
			add_settings_field( 'api_key_field', 'API key', array(__CLASS__, 'optionDisplaySettings'), self::SFNG_SLUG_OPTION_NAME, 'sfng_api_section', $option_value );
		}
	}

	public static function optionDisplaySettings($args) {
		extract( $args );
		switch ($type) {
			case 'text':
						echo "<input class='regular-text' type='text' id='$id' name='" . self::PLUGIN_OPTIONS . "[$id]' value='$value' />";  
						echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : ""; 
						break;
			default:
						echo "<input class='regular-text' type='text' id='' name='name' value='value' />";  
						break;
		}
	}

	public static function parseValue($options) {
		return $options;
	}

}
