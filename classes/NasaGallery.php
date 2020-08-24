<?php
namespace sf\ng\classes;

use DateTime;

class NasaGallery {
	public static function init() {
		add_action( 'init', array(__CLASS__, 'registerPostType'), 10, 1 );
		add_action( 'admin_init', array(__CLASS__, 'testApiKey'), 10 );
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'enqueueScripts') );
		add_shortcode('nasa-gallery', array(__CLASS__, 'shortcode'));

		if( ! wp_next_scheduled( 'get_nasa_gallery_items' ) ) {
			wp_schedule_event( time(), 'daily', 'get_nasa_gallery_items');
		}

		add_action( 'get_nasa_gallery_items', array(__CLASS__, 'checkPosts'));
	}

	public static function checkPosts($qty = 1){
		$posts = get_posts(array('post_type' => SFNG_POST_TYPE, 'numberposts' => 5, 'post_status' => 'publish'));
		$qty = ( count($posts) >= 5 ) ? $qty : ( 5 - count($posts) ) ;
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
			$notices = get_transient(SFNG_NOTICES);
			$notices[] = [
				'level'   => 'warning',
				'message' => $post_id->get_error_message()
			];
			set_transient(SFNG_NOTICES, $notices);
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
				'show_in_menu'       => SFNG_PLUGIN_SLUG,
				'query_var'          => false,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'rewrite'			 => false,
				'menu_position'      => 10,
				'menu_icon'          => 'dashicons-palmtree',
				'supports'           => array('title','thumbnail'),
			)
		);
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

	public static function getRemoteItem(DateTime $date = null) {
		$format  = 'Y-m-d';
		$api_key = Options::getOption( 'sfng_api_key' );

		if ( $date === null ) {
			$date = new DateTime();
			$date->modify("-1 day");
		}

		$atts = array(
			'api_key' => $api_key,
			'date'    => $date->format( $format ),
			'hd'      => true,
		);

		$url = add_query_arg( $atts, SFNG_REMOTE_URL );

		return wp_remote_get( $url );
	}

	public static function getRemoteItems($qty = 1) {
		$items = array();
		$format  = 'Y-m-d';
		$date = new DateTime();

		if ( !static::testApiKey() ) {
			return null;
		}

		$date->modify( "-1 day" );
		$i = 0;
		while( $i < $qty ) {
			if ( $id = post_exists($date->format($format)) ) {
				if ( get_post($id)->post_status === 'trash' ) {
					wp_untrash_post($id);
					$i++;
				}
			} else {
				$response = static::getRemoteItem($date);
				if ( !is_wp_error($response) ) {
					$item = json_decode( $response['body'] );
					if ( strpos( $item->media_type, 'video' ) === false ) {
						$i++;
						$items[] = $item;
					}
				}
			}
			$date->modify( "-1 day" );
		}

		return $items;
	}

	public static function testApiKey($new_key = false) {
		$result = false;
		$notices = get_transient(SFNG_NOTICES);

		if ( $new_key || empty($notices) ) {
			$response = static::getRemoteItem();
			if ( !is_wp_error($response) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				if ( isset( $body->error ) ) {
					$notices = array();
					$body = $body->error;
					if ( isset( $body->code ) ) {
						$notices[] = [
							'level'   => 'error',
							'message' => SFNG_PLUGIN_NAME . ' error (code: ' .
							             $body->code . ') ' .
							             ( isset( $body->msg ) ? $body->msg :
								             $body->message ) .
							             "<br>\nFix it on <a href='" .
							             get_admin_url( null,
								             'admin.php?page=' .
								             SFNG_PLUGIN_OPTIONS ) .
							             "'>options page</a>."
						];
					}
					set_transient( SFNG_NOTICES, $notices );
				} else {
					delete_transient( SFNG_NOTICES );
					$result = true;
				}
			}
		}

		return $result;
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

	public static function _install() {
		static::checkPosts(0);
		wp_clear_scheduled_hook( 'get_nasa_gallery_items' );
		wp_schedule_event( time(), 'daily', 'get_nasa_gallery_items');
	}

	public static function _uninstall() {
		wp_clear_scheduled_hook( 'get_nasa_gallery_items' );
	}

}
