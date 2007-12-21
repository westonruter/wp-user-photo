=== Plugin Name ===
Contributors: westonruter
Tags: users, photos, images
Tested up to: 2.3.1
Stable tag: trunk

Allows a user to associate a photo with their account and for this photo to be displayed in their posts and comments.

== Description ==

    Allows users to associate photos with their accounts by accessing their "Your Profile" page. Admins may 
add a user photo by accessing the "Edit User" page. Uploaded images are resized to fit the dimensions specified 
on the options page; a thumbnail image is also generated. New template tags introduced are: 

*   <code>userphoto_the_author_photo()</code>
*   <code>userphoto_the_author_thumbnail()</code>
*   <code>userphoto_comment_author_photo()</code>
*   <code>userphoto_comment_author_thumbnail()</code>

Uploaded images may be moderated by administrators via the "Edit User" page.

== Changelog ==
= 2007-12-22: 0.7.1
* All functions (and template tags) now are prefixed with "userphoto_"

= 2007-12-18: 0.7.0.1 =
* Now using `siteurl` option instead of `home` option
* Fixed the inclusion of the stylesheet for the options page

== Todo ==
1. Add a management page to allow admins to quickly approve/reject user photos.
1. Add option so that when a photo is rejected, the user is notified.
1. Restrict image types acceptable?
