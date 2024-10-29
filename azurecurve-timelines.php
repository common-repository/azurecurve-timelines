<?php
/*
Plugin Name: azurecurve Timelines
Plugin URI: http://development.azurecurve.co.uk/plugins/timelines

Description: Create a multiple timelines and place on pages or posts using the timeline shortcode. This plugin is multi-site compatible.
Version: 2.0.2

Author: azurecurve
Author URI: http://development.azurecurve.co.uk

Text Domain: azc_t
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

//include menu
require_once( dirname(  __FILE__ ) . '/includes/menu.php');

function azc_t_load_css(){
	wp_enqueue_style( 'azc_t', plugins_url( 'style.css', __FILE__ ), '', '1.0.0' );
}
add_action('wp_enqueue_scripts', 'azc_t_load_css');

function azc_t_load_plugin_textdomain(){
	$loaded = load_plugin_textdomain('azc_t', false, dirname(plugin_basename(__FILE__)).'/languages/');
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}
add_action('plugins_loaded', 'azc_t_load_plugin_textdomain');

function azc_t_set_default_options($networkwide) {
	
	$option_name = 'azc_t';
	$new_options = array(
						'color' => '#007FFF',
						'default' => '',
						'date' => 'd/m/Y',
						'dateleftalignment' => '-150px',
						'orderby' => 'Ascending',
					);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$original_blog_id = get_current_blog_id();

			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);

				if (get_option($option_name) === false) {
					add_option($option_name, $new_options);
				}
			}

			switch_to_blog($original_blog_id);
		}else{
			if (get_option($option_name) === false) {
				add_option($option_name, $new_options);
			}
		}
		if (get_site_option($option_name) === false) {
			add_site_option($option_name, $new_options);
		}
	}
	//set defaults for single site
	else{
		if (get_option($option_name) === false) {
			add_option($option_name, $new_options);
		}
	}
}
register_activation_hook(__FILE__, 'azc_t_set_default_options');

function azc_t_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azc-t">'.__('Settings', 'azc_t').' </a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
add_filter('plugin_action_links', 'azc_t_plugin_action_links', 10, 2);

/*
function azc_t_site_settings_menu() {
	add_options_page('azurecurve Timelines',
	'azurecurve Timelines', 'manage_options',
	'azc_t', 'azc_t_site_settings');
}
add_action('admin_menu', 'azc_t_site_settings_menu');
*/

function azc_t_site_settings() {
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to access this page.' , 'azc_t'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Retrieve plugin site options from database
	$options = get_option('azc_t');
	?>
	<div id="azc-t-general" class="wrap">
		<fieldset>
			<h2>azurecurve Timelines <?php _e(' Settings', 'azc_t'); ?></h2>
			<?php if(isset($_GET['options-updated'])) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Settings have been saved.','azc_t') ?></strong></p>
				</div>
			<?php } ?>

			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_t_site_settings" />
				<input name="page_options" type="hidden" value="color,timeline,date" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azc_t_nonce', 'azc_t_nonce'); ?>
				<table class="form-table">
					<tr><th scope="row"><label for="explanation"><?php _e('Shortcode usage', 'azc_t'); ?></label></th>
					<td>
						<p class="description"><?php _e('All parameters except slug are optional: [timeline slug=\'test-timeline\' color=\'green\' orderby=\'DESC/ASC\' date=\'d/m/Y\' left=\'-150px\']', 'azc_t'); ?></p>
					</td></tr>
					<tr><th scope="row"><label for="color"><?php _e('Default Color', 'azc_t'); ?></label></th><td>
						<input type="text" name="color" value="<?php echo esc_html(stripslashes($options['color'])); ?>" class="regular-text" />
						<p class="description"><?php _e('Specify default color of timeline (this can be overriden in the shortcode using the arguement "color="', 'azc_t'); ?></p>
					</td></tr>
					<tr><th scope="row"><label for="timeline"><?php _e('Default Timeline', 'azc_t'); ?></label></th><td>
						<select name="timeline" style="width: 200px;">
						<?php
							$timelines = get_terms( 'timeline', array( 'orderby' => 'name', 'hide_empty' => 0) );
							if ( $timelines ) {
								foreach ( $timelines as $timeline ) {
									//echo '|'.$timeline->term_id.'|';
									echo "<option value='" . $timeline->term_id . "' ";
									echo selected( $options["timeline"], $timeline->term_id ) . ">";
									echo esc_html( $timeline->name );
									echo "</option>";
								}        
							}
						?>
						</select>
					</td></tr>
					<tr><th scope="row"><label for="date"><?php _e('Default Date Format', 'azc_t'); ?></label></th><td>
						<input type="text" name="date" value="<?php echo esc_html(stripslashes($options['date'])); ?>" class="regular-text" />
						<p class="description"><?php _e('Specify default date format (default is d/M/Y)', 'azc_t'); ?></p>
					</td></tr>
					<tr><th scope="row"><label for="dateleftalignment"><?php _e('Date Left Alignment', 'azc_t'); ?></label></th><td>
						<input type="text" name="dateleftalignment" value="<?php echo esc_html(stripslashes($options['dateleftalignment'])); ?>" class="regular-text" />
						<p class="description"><?php _e('Specify left alignment for date (default for d/M/Y is -150px)', 'azc_t'); ?></p>
					</td></tr>
					<tr><th scope="row"><label for="orderby"><?php _e('Default Timeline Order By', 'azc_t'); ?></label></th><td>
						<select name="orderby" style="width: 200px;">
						<?php
							$orderbyarray = array( 'Ascending', 'Descending');
							foreach ( $orderbyarray as $orderby ) {
								echo "<option value='" . $orderby . "' ";
								echo selected( $options["orderby"], $orderby ) . ">";
								echo esc_html( $orderby );
								echo "</option>";
							}
						?>
						</select>
					</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary" />
			</form>
		</fieldset>
	</div>
