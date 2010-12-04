<?php
/*
Plugin Name: Video Thumbnails
Plugin URI: http://sutherlandboswell.com/2010/11/wordpress-video-thumbnails/
Description: A plugin designed to fetch video thumbnails. Add <code>&lt;?php video_thumbnail(); ?&gt;</code> to your loop to return a thumbnail, or check the FAQ section for more advanced uses. Currently works with YouTube, Vimeo, and Blip.tv, with other services coming soon.
Author: Sutherland Boswell
Author URI: http://sutherlandboswell.com
Version: 0.5.2
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
    if (!function_exists('curl_init')) die('CURL is not installed!');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://vimeo.com/api/v2/video/$id.php");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = unserialize(curl_exec($ch));
    $output = $output[0][$info];
    curl_close($ch);
    return $output;
};

// Blip.tv Functions
function getBliptvInfo($id) {
	$xml = simplexml_load_file("http://blip.tv/file/$id?skin=rss");
	$result = $xml->xpath("/rss/channel/item/media:thumbnail/@url");
	$thumbnail = (string) $result[0]['url'];
	return $thumbnail;
}

// The Main Event
function get_video_thumbnail() {
	
	// Check to see if thumbnail has already been found
	$postid = get_the_ID();
	if( ($thumbnail_meta = get_post_meta($postid, '_video_thumbnail', true)) != '' ) {
		return $thumbnail_meta;
	}
	// If the thumbnail isn't stored in custom meta, fetch a thumbnail
	else {

		// Gets the post's content
		$markup = get_the_content();
		$new_thumbnail = null;
		
		// Simple Video Embedder Compatibility
		if(function_exists('p75HasVideo')) {
			if ( p75HasVideo($postid) ) {
			    $markup = p75GetVideo($postid);
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
		
		// Return the new thumbnail variable and update meta if one is found
		if($new_thumbnail!=null) {
			if(!update_post_meta($postid, '_video_thumbnail', $new_thumbnail)) add_post_meta($postid, '_video_thumbnail', $new_thumbnail);
		}
		return $new_thumbnail;

	}
};

// Echo thumbnail
function video_thumbnail() {
	if( ( $video_thumbnail = get_video_thumbnail() ) == null ) { echo plugins_url() . "/video-thumbnails/default.jpg"; }
	else { echo $video_thumbnail; }
};

// Create Meta Fields on Edit Page

add_action("admin_init", "admin_init");
 
function admin_init(){
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

?>