<?php
/*
Plugin Name: tfTest
*/

if (!class_exists("TweetFeed")) {
	class TweetFeed {
		var $adminOptionsName = "TweetFeedAdminOptions";
		function TweetFeed() { //constructor
		}
		
		function init() {
			$this->getAdminOptions();
		}
		
		//Returns an array of admin options
		function getAdminOptions() {
			$tf_twitter_id = '';
			$tf_cache_mins = 15;
			$tf_create_links = 'on';
			$tf_link_users = 'on';
			$tf_show_replies = 'on';
			
			//$devOptions = get_option($this->adminOptionsName);
			
			$tf_twitter_id = get_option('tf_twitter_id');
			$tf_cache_mins = get_option('tf_cache_mins');
			$tf_create_links = get_option('tf_create_links');
			$tf_link_users = get_option('tf_link_users');
			$tf_show_replies = get_option('tf_show_replies');
			
			/*if (!empty($devOptions)) {
				foreach ($devOptions as $key => $option)
					$tweetfeedAdminOptions[$key] = $option;
			}*/
			//update_option($this->adminOptionsName, $tweetfeedAdminOptions);
			
			update_option('tf_twitter_id', $tf_twitter_id);
			update_option('tf_cache_mins', $tf_cache_mins);
			update_option('tf_create_links', $tf_create_links);
			update_option('tf_link_users', $tf_link_users);
			update_option('tf_show_replies', $tf_show_replies);
			
			$tweetfeedAdminOptions = array('twitter_id' => $tf_twitter_id,
				'cache_mins' => $tf_cache_mins,
				'create_links' => $tf_create_links,
				'link_users' => $tf_link_users,
				'show_replies' => $tf_show_replies);
			
			
			return $tweetfeedAdminOptions;
		}
		
		//Prints out the admin page
		function printAdminPage() {
			
			if (isset($_POST['update_tweetfeedSettings'])) {
				if (isset($_POST['twitter_id'])) {
					//$devOptions['twitter_id'] = $_POST['twitter_id'];
					update_option('tf_twitter_id', $_POST['twitter_id']);
				}
				if (isset($_POST['cache_mins'])) {
					//$devOptions['cache_mins'] = $_POST['cache_mins'];
					update_option('tf_cache_mins', $_POST['cache_mins']);
				}
				if (isset($_POST['create_links'])) {
					//$devOptions['create_links'] = $_POST['create_links'];
					update_option('tf_create_links', $_POST['create_links']);
				}
				else{
					//$devOptions['create_links'] = 'off';
					update_option('tf_create_links', 'off');
				}
				if (isset($_POST['link_users'])) {
					//$devOptions['link_users'] = $_POST['link_users'];
					update_option('tf_link_users', $_POST['link_users']);
				}
				else{
					//$devOptions['link_users'] = 'off';
					update_option('tf_link_users', 'off');
				}
				if (isset($_POST['show_replies'])) {
					//$devOptions['show_replies'] = $_POST['show_replies'];
					update_option('tf_show_replies', $_POST['show_replies']);
				}
				else{
					//$devOptions['show_replies'] = 'off';
					update_option('tf_show_replies', 'off');
				}
				//update_option($this->adminOptionsName, $devOptions);
				
				?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "TweetFeed");?></strong></p></div>
			<?php
			} 
			$devOptions = $this->getAdminOptions();
			?>
			<div class=wrap>
			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<h2>TweetFeed</h2>
			<fieldset class="options">
			<legend><?php _e('Setup') ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
			<tr valign="top"> 
			<th width="33%" scope="row"><label for="twitter_id"><?php _e('Your twitter username:') ?></label></th> 
			<td><input type="text" name="twitter_id" id="twitter_id" value="<?php echo $devOptions['twitter_id']; ?>"/></td> 
			</tr>
			<tr valign="top">
			<th scope="row"><label for="cache_mins"><?php _e('Cache each message for:') ?></label></th>
			<td><input type="text" name="cache_mins" id="cache_mins" size="3" value="<?php echo $devOptions['cache_mins']; ?>"/> <?php _e('minutes') ?></td>
			</tr> 
			<tr valign="top">
			<th scope="row"><label for="create_links"><?php _e('Checked = Make Links Clickable:') ?></label></th>
			<td><input type="checkbox" name="create_links" id="create_links" <?php if($devOptions['create_links'] == 'on'){echo 'checked="checked"';} ?>/></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="link_users"><?php _e('Checked = Make UserNames Clickable:') ?></label></th>
			<td><input type="checkbox" name="link_users" id="link_users" <?php if($devOptions['link_users'] == 'on'){echo 'checked="checked"';} ?> /></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="show_replies"><?php _e('Checked = Display @ Replies:') ?></label></th>
			<td><input type="checkbox" name="show_replies" id="show_replies" <?php if($devOptions['show_replies'] == 'on'){echo 'checked="checked"';} ?> /></td>
			</tr>
			</table> 
			<p class="submit">
			<input type="submit" name="update_tweetfeedSettings" value="<?php _e('Update Options') ?> &raquo;" />
			</p>
			</fieldset>
			</form>
			 </div>
		<?php
		}//End function printAdminPage()
		
		
		public $page;
		public $last;
		public $msg;
		public $tf_array;
		
		function get_page($url){
			$this->page = '';
			if (function_exists('curl_init')) {

				$curl_session = curl_init($url);
				curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_session, CURLOPT_CONNECTTIMEOUT, 4);
				curl_setopt($curl_session, CURLOPT_TIMEOUT, 8);
				$this->page = curl_exec($curl_session);
				curl_close($curl_session);
			}		
		}

		// Returns the stored message
		function get_twitter_msg() {
			//$msg = get_option();
			//echo $msg;
			
			$msg = $this->update_twitter_message();
			if($msg[0] == '@'){
				unset($msg);
				$this->get_twitter_msg();
			}

			$msg_chunks = explode(" ", $msg);
			foreach($msg_chunks as $chunk){
				if(strstr($chunk, '@') && 	strlen($chunk) > 1){
					$out_msg .= " <a href='http://twitter.com/" . substr($chunk, 1) . "'>" . $chunk . "</a> ";
				}
				elseif(strstr($chunk, 'http://')){
					$out_msg .= " <a href='" . $chunk . "'>" . $chunk . "</a> ";
				}
				else{
					$out_msg .= " " . $chunk;
				}
			}
			echo $out_msg;
			unset($out_msg);
			unset($this->page);
			unset($this->last);
		}
		
		// Called by hook into wp_head. Checks for message expiry
		function check_twitter_cache() {
			$this->tf_array = $this->getAdminOptions();
			
			$cache_mins = get_option('tf_cache_mins');
			if ($cache_mins == '')
				$cache_mins = 1;
			$cache_time = $cache_mins * 60;

			// Time and file stats
			$now = time();
			$lsmod = get_option('tf_last_cache_time');
			if ($lsmod == '')
				$lsmod = 0;

			// Cache is expired if the diff between now time and last mod time
			// is greater than cache time
			$cache_expired = ($now - $lsmod) > $cache_time;
			if ($cache_expired) {
				$this->update_twitter_message();
			}
		}

		// Updates the message cache
		function update_twitter_message() {
			// Update cache
			$twitterId = 'theunlivedlife';
			if ($twitterId != '') {
				$url = 'http://twitter.com/statuses/user_timeline/'.$twitterId.'.rss';
				$title = $this->get_message_from_url($url);
				if ($title != '') {
					$msg = $this->extract_message_from_twitter_title($title);
					return $msg;
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

			if(!isset($this->page)){
				$this->get_page($url);
			}

			if ($this->page == '') {
				return '';
			}

			$itemTag = "<$item>";
			$startTag = "<$tag>";
			$endTag = "</$tag>";

			$inItem = true;

			$offset = $this->last;
			$titlePos = $this->strposOffset($itemTag, $this->page, $offset);
			$this->page = substr($this->page, $titlePos + 6); 
			$lines = explode("\n",$this->page);
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

		function strposOffset($tag, $string, $offset)
		{
		    /*** explode the string ***/
		    $arr = explode($tag, $string);
		    /*** check the tag is not out of bounds ***/
		    switch( $offset )
		    {
		        case $offset == 0:
		        return false;
		        break;

		        case $offset > max(array_keys($arr)):
		        return false;
		        break;

		        default:
		        return strlen(implode($tag, array_slice($arr, 0, $offset)));
		    }
		}
	}
} //End Class TweetFeed

if (class_exists("TweetFeed")) {
	$tf = new TweetFeed();
}

//Actions and Filters
if (isset($tf)) {
	//Initialize the admin panel
	if (!function_exists("tweetfeed_ap")) {
		function tweetfeed_ap() {
			global $tf;
			if (!isset($tf)) {
				return;
			}
			if (function_exists('add_options_page')) {
				add_options_page('TweetFeed', 'TweetFeed', 9, basename(__FILE__), array(&$tf, 'printAdminPage'));
			}
		}
	}
	//Actions
	add_action('tweetfeed/tfTest.php',  array(&$tf, 'init'));
	add_action('admin_menu', 'tweetfeed_ap');
	//Filters
}

?>