<?php
/*
Plugin Name: ESSB: Experimental Grid Share Buttons
Plugin URI: http://appscreo.com
Description: An experiment to display share buttons inside Visual Composer Grid
Author: AppsCreo
Version: 1.0
Author URI: http://appscreo.com
*/

add_action('plugins_loaded', 'appscreo_register_grid_shortcode', 99);

//add_filter( 'vc_gitem_template_attribute_custom_post_title', 'vc_gitem_template_attribute_custom_post_title', 10, 2 );
add_filter( 'vc_gitem_template_attribute_custom_post_image', 'vc_gitem_template_attribute_custom_post_image', 10, 2 );
add_filter( 'vc_gitem_template_attribute_custom_post_link_url', 'vc_gitem_template_attribute_custom_post_link_url', 10, 2 );

function vc_gitem_template_attribute_custom_post_title($value, $data) {
   /**
    * @var Wp_Post $post
    * @var string $data
    */
   extract( array_merge( array(
      'post' => null,
      'data' => ''
   ), $data ) );
 
   /** @var $post - current loop post */
   $post_title = get_the_title($post);
   
   return $post_title;
}

function vc_gitem_template_attribute_custom_post_image( $value, $data ) {
	/**
	 * @var null|Wp_Post $post ;
	 */
	extract( array_merge( array(
			'post' => null,
			'data' => '',
	), $data ) );
	$feat_image = wp_get_attachment_url( get_post_thumbnail_id($post->ID) ); 

	return $feat_image;
}

function vc_gitem_template_attribute_custom_post_link_url( $value, $data ) {
	/**
	 * @var null|Wp_Post $post ;
	 */
	extract( array_merge( array(
			'post' => null,
			'data' => '',
	), $data ) );
	return get_permalink( $post->ID );
	//return $post->ID;
}

function appscreo_register_grid_shortcode() {
	add_filter( 'vc_grid_item_shortcodes', 'my_module_add_grid_shortcodes' );
	add_shortcode( 'essb_vc_grid_sharing', 'vc_post_id_render' );
}


function my_module_add_grid_shortcodes( $shortcodes ) {
	if (! class_exists ( 'ESSBShortcodeGenerator3' )) {
		include_once (ESSB3_PLUGIN_ROOT . 'lib/admin/essb-shortcode-generator.php');
	}
	
	global $essb_options, $essb_networks;
	
	// creating instance of Shortcode Generator
	$scg = new ESSBShortcodeGenerator3 ();
	
	$scg->activate ( 'easy-social-share' );
	$shortcode = 'easy-social-share';
	$last_used_group = 'Easy Social Share Buttons';
	$vc_shortcode_settings [$shortcode] = array ();
	$exist_network_names = false;
	$exist_sections = false;
	foreach ( $scg->shortcodeOptions as $param => $settings ) {
		$type = isset ( $settings ['type'] ) ? $settings ['type'] : 'textbox';
		$text = isset ( $settings ['text'] ) ? $settings ['text'] : '';
		if ($type == "section" && ! empty ( $text )) {
			$exist_sections = true;
		}
	}
	
	foreach ( $scg->shortcodeOptions as $param => $settings ) {
		$type = isset ( $settings ['type'] ) ? $settings ['type'] : 'textbox';
		$text = isset ( $settings ['text'] ) ? $settings ['text'] : '';
		if ($type == "section" && ! empty ( $text )) {
			$last_used_group = $text;
		}
		if ($type == "section" || $type == "subsection") {
			continue;
		}
	
		// additional options
	
		$comment = isset ( $settings ['comment'] ) ? $settings ['comment'] : '';
		$default_value = isset ( $settings ['value'] ) ? $settings ['value'] : '';
		$values = isset ( $settings ['sourceOptions'] ) ? $settings ['sourceOptions'] : array ();
	
		$vc_type = $type;
	
		if ($vc_type == "textbox") {
			$vc_type = "textfield";
		}
	
		$is_networks_selection = false;
	
		if ($vc_type == "networks") {
			$vc_type = "checkbox";
			$is_networks_selection = true;
		}
	
		if ($vc_type == "network_names") {
			$exist_network_names = true;
		}
	
		// TODO: make network selection possible
		if ($vc_type == "networks" || $vc_type == "networks_sp" || $vc_type == "network_names") {
			continue;
		}
	
		$singleParam = array ();
		$singleParam ['type'] = $vc_type;
		$singleParam ['heading'] = $text;
		$singleParam ['param_name'] = $param;
		$singleParam ['description'] = $comment;
		if ($exist_sections) {
			$singleParam ['group'] = $last_used_group;
		}
	
		if ($param == "title" || $param == "columns" || $param == "template") {
			$singleParam ['admin_label'] = true;
		}
	
		if ($vc_type == "checkbox") {
			if (! $is_networks_selection) {
				$singleParam ['value'] = array ();
				$singleParam ['value'] ["Yes"] = $default_value;
			} else {
				$singleParam ['value'] = array ();
				$singleParam ['admin_label'] = true;
				if ($is_networks_selection) {
					foreach ( $essb_networks as $key => $value ) {
						$network_name = isset ( $value ['name'] ) ? $value ['name'] : $key;
						$singleParam ['value'] [$network_name] = $key;
					}
				}
			}
		}
		if ($vc_type == "dropdown") {
			$singleParam ['value'] = array ();
			foreach ( $values as $key => $value ) {
				$singleParam ['value'] [$value] = $key;
			}
		}
	
		$vc_shortcode_settings [$shortcode] [] = $singleParam;
	}
	
	if ($exist_network_names) {
		foreach ( $essb_networks as $key => $value ) {
			$network_name = isset ( $value ['name'] ) ? $value ['name'] : $key;
			$singleParam = array ();
			$singleParam ['type'] = 'textfield';
			$singleParam ['heading'] = $network_name . ' custom button text';
			$singleParam ['param_name'] = $key . '_text';
			$singleParam ['description'] = 'Customize text that will appear for network name';
			if ($exist_sections) {
				$singleParam ['group'] = $last_used_group;
			}
			$vc_shortcode_settings [$shortcode] [] = $singleParam;
		}
	}
	
	
	
   $shortcodes['essb_vc_grid_sharing'] = array(
     'name' => __( 'Social Sharing Buttons', 'essb' ),
     'base' => 'essb_vc_grid_sharing',
     'category' => __( 'Easy Social Share Buttons', 'essb' ),
     'description' => __( 'Include sharing buttons into Visual Composer Grid', 'essb' ),
     'post_type' => Vc_Grid_Item_Editor::postType(),
   	 "params" => $vc_shortcode_settings [$shortcode],
  );
 
 
   return $shortcodes;
}
 
// output function

function vc_post_id_render() {
   
	return do_shortcode('[easy-social-share url="{{custom_post_link_url}}" title="{{post_data:post_title}}" image="{{custom_post_image}}"]');
	
}
?>
