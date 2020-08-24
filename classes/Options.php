<?php
namespace sf\ng\classes;

use sf\ng\classes\NasaGallery;

class Options {
	const PLUGIN_OPTIONS_MENU_ID = 'sfng';
	const SFNG_PLUGIN_SLUG = 'sfng_slug';
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
		add_action('updated_option', array(__CLASS__, 'checkUpdatedOptions'), 10, 3);
	}

	public static function getOption($name, $default = '') {
		$options = get_option(SFNG_PLUGIN_OPTIONS, $default);
		$value = isset($options[$name]) ? $options[$name] : $default;
		return $value ? $value : $default;
	}
	
	public static function regOptionsPage() {
		add_menu_page( 'NASA Gallery Plugin', 'NASA Gallery Plugin', 'manage_options', SFNG_PLUGIN_SLUG );
		add_submenu_page( SFNG_PLUGIN_SLUG,
            'NASA Gallery Options',
            'Options',
            'manage_options',
			SFNG_PLUGIN_OPTIONS,
            array(__CLASS__, 'optionsPage') );
		remove_submenu_page(SFNG_PLUGIN_SLUG, SFNG_PLUGIN_SLUG);
	}

	public static function optionsPage() {
		?><div class="wrap">
			<h2>NASA Gallery options</h2>
			<form method="post" enctype="multipart/form-data" action="options.php">
				<?php 
				settings_fields(SFNG_PLUGIN_SLUG);
				do_settings_sections( SFNG_PLUGIN_SLUG );
				?>
				<p class="submit">  
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />  
				</p>
			</form>
		</div><?php
	}

	public static function optionSettings() {
		register_setting( SFNG_PLUGIN_SLUG, SFNG_PLUGIN_OPTIONS, array(__CLASS__, 'parseValue') );
		foreach ( static::$sections as $name => $atts ) {
			add_settings_section( $atts['id'], $atts['title'], '', self::PLUGIN_OPTIONS_MENU_ID );
		}
			add_settings_section('sfng_api_section', 'API section', '', SFNG_PLUGIN_SLUG );
		$option_value = array();
		foreach ( static::$options as $name => $option_value ) {
			$option_value['value'] = static::getOption($name);
			if ( isset($option_value['section']) ) {
				$section_id = static::$sections[ $option_value['section'] ]['id'];
			} else {
				$option_value['section'] =  '';
			}
			add_settings_field( 'api_key_field',
                'API key',
                array(__CLASS__, 'optionDisplaySettings'),
                SFNG_PLUGIN_SLUG,
                'sfng_api_section', $option_value );

		}
	}

	public static function optionDisplaySettings($args) {
		extract( $args );
		switch ($type) {
			case 'text':
						echo "<input class='regular-text' type='text' id='$id' name='" .
						     SFNG_PLUGIN_OPTIONS . "[$id]' value='$value' />";
						echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : ""; 
						break;
			default:
						echo "<input class='regular-text' type='text' id='' name='name' value='value' />";  
						break;
		}
	}

	public static function checkUpdatedOptions($opt_name, $old_value = '', $value = '') {
	    If ( $opt_name === 'sfng_options' ) {
			if ( NasaGallery::testApiKey( $old_value != $value) ) {
				NasaGallery::checkPosts();
			}
		}
    }

	public static function parseValue($options) {
//		NasaGallery::testApiKey(true);
		return $options;
	}

}
