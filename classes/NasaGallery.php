<?php
namespace sf\ng\classes;

class NasaGallery {
    public static function init() {
		add_action( 'init', array(__CLASS__, 'registerPostType'), 20 );
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'enqueueScripts') );
		add_shortcode('nasa-gallery', array(__CLASS__, 'shortcode'));
		if( ! wp_next_scheduled( 'get_remote_item_hook' ) ) {  
			wp_schedule_event( time(), 'daily', 'get_remote_item_hook');  
		}
		add_action( 'get_remote_item_hook', array(__CLASS__, 'checkPosts'));
    }

	public static function checkPosts(){
		$posts = get_posts(array('post_type' => SFNG_POST_TYPE, 'numberposts' => 5));
		$qty = ( count($posts) === 0 ) ? 5 : 0;
		$items = self::getRemoteItems($qty);
		foreach ( $items as $item ) {
			self::insertPost($item);
		}
	}
	public static function insertPost($item){
		$post_data = array(
			'post_title'    => $item->date,
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_type'     => SFNG_POST_TYPE
		);
		
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$post_id = wp_insert_post($post_data, true);
		if( is_wp_error($post_id) ){
			echo $post_id->get_error_message();
			die();
		}
		$attach_id = media_sideload_image( $item->url, $post_id, null, 'id' );
        set_post_thumbnail($post_id, $attach_id);

	}
    public static function registerPostType() {
		$post_labels = array(
				'name'               => 'NASA Galleries',
				'singular_name'      => 'NASA Gallery',
				'add_new'            => 'Add new',
				'add_new_item'       => 'Add new NASA Gallery',
				'edit_item'          => 'Edit NASA Gallery',
				'new_item'           => 'New NASA Gallery',
				'view_item'          => 'View NASA Gallery',
				'search_items'       => 'Search NASA Gallery',
				'not_found'          =>  'NASA Gallery not found',
				'not_found_in_trash' => 'NASA Gallery not found in trash',
				'menu_name'          => 'NASA Galleries',
			);
		register_post_type( SFNG_POST_TYPE, array(
				'labels'             => $post_labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => false,
				'rewrite'            => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'rewrite'			=> false,
				'menu_position'      => 10,
				'menu_icon'          => 'dashicons-palmtree',
				'supports'           => array('title','thumbnail'),
			) 
		);		
	}

    public static function getVersion() {
		global $wp_version;

		if ( SFNG_DEBUG === 1 ) {
			return time();
		} elseif ( defined( 'SFNG_VERSION' ) )  {
			return SFNG_VERSION;
		}
		return $wp_version;
	}
    public static function shortcode($atts) {
		$atts = shortcode_atts( array(
					'items_number' => 5,
					'sort_order'   => 'date',
					'size'         => 'medium',
				), $atts );
		
		ob_start();
		$gallery_items = get_posts(array(
				'post_type'   => SFNG_POST_TYPE,
				'numberposts' => $atts['items_number'],
				'orderby'     => $atts['sort_order'],
				'order'       => 'DESC',
				));
		include SFNG_PLUGIN_DIR . '/frontend/shortcode_view.php';
		
		return ob_get_clean();
	}
	public static function enqueueScripts() {
		wp_enqueue_style('sfng-slick-style', SFNG_PLUGIN_URL . "assets/css/slick.css", array(), self::getVersion());
		wp_enqueue_style('sfng-slick-theme', SFNG_PLUGIN_URL . "assets/css/slick-theme.css", array(), self::getVersion());
		wp_enqueue_script('sfng-slick-js', SFNG_PLUGIN_URL . "assets/js/slick.min.js", array('jquery'), self::getVersion(), true);
		wp_enqueue_script('sfng-js', SFNG_PLUGIN_URL . "assets/js/sfng.js", array('jquery', 'sfng-slick-js'), self::getVersion(), true);
	}

	public static function getRemoteItems($qty = 1) {
		$items = array();

		$api_key = Options::getOption('sfng_api_key');
		if ( empty($api_key) ) {
			return $items;
		}

	    $date = new \DateTime();
		$date->modify( "-1 day" );
		$format = 'Y-m-d';
		$remote_url = "https://api.nasa.gov/planetary/apod";
		$atts = array(
				'api_key' => $api_key,
				'date'    => $date->format( $format ),
				'hd'      => false,
			);
		$i = 0;
		$j = 0;
		while( $i < $qty && $j < 20 ) {
			$url = add_query_arg($atts, $remote_url);
			$response = wp_remote_get($url);
			$item = json_decode( $response['body'] );
 			if ( strpos($item->media_type, 'video') === false ) {
 				$i++;
				$items[] = $item;
 			}
			$date->modify( "-1 day" );
			$atts['date'] = $date->format( $format );
			$j++;
		}
		return $items;
	}
    public static function _install() {
		wp_clear_scheduled_hook( 'get_remote_item_hook' );
		wp_schedule_event( time(), 'daily', 'get_remote_item_hook');
    }
    public static function _uninstall() {
		wp_clear_scheduled_hook( 'get_remote_item_hook' );
    }
}
