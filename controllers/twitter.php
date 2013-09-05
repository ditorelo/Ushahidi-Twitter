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
	public function search($keywords, $location, $since)
	{
		$consumer_key = socialmedia_helper::getSetting('twitter_api_key');
		$consumer_secret = socialmedia_helper::getSetting('twitter_api_key_secret');
		$access_token = socialmedia_helper::getSetting('twitter_token');
		$access_token_secret = socialmedia_helper::getSetting('twitter_token_secret');

		// Gets token based on info provided by user
		$twitter = new Twitter_Oauth($consumer_key, $consumer_secret, $access_token, $access_token_secret);

		// set up parameters for url
		$parameters = array();
		$parameters["q"] = urlencode(join($keywords, " OR "));
		$parameters["include_entities"] = true;

		if (! empty($location))
		{
			$location = number_format($location["lat"],6) . "," . number_format($location["lon"],6) . "," . $location["radius"] . "km";
			$parameters["geocode"] = $location;
		}

		$settings = ORM::factory('socialmedia_settings')->where('setting', 'twitter_last_id')->find();

		if (! is_null($settings->value)) {
			$parameters["since_id"] = $settings->value;
		}
		else
		{
			if (! empty($since))
			{
				$parameters["since"] = urlencode($since);
			}
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

		$result = $this->parse($result, (is_null($settings->value) ? 0 : $settings->value));

		$settings->setting = 'twitter_last_id';
		$settings->value = $result["highest_id"];
		$settings->save();

	}

	/**
	*
	*/
	public function parse($array_result, $highest_id = 0) {
		$statuses = $array_result["statuses"];

		foreach ($statuses as $s) {
			$entry = ORM::factory("Socialmedia");

			$entry->status = $entry::STATUS_TOREVIEW;
			$entry->channel = $entry::CHANNEL_TWITTER;
			$entry->channel_id = $s["id_str"];
			$entry->message = $s["text"];
			$entry->original_date = strtotime($s["created_at"]);
			$entry->url = "http://twitter.com/" . $s["user"]["screen_name"] . "/status/" . $s["id_str"];

			if ( ! is_null($s["coordinates"]) ) 
			{
				$entry->latitude = $s["coordinates"]["coordinates"][1]; //twitter uses long,lat
				$entry->longitude = $s["coordinates"]["coordinates"][0];
			}

			$entry->save();
			unset($entry);

			if ($s["id_str"] >	 $highest_id) {
				$highest_id = $s["id_str"];
			}
		}

		return array(
				"highest_id"		=> $highest_id
			);
	}
}
