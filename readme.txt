=== Plugin Name ===
Contributors: Qurl
Donate link:
Tags: widget, widgets, twitter, tweet, followers, following, fans, friends, lovers
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.0.3

Tweet Lovers shows the Twitter profile pictures of the ones you are following or your followers in a widget.

== Description ==

Tweet Lovers shows the Twitter profile pictures of the ones you are following or your followers in a widget. The plugin communicates with Twitter without the need of your password. Only your Twitter username.

== Installation ==

Installation of this plugin is fairly easy:

1. Unpack `tweet-lovers.zip`
2. Upload the whole directory and everything underneath to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Add the desired widgets to a sidebar.
5. Configure the widget by at least entering your Twitter username.

== Frequently Asked Questions ==

For the latest FAQ, please visit the [online FAQ](http://www.qurl.nl/tweet-lovers/faq/).

= What are the (system) requirements to use this plugin? =

1. A properly working WordPress site (doh!).
2. Your theme must have at least one dynamic sidebar.
3. Your host must use PHP5. No, PHP4 is not supported this time (really).

= My Twitter username is not saved. What's wrong? =

You probably didn't enter your correct Twitter username.

= Can I use this plugin when I'm a protected Twitter user? =

I'm afraid not. This plugin does not authenticate with Twitter. Therefor it's not possible to retrieve your friends or followers list.

= Where do I add the oAuth keys? =

You don't need to add those keys as the plugin does not need to authenticate with Twitter.

== Changelog ==

= Version 1.0.3 =

* Changed widget construction to reflect the way WP handles widgets these days.
* Added a check to see if Twitter has returned data, preventing an empty widget.

= Version 1.0.2 =

* Added number of followers option to followers widget.
* Moved away from deprecated widget registering functions.

= Version 1.0.1 =

* Added Follow Me button option to followers widget.
* Fixed an incompatible use of XHTML.


== Upgrade Notice ==

= 1.0.3 =

Tweet Lovers now uses the improved widget construction introduced by WordPress. Becasue of this, there is a possiblity WP does not recognize your settings anymore. Please check after upgrading. 


== Screenshots ==

1. Tweet Lovers Followers Widget Admin
