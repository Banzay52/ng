<?php
/*
 Plugin Name: NASA Gallery Plugin
 Plugin URI: 
 Description: This plugin renders NASA image galleries
 Version: 1.0.0
 Author: Serhii Franchuk
 Author URI: 
 Licence: GPL
*/

namespace sf\ng;

if ( !defined( 'ABSPATH' ) ) exit;


define( 'SFNG_POST_TYPE', 'post-nasa-gallery' );
define( 'SFNG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SFNG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SFNG_PLUGIN_FILE', __FILE__ );
define( 'SFNG_PLUGIN_NAME', 'NASA Gallery' );
define( 'SFNG_PLUGIN_SLUG', 'nasa-gallery' );
define( 'SFNG_PLUGIN_OPTIONS', 'sfng_options' );
define( 'SFNG_VERSION', '1.0.0' );
define( 'SFNG_DEBUG', 1 );
define( 'SFNG_REMOTE_URL', "https://api.nasa.gov/planetary/apod");
define( 'SFNG_NOTICES', "sfng_admin_notices");

require_once SFNG_PLUGIN_DIR . '/classes/Options.php';
require_once SFNG_PLUGIN_DIR . '/classes/NasaGallery.php';

classes\NasaGallery::init();
classes\Options::init();

register_activation_hook( __FILE__, array( 'sf\ng\classes\NasaGallery', '_install' ) );
register_deactivation_hook( __FILE__, array( 'sf\ng\classes\NasaGallery', '_uninstall' ) );
add_action( 'admin_notices', 'sf\ng\my_plugin_notice' );
function my_plugin_notice() {
	$notices = get_transient(SFNG_NOTICES);
	if ( !empty($notices) && is_array($notices) && count($notices) ) {
		foreach ( $notices as $notice ) {
			echo "<div class='notice notice-{$notice['level']} is-dismissible'><p>{$notice['message']}</p></div>";
		}
	}
	delete_transient(SFNG_NOTICES);
}
