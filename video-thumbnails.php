<?php
/*
Plugin Name: Video Thumbnails
Plugin URI: http://sutherlandboswell.com/2010/11/wordpress-video-thumbnails/
Description: Automatically retrieve video thumbnails for your posts and display them in your theme. Currently supports YouTube, Vimeo, Blip.tv, and Justin.tv.
Author: Sutherland Boswell
Author URI: http://sutherlandboswell.com
Version: 1.0.3
License: GPL2
*/
/*  Copyright 2010 Sutherland Boswell  (email : sutherland.boswell@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Get Vimeo Thumbnail
function getVimeoInfo($id, $info = 'thumbnail_large') {
    if (!function_exists('curl_init')) {
    	return null;
    } else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://vimeo.com/api/v2/video/$id.php");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = unserialize(curl_exec($ch));
		$output = $output[0][$info];
		curl_close($ch);
		return $output;
    }
};

// Blip.tv Functions
function getBliptvInfo($id) {
	$xml = simplexml_load_file("http://blip.tv/file/$id?skin=rss");
	$result = $xml->xpath("/rss/channel/item/media:thumbnail/@url");
	$thumbnail = (string) $result[0]['url'];
	return $thumbnail;
}

// Justin.tv Functions
function getJustintvInfo($id) {
	$xml = simplexml_load_file("http://api.justin.tv/api/clip/show/$id.xml");
	return (string) $xml->clip->image_url_large;
}

// The Main Event
function get_video_thumbnail($post_id=null) {
	
	// Get the post ID if none is provided
	if($post_id==null OR $post_id=='') $post_id = get_the_ID();
	
	// Check to see if thumbnail has already been found
	if( ($thumbnail_meta = get_post_meta($post_id, '_video_thumbnail', true)) != '' ) {
		return $thumbnail_meta;
	}
	// If the thumbnail isn't stored in custom meta, fetch a thumbnail
	else {

		// Gets the post's content
		$post_array = get_post($post_id); 
		$markup = $post_array->post_content;
		$new_thumbnail = null;
		
		// Simple Video Embedder Compatibility
		if(function_exists('p75HasVideo')) {
			if ( p75HasVideo($post_id) ) {
			    $markup = p75GetVideo($post_id);
			}
		}
		
		// Checks for a standard YouTube embed
		preg_match('#<object[^>]+>.+?http://www.youtube.com/v/([A-Za-z0-9\-_]+).+?</object>#s', $markup, $matches);
	
		// Checks for any YouTube URL
		if(!isset($matches[1])) {
			preg_match('#http://w?w?w?.?youtube.com/watch\?v=([A-Za-z0-9\-_]+)#s', $markup, $matches);
		}
		
		// If no standard YouTube embed is found, checks for one embedded with JR_embed
		if(!isset($matches[1])) {
			preg_match('#\[youtube id=([A-Za-z0-9\-_]+)]#s', $markup, $matches);
		}
		
		// If we've found a YouTube video ID, create the thumbnail URL
		if(isset($matches[1])) {
			$youtube_thumbnail = 'http://img.youtube.com/vi/' . $matches[1] . '/0.jpg';
			
			// Check to make sure it's an actual thumbnail
			if (!function_exists('curl_init')) {
				$new_thumbnail = $youtube_thumbnail;
			} else {
				$ch = curl_init($youtube_thumbnail);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_exec($ch);
				$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				// $retcode > 400 -> not found, $retcode = 200, found.
				curl_close($ch);
				if($retcode==200) {
					$new_thumbnail = $youtube_thumbnail;
				}
			}
		}
		
		// Vimeo
		if($new_thumbnail==null) {
		
			// Standard embed code
			preg_match('#<object[^>]+>.+?http://vimeo.com/moogaloop.swf\?clip_id=([A-Za-z0-9\-_]+)&.+?</object>#s', $markup, $matches);
			
			// Find Vimeo embedded with iframe code
			if(!isset($matches[1])) {
				preg_match('#http://player.vimeo.com/video/([0-9]+)#s', $markup, $matches);
			}
			
			// If we still haven't found anything, check for Vimeo embedded with JR_embed
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo id=([A-Za-z0-9\-_]+)]#s', $markup, $matches);
		    }
	
			// If we still haven't found anything, check for Vimeo URL
			if(!isset($matches[1])) {
		    	preg_match('#http://w?w?w?.?vimeo.com/([A-Za-z0-9\-_]+)#s', $markup, $matches);
		    }
	
			// If we still haven't found anything, check for Vimeo shortcode
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo clip_id="([A-Za-z0-9\-_]+)"[^>]*]#s', $markup, $matches);
		    }
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo video_id="([A-Za-z0-9\-_]+)"[^>]*]#s', $markup, $matches);
		    }
		
			// Now if we've found a Vimeo ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$vimeo_thumbnail = getVimeoInfo($matches[1], $info = 'thumbnail_large');
				if(isset($vimeo_thumbnail)) {
					$new_thumbnail = $vimeo_thumbnail;
				}
			}
		}
		
		// Blip.tv
		if($new_thumbnail==null) {
		
			// Blip.tv file URL
			preg_match('#http://blip.tv/file/([0-9]+)#s', $markup, $matches);

			// Now if we've found a Blip.tv file URL, let's set the thumbnail URL
			if(isset($matches[1])) {
				$blip_thumbnail = getBliptvInfo($matches[1]);
				$new_thumbnail = $blip_thumbnail;
			}
		}
		
		// Justin.tv
		if($new_thumbnail==null) {
		
			// Justin.tv archive ID
			preg_match('#archive_id=([0-9]+)#s', $markup, $matches);

			// Now if we've found a Justin.tv archive ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$justin_thumbnail = getJustintvInfo($matches[1]);
				$new_thumbnail = $justin_thumbnail;
			}
		}
		
		// Return the new thumbnail variable and update meta if one is found
		if($new_thumbnail!=null) {
		
			// Save as Attachment if enabled
			if(get_option('video_thumbnails_save_media')==1) {
			
				$upload = wp_upload_bits(basename($new_thumbnail), null, file_get_contents($new_thumbnail));
				
				$new_thumbnail = $upload['url'];
				
				$filename = $upload['file'];
				
				$wp_filetype = wp_check_filetype(basename($filename), null );
				$attachment = array(
				   'post_mime_type' => $wp_filetype['type'],
				   'post_title' => get_the_title($post_id),
				   'post_content' => '',
				   'post_status' => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
				wp_update_attachment_metadata( $attach_id,  $attach_data );
				
			}
			
			// Add hidden custom field with thumbnail URL
			if(!update_post_meta($post_id, '_video_thumbnail', $new_thumbnail)) add_post_meta($post_id, '_video_thumbnail', $new_thumbnail);
			
			// Set attachment as featured image if enabled
			if(get_option('video_thumbnails_set_featured')==1 && get_option('video_thumbnails_save_media')==1 && get_post_meta($post_id, '_thumbnail_id', true) == '' ) {
				if(!update_post_meta($post_id, '_thumbnail_id', $attach_id)) add_post_meta($post_id, '_thumbnail_id', $attach_id);
			}
		}
		return $new_thumbnail;

	}
};

// Echo thumbnail
function video_thumbnail($post_id=null) {
	if( ( $video_thumbnail = get_video_thumbnail($post_id) ) == null ) { echo plugins_url() . "/video-thumbnails/default.jpg"; }
	else { echo $video_thumbnail; }
};

// Create Meta Fields on Edit Page

add_action("admin_init", "video_thumbnail_admin_init");
 
function video_thumbnail_admin_init(){
	add_meta_box("video_thumbnail", "Video Thumbnail", "video_thumbnail_admin", "post", "side", "low");
}
 
function video_thumbnail_admin(){
	global $post;
	$custom = get_post_custom($post->ID);
	$video_thumbnail = $custom["_video_thumbnail"][0];
	?>
	<p><label>Video Thumbnail URL:</label></p>
	<p><input type="text" size="16" name="video_thumbnail" style="width:250px;" value="<?php echo $video_thumbnail; ?>" /></p>
	<?php if(isset($video_thumbnail) && $video_thumbnail!='') { ?><p><img src="<?php echo $video_thumbnail; ?>" width="100%" /></p><?php } ?>
	<?php
}

// Save Meta Details

add_action('save_post', 'save_details');

function save_details(){
	global $post;
	if(isset($_POST["video_thumbnail"]) && $_POST["video_thumbnail"]!='') {
		if(!update_post_meta($post->ID, "_video_thumbnail", $_POST["video_thumbnail"])) add_post_meta($post->ID, "_video_thumbnail", $_POST["video_thumbnail"]);
	}
	if(isset($_POST["video_thumbnail"]) && $_POST["video_thumbnail"]=='') {
		delete_post_meta($post->ID, "_video_thumbnail");
	}
}

// Find video thumbnail when saving a post

add_action('save_post', 'save_video_thumbnail');

function save_video_thumbnail($post_ID){
  get_video_thumbnail($post_ID);
}

// Set Default Options

register_activation_hook(__FILE__,'video_thumbnails_activate');
register_deactivation_hook(__FILE__,'video_thumbnails_deactivate');

function video_thumbnails_activate() {
	add_option('video_thumbnails_save_media','1');
	add_option('video_thumbnails_set_featured','1');
}

function video_thumbnails_deactivate() {
	delete_option('video_thumbnails_save_media');
	delete_option('video_thumbnails_set_featured');
}

// Check for cURL

register_activation_hook(__FILE__,'video_thumbnails_curl_check');

function video_thumbnails_curl_check(){
	if (!function_exists('curl_init')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die("Sorry, but this plugin requires cURL to be activated on your server.<BR><BR>Google should be able to help you.");
	}
}

// Aministration

add_action('admin_menu', 'video_thumbnails_menu');

function video_thumbnails_menu() {

  add_options_page('Video Thumbnail Options', 'Video Thumbnails', 'manage_options', 'video-thumbnail-options', 'video_thumbnail_options');

}

function video_thumbnail_options() {

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
  
function video_thumbnails_checkbox_option($option_name, $option_description) { ?>
	<fieldset><legend class="screen-reader-text"><span><?php echo $option_description; ?></span></legend> 
	<label for="<?php echo $option_name; ?>"><input name="<?php echo $option_name; ?>" type="checkbox" id="<?php echo $option_name; ?>" value="1" <?php if(get_option($option_name)==1) echo "checked='checked'"; ?>/> <?php echo $option_description; ?></label> 
	</fieldset> <?php
}

?>

<div class="wrap">
	
	<div id="icon-options-general" class="icon32"></div><h2>Video Thumbnails Options</h2>

	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	
	<table class="form-table">
	
	<tr valign="top"> 
	<th scope="row">Save Thumbnail to Media</th> 
	<td><?php video_thumbnails_checkbox_option('video_thumbnails_save_media', 'Save local copies of thumbnails using the media library'); ?></td> 
	</tr>

	<tr valign="top"> 
	<th scope="row">Set as Featured Image</th> 
	<td><?php video_thumbnails_checkbox_option('video_thumbnails_set_featured', 'Automatically set thumbnail as featured image ("Save Thumbnail to Media" must be enabled)'); ?></td> 
	</tr>
		
	</table>
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	<h3>How to use</h3>
	
	<p>For themes that use featured images, simply leave the two settings above enabled.</p>
	
	<p>For more detailed instructions, check out the page for <a href="http://wordpress.org/extend/plugins/video-thumbnails/">Video Thumbnails on the official plugin directory</a>.</p>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="video_thumbnails_save_media,video_thumbnails_set_featured" />

	</form>


</div>

<?php

}

?>