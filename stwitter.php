<?php
/*
Plugin Name: STwitter
*/

$t = new stwitter();
$t->last = 1;
$t->get_twitter_msg();

class stwitter {
	
	public $page;
	public $last;
	public $msg;
	
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
		$this->update_last_tweet($out_msg);
		unset($out_msg);
		unset($this->page);
		unset($this->last);
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
	
	function update_last_tweet($msg) {
		$query = 'UPDATE latest_tweet SET tweet_text = "' . $msg . '" WHERE tweet_id = 0';
		$result = mysql_query($query);
		if(!$result){
			$result = mysql_query('INSERT INTO latest_tweet (tweet_text) VALUES ("$msg")');
		}
	}
}
?>