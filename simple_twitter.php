<?php
/*
Plugin Name: TweetFeed
Plugin URI: http://lab.derekfernholz.com/wordpress/plugins/TweetFeed
Description: A plug-in to show the last twitter tweet for a user.
Version: 0.1
Author: Derek Fernholz
Author URI: http://derekfernholz.com
*/
/*  Copyright 2008  Derek Fernholz (email : fernholz@gmail.com)

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

$_opt_twitter_msg = 'tf_twitter_msg';
$_opt_twitter_id = 'tf_twitter_id';
$_opt_create_links = 'tf_create_links';
$_opt_link_users = 'tf_link_users';
$_opt_cache_mins = 'tf_cache_mins';
$_opt_last_cache_time = 'tf_last_cache_time';

add_action('wp_head', 'check_twitter_cache');
add_action('admin_menu', 'add_twitter_options');

// Options hook
function add_twitter_options() {
    if (function_exists('add_options_page')) {
		add_options_page('TweetFeed', 'TweetFeed', 8, 'tweetfeed', 'tweetfeed_options_subpanel');
    }
}
 
// Options panel and form processing
function tweetfeed_options_subpanel() {
	echo "<h2>TweetFeed</h2>";

	if (!function_exists('curl_init')) {
		_show_tweetfeed_curl_warning();	
	}
	else {
		if (isset($_POST['info_update'])) {
			global $_opt_twitter_id;
			global $_opt_cache_mins;
			global $_opt_create_links;
			global $_opt_link_users
			
			$twitterId = $_POST['twitter_id'];
			$cacheMins = $_POST['cache_mins'];
			$create_links = $_POST['create_links'];
			$link_users = $_POST['link_users'];
			
			update_option($_opt_twitter_id, $twitterId);
			update_option($_opt_cache_mins, $cacheMins);
			update_option($_opt_create_links, $create_links);
			update_option($_opt_link_users, $link_users);
		}
		_show_tweetfeed_form();
	}
}

// Displays a form to edit configuration options
function _show_tweetfeed_form() {
	?>
<div class="wrap">
<form method="post">
<fieldset class="options">
<legend><?php _e('Setup') ?></legend>
<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
<tr valign="top"> 
<th width="33%" scope="row"><label for="twitter_id"><?php _e('Your twitter username:') ?></label></th> 
<td><input type="text" name="twitter_id" id="twitter_id" value="<?php form_option('tf_twitter_id'); ?>"/></td> 
</tr>
<tr valign="top">
<th scope="row"><label for="cache_mins"><?php _e('Cache each message for:') ?></label></th>
<td><input type="text" name="cache_mins" id="cache_mins" size="3" value="<?php form_option('tf_cache_mins'); ?>"/> <?php _e('minutes') ?></td>
</tr> 
<tr valign="top">
<th scope="row"><label for="create_links"><?php _e('Checked = Make Links Clickable:') ?></label></th>
<td><input type="text" name="create_links" id="create_links" value="<?php form_option('tf_create_links'); ?>"/></td>
</tr>
<tr valign="top">
<th scope="row"><label for="user_links"><?php _e('Checked = Make UserNames Clickable:') ?></label></th>
<td><input type="text" name="user_links" id="user_links" value="<?php form_option('tf_user_links'); ?>"/></td>
</tr>
</table> 
<p class="submit">
<input type="submit" name="info_update" value="<?php _e('Update Options') ?> &raquo;" />
</p>
</fieldset>
</form>
</div>
	<?php
}

// Displays a warning message when cURL isn't available
function _show_tweetfeed_curl_warning() {
	?>
<div class="error">
<h3>TweetFeed needs the php cURL library to be installed</h3>
<p>TweetFeed uses the cURL php library to connect to the Twitter website. 
This doesn't seem to be available with your current php configuration - it has 
possibly been disabled in your php.ini file.<br /><br />Please contact your 
System Administrator or Service Provider for information.</p>
</div>	
	<?php
}

// Returns the stored message
function get_twitter_msg() {
	global $_opt_twitter_msg;
	$msg = get_option($_opt_twitter_msg);
	echo $msg;
}

// Called by hook into wp_head. Checks for message expiry
function check_twitter_cache() {
	global $_opt_cache_mins;
	global $_opt_last_cache_time;
	$cache_mins = get_option($_opt_cache_mins);
	if ($cache_mins == '')
		$cache_mins = 1;
	$cache_time = $cache_mins * 60;

	// Time and file stats
	$now = time();
	$lsmod = get_option($_opt_last_cache_time);
	if ($lsmod == '')
		$lsmod = 0;

	// Cache is expired if the diff between now time and last mod time
	// is greater than cache time
	$cache_expired = ($now - $lsmod) > $cache_time;
	if ($cache_expired) {
		update_twitter_message();
	}
}

// Updates the message cache
function update_twitter_message() {
	// Update cache
	global $_opt_twitter_id;
	global $_opt_twitter_msg;
	global $_opt_last_cache_time;
	$twitterId = get_option($_opt_twitter_id);
	if ($twitterId != '') {
		$url = 'http://twitter.com/statuses/user_timeline/'.$twitterId.'.rss';
		$title = get_message_from_url($url);
		if ($title != '') {
			$msg = extract_message_from_twitter_title($title);
			update_option($_opt_twitter_msg, $msg);
			update_option($_opt_last_cache_time, time());
		}
	}
}
	
// Message comes in the format 'Name : Message'. This removes the 'Name : ' part
function extract_message_from_twitter_title($title) {
	$msg = substr($title, strpos($title, ':') + 2);
	return $msg;
}

// Gets the RSS feed and reads the title of the first item
function get_message_from_url($url, $tag = 'title', $item = 'item') {
	$msg = '';
	
	$page = '';
	if (function_exists('curl_init')) {
		
		$curl_session = curl_init($url);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($curl_session, CURLOPT_TIMEOUT, 8);
		$page = curl_exec($curl_session);
		curl_close($curl_session);

	}		
	if ($page == '') {
		return '';
	}

	$lines = explode("\n",$page);
	
	$itemTag = "<$item>";
	$startTag = "<$tag>";
	$endTag = "</$tag>";
	
	$inItem = false;
	foreach ($lines as $s) {
		$s = rtrim($s);		
		if (strpos($s, $itemTag)) {
			$inItem = true;
		}
		if ($inItem) {
			$msg .= $s;
		}
		if ($inItem && strpos($s, $endTag)) {
			$msg = substr_replace($msg, '', strpos($msg, $endTag));
			$msg = substr($msg, strpos($msg, $startTag) + strlen($startTag));
			break;
		}
	}
	return $msg;
}
?>