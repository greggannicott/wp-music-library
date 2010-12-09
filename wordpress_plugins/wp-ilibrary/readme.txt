=== Plugin Name ===
Contributors: greggannicott
Donate link: http://example.com/
Tags: itunes, music, library, collection
Requires at least: 3.0
Tested up to: 3.02
Stable tag: trunk

This plugin enables you to display your iTunes music library on a page on your blog.

== Description ==

WP iLibrary allows you to upload your iTunes library.xml file to your WordPress
database and have it's music related content displayed on a page on your blog, allowing
your readers to view the music you own and the ratings you have given individual
songs.

An example can be found at http://greg.gannicott.co.uk/TBC.

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Download the plugin and unzip it.
1. Upload the wp-ilibrary folder to the /wp-content/plugins/ directory of your web site.
1. Activate the plugin in WordPress Admin.
1. Go to Settings -> WP iLibrary
1. Click 'Choose File' and locate your iTunes Library File ([Help finding your XML library file](http://support.apple.com/kb/HT1660))
1. Click 'Update Library'. This will import your library file.
1. Create a new 'Page'.
1. Enter a title for the page (eg. My Music Collection)
1. For the contents of that page, enter the following:</br>[ilibrary]
1. Save the page.
1. You should now be able to view your music library on that page.

== Frequently Asked Questions ==

= How do I update my online library with the latest version of my library.xml file? =

1. Login to your WordPress Admin
1. Go to Settings -> WP iLibrary
1. Click 'Choose File' and locate your iTunes Library File ([Help finding your XML library file](http://support.apple.com/kb/HT1660))
1. Click 'Update Library'. This will import your library file and update the online library with any changes.

= How often should I update my library? =

With regards to this plugin, the following changes to your iTunes library are
good reasons to update/import your library:

* Addition of new songs
* Removal of songs
* Manual updating of meta data such as ratings, song names album names etc.

= Is there an easier way to update my library? =

One day, hopefully. For now you'll have to use the previously mentioned method I'm afraid.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.
