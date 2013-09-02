<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Twitter Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
*/

class Twitter_Controller extends Controller
{
	const API_URL = "https://api.twitter.com/1.1/search/tweets.json?";

	/**
	*
	*/
	public function search($keywords, $location, $since, $count = 100)
	{
		$consumer_key = "atPNN395YkX2kUvN2vEiQ";
		$consumer_secret = "gqofxaB9Hbg2KGPBCZ9A7RDOTiHy4k7a1CHO4xfO0";
		$access_token = "11174562-aVilaH6SctBhTMHo1cVvArBpGLdq5KbfUPJOFRXYc";
		$access_token_secret = "7AUjtxzD5x6WbH7mVEFZ6UqZ5hYVpMlyqX8tuTWZw";

		// Gets token based on info provided by user
		$twitter = new Twitter_Oauth($consumer_key, $consumer_secret, $access_token, $access_token_secret);

		// set up parameters for url
		$parameters = array();
		$parameters["q"] = urlencode(join($keywords, " OR "));
		$parameters["count"] = urlencode($count);

		if ( ! empty($location) ) 
		{
			$parameters["geocode"] = urlencode($location);
		}

		if ( ! empty($since) ) 
		{
			$parameters["since"] = urlencode($since);
		}

		//make request using fancy Twitter class method
		$result = $twitter->oAuthRequest(self::API_URL, 'GET', $parameters);
		//var_dump($result);
		$result = json_decode($result, true);



		if (isset($result["errors"])) 
		{
			var_dump($result["errors"]);
			return false;
		}

		return $this->parse($result);
	}

	/**
	*
	*/
	public function parse($array_result) {
		$statuses = $array_result["statuses"];
		foreach ($statuses as $s) {
			$entry = ORM::factory("Twitter");

			/*$entry->status = $entry::TO_REVIEW;
			$entry->channel_id = $s["id_str"];
			$entry->message = $s["text"];
			$entry->original_date = $s["created_at"];
			if ( ! is_null($s["coordinates"]) ) 
			{
				$entry["lat"] = $s["coordinates"][1]; //twitter uses long,lat
				$entry["long"] = $s["coordinates"][0];
			}*/

			echo "A";
			//var_dump($entry);
/*
			var_dump($entry->save());
			unset($entry);
			var_dump("A");
			die();
*/
			//var_dump($s);
		}

//		return true;
	}
}
