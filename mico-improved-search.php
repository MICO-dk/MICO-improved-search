<?php 
/**
 * MICO Improved Search
 *
 * @package 	MICO Improved Search
 * @author  	Malthe Milthers <malthe@milthers.dk> & Nina Cecilie Højholdt
 * @license 	GPL
 * @link 		MICO, http://www.mico.dk
 *
 * @wordpress-plugin
 * Plugin Name: 	MICO Improved Search
 * Plugin URI:		@TODO
 * Description: 	Extend admin search capabilities to include meta data.
 * Version: 		1.0.0
 * Author: 			Malthe Milthers & Nina Cecilie Højholdt
 * Author URI: 		http://www.malthemilthers.com
 * Text Domain: 	mico-improved-search
 * License: 		GPL
 * GitHub URI:		@TODO
 */


/**
 * Start plugins
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Limit frontend search to chosen post types + defaults (post + page)
 */
function limit_post_type($query) {

	global $pagenow;

	$areas = get_option('mico-improved-search' . '_areas_to_search');

	if ( is_search() && !is_admin() && $pagenow!='edit.php' ) :

	    $mas_post_type = get_option('mico-improved-search' . '_post_type_support');
	    $default_post_types = array('post', 'page');

	    if(is_array($mas_post_type) ) :
            $all_post_types = array_merge($mas_post_type, $default_post_types);
        else : 
            $all_post_types = $default_post_types;
        endif;

	    if ($query->is_search) {
	        //var_dump($query->get('post_type'));
	        $query->set('post_type', $all_post_types);
	    };
	    return $query;

	endif;

}
add_filter('pre_get_posts','limit_post_type');


/**
 * Admin search join
 */
add_filter('posts_join', 'admin_search_join' );
function admin_search_join($join) {
	global $pagenow, $wpdb;

	$mas_post_type = get_option('mico-improved-search' . '_post_type_support');

   	if(isset($_GET['s']) && is_array($mas_post_type) ){
	    // I want the filter only when performing a search on edit page of Custom Post Type named "segnalazioni"
	    if ( is_admin() && $pagenow=='edit.php' && in_array($_GET['post_type'], $mas_post_type) && $_GET['s'] != '') {    
	        $join .="LEFT JOIN $wpdb->postmeta AS mm ON ($wpdb->posts.ID = mm.post_id)";
	    }
	}
    return $join;
}

/**
 * Frontend search join
 */
add_filter('posts_join', 'frontend_search_join' );
function frontend_search_join($join) {
	global $pagenow, $wpdb;

	$areas = get_option('mico-improved-search' . '_areas_to_search');

   	if(isset($_GET['s']) && is_array($areas) && in_array('frontend', $areas) && is_search() && !is_admin() ){
	    $join .="LEFT JOIN $wpdb->postmeta AS mm ON ($wpdb->posts.ID = mm.post_id)";
	}
    return $join;
}

/**
 * Admin search where
 */
add_filter('posts_where', 'admin_search_where' );
function admin_search_where( $where ){
    global $pagenow, $wpdb;
    
    $mas_post_type = get_option('mico-improved-search' . '_post_type_support');
    if(isset($_GET['s']) && is_array($mas_post_type)){

	    if ( is_admin() && $pagenow=='edit.php' && in_array($_GET['post_type'], $mas_post_type) && $_GET['s'] != '') {
	        $where = preg_replace(
	       "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
	       "(".$wpdb->posts.".post_title LIKE $1) OR (mm.meta_value LIKE $1)", $where );
	    }

	}
    return $where;
}

/**
 * Frontend search where
 */
add_filter('posts_where', 'frontend_search_where' );
function frontend_search_where( $where ){
    global $pagenow, $wpdb;

    $areas = get_option('mico-improved-search' . '_areas_to_search');

   	if(isset($_GET['s']) && is_array($areas) && in_array('frontend', $areas) && is_search() && !is_admin() ){
    	$where = preg_replace(
       "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
       "(".$wpdb->posts.".post_title LIKE $1) OR (mm.meta_value LIKE $1)", $where );
    }

    return $where;
}


/**
 * Admin search destinct
 */
add_filter('posts_distinct', 'admin_search_destinct' );
function admin_search_destinct($where) {
	global $pagenow, $wpdb;
	$mas_post_type = get_option('mico-improved-search' . '_post_type_support');
    if(isset($_GET['s']) && is_array($mas_post_type)){
	    if ( is_admin() && $pagenow=='edit.php' && in_array($_GET['post_type'], $mas_post_type) && $_GET['s'] != '') {
	    	return "DISTINCT";

	    }
	}
    return $where;
}


/**
 * Frontend search destinct
 */
add_filter('posts_distinct', 'frontend_search_destinct' );
function frontend_search_destinct($where) {
	global $pagenow, $wpdb;

    $areas = get_option('mico-improved-search' . '_areas_to_search');

   	if(isset($_GET['s']) && is_array($areas) && in_array('frontend', $areas) && is_search() && !is_admin() ){
	    return "DISTINCT";
	}
    return $where;
}



/**
 * Register the administration menus for this plugin into the WordPress Dashboard menu.
 *
 * @since    1.0.0
 */
add_action( 'admin_menu', 'add_plugin_settings_menu' );
function add_plugin_settings_menu() {
	/*
	 * Add a settings page for this plugin to the Settings menu.
	 *
	 */
	add_options_page( 
		//$page_title
		__('Mico Admin Search Settings', 'mico-improved-search'),
		//$menu_title
		__('Mico Admin Search', 'mico-improved-search'),
		//$capability
		'manage_options',
		//$menu_slug
		'mico-improved-search'. '-settings',
		//$callback
		'display_plugin_admin_page'
	);
	
}


// /**
//  * Render the settings page for this plugin.
//  *
//  * @since    1.0.0
//  */
function display_plugin_admin_page() {
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<form method="POST" action="options.php">
			<?php 
				//pass slug name of page, also referred to in Settings API as option group name
				settings_fields( 'mico-improved-search' . '_mico_calendar' );
				//pass slug name of page
				do_settings_sections( 'mico-improved-search' . '-settings' );
				submit_button();
			?>
		</form>
	</div> <!-- .wrap -->

	<?php
}


/**
 * Add settings action link to the plugins page.
 *
 * @since    	1.0.0
 * @param  		array 	$links 		an array of links to desplay on the plugin page
 */
add_filter( 'plugin_action_links_'. 'mico-improved-search' .'/'. 'mico-improved-search' .'.php', 'add_action_links' );
function add_action_links( $links ) {

	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=' . 'mico-improved-search' ) . '-settings' . '">' . __( 'Settings', 'mico-improved-search' ) . '</a>'
		),
		$links
	);
}


/**
 * Add the plugin settings sections and fields. 
 * NOTE: as long as we only have one section, we dont need a title and description for the section.
 *
 * @since  1.0.0
 */
add_action('admin_init', 'add_plugin_settings');
function add_plugin_settings() {
	
	// First, we register a section. This is necessary since all future options must belong to one.
    add_settings_section(
        // ID used to identify this section and with which to register options
        'mico-improved-search' . '-settings',
		// Title to be displayed on the administration page. we dont need this right now
       	null,
        // Callback used to render the description of the section. we dont need this right now
        null,
        // Page on which to add this section of options
        'mico-improved-search' . '-settings'
    );

    // Add the post type checkbox fields.
	add_settings_field( 
	    // ID used to identify the field throughout the plugin
		'mico-improved-search' . '_post_type_support',
	    // The label to the left of the option interface element
	    'Post Types',
	    // The name of the function responsible for rendering the option interface
	    'display_post_type_support_field',
	    // The page on which this option will be displayed
	    'mico-improved-search' . '-settings',
	    // The name of the section to which this field belongs
	    'mico-improved-search' . '-settings',
	    // The array of arguments to pass to the callback. In this case, just a description.
	    array('')
	);

	add_settings_field(
		'mico-improved-search' . '_areas_to_search',
		'Areas to Search',
		'display_areas_to_search_field',
		'mico-improved-search' . '-settings',
		'mico-improved-search' . '-settings',
		array('')

	);

	// Finally, we register the fields with WordPress
	register_setting(
	    //group name. security. Must match the settingsfield() on form page
	    'mico-improved-search' . '_mico_calendar',
	    //name of field
	    'mico-improved-search' . '_post_type_support'
	);

	register_setting(
	    //group name. security. Must match the settingsfield() on form page
	    'mico-improved-search' . '_mico_calendar',
	    //name of field
	    'mico-improved-search' . '_areas_to_search'
	);
}


/**
 * Render the areas_to_search field
 *
 * @since    1.0.0
 * @param    $args      Optional arguments passed by the add_settings_field function.
 */
function display_areas_to_search_field($args) {

    $area_options = array('admin', 'frontend');

    $current_options = get_option('mico-improved-search' . '_areas_to_search'); 

    foreach($area_options as $area) :
        $current = is_array($current_options) ? in_array($area, $current_options) : false ;?>

        <p>
            <input type="checkbox" id="<?php echo 'mico-improved-search' . '_areas_to_search_' . $area ?>" name="<?php echo 'mico-improved-search' . '_areas_to_search[]' ?>" value="<?php echo $area ?>" <?php checked( true, $current, true ); ?>/>
            <label for="<?php echo 'mico-improved-search' . '_areas_to_search_' . $area ?>"><?php echo $area?></label>
        </p>

    <?php endforeach;
    
}



/**
 * Render the post_type_support field
 *
 * @since    1.0.0
 * @param    $args 		Optional arguments passed by the add_settings_field function.
 */
function display_post_type_support_field($args) {
	//get all the registered post types within WordPress
	$post_types = get_post_types(array('_builtin' => false), 'objects');

	//remove attachments and our event post type from the array
	unset($post_types['attachment']);
	unset($post_types['event']);

	//get the current option value from db. 
	$current_options = get_option('mico-improved-search' . '_post_type_support');

	?>
	<?php foreach ($post_types as $post_type) : ?>
	<?php  
		//check if post type has been checked already.
	    $current = is_array($current_options) ? in_array($post_type->name, $current_options) : false ;
	?>
	<p>
		<input type="checkbox" id="<?php echo 'mico-improved-search' . '_post_type_support_' . $post_type->name; ?>" name="<?php echo 'mico-improved-search' . '_post_type_support[]' ?>" value="<?php echo $post_type->name; ?>" <?php checked( true, $current, true ); ?>/>
		<label for="<?php echo 'mico-improved-search' . '_post_type_support_' . $post_type->name; ?>"><?php echo $post_type->labels->name;?></label>
	</p>

	<?php endforeach ?>
	<?php
}