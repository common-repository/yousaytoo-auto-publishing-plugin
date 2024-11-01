<?php
/*
Contributors: Sergey Margaritov
Plugin Name: YouSayToo 
Plugin URI: http://www.margaritov.net
Description: YouSayToo auto-publishing plugin and backlink widget all in one. It will automatically publish your posts in your YouSayToo account with credits to your blog. Email us at <a href="mailto:feedback@yousaytoo.com">feedback@yousaytoo.com</a> if you have any questions.
Version: 1.0
Author: Sergey Margaritov
Author URI: http://www.yousaytoo.com/
Tags: yousaytoo, blog, social
License: GPL2
*/

define('YOUSAYTOO_PLUGIN_VERSION', '1.0');
define('YOUSAYTOO_WIDGET_NAME', 'YouSayToo');
define('YOUSAYTOO_WIDGET_PREFIX', 'uSay2');
define('YOUSAYTOO_WIDGET_NAME_INTERNAL', 'yousaytoo');
define('YOUSAYTOO_PLUGIN_ADMIN_URL', __("plugins.php?page=yousaytoo"));
define('YOUSAYTOO_LOGO_URL', 'http://www.yousaytoo.com/images/new/yousaytoo_logo_tiny.png');
define('YOUSAYTOO_IMAGE_URL', 'http://www.yousaytoo.com/backlinks/%s.png'); # %s is API KEY
define('YOUSAYTOO_REFERENCE_URL', 'http://www.yousaytoo.com/backlinks/%s'); # %s is API KEY
define('YOUSAYTOO_POST_URL', 'http://www.yousaytoo.com/posts.wordpress');
define('YOUSAYTOO_IMAGE_ALT_TEXT', 'YouSayToo Revenue Sharing Comminity');
define('YOUSAYTOO_POST_CHECK', '__yousaytoo__');

add_action('init', 'yousaytoo_init');
add_action('admin_notices', 'yousaytoo_warning', 1);
add_action('publish_post','yousaytoo_publish_post');

// ============================================================
// execute yousaytoo_widget_init()
// ============================================================
add_action('widgets_init', 'yousaytoo_widget_init');

class yousaytoo_widget extends WP_Widget {
 
	// ============================================================
	// Constructer
	// ============================================================
	function yousaytoo_widget () {
		$widget_ops = array(
            'description' => 'Display YouSayToo Widget'
    	);
    	parent::WP_Widget(false, $name = 'YouSayToo', $widget_ops);
    }
 
	// ============================================================
    // Form
	// ============================================================
    function form( $instance ) {
		//Reading the existing data from $instance
    	$options = get_option(YOUSAYTOO_WIDGET_NAME_INTERNAL);
    	if (!isset($options['api_key'])) $options['api_key'] = '';
        echo '
        <p style="text-align:center;">
            <img src="'.YOUSAYTOO_LOGO_URL.'">
        </p>
        <!--Form-->
        <p>
            <label for="'.$this->get_field_id('api_key').'">'.__('YouSayToo API key:').' <input class="widefat" id="'.$this->get_field_id('api_key').'" name="'.$this->get_field_name('api_key').'" type="text" value="'.$options['api_key'].'" /></label>
        </p>
        <!--/Form-->';
    }
 
 
	// ============================================================
    // Update
	// ============================================================
    function update( $new_instance, $old_instance ) {
        // Old Instance and New instance
    	$options = get_option(YOUSAYTOO_WIDGET_NAME_INTERNAL);
    	if (!isset($options['api_key'])) $options['api_key'] = '';
    	$instance = $old_instance;
		$instance['api_key'] = yousaytoo_api_key($new_instance['api_key']);
        return $instance;    
    }
 
	// ============================================================
	// View
	// ============================================================
	function widget( $args, $instance ) {

		extract($args);
		
		$api_key = yousaytoo_api_key();
		if (!$api_key) return false;
        
        echo $before_widget;
		if ( $account )
		    echo $before_title . 'YouSayToo' . $after_title;
		    
		echo '<div align="center"><a href="'.sprintf(YOUSAYTOO_REFERENCE_URL, urlencode($api_key)).'"><img alt="'.YOUSAYTOO_IMAGE_ALT_TEXT.'" src="'.sprintf(YOUSAYTOO_IMAGE_URL, $api_key).'"></a></div>';

		echo $after_widget; 
	}
}

// ============================================================
// Registering plug-ins
// ============================================================
function yousaytoo_widget_init() {
    // Registering class name
    register_widget('yousaytoo_widget');
}

function yousaytoo_init() {
	if ( is_admin() ) {
		add_action('admin_menu', 'yousaytoo_pages');
	}
}

function yousaytoo_pages() {
	if ( function_exists('add_submenu_page') ) {
		$page = add_submenu_page('plugins.php', YOUSAYTOO_WIDGET_NAME, YOUSAYTOO_WIDGET_NAME, 'manage_options', YOUSAYTOO_WIDGET_NAME_INTERNAL, 'yousaytoo_conf');
	}
}

function yousaytoo_api_key_name() {
    return sprintf('%s_%s', YOUSAYTOO_WIDGET_PREFIX, 'api_key');
}

function yousaytoo_api_key($api_key = null) {
    $options = get_option(YOUSAYTOO_WIDGET_NAME_INTERNAL);
    if (!isset($options['api_key'])) $options['api_key'] = '';
    if (isset($api_key)) {
	    $options['api_key'] = $api_key;
	    update_option(YOUSAYTOO_WIDGET_NAME_INTERNAL, $options);
	} 
	return $options['api_key'];
}

function yousaytoo_conf() {

	$field_name = yousaytoo_api_key_name();
    
	if ( isset($_POST) && array_key_exists($field_name, $_POST) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
			die(__('Cheatin&#8217; uh?'));
		}
		
		if (array_key_exists($field_name, $_POST)) {
		    yousaytoo_api_key(htmlspecialchars($_POST[$field_name]));
		}
		
	}
	
    $html = '
	<div id="networkpub_msg"></div>
	<div class="wrap">
		<h2><img src="http://www.yousaytoo.com/images/new/yousaytoo_logo_tiny.png"></h2>
	</span>
	</div>
    <form action="" method="post" id="yousaytoo_api" name="yousaytoo_api">
        <div id="poststuff" class="metabox-holder has-right-sidebar">
            <div id="post-body">
                <div id="post-body-content">
                    <div id="addressdiv" class="stuffbox">
                        <h3><label for="'.$field_name.'">YouSayToo API Key</label></h3>
                        <div class="inside">
                        	<input type="text" name="'.$field_name.'" size="30" class="code" tabindex="1" value="'.yousaytoo_api_key().'" id="'.$field_name.'" style="width:80%;">
                            <p>Put the API key given at <a href="">[link]</a> in the field above to install the YouSayToo plugin on your blog.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>
        <input type="submit" name="submit" class="button-primary" value="Save API Key" />
	</form>';
	echo $html;
}

function yousaytoo_version() {
	return YOUSAYTOO_PLUGIN_VERSION;
}

function yousaytoo_publish_post($post_id) {

  $api_key = yousaytoo_api_key();
  if (!$api_key) return false;
	//Post data
	$post_data = get_post( $post_id, ARRAY_A );
	//Post Published?
	if(!in_array($post_data['post_status'], array('future', 'publish'))) {
		return;	
	}
	//Post data: Permalink
	$post_link = get_permalink($post_id);
	//Post data: Categories
	$post_categories_array = array();
	$post_categories_data = get_the_category( $post_id );
	foreach($post_categories_data as $category) {
		$post_categories_array[] = $category->cat_name;
	}
	$post_categories = implode(",", $post_categories_array);
	//Post tags
	$post_tags_array = array();
	$post_tags_data = wp_get_post_tags( $post_id );
	foreach($post_tags_data as $tag) {
		$post_tags_array[] = $tag->name;
	}
	$post_tags = implode(",", $post_tags_array);
	//Post Geo
	if(function_exists('get_wpgeo_latitude')) {
		if(get_wpgeo_latitude( $post_id ) and get_wpgeo_longitude( $post_id )) {
			$post_geotag = get_wpgeo_latitude( $post_id ).' '.get_wpgeo_longitude( $post_id );
		}
	}
	if(!isset($post_geotag)) {
		$post_geotag = '';
	}
	// Build Params
	$link = YOUSAYTOO_POST_URL;
	$params = array(
    'id'=>$id,
    'api_key'=>$api_key,
		'post_id' => $post_id,
		'post_link' => $post_link,
		'post_title' => $post_title,
		'post_content' => $post_content,
    'plugin' => 'yousaytoo',
    'plugin_version' => yousaytoo_version(),
		'post_categories' => $post_categories,
		'post_tags' => $post_tags,
		'post_geotag' => $post_geotag,
		'post_data' => $post_data,
		'post'  => array(
		  
		)
	);
	$response_full = yousaytoo_http_post($link, $params);
	$response_code = $response_full[0];
	if($response_code == 200) {
		update_post_meta( $post_id, '_yousaytoo_meta_published', time() );
	}
	return;
}

function yousaytoo_http_post($link, $body) {
  if (!$link) {
		return array(500, 'invalid url');
	}
	if( !class_exists( 'WP_Http' ) ) {
		include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	if (class_exists('WP_Http')) {
		$request = new WP_Http;
		$headers = array( 'Agent' => YOUSAYTOO_WIDGET_NAME.' - '.get_bloginfo('url') );
		$response_full = $request->request( $link, array( 'method' => 'POST', 'body' => $body, 'headers'=>$headers) );
		if(isset($response_full->errors)) {
			return array(500, 'internal error');
		}
		if(!is_array($response_full['response'])) {
      return array(500, 'internal error');
    }
		$response_code = $response_full['response']['code'];
		if ($response_code == 200) {
			$response = $response_full['body'];
			return array($response_code, $response);
		}
		$response_msg = $response_full['response']['message'];
		return array($response_code, $response_msg);
	}
	return array(500, 'internal error');
}

// Add a link to this plugin's settings page
function yousaytoo_actlinks( $links ) { 
    $settings_link = '<a href="'.YOUSAYTOO_PLUGIN_ADMIN_URL.'">Settings</a>'; 
    array_unshift( $links, $settings_link ); 
    return $links; 
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'yousaytoo_actlinks' ); 

function yousaytoo_warning() {
	$options = get_option(YOUSAYTOO_WIDGET_NAME_INTERNAL);
    $field_name = yousaytoo_api_key_name();
    if (isset($_POST[$field_name]) && $_POST[$field_name]) return;
    if (!empty($options['api_key']) && (!isset($_POST[$field_name]) || $POST[$field_name])) return;
	echo "
	<div class='updated fade' style='width:80%;'>
		<p>
			<strong>".__('YouSayToo plugin is almost ready.')."</strong> ".
			sprintf(__('Enter the API key provided at <a href="%1$s">Plugins->YouSayToo</a>'), "plugins.php?page=yousaytoo")."
		</p>
	</div>
	";
}

# yousaytoo check request
if (isset($_GET[YOUSAYTOO_POST_CHECK])) {
    header("Content-Type: text");
    if ($_GET[YOUSAYTOO_POST_CHECK] == yousaytoo_api_key()) {
        header("X-YouSayToo-Plugin: OKyst");
        print "OKyst";
    } 
    die();
}

?>