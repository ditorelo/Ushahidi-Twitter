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
	* Search function for Twitter
	* @param array $keywords Keyworkds for search
	* @param array[lat,lon,radius] $location Array with Geo point and radius to constrain search results
	* @param string $since yyyy-mm-dd Date to be used as since date on twitter search
	*/
	public function search($keywords, $location, $since)
	{
		$consumer_key = socialmedia_helper::getSetting('twitter_api_key');
		$consumer_secret = socialmedia_helper::getSetting('twitter_api_key_secret');
		$access_token = socialmedia_helper::getSetting('twitter_token');
		$access_token_secret = socialmedia_helper::getSetting('twitter_token_secret');

		$twitter = self::getTwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);

		// Set up parameters for url
		$parameters = array();
		foreach ($keywords as &$k) {
			$k = "(" . $k . ")";
		}
		$parameters["q"] = join($keywords, " OR ");
		$parameters["include_entities"] = true;

		// Adds location to parameters if it is set
		if (! empty($location))
		{
			$location = number_format($location["lat"],6) . "," . number_format($location["lon"],6) . "," . $location["radius"] . "km";
			$parameters["geocode"] = $location;
		}

		// uses last id in case we have one
		$settings = ORM::factory('socialmedia_settings')->where('setting', 'twitter_last_id')->find();
		if (! is_null($settings->value)) {
			$parameters["since_id"] = $settings->value;
		}
		else
		{
			// uses a since date in case it is set and we don't have a last id set
			if (! empty($since))
			{
				$parameters["since"] = urlencode($since);
			}
		}

		//make request using fancy Twitter class method
		$result = $twitter->oAuthRequest(self::API_URL, 'GET', $parameters);
		$result = json_decode($result, true);

		if (isset($result["errors"]))
		{
			// TODO: Better error handling
			var_dump($result["errors"]);
			return false;
		}

		// parse our lovely results
		$result = $this->parse($result, (is_null($settings->value) ? 0 : $settings->value));

		// Save new highest id
		$settings->setting = 'twitter_last_id';
		$settings->value = $result["highest_id"];
		$settings->save();

	}

	/**
	 * Creates Twitter_Oauth object
	 * @param string $consumer_key
	 * @param string $consumer_access
	 * @param string $access_token
	 * @param string $access_token_secret
	 * @return Twitter_Oauth
	 */
	static function getTwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret) {
		// Gets token based on info provided by user
		return new Twitter_Oauth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
	}

	/**
	* Parses API results and inserts them on the database
	* @param array $array_result json arrayed result
	* @param int $highest_id Current highest message ID on the database
	* @return int highest_id New highest id after parsing results
	*/
	public function parse($array_result, $highest_id = 0) {
		$statuses = $array_result["statuses"];

		foreach ($statuses as $s) {
			$entry = ORM::factory("Socialmedia_Message")
						->where("channel_id", $s["id_str"])
						->where("channel", Socialmedia_Message_Model::CHANNEL_TWITTER)
						->find();

			// don't resave messages we already have
			if (! $entry->loaded) 
			{

				// catch author if they already exist
				$author = ORM::factory("Socialmedia_Author")
							->where("channel_id", $s["user"]["id_str"])
							->where("channel", Socialmedia_Message_Model::CHANNEL_TWITTER)
							->find();

				// save author in case they don't exist
				if (! $author->loaded) 
				{
					$author->channel_id = $s["user"]["id_str"];
					$author->channel = Socialmedia_Message_Model::CHANNEL_TWITTER;
					$author->author = $s["user"]["screen_name"];
					$author->status = Socialmedia_Author_Model::STATUS_NORMAL;
					$author->save();
				}

				// get message data
				$entry->status = $entry::STATUS_TOREVIEW;
				$entry->channel = Socialmedia_Message_Model::CHANNEL_TWITTER;
				$entry->channel_id = $s["id_str"];
				$entry->message = $s["text"];
				$entry->original_date = strtotime($s["created_at"]);
				$entry->url = "http://twitter.com/" . $s["user"]["screen_name"] . "/status/" . $s["id_str"];
				$entry->author_id = $author->id;

				// saves entities in array for later
				$media = array();
				if (count($s["entities"]["urls"]) > 0) 
				{
					$media["url"] = array();

					foreach ($s["entities"]["urls"] as $url) {
						$media["url"][] = $url["expanded_url"];
					}
				}

				if (isset($s["entities"]["media"]) && count($s["entities"]["media"]) > 0)
				{
					$media["photo"] = array();
					$media["other"] = array();

					foreach ($s["entities"]["media"] as $url) {
						if ($url["type"] == "photo")
						{
							$media["photo"][] = $url["media_url"];
						} else {
							$media["other"][] = $url["media_url"];
						}
					}
				}

				// geo data
				if (! is_null($s["coordinates"]))
				{
					$entry->latitude = $s["coordinates"]["coordinates"][1]; //twitter uses long,lat
					$entry->longitude = $s["coordinates"]["coordinates"][0];
				}

				// save message and assign data to it
				$entry->save();
				$entry->addAssets($media);
			}

			if ($s["id_str"] > $highest_id) {
				$highest_id = $s["id_str"];
			}
		}

		return array(
				"highest_id"		=> $highest_id
			);
	}
}
