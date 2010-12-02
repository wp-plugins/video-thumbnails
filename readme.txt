=== Video Thumbnails ===
Contributors: sutherlandboswell
Donate link: http://sutherlandboswell.com
Tags: Video, YouTube, Vimeo, Thumbnails
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 0.2.3

Video Thumbnails is a simple plugin that makes it easier to display video thumbnails in your template.

== Description ==

Video Thumbnails makes it simple to display video thumbnails in your templates. Simply use `<?php video_thumbnail(); ?>` in a loop to find and echo the URL of the first video embedded in a post, or use `<?php $video_thumbnail = get_video_thumbnail(); ?>` if you want to return the URL for use as a variable.

Video Thumbnails currently supports:

*   YouTube
*   Vimeo
*   JR Embed (this plugin seems to have disappeared)
*	[Vimeo Shortcode](http://blog.esimplestudios.com/2010/08/embedding-vimeo-videos-in-wordpress/)

When using `video_thumbnail()` and no thumbnail is found, a default thumbnail is echoed, which can be changed by replacing the `default.jpg` file found in your `/plugins/video-thumbnails/` directory.

For more advanced users, the `get_video_thumbnail()` function will return null when no thumbnail is found so a conditional statement can be used to detect if a thumbnail is present and decide what to echo. Here's an example of how to only echo a thumbnail when one is found: `<?php if( ( $video_thumbnail = get_video_thumbnail() ) != null ) { echo "<img src='" . $video_thumbnail . "' />"; } ?>`

This is just a start, so don't hesitate to share suggestions and let me know if you find any problems.

== Installation ==

1. Upload the `/video-thumbnails/` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use `<?php video_thumbnail(); ?>` in a loop inside your template to echo the thumbnail URL. Because this is only a URL, you should set it as an image tag's source. For example, `<img src="<?php video_thumbnail(); ?>" />`. If you'd like to return the URL for use in your PHP, use `get_video_thumbnail()`. For example, `<?php $video_thumbnail = get_video_thumbnail(); ?>`.

== Frequently Asked Questions ==

= My video service isn't included, can you add it? =

If the service allows a way to retrieve thumbnails, I'll do my best to add it.

= I only want a thumbnail when one is found, how would I do this? =

In version 0.2 `get_video_thumbnail()` was added which returns null when no thumbnail is found. This means you can use something like `<?php if( ( $video_thumbnail = get_video_thumbnail() ) != null ) { echo "<img src='" . $video_thumbnail . "' />"; } ?>` to only display a thumbnail when one exists.

== Screenshots ==

Coming Soon

== Changelog ==

= 0.2.3 =
* Added support for any Vimeo URL

= 0.2.2 =
* Added support for [Vimeo Shortcode](http://blog.esimplestudios.com/2010/08/embedding-vimeo-videos-in-wordpress/)

= 0.2.1 =
* Added support for Vimeo players embedded using an iframe

= 0.2 =
* Added `get_video_thumbnail()` to return the URL without echoing or return null if no thumbnail is found, making it possible to only display a thumbnail if one is found.

= 0.1.3 =
* Fixed an issue where no URL was returned when Vimeo's rate limit had been exceeded. The default image URL is now returned, but a future version of the plugin will store thumbnails locally for a better fix.

= 0.1.2 =
* Fixed a possible issue with how the default image URL is created

= 0.1.1 =
* Fixed an issue with the plugin directory's name that caused the default URL to be broken
* Added support for YouTube URLs

= 0.1 =
* Initial release

== Known Issues ==

* The Vimeo API is rate limited, so the default image will be displayed when the limit has been exceeded. I'm planning to add local copies of files in a future release to solve this problem.
* While not really an issue, the current method for only displaying a thumbnail if one is found seems like it could be streamlined, so if you have any suggestions let me know.

== Roadmap ==

This plugin is still very young, and has a future planned as the ultimate plugin for video thumbnails. Here's some of the planned additions:

* Blip.tv support
* Local thumbnail storage
* More services
* More shortcode plugins