<?php }

function azc_t_admin_init() {
	add_action('admin_post_save_azc_t_site_settings', 'azc_t_save_site_settings');
}
add_action('admin_init', 'azc_t_admin_init');

function azc_t_save_site_settings() {
	// Check that user has proper security level
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to perform this action.' , 'azc_t'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azc_t_nonce', 'azc_t_nonce')) {
		// Retrieve original plugin options array
		$options = get_option('azc_t');
		
		$option_name = 'color';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'timeline';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'date';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'dateleftalignment';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'orderby';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		update_option('azc_t', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azc-t&options-updated', admin_url('admin.php')));
		exit;
	}
}

function azc_t_shortcode($atts, $content = null) {
	
	global $wpdb;
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_t' );
	
	extract(shortcode_atts(array(
		'slug' => stripslashes(sanitize_text_field($options['timeline'])),
		'color' => stripslashes(sanitize_text_field($options['color'])),
		'date' => stripslashes(sanitize_text_field($options['date'])),
		'left' => stripslashes(sanitize_text_field($options['dateleftalignment'])),
		'orderby' => stripslashes(sanitize_text_field($options['orderby'])),
	), $atts));
	if ($color == ''){ $color = '#000'; }
	if ($date == ''){ $date = 'd/m/Y'; }
	if ($orderby != 'ASC' and $orderby != 'DESC'){
		if ($orderby == 'Descending'){
			$orderby = 'DESC';
		}else{
			$orderby = 'ASC';
		}
	}
	if ($left == ''){ $left = '150px'; }
	$left = 'style="left:'.$left.'; "';
	
	$sql = $wpdb->prepare("select p.ID,p.post_date,p.post_title,p.post_name,p.post_content,t.name,t.slug from ".$wpdb->prefix."posts as p
			inner join ".$wpdb->prefix."term_relationships as tr on tr.object_id = p.ID
			inner join ".$wpdb->prefix."term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
			inner join ".$wpdb->prefix."terms as t on t.term_id = tt.term_id
			where post_type = 'timeline-entry' and post_status = 'publish' and t.slug = %s
			order by post_date ".$orderby, $slug );
	//echo $sql;
	$return = "<div style='display: block; clear: both; '><ul id='azc_t' style='border-left-color: $color; '>";
	$count = 0;
	$timeline_entries = $wpdb->get_results( $sql );
	foreach ($timeline_entries as $timeline_entry){
		$count++;
		$return .= "<li class='azc_t_work'>
			<div class='azc_t_relative'>
			  <label class='azc_t' for='azc_t_work$count'>".$timeline_entry->post_title;
		$meta_fields = get_post_meta( $timeline_entry->ID, 'azc_t_metafields', true );
		if (is_array($meta_fields)){
			if (isset($meta_fields['timeline-link'])){
				if (strlen($meta_fields['timeline-link']) > 0){
					$return .= "&nbsp;<a href='".$meta_fields['timeline-link']."'><img class='azc_t' src='".plugin_dir_url(__FILE__)."images/link.png' /></a>";
				}
			}
		}
		$return .= "</label>
			  <span class='azc_t_date' $left>".Date($date, strtotime($timeline_entry->post_date))."</span>
			  <span class='azc_t_circle' style='border-color: $color; '></span>
			</div>
			<div class='azc_t_content'>
			  <p>
				".$timeline_entry->post_content."
			  </p>
			</div>
		  </li>";
	}
	$return .= "</ul></div>";
	return $return;
}

add_shortcode( 'timeline', 'azc_t_shortcode' );

function azc_t_create_custom_post_type() {
	register_post_type( 'timeline-entry	',
		array(
				'labels' => array(
				'name' => __('Timelines', 'azc_t'),
				'singular_name' => __('Timeline', 'azc_t'),
				'add_new' => __('Add New', 'azc_t'),
				'add_new_item' => __('Add New Timeline Entry', 'azc_t'),
				'edit' => __('Edit', 'azc_t'),
				'edit_item' => __('Edit Timeline Entry', 'azc_t'),
				'new_item' => __('New Timeline Entry', 'azc_t'),
				'view' => __('View', 'azc_t'),
				'view_item' => __('View Timeline Entry', 'azc_t'),
				'search_items' => __('Search Timeline Entries', 'azc_t'),
				'not_found' => __('No Timeline Entry found', 'azc_t'),
				'not_found_in_trash' => __('No Timeline Entries found in Trash', 'azc_t'),
				'parent' => __('Parent Timeline Entry', 'azc_t')
			),
		'public' => true,
		'menu_position' => 20,
		'supports' => array( 'title', 'comments', 'trackbacks', 'revisions', 'excerpt', 'editor' ),
		'taxonomies' => array( '' ),
		'menu_icon' => plugins_url( 'images/timelines-16x16.png', __FILE__ ),
		'has_archive' => true
		)
	);
}
add_action( 'init', 'azc_t_create_custom_post_type' );

function azc_t_create_timeline_taxonomy() {
$labels = array(
		'name'              => __( 'Timelines', 'azc_t' ),
		'singular_name'     => __( 'Timeline', 'azc_t' ),
		'search_items'      => __( 'Search Timelines', 'azc_t' ),
		'all_items'         => __( 'All Timelines', 'azc_t' ),
		'parent_item'       => __( 'Parent Timeline', 'azc_t' ),
		'parent_item_colon' => __( 'Parent Timeline:', 'azc_t' ),
		'edit_item'         => __( 'Edit Timeline', 'azc_t' ),
		'update_item'       => __( 'Update Timeline', 'azc_t' ),
		'add_new_item'      => __( 'Add New Timeline', 'azc_t' ),
		'new_item_name'     => __( 'New Timeline', 'azc_t' ),
		'menu_name'         => __( 'Timeline', 'azc_t' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'timeline' ),
	);

	register_taxonomy( 'timeline', 'timeline-entry', $args );

}
add_action( 'init', 'azc_t_create_timeline_taxonomy', 0 );

function azc_t_add_meta_box() {
	add_meta_box(
		'azc_t_meta_box', // $id
		'Timeline Meta Fields', // $title
		'azc_t_show_meta_box', // $callback
		'timeline-entry', // $screen
		'normal', // $context
		'high' // $priority
	);
}
add_action( 'add_meta_boxes', 'azc_t_add_meta_box' );

function azc_t_show_meta_box() {
	global $post;  
	
	$meta_fields = get_post_meta( $post->ID, 'azc_t_metafields', true ); ?>

	<input type="hidden" name="azc_t_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">

	<p>
		<label for="azc_t_metafields[timeline-link]">Timeline Link</label>
		&nbsp;&nbsp;&nbsp;
		<input type="text" name="azc_t_metafields[timeline-link]" id="azc_t_metafields[timeline-link]" class="regular-text" value="<?php echo $meta_fields['timeline-link']; ?>">
	</p>

<?php

}

function azc_t_save_meta_box( $post_id ) {   
	// verify nonce
	if ( !wp_verify_nonce( $_POST['azc_t_meta_box_nonce'], basename(__FILE__) ) ) {
		return $post_id; 
	}
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// check permissions
	if ( 'page' === $_POST['timeline-link'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}  
	}
	
	$old = get_post_meta( $post_id, 'azc_t_metafields', true );
	$new = $_POST['azc_t_metafields'];

	if ( $new && $new !== $old ) {
		update_post_meta( $post_id, 'azc_t_metafields', $new );
	} elseif ( '' === $new && $old ) {
		delete_post_meta( $post_id, 'azc_t_metafields', $old );
	}
}
add_action( 'save_post', 'azc_t_save_meta_box' );


// azurecurve menu
function azc_create_t_plugin_menu() {
	global $admin_page_hooks;
    
	add_submenu_page( "azc-plugin-menus"
						,"Timelines"
						,"Timelines"
						,'manage_options'
						,"azc-t"
						,"azc_t_site_settings" );
}
add_action("admin_menu", "azc_create_t_plugin_menu");

?>