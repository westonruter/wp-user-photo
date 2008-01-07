<?php
/*
Plugin Name: User Photo
Plugin URI: http://wordpress.org/extend/plugins/user-photo/
Description: Allows users to associate photos with their accounts by accessing their "Your Profile" page. Uploaded images are resized to fit the dimensions specified on the options page; a thumbnail image is also generated. New template tags introduced are: <code>userphoto_the_author_photo</code>, <code>userphoto_the_author_thumbnail</code>, <code>userphoto_comment_author_photo</code>, and <code>userphoto_comment_author_thumbnail</code>. Uploaded images may be moderated by administrators.
Version: 0.7.4b
Author: Weston Ruter
Author URI: http://weston.ruter.net/
Copyright: 2007, Weston Ruter

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('USERPHOTO_PLUGIN_IDENTIFIER', 'user-photo/user-photo.php');

if(!function_exists('imagecopyresampled')){
	//Remove this from active plugins
	$active_plugins = get_option('active_plugins');
	array_splice($active_plugins, array_search(USERPHOTO_PLUGIN_IDENTIFIER, $active_plugins), 1); //preg_replace('{^.+(?=[^/]+/[^/]+)}', '', __FILE__)
	update_option('active_plugins', $active_plugins);
	
	trigger_error(__("User Photo plugin not usable on this system because image resizing is not available, specifically the imagecopyresampled() and related functions. It has been deactivated.", 'user-photo'), E_USER_ERROR);
}


$userphoto_validtypes = array(
	"image/jpeg" => true,
	"image/pjpeg" => true,
	"image/gif" => true,
	"image/png" => true,
	"image/x-png" => true
);

define('USERPHOTO_PENDING', 0);
define('USERPHOTO_REJECTED', 1);
define('USERPHOTO_APPROVED', 2);
#define('USERPHOTO_PLUGINPATH', ABSPATH.'wp-content/plugins/user-photo');
#define('USERPHOTO_PLUGINLINK', get_settings('siteurl') . 'wp-content/plugins/user-photo/');

#define('USERPHOTO_DEFAULT_MAX_DIMENSION', 150);
#define('USERPHOTO_DEFAULT_THUMB_DIMENSION', 80);
#define('USERPHOTO_DEFAULT_JPEG_COMPRESSION', 90);
#define('USERPHOTO_DEFAULT_LEVEL_MODERATED', 2);

add_option("userphoto_jpeg_compression", 90);
add_option("userphoto_maximum_dimension", 150);
add_option("userphoto_thumb_dimension", 80);
add_option("userphoto_admin_notified", 0); //0 means disable
add_option("userphoto_level_moderated", 2); //Note: -1 means disable

# Load up the localization file if we're using WordPress in a different language
# Place it in the "localization" folder and name it "user-photo-[value in wp-config].mo"
load_plugin_textdomain('user-photo', PLUGINDIR . '/user-photo/localization'); #(thanks Pakus)

function userphoto_get_userphoto_the_author_photo($user_id = false){
	#global $authordata;
	#global $comment;
	#if(!$user_id){
	#	if(!empty($comment) && $comment->user_id)
	#
	#		$user_id = $comment->user_id;
	#	else if(!empty($authordata))
	#		$user_id = $authordata->ID;
	#	//else trigger_error("Unable to discern user ID.");
	#}
	if($user_id && ($userdata = get_userdata($user_id)) && $userdata->userphoto_image_file){
		$img = '<img src="' . get_option('siteurl') . '/wp-content/uploads/userphoto/' . $userdata->userphoto_image_file . '"';
		$img .= ' alt="' . htmlspecialchars($userdata->display_name) . '"';
		$img .= ' width="' . htmlspecialchars($userdata->userphoto_image_width) . '"';
		$img .= ' height="' . htmlspecialchars($userdata->userphoto_image_height) . '"';
		$img .= ' />';
		return $img;
	}
	#Print default image
	else {
		return "";
	}
}
function userphoto_get_userphoto_the_author_thumbnail($user_id){
	#global $authordata;
	#global $comment;
	#if(!$user_id){
	#	if(!empty($comment) && $comment->user_id)
	#
	#		$user_id = $comment->user_id;
	#	else if(!empty($authordata))
	#		$user_id = $authordata->ID;
	#	//else trigger_error("Unable to discern user ID.");
	#}
	if($user_id && ($userdata = get_userdata($user_id)) && $userdata->userphoto_thumb_file){
		$img = '<img src="' . get_option('siteurl') . '/wp-content/uploads/userphoto/' . $userdata->userphoto_thumb_file . '"';
		$img .= ' alt="' . htmlspecialchars($userdata->display_name) . '"';
		$img .= ' width="' . htmlspecialchars($userdata->userphoto_thumb_width) . '"';
		$img .= ' height="' . htmlspecialchars($userdata->userphoto_thumb_height) . '"';
		$img .= ' />';
		return $img;
	}
	#Print default image
	else {
		return "";
	}
}

function userphoto_comment_author_photo(){
	global $comment;
	if(!empty($comment) && $comment->user_id)
		echo userphoto_get_userphoto_the_author_photo($comment->user_id);
}
function userphoto_comment_author_thumbnail(){
	global $comment;
	if(!empty($comment) && $comment->user_id)
		echo userphoto_get_userphoto_the_author_thumbnail($comment->user_id);
}
function userphoto_the_author_photo(){
	global $authordata;
	if(!empty($authordata) && $authordata->ID)
		echo userphoto_get_userphoto_the_author_photo($authordata->ID);
}
function userphoto_the_author_thumbnail(){
	global $authordata;
	if(!empty($authordata) && $authordata->ID)
		echo userphoto_get_userphoto_the_author_thumbnail($authordata->ID);
}


function userphoto_profile_update($userID){
	global $userphoto_validtypes;
	global $current_user;
	
	$userdata = get_userdata($userID);
	
	#Delete photo
	if(@$_POST['userphoto_delete']){
		delete_usermeta($userID, "userphoto_error");
		if($userdata->userphoto_image_file){
			$imagepath = ABSPATH . "/wp-content/uploads/userphoto/" . basename($userdata->userphoto_image_file);
			$thumbpath = ABSPATH . "/wp-content/uploads/userphoto/" . basename($userdata->userphoto_image_file);
			
			if(file_exists($imagepath) && !@unlink($imagepath)){
				update_usermeta($userID, 'userphoto_error', __("Unable to delete photo.", 'user-photo'));
			}
			else {
				delete_usermeta($userID, "userphoto_image_file");
				delete_usermeta($userID, "userphoto_approvalstatus");
				delete_usermeta($userID, "userphoto_image_width");
				delete_usermeta($userID, "userphoto_image_height");
			}
		}
	}
	#Upload photo or change approval status
	else {
		#Upload the file
		if(isset($_FILES['userphoto_image_file']) && @$_FILES['userphoto_image_file']['name']){
			
			#Upload error
			$error = '';
			if($_FILES['userphoto_image_file']['error']){
				switch($_FILES['userphoto_image_file']['error']){
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$error = __("The uploaded file exceeds the max upload size.", 'user-photo');
						break;
					case UPLOAD_ERR_PARTIAL:
						$error = __("The uploaded file was only partially uploaded.", 'user-photo');
						break;
					case UPLOAD_ERR_NO_FILE:
						$error = __("No file was uploaded.", 'user-photo');
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$error = __("Missing a temporary folder.", 'user-photo');
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$error = __("Failed to write file to disk.", 'user-photo');
						break;
					case UPLOAD_ERR_EXTENSION:
						$error = __("File upload stopped by extension.", 'user-photo');
						break;
					default:
						$error = __("File upload failed due to unknown error.", 'user-photo');
				}
			}
			else if(!$_FILES['userphoto_image_file']['size'])
				$error = sprintf(__("The file &ldquo;%s&rdquo; was not uploaded. Did you provide the correct filename?", 'user-photo'), $_FILES['userphoto_image_file']['name']);
			else if(@!$userphoto_validtypes[$_FILES['userphoto_image_file']['type']]) //!preg_match("/\.(" . join('|', $userphoto_validextensions) . ")$/i", $_FILES['userphoto_image_file']['name'])) ||
				$error = sprintf(__("The uploaded file type &ldquo;%s&rdquo; is not allowed.", 'user-photo'), $_FILES['userphoto_image_file']['type']);
			
			$tmppath = $_FILES['userphoto_image_file']['tmp_name'];
			
			$imageinfo = null;
			$thumbinfo = null;
			if(!$error){
				$userphoto_maximum_dimension = get_option( 'userphoto_maximum_dimension' );
				#if(empty($userphoto_maximum_dimension))
				#	$userphoto_maximum_dimension = USERPHOTO_DEFAULT_MAX_DIMENSION;
				
				$imageinfo = getimagesize($tmppath);
				if(!$imageinfo || !$imageinfo[0] || !$imageinfo[1])
					$error = __("Unable to get image dimensions.", 'user-photo');
				else if($imageinfo[0] > $userphoto_maximum_dimension || $imageinfo[1] > $userphoto_maximum_dimension){
					if(userphoto_resize_image($tmppath, null, $userphoto_maximum_dimension, $error))
						$imageinfo = getimagesize($tmppath);
				}
				
				//else if($imageinfo[0] > $userphoto_maximum_dimension)
				//	$error = sprintf(__("The uploaded image had a width of %d pixels. The max width is %d.", 'user-photo'), $imageinfo[0], $userphoto_maximum_dimension);
				//else if($imageinfo[0] > $userphoto_maximum_dimension)
				//	$error = sprintf(__("The uploaded image had a height of %d pixels. The max height is %d.", 'user-photo'), $imageinfo[1], $userphoto_maximum_dimension);
			}
			
			if(!$error){
				$dir = ABSPATH . "/wp-content/uploads/userphoto";
				#$umask = umask(0);
				if(!file_exists($dir) && !mkdir($dir, 0777))
					$error = __("The userphoto upload content directory does not exist and could not be created. Please ensure that you have write permissions for the /wp-content/uploads/ directory.", 'user-photo');
				#umask($umask);
				
				if(!$error){
					#$oldFile = basename($userdata->userphoto_image_file);
					$imagefile = preg_replace('/^.+(?=\.\w+$)/', $userdata->user_nicename, $_FILES['userphoto_image_file']['name']);
					$imagepath = $dir . '/' . $imagefile;
					$thumbfile = preg_replace("/(?=\.\w+$)/", '.thumbnail', $imagefile);
					$thumbpath = $dir . '/' . $thumbfile;
					
					if(!move_uploaded_file($tmppath, $imagepath)){
						$error = __("Unable to move the file to the user photo upload content directory.", 'user-photo');
					}
					else {
						chmod($imagepath, 0666);
						
						#Generate thumbnail
						$userphoto_thumb_dimension = get_option( 'userphoto_thumb_dimension' );
						#if(empty($userphoto_thumb_dimension))
						#	$userphoto_thumb_dimension = USERPHOTO_DEFAULT_THUMB_DIMENSION;
						if(!($userphoto_thumb_dimension >= $imageinfo[0] && $userphoto_thumb_dimension >= $imageinfo[1])){
							userphoto_resize_image($imagepath, $thumbpath, $userphoto_thumb_dimension, $error);
						}
						else {
							copy($imagepath, $thumbpath);
							chmod($thumbpath, 0666);
						}
						$thumbinfo = getimagesize($thumbpath);
						
						#Update usermeta
						if($current_user->user_level <= get_option('userphoto_level_moderated') ){
							update_usermeta($userID, "userphoto_approvalstatus", USERPHOTO_PENDING);
							
							$admin_notified = get_option('userphoto_admin_notified');
							if($admin_notified){
								$admin = get_userdata($admin_notified);
								@mail($admin->user_email,
									 "User Photo for " . $userdata->display_name . " Needs Approval",
									 get_option("home") . "/wp-admin/user-edit.php?user_id=" . $userdata->ID . "#userphoto");
							}
						}
						else {
							update_usermeta($userID, "userphoto_approvalstatus", USERPHOTO_APPROVED);
						}
						update_usermeta($userID, "userphoto_image_file", $imagefile); //TODO: use userphoto_image
						update_usermeta($userID, "userphoto_image_width", $imageinfo[0]); //TODO: use userphoto_image_width
						update_usermeta($userID, "userphoto_image_height", $imageinfo[1]);
						update_usermeta($userID, "userphoto_thumb_file", $thumbfile);
						update_usermeta($userID, "userphoto_thumb_width", $thumbinfo[0]);
						update_usermeta($userID, "userphoto_thumb_height", $thumbinfo[1]);
			
						#if($oldFile && $oldFile != $newFile)
						#	@unlink($dir . '/' . $oldFile);
					}
				}
			}
		}
		
		#Set photo approval status
		if($current_user->has_cap('edit_users') &&
		   array_key_exists('userphoto_approvalstatus', $_POST) &&
		   in_array((int)$_POST['userphoto_approvalstatus'], array(USERPHOTO_PENDING, USERPHOTO_REJECTED, USERPHOTO_APPROVED))
		){
			update_usermeta($userID, "userphoto_approvalstatus", (int)$_POST['userphoto_approvalstatus']);
			if((int)$_POST['userphoto_approvalstatus'] == USERPHOTO_REJECTED)
				update_usermeta($userID, "userphoto_rejectionreason", $_POST['userphoto_rejectionreason']);
			else
				delete_usermeta($userID, "userphoto_rejectionreason");
		}
	}
	
	if($error)
		update_usermeta($userID, 'userphoto_error', $error);
	else
		delete_usermeta($userID, "userphoto_error");
}
add_action('profile_update', 'userphoto_profile_update');
#add_action('personal_options_update', ???);

#QUESTION: Should we store a serialized datastructure in the usermeta...
# Width, height, size, filename/path


function userphoto_delete_user($userID){
	$userdata = get_userdata($userID);
	if($userdata->userphoto_image_file)
		@unlink(ABSPATH . "/wp-content/uploads/userphoto/" . basename($userdata->userphoto_image_file));
	if($userdata->userphoto_thumb_file)
		@unlink(ABSPATH . "/wp-content/uploads/userphoto/" . basename($userdata->userphoto_thumb_file));
}
add_action('delete_user', 'userphoto_delete_user');


function userphoto_admin_useredit_head(){
	if(preg_match("/(user-edit\.php|profile.php)$/", $_SERVER['PHP_SELF']))
		print '<link rel="stylesheet" href="../wp-content/plugins/user-photo/admin.css" />';
}
function userphoto_admin_options_head(){
	print '<link rel="stylesheet" href="../wp-content/plugins/user-photo/admin.css" />';
}

add_action('admin_head-options_page_user-photo/user-photo', 'userphoto_admin_options_head');
add_action('admin_head', 'userphoto_admin_useredit_head');
#add_action('admin_head-userphoto', 'userphoto_admin_head');

function userphoto_display_selector_fieldset(){
    #NOTE: an email needs to be sent to the admin when a contributor uploads a photo
    
    global $profileuser;
    global $current_user;
	global $userphoto_error;
    
	$isSelf = $profileuser->ID == $current_user->ID;
	
	#if($isSelf)
    #    $userdata = get_userdata($profileuser->ID);
    #else
    #    $userdata = get_userdata($current_user->ID);
    
	#$userphoto = unserialize($userdata->userphoto);
	
    ?>
    <fieldset id='userphoto'>
        <script type="text/javascript">
		var form = document.getElementById('your-profile');
		//form.enctype = "multipart/form-data"; //FireFox, Opera, et al
		form.encoding = "multipart/form-data"; //IE5.5
		form.setAttribute('enctype', 'multipart/form-data'); //required for IE6 (is interpreted into "encType")
		
		function userphoto_onclick(){
			var is_delete = document.getElementById('userphoto_delete').checked;
			document.getElementById('userphoto_image_file').disabled = is_delete;
			
			if(document.getElementById('userphoto_approvalstatus'))
				document.getElementById('userphoto_approvalstatus').disabled = is_delete;
			if(document.getElementById('userphoto_rejectionreason'))
				document.getElementById('userphoto_rejectionreason').disabled = is_delete;
		}
		function userphoto_approvalstatus_onchange(){
			var select = document.getElementById('userphoto_approvalstatus');
			document.getElementById('userphoto_rejectionreason').style.display = (select.options[select.selectedIndex].value == <?php echo USERPHOTO_REJECTED ?> ? 'block' : 'none');
		}
		<?php if($profileuser->userphoto_error && @$_POST['action'] == 'update'): ?>
		window.location = "#userphoto";
		<?php endif; ?>
		
        </script>
        <legend><?php echo $isSelf ? _e("Your Photo", 'user-photo') : _e("User Photo", 'user-photo') ?></legend>
        <?php if($profileuser->userphoto_image_file): ?>
            <p class='image'><img src="<?php echo get_option('siteurl') . '/wp-content/uploads/userphoto/' . $profileuser->userphoto_image_file . "?" . rand() ?>" alt="Full size image" /><br />
			Full size
			</p>
			<p class='image'><img src="<?php echo get_option('siteurl') . '/wp-content/uploads/userphoto/' . $profileuser->userphoto_thumb_file . "?" . rand() ?>" alt="Thumbnail image" /><br />
			Thumb
			</p>
			<hr />
            
			<?php if(!$current_user->has_cap('edit_users')): ?>
				<?php if($profileuser->userphoto_approvalstatus == USERPHOTO_PENDING): ?>
					<p id='userphoto-status-pending'><?php echo _e("Your profile photo has been submitted for review.", 'user-photo') ?></p>
				<?php elseif($profileuser->userphoto_approvalstatus == USERPHOTO_REJECTED): ?>
					<p id='userphoto-status-rejected'><strong>Notice: </strong> <?php _e("Your chosen profile photo has been rejected.", 'user-photo') ?>
					<?php
					if($profileuser->userphoto_rejectionreason){
						_e("Reason: ", 'user-photo');
						echo htmlspecialchars($profileuser->userphoto_rejectionreason);
					}
					?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
        <?php endif; ?>

        <?php if($profileuser->userphoto_error): ?>
		<p id='userphoto-upload-error'><strong>Upload error:</strong> <?php echo $profileuser->userphoto_error ?></p>
		<?php endif; ?>
        <p id='userphoto_image_file_control'>
        <label><?php echo _e("Upload image file:", 'user-photo') ?>
		<span class='field-hint'>(<?php
		//if(!get_option('userphoto_autoresize'))
		//	printf(__("max dimensions %d&times;%d;"), get_option('userphoto_maximum_dimension'), get_option('userphoto_maximum_dimension'));
		printf(__("max upload size %s"),ini_get("upload_max_filesize"));
		?>)</span>
		<input type="file" name="userphoto_image_file" id="userphoto_image_file" /></label>
		</p>
        <!--<em>or</em>
        <label for="uphoto-fileURL">Image URL: </label><input type="url" name="uphoto-fileURL" id="uphoto-fileURL" value="http://" /><br />-->
        <?php if($current_user->has_cap('edit_users') && ($profileuser->ID != $current_user->ID) && $profileuser->userphoto_image_file): ?>
			<p id="userphoto-approvalstatus-controls" <?php if($profileuser->userphoto_approvalstatus == USERPHOTO_PENDING) echo "class='pending'" ?>>
			<label><?php _e("Approval status:", 'user-photo') ?>
			<select name="userphoto_approvalstatus" id="userphoto_approvalstatus" onchange="userphoto_approvalstatus_onchange()">
				<option value="<?php echo USERPHOTO_PENDING ?>" <?php if($profileuser->userphoto_approvalstatus == USERPHOTO_PENDING) echo " selected='selected' " ?>><?php _e("pending", 'user-photo') ?></option>
				<option value="<?php echo USERPHOTO_REJECTED ?>" <?php if($profileuser->userphoto_approvalstatus == USERPHOTO_REJECTED) echo " selected='selected' " ?>><?php _e("rejected", 'user-photo') ?></option>
				<option value="<?php echo USERPHOTO_APPROVED ?>" <?php if($profileuser->userphoto_approvalstatus == USERPHOTO_APPROVED) echo " selected='selected' " ?>><?php _e("approved", 'user-photo') ?></option>
			</select></label><br /><textarea name="userphoto_rejectionreason" <?php
			if($profileuser->userphoto_approvalstatus != USERPHOTO_REJECTED)
				echo ' style="display:none"';
			?> id="userphoto_rejectionreason"><?php echo $profileuser->userphoto_rejectionreason ? $profileuser->userphoto_rejectionreason : __('The photo is inappropriate.', 'user-photo') ?></textarea>
			</p>
			<script type="text/javascript">userphoto_approvalstatus_onchange()</script>
        <?php endif; ?>
		<?php if($profileuser->userphoto_image_file): ?>
		<p><label><input type="checkbox" name="userphoto_delete" id="userphoto_delete" onclick="userphoto_onclick()" /> <?php _e('Delete photo?', 'user-photo')?></label></p>
		<?php endif; ?>
    </fieldset>
    <?php
}
add_action('show_user_profile', 'userphoto_display_selector_fieldset');
add_action('edit_user_profile', 'userphoto_display_selector_fieldset');

/***** ADMIN ******************************************/

function userphoto_add_page() {
	//if (function_exists('add_options_page'))
	add_options_page('User Photo', 'User Photo', 8, __FILE__, 'userphoto_options_page');
}
add_action('admin_menu', 'userphoto_add_page');

function userphoto_options_page(){
	#Get option values
	$userphoto_jpeg_compression = get_option( 'userphoto_jpeg_compression' );
	$userphoto_maximum_dimension = get_option( 'userphoto_maximum_dimension' );
	$userphoto_thumb_dimension = get_option( 'userphoto_thumb_dimension' );
	$userphoto_admin_notified = get_option( 'userphoto_admin_notified' );
	$userphoto_level_moderated = get_option( 'userphoto_level_moderated' );
		
	#Get new updated option values, and save them
	if( @$_POST['action'] == 'update' ) {
		$userphoto_jpeg_compression = (int)$_POST['userphoto_jpeg_compression'];
		update_option('userphoto_jpeg_compression', $userphoto_jpeg_compression);
		
		$userphoto_maximum_dimension = (int)$_POST['userphoto_maximum_dimension'];
		update_option('userphoto_maximum_dimension', $userphoto_maximum_dimension);
		
		$userphoto_thumb_dimension = (int)$_POST['userphoto_thumb_dimension'];
		update_option('userphoto_thumb_dimension', $userphoto_thumb_dimension);
		
		$userphoto_admin_notified = (int)$_POST['userphoto_admin_notified'];
		update_option('userphoto_admin_notified', $userphoto_admin_notified);
		
		$userphoto_level_moderated = (int)$_POST['userphoto_level_moderated'];
		update_option('userphoto_level_moderated', $userphoto_level_moderated);
		
		?>
		<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
		<?php
	}
	
	?>
	<div class="wrap">
		<h2>User Photo Options</h2>
		<form method="post" action="options.php" id='userphoto_options_form'>
			<?php wp_nonce_field('update-options') ?>
			<p>
				<label>
					<?php _e("Maximum dimension: ", 'user-photo') ?>
					<input type="number" min="1" step="1" size="3" name="userphoto_maximum_dimension" value="<?php echo $userphoto_maximum_dimension ?>" />px
				</label>
			</p>
			<p>
				<label>
					<?php _e("Thumbnail dimension: ", 'user-photo') ?>
					<input type="number" min="1" step="1" size="3" name="userphoto_thumb_dimension" value="<?php echo $userphoto_thumb_dimension ?>" />px
				</label>
			</p>
			<p>
				<label>
					<?php _e("JPEG compression: ", 'user-photo') ?>
					<input type="range" min="1" max="100" step="1" size="3" name="userphoto_jpeg_compression" value="<?php echo $userphoto_jpeg_compression ?>" />%
				</label>
			</p>
			<p>
				<label>
					<?php _e("Notify this administrator by email when user photo needs approval: ", 'user-photo') ?>
					<select id='userphoto_admin_notified' name="userphoto_admin_notified">
						<option value="0" class='none'>(none)</option>
						<?php
						global $wpdb;
						$users = $wpdb->get_results("SELECT ID FROM $wpdb->users ORDER BY user_login");
						foreach($users as $user){
							$u = get_userdata($user->ID);
							if($u->user_level == 10){ #if($u->has_cap('administrator')){
								print "<option value='" . $u->ID . "'";
								if($userphoto_admin_notified == $u->ID)
									print " selected='selected'";
								print ">" . $u->user_login . "</option>";
							}
						}
						?>
					</select>
				</label>
			</p>
			<p>
				<label>
					<!--<input type="checkbox" id="userphoto_do_moderation" onclick="document.getElementById('userphoto_level_moderated').disabled = !this.checked" <?php /*if(isset($userphoto_level_moderated)) echo ' checked="checked"'*/ ?> />-->
					<?php _e("Require user photo moderation for all users at or below this level: ", 'user-photo') ?>
					<select name="userphoto_level_moderated" id="userphoto_level_moderated">
						<option value="-1" <?php if($userphoto_level_moderated == -1) echo ' selected="selected"' ?> class='none'>(none)</option>
						<option value="0" <?php if($userphoto_level_moderated == 0) echo ' selected="selected"' ?>>Subscriber</option>
						<option value="1" <?php if($userphoto_level_moderated == 1) echo ' selected="selected"' ?>>Contributor</option>
						<option value="2" <?php if($userphoto_level_moderated == 2) echo ' selected="selected"' ?>>Author</option>
						<option value="7" <?php if($userphoto_level_moderated == 7) echo ' selected="selected"' ?>>Editor</option>
					</select>
				</label>
				<!--<script type="text/javascript">
				document.getElementById('userphoto_do_moderation').onclick();
				</script>-->
			</p>
	
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="userphoto_jpeg_compression,userphoto_admin_notified,userphoto_maximum_dimension,userphoto_thumb_dimension,userphoto_level_moderated" />
			
			<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Update options &raquo;'); ?>" />
			</p>
		</form>
	</div>
	<?php
}


function userphoto_resize_image($filename, $newFilename, $maxdimension, &$error){
	if(!$newFilename)
		$newFilename = $filename;
	$userphoto_jpeg_compression = (int)get_option( 'userphoto_jpeg_compression' );
	#if(empty($userphoto_jpeg_compression))
	#	$userphoto_jpeg_compression = USERPHOTO_DEFAULT_JPEG_COMPRESSION;
	
	$info = @getimagesize($filename);
	if(!$info || !$info[0] || !$info[1]){
		$error = __("Unable to get image dimensions.", 'user-photo');
	}
	//From WordPress image.php line 22
	else if (
		!function_exists( 'imagegif' ) && $info[2] == IMAGETYPE_GIF
		||
		!function_exists( 'imagejpeg' ) && $info[2] == IMAGETYPE_JPEG
		||
		!function_exists( 'imagepng' ) && $info[2] == IMAGETYPE_PNG
	) {
		$error = __( 'Filetype not supported.', 'user-photo' );
	}
	else {
		// create the initial copy from the original file
		if ( $info[2] == IMAGETYPE_GIF ) {
			$image = imagecreatefromgif( $filename );
		}
		elseif ( $info[2] == IMAGETYPE_JPEG ) {
			$image = imagecreatefromjpeg( $filename );
		}
		elseif ( $info[2] == IMAGETYPE_PNG ) {
			$image = imagecreatefrompng( $filename );
		}
		if(!isset($image)){
			$error = __("Unrecognized image format.", 'user-photo');
			return false;
		}
		if ( function_exists( 'imageantialias' ))
			imageantialias( $image, TRUE );

		// figure out the longest side

		if ( $info[0] > $info[1] ) {
			$image_width = $info[0];
			$image_height = $info[1];
			$image_new_width = $maxdimension;

			$image_ratio = $image_width / $image_new_width;
			$image_new_height = $image_height / $image_ratio;
			//width is > height
		} else {
			$image_width = $info[0];
			$image_height = $info[1];
			$image_new_height = $maxdimension;

			$image_ratio = $image_height / $image_new_height;
			$image_new_width = $image_width / $image_ratio;
			//height > width
		}

		$imageresized = imagecreatetruecolor( $image_new_width, $image_new_height);
		@ imagecopyresampled( $imageresized, $image, 0, 0, 0, 0, $image_new_width, $image_new_height, $info[0], $info[1] );

		// move the thumbnail to its final destination
		if ( $info[2] == IMAGETYPE_GIF ) {
			if (!imagegif( $imageresized, $newFilename ) ) {
				$error = __( "Thumbnail path invalid" );
			}
		}
		elseif ( $info[2] == IMAGETYPE_JPEG ) {
			if (!imagejpeg( $imageresized, $newFilename, $userphoto_jpeg_compression ) ) {
				$error = __( "Thumbnail path invalid" );
			}
		}
		elseif ( $info[2] == IMAGETYPE_PNG ) {
			if (!imagepng( $imageresized, $newFilename ) ) {
				$error = __( "Thumbnail path invalid" );
			}
		}
	}
	if(!empty($error))
		return false;
	return true;
}

?>