<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Performs install/uninstall methods for the Social Media plugin
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	 Ushahidi Team <team@ushahidi.com> 
 * @package	Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */


class Socialmedia_twitter_Install {
	/**
	 * Creates the required database tables for my_plugin_name
	 */
	public function run_install()
	{
		$twitter_service = ORM::factory("Service")->where("service_name", "SocialMedia Twitter")->find_all();

		if ($twitter_service->count() == 0) {
			$twitter_service->service_name = "SocialMedia Twitter";
			$twitter_service->description = "Implements Twitter Crawling for Social Media plugin";
			$twitter_service->save();
		}

		die();
	}


	/**
	 * Deletes the database tables for my_plugin_name
	 */
	public function uninstall()
	{
		$twitter_service = ORM::factory("Service")->where("service_name", "SocialMedia Twitter")->find_all();

		if ($twitter_service->count() == 0) {
			$twitter_service->current()->delete();
		}
	}
}