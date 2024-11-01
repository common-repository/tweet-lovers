<?php
/**
 * Plugin Name: Tweet Lovers
 * Plugin URI: http://www.qurl.nl/tweet-lovers/
 * Description: Tweet Lovers shows the Twitter profile pictures of the ones you are following or your followers in a widget.
 * Author: Jacco
 * Version: 1.0.3
 * Author URI: http://www.qurl.nl/
 * Tags: widget, twitter, tweet, follower, following, fans, friends, lovers
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * Released under the GPL v.2, http://www.gnu.org/copyleft/gpl.html
 *
 * @version $Id: tweet-lovers.php 500653 2012-02-05 16:55:21Z qurl $
 */

	// Constants
	define('TWL_DATA_TYPE', 'json');
	define('TWL_PLUGIN_URL', WP_PLUGIN_URL . '/' . str_replace( basename(__FILE__), '', plugin_basename(__FILE__) ) );
	define('TWL_TWITTER_BASE_URL', 'http://twitter.com/');
	define('TWL_TWITTER_LIMIT', 100);
	define('TWL_TWITTER_REST', 'http://api.twitter.com/1/');
	define('TWL_TWITTER_SEARCH', 'http://search.twitter.com/search.json?q=@');
	define('TWL_REST_FOLLOWERS', TWL_TWITTER_REST . 'statuses/followers/');
	define('TWL_REST_FRIENDS', TWL_TWITTER_REST . 'statuses/friends/');
	define('TWL_REST_USERSHOW', TWL_TWITTER_REST . 'users/show/');
	define('TWL_TITLE_FOLLOWERS', 'My Twitter followers');
	define('TWL_TITLE_FRIENDS', 'I\'m following');
	define('TWL_TIME_LIMIT', 900);	// 15 minutes
	define('TWL_VERSION', '1.0.3');

	// TWL Functions
	/**
	 * checkUser() Checks for valid Twitter username
	 *
	 * @param string $username The username to check
	 * @return boolean
	 * @since 0.2
	 */
	function checkUser($username) {
		$url = TWL_TWITTER_SEARCH . strtolower($username);
		$data = wp_remote_fopen($url);

		if ( $data && ! empty($data) ) {
			$data = json_decode($data);

			if ( $data->max_id == '-1' ) {
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * createURL() Creates REST URL
	 *
	 * @param string $type Type (friends or followers)
	 * @return string URL or boolean FALSE when invalid
	 * @since 0.2
	 */
	function createURL($type) {
		$user = get_option('twl_twitter_username');

		if ( $user && ! empty($user) ) {
			$url_fld = 'twl_rest_' . $type;
			$const_name = strtoupper($url_fld);

			$url  = constant($const_name);
			$url .= $user . '.' . TWL_DATA_TYPE;
			return $url;
		} else {
			return FALSE;
		}
	}

	/**
	 * createWidget() Outputs widget
	 *
	 * @param string $args Widget args
	 * @param string $type Type (friends or followers)
	 * @since 0.1
	 */
	function createWidget($args, $type, $instance) {
		extract($args);
		$limit = get_option('twl_limit');
		$image_size = get_option('twl_image_size');

		$title_fld = 'twl_title_' . $type;
		$title = get_option($title_fld);
		if ( empty($title) ) {
			$const_name = strtoupper($title_fld);
			$title = constant($const_name);
		}

		$all_data = getData($type);
		$data = $all_data['twl'];
		$userdata = $all_data['userdata'];
		$u_arg = $type . '_count';

		if (! $limit || count($data) < $limit ) {
			$limit = count($data);
		}

		if ( empty($image_size) ) {
			$image_size = '48';
		}

		echo $before_widget;
		echo $before_title . $title. $after_title;

		echo '<style type="text/css"> #tweet-lovers img { margin : 3px; margin-bottom: 0px; } </style>';
		echo '<div id="tweet-lovers">';
		if ( $type == 'followers' && get_option('twl_nrfollow') && ! empty($userdata) ) {
			echo '<div id="twitter-followers">' . $userdata->name . ' has ' . $userdata->$u_arg . ' ' . $type . '</div><br />';
		}

		for ( $i = 0; $i < $limit; $i++ ) {
			$twl = $data[$i];
			echo '<a rel="nofollow" href="' . TWL_TWITTER_BASE_URL . $twl['screen_name'] . '" target="_blank" title="' . $twl['screen_name'] . '"><img src="' . $twl['image'] . '" width="' . $image_size . '" height="' . $image_size . '" alt="' . $twl['screen_name'] . '" /></a>';
		}
		echo '</div>';

		echo '<div style="' . ( $type == 'followers' && get_option('twl_followme') ? 'height:48px;line-height:48px;' : '' ) . '">';
		if ( $type == 'followers' && get_option('twl_followme') ) {
			echo '<a href="' . TWL_TWITTER_BASE_URL . get_option('twl_twitter_username') . '" target="_blank" title="Follow me on Twitter"><img src="' . TWL_PLUGIN_URL . 'twitter_followme.png" width="48" height="48" align="right" alt="Follow me" /></a>';
		}
		if ( get_option('twl_credit')) {
			echo '<div style="position:relative;top:25%;font-size:0.8em;margin-right:48px;">Powered by <a href="http://www.qurl.nl/tweet-lovers/" target="_blank">Tweet Lovers</a></div>';
		}
		echo '</div>';

		echo $after_widget;
	}

	/**
	 * getData() Retrieves data from Twitter API or cache
	 *
	 * @param string $type Type (friends or followers)
	 * @return array
	 * @since 0.1
	 */
	function getData($type) {
		$lastrun_fld = 'twl_lastrun_' . $type;
		$data_fld = 'twl_data_' . $type;
		$lastrun = get_option($lastrun_fld);

		if ( (time() - $lastrun) > TWL_TIME_LIMIT ) {
			// Time to try a query on twitter
			$twl = array();
			$url = createURL($type);
			if ( $url ) {
				$data = qTwitter($url);
				if ( is_array($data) && count($data) > 0 ) {
					foreach ( $data as $f ) {
						$twl[ ] = array(
												'screen_name' => $f->screen_name,
												'image' 			=> $f->profile_image_url
											);
					}

					if ( is_array($twl) && count($twl) > 0 ) {
						update_option($lastrun_fld, time());
						update_option($data_fld, $twl);
					}

					$userdata = getUserData();
					if ( is_array($userdata) && count($userdata) > 0 ) {
						update_option('twl_usershow', $userdata);
					}
				}
			}
		}

		$twl = get_option($data_fld);
		$userdata = get_option('twl_usershow');

		return array('twl' => $twl, 'userdata' => $userdata);
	}

	/**
	 * getUserData() Retrieves userdata
	 *
	 * @return object
	 * @since 1.0.2
	 */
	function getUserData() {
		$url = createURL('usershow');
		if ( $url && $data = qTwitter($url) ) {
			return $data;
		}
		return FALSE;
	}

	/**
	 * qTwitter() Queries data from Twitter API
	 *
	 * @param string $url URL
	 * @return mixed
	 * @since 1.0.2
	 */
	function qTwitter($url) {
		$data = wp_remote_fopen($url);
		if ( $data && ! empty($data) ) {
			// Only JSON for now
			switch( TWL_DATA_TYPE ) {
				case 'json':
					$data = json_decode($data);
					break;
			}
			return $data;
		}
		return FALSE;
	}

	/**
	 * widgetControl() Saves and output Widget control
	 *
	 * @param string $type Type (friends or followers)
	 * @since 0.1
	 */
	function widgetControl($type, $instance) {

		$title_fld = 'twl_title_' . $type;
		$size = array('48', '40', '32', '24', '16');

		_e('Widget title:');
		echo '<input class="widefat" id="' . $title_fld . '" name="' . $title_fld . '" type="text" value="' . get_option($title_fld) . '" />';
		echo '<br /><br />';

		echo 'Twitter Username';
		echo '<input class="widefat" id="twl_twitter_username" name="twl_twitter_username" type="text" value="' . get_option('twl_twitter_username') . '" />';
		echo '<br /><br />';

		echo 'Image size ';
		echo '<select name="twl_image_size">';
		foreach ( $size as $s ) {
			echo '<option value="' . $s . '" ' . ( get_option('twl_image_size') == $s ? 'selected' : '' ) . '>' . $s . '</option>';
		}
		echo '</select>';
		echo '<br /><br />';

		echo 'Number of images to display:<br />';
		echo '<input type="text" id="twl_limit" name="twl_limit" size="3" value="' . get_option('twl_limit') . '" />  (max 100)';
		echo '<br /><br />';

		if ( $type == 'followers' ) {
			echo '<input type="checkbox" id="twl_followme" name="twl_followme" ' . ( get_option('twl_followme') ? 'checked=checked' : '' ) . ' /> <label for="twl_followme">Add Twitter Follow Me button</label><br />';
			echo '<input type="checkbox" id="twl_nrfollow" name="twl_nrfollow" ' . ( get_option('twl_nrfollow') ? 'checked=checked' : '' ) . ' /> <lable for="twl_nrfollow">Show number of followers</label><br />';
		}
		echo '<input type="checkbox" id="twl_credit_' . $type . '" name="twl_credit" ' . ( get_option('twl_credit') ? 'checked=checked' : '' ) . ' /> <label for="twl_credit_' . $type . '">Give credits to Tweet Lovers</label>';
		echo '<br /><br />';
	}

	function twl_install() {
		// refuse to activate on PHP < 5
		if ( version_compare(PHP_VERSION, '5.0.0', '<') ) {
			$plugin = plugin_basename(__FILE__);
			deactivate_plugins($plugin);
		} else {
			if ( get_option('twl_version') === FALSE ) {
				update_option('twl_credit', TRUE);
			}
			update_option('twl_version', TWL_VERSION);
		}
	}
	
	// Widget constructors	
	class TWL_Friends_Widget extends WP_Widget {
		function __construct() {
			parent::WP_Widget( 'twlfriends', 'Tweet Lovers Friends', array( 'classname' => 'twl_friends', 'description' => 'Displayes your Twitter Friends' ) );
		}
		
		function form($instance) {
			widgetControl('friends', $instance);
		}
		
		function update($new_instance, $old_instance) {
			// check username
			$user = get_option('twl_twitter_username');
			if (! empty($_POST['twl_twitter_username']) && $user != strtolower($_POST['twl_twitter_username']) ) {
				$check = checkUser($_POST['twl_twitter_username']);
				if ( $check ) {
					update_option('twl_twitter_username', strtolower($_POST['twl_twitter_username']));

					delete_option('twl_data_followers');
					delete_option('twl_data_friends');
					delete_option('twl_lastrun_followers');
					delete_option('twl_lastrun_friends');
				}
			}

			// limit
			(int) $limit = $_POST['twl_limit'];
			if ( $limit > TWL_TWITTER_LIMIT ) {
				$limit = TWL_TWITTER_LIMIT;
			}
			update_option('twl_limit', $limit);

			// credit
			if ( $_POST['twl_credit'] == 'on' ) {
				update_option('twl_credit', TRUE);
			} else {
				update_option('twl_credit', FALSE);
			}

			update_option($title_fld, $_POST[$title_fld]);
			update_option('twl_image_size', $_POST['twl_image_size']);			
		}
		
		function widget($args, $instance) {
			createWidget($args, 'friends', $instance);
		}
	}
	
	// Widget class for the multi-widget creation
	class TWL_Follow_Widget extends WP_Widget {
		function __construct() {
			parent::WP_Widget( 'twlfollow', 'Tweet Lovers Followers', array( 'classname' => 'twl_followers', 'description' => 'Displays your Twitter Followers' ) );
		}

		function form($instance) {
			widgetControl('followers', $instance);
		}

		function update($new_instance, $old_instance) {
			// check username
			$user = get_option('twl_twitter_username');
			if (! empty($_POST['twl_twitter_username']) && $user != strtolower($_POST['twl_twitter_username']) ) {
				$check = checkUser($_POST['twl_twitter_username']);
				if ( $check ) {
					update_option('twl_twitter_username', strtolower($_POST['twl_twitter_username']));

					delete_option('twl_data_followers');
					delete_option('twl_data_friends');
					delete_option('twl_lastrun_followers');
					delete_option('twl_lastrun_friends');
				}
			}

			// limit
			(int) $limit = $_POST['twl_limit'];
			if ( $limit > TWL_TWITTER_LIMIT ) {
				$limit = TWL_TWITTER_LIMIT;
			}
			update_option('twl_limit', $limit);

			// followme
			if ( isset($_POST['twl_followme']) ) {
				if ( $_POST['twl_followme'] == 'on' ) {
					update_option('twl_followme', TRUE);
				} else {
					update_option('twl_followme', FALSE);
				}
			}

			// nrfollow
			if ( isset($_POST['twl_nrfollow']) ) {
				if ( $_POST['twl_nrfollow'] == 'on' ) {
					update_option('twl_nrfollow', TRUE);
				} else {
					update_option('twl_nrfollow', FALSE);
				}
			}

			// credit
			if ( $_POST['twl_credit'] == 'on' ) {
				update_option('twl_credit', TRUE);
			} else {
				update_option('twl_credit', FALSE);
			}

			update_option($title_fld, $_POST[$title_fld]);
			update_option('twl_image_size', $_POST['twl_image_size']);
		}

		function widget($args, $instance) {
			createWidget($args, 'followers', $instance);
		}

	}	

	// WP hook action
	add_action( 'widgets_init', create_function( '', 'register_widget("TWL_Friends_Widget");' ) );
	add_action( 'widgets_init', create_function( '', 'register_widget("TWL_Follow_Widget");' ) );
	register_activation_hook(__FILE__, 'twl_install');
?>