=== Plugin Name ===
Contributors: westonruter
Tags: users, photos, images
Tested up to: 2.3.1
Stable tag: 0.7

Allows a user to associate a photo with their account and for this photo to be displayed in their posts and comments.

== Description ==

    Allows users to associate photos with their accounts by accessing their "Your Profile" page. Admins may 
add a user photo by accessing the "Edit User" page. Uploaded images are resized to fit the dimensions specified 
on the options page; a thumbnail image is also generated. New template tags introduced are: 
 * `the_author_photo()`
 * `the_author_thumbnail()`
 * `comment_author_photo()`
 * `comment_author_thumbnail()`
Uploaded images may be moderated by administrators.

== Todo ==
 1. Add a management page to allow admins to quickly approve/reject user photos.
 1. Add option so that when a photo is rejected, the user is notified.
 1. Restrict image types acceptable?
