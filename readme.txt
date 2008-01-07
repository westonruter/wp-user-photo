=== User Photo ===
Contributors: westonruter
Tags: users, photos, images
Tested up to: 2.3.1
Stable tag: trunk
Donate link: http://weston.ruter.net/donate/

Allows a user to associate a photo with their account and for this photo to be displayed in their posts and comments.

== Description ==

Allows a user to associate a profile photo with their account through their "Your Profile" page. Admins may 
add a user profile photo by accessing the "Edit User" page. Uploaded images are resized to fit the dimensions specified 
on the options page; a thumbnail image correspondingly is also generated. 
User photos may be displayed within a post or a comment to 
help identify the author. New template tags introduced are: 

*   <code>userphoto_the_author_photo()</code>
*   <code>userphoto_the_author_thumbnail()</code>
*   <code>userphoto_comment_author_photo()</code>
*   <code>userphoto_comment_author_thumbnail()</code>

The first two should be placed in the posts loop near <code>the_author()</code>, and the second two in the comments
loop near <code>comment_author()</code> (or their respective equivalents). Furthermore, <code>userphoto_the_author_photo()</code>
and <code>userphoto_the_author_thumbnail()</code> may be called anywhere (i.e. sidebar) if <code>$authordata</code> is set.

Uploaded images may be moderated by administrators via the "Edit User" page.

Localizations included for Spanish and German.

If you value this plugin, *please donate* to ensure that it may continue to be maintained and improved.

= Changelog =
*2008-01-07: 0.7.4b*

* Added German localization translated by Robert Harm (thanks!)

*2008-01-06: 0.7.4*

* Added support for localization and added Spanish localization translated by Pakus (thanks!)

*2008-01-02: 0.7.3*

* Fixed issue where the post author photo was inadvertently used for non-registered comment author photos.

*2007-12-28: 0.7.2*

* Improved error message raised when unable to create 'userphoto' directory under /wp-content/uploads/. It now asks about whether write-permissions are set for the directory.
* Improved the plugin activation handler.
* All uploaded images are now explicitly set to chmod 666.

*2007-12-22: 0.7.1*

* All functions (and template tags) now are prefixed with "userphoto_"

*2007-12-18: 0.7.0.1*

* Now using `siteurl` option instead of `home` option
* Fixed the inclusion of the stylesheet for the options page

= Todo =
1. Add a management page to allow admins to quickly approve/reject user photos.
1. Add option so that when a photo is rejected, the user is notified.
1. Restrict image types acceptable?
1. Add an option to indicate a default photo to be used when none supplied.
1. Allow IMG attributes to be passed into template tags, including a default SRC value to be used when there is no user photo.

== Screenshots ==
1. Admin section in User Profile page

