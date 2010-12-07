<?php
/*
Plugin Name: Music Library
Description: Displays music library.
Version: 0.1
Author: Greg Gannicott
Author URI: http://greg.gannicott.co.uk
License: GPL2
*/
/*  Copyright 2010  Greg Gannicott  (email : greg@gannicott.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

///////////////////////////////// GLOBAL VARIABLES

global $music_library_db_version;
$music_library_db_version = "0.1";

///////////////////////////////// REGISTER HOOKS

// activation hooks

register_activation_hook(__FILE__,'music_library_install_func');

// admin pages

add_action('admin_menu', 'music_library_replace_menu_func');

// register actions

# Calls a function to handle additions required to <head>
add_action('wp_head', 'addheadercode_func');

// register shortcodes

# [music_library]
add_shortcode ( 'music_library', 'musiclibrary_func');

//////////////////////////////// SUPPORTING FUNCTIONS

/**
 * Handles any additions required to the <head>
 */

function addheadercode_func() {
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/MusicLibrary/styles/generic.css" />' . "\n";
}

/**
 * Outputs contents of music library when [music_library] shortcode is found
 * @param array $atts
 * @return string Content generated to replace shortcode
 */
function musiclibrary_func($atts) {

   // Created required vars
   $output = null;
   $custom_request_uri = null;

   // Connect to the music library database
   $database = new wpdb('greg','wooky711','music','localhost');

   // Determine whether permalinks are in use or not...
   $permalinks = (get_option('permalink_structure') != '') ? TRUE : FALSE;

   // Determine the request_uri to be used based on whether permalinks are
   // in use and also whether args are being passed in or not. There is a known
   // issue where if permalinks are disabled and you're viewing a subpage (ie.
   // not the list of all artists), the urls generated on that page include
   // duplicate fields. This doesn't cause a failure.
   if (count($_SERVER['argv']) > 0) {
      // If permalinks are enabled, we want to get rid of all arguments to avoid
      // duplicates.
      if ($permalinks) {
         $custom_request_uri = '?';
      } else {
         $custom_request_uri = $_SERVER['REQUEST_URI'].'&';
      }
   } else {
      $custom_request_uri = $_SERVER['REQUEST_URI'].'?';
   }

   // If no artist or album is passed, display all artists
   if (!isset($_GET['artist']) && !isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE compilation != 1 AND podcast != 1  AND artist != 'null' GROUP BY artist ORDER BY song_artist", OBJECT_K);

      // Sub Title
      $output = '<h3>All Bands & Artists</h3>';

      // Intro Text
      $output .= '<p>This page contains a list of all the bands and artists in my music collection. To view the list of albums I own per artist/band, just click the relevant artist/band.</p>';

      foreach ($results as $song) {

         // Check to see if the first letter differs from the previous
         if ($prev_first_letter != substr(strtoupper($song->artist), 0,1)) {
            // If we have a prev letter, close off the previous ul.
            if (isset($prev_first_letter)) {
               $output .= '</ul>';
            }
            // Print the letter
            $output .=  '<h4 class="initial_letter">'.substr($song->artist,0,1).'</h4>';
            // Start off the next ul.
            $output .=  '<ul>';
         }

         // Output the song name
         $output .= '<li><a href="'.$custom_request_uri.'artist='.urlencode($song->artist).'">'.$song->artist.'</a></li>';

         // Note the prev first letter.
         $prev_first_letter = strtoupper(substr($song->artist,0,1));
         
      }

      $output .= '</ul>';

   // If only artist is passed in, display list of albums
   } elseif (isset($_GET['artist']) && !isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE artist = '".$_GET['artist']."' and compilation != 1 AND podcast != 1 GROUP BY album ORDER BY album", OBJECT_K);

      $output = '<h3>'.$_GET['artist'].' albums in my collection:</h3>';

      $output .= '<ul>';

      foreach ($results as $song) {
         $output .= '<li><a href="'.$custom_request_uri.'artist='.urlencode($song->artist).'&album='.urlencode($song->album).'">'.$song->album.'</a></li>';
      }

      $output .= '</ul>';

   // If artist AND album are present, display track list
   } elseif (isset($_GET['artist']) && isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE artist = '".$_GET['artist']."' and album = '".$_GET['album']."' and compilation != 1 AND podcast != 1 ORDER BY track_number", OBJECT_K);

      // Define the HTML for a star
      $star_html = '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/MusicLibrary/images/star_on.gif">';

      $output = '<h3>'.$_GET['artist'].' - '.$_GET['album'].'</h3>';

      $output .= '<ul>';

      foreach ($results as $song) {

         // Generate the collection of starts
         for ($i = 1; $i <= $song->rating; $i = $i + 20) {
            $stars .= $star_html;
         }

         // Output the song
         $output .= '<li>'.$song->track_number.' - '.$song->name.' '.$stars.'</li>';

         // Clear out the previous stars
         $stars = null;
      }

      $output .= '</ul>';
   } else {
      print '<p>Unable to display music library. Parameters passed in do not match requirements.</p>';
   }

   // Return the music library in place of the shortcode
   return $output;
}

/**
 * Add a link to the admin page to the WordPress menu
 */
function music_library_replace_menu_func() {
   add_options_page('Music Library Options', 'Music Library', 'manage_options', basename(__FILE__), 'music_library_options_page');
}

/**
 * Handles the display of the options page for this plugin
 */
function music_library_options_page() {

   // Check to see whether the import form has been used
   if (isset($_POST['action']) && $_POST['action'] == 'import') {
      // Move the uploaded file to the 'uploads' directory
      $upload = wp_upload_bits($_FILES["library_file"]["name"], null, file_get_contents($_FILES["library_file"]["tmp_name"]));

      print '<p>'.print_r($upload).'</p>';
   }

   echo '<div class="wrap">';

      // Title (wp standard is 'h2')
      echo '<h2>Music Library Options</h2>';

      // Start the form
      echo '<form name="music_library_options" method="post" enctype="multipart/form-data">';

         // Include two hidden fields which automatically help to check that the user can update options and also redirect the user back
         wp_nonce_field('update-options');

         echo '<h3>Upload Library</h3>';
         echo '<p>This will up trigger an update of your library, adding new entries, updating existing and removing deleted entries.</p>';

         // Start the table -- this uses a standard look n feel for WP
         print '<table class="form-table">';

         print '<tr valign="top">';
            print '<th scope="row">Library File:</th>';
            print '<td><input size="50" type="file" name="library_file" /></td>';
         print '</tr>';

         print '</table>';

         print '<input type="hidden" name="action" value="import" />';
         print '<input type="submit" class="button-primary" value="Update Library" />';

      echo '</form>';

   print '</div>';

}

/**
 * This function is called when the plugin is activated
 * @global <type> $wpdb
 * @global string $music_library_db_version
 */

function music_library_install_func() {
   global $wpdb;
   global $music_library_db_version;

   // Set the name for the table that's hold the list of iTunes songs.
   $table_name = $wpdb->prefix . "music_library_songs";

   // Check to see if the music_library_songs table already exists. If it doesn't,
   // create it.
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

      // Specify the sql to create the table. Note, that in order for it to work
      // using dbDelta, the following rules must be obeyed:
      // - You have to put each field on its own line in your SQL statement.
      // - You have to have two spaces between the words PRIMARY KEY and the definition of your primary key.
      // - You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY. 
      $sql = "CREATE TABLE " . $table_name . " (
                  `persistent_id` text NOT NULL,
                  `track_id` int(11) DEFAULT NULL,
                  `name` text,
                  `artist` text,
                  `song_artist` text COMMENT 'Artist, but without the ''The''',
                  `album` text,
                  `kind` text,
                  `size` int(11) DEFAULT NULL,
                  `total_time` int(11) DEFAULT NULL,
                  `track_number` int(11) DEFAULT NULL,
                  `track_count` int(11) DEFAULT NULL,
                  `year` int(11) DEFAULT NULL,
                  `date_modified` timestamp NULL DEFAULT NULL,
                  `date_added` timestamp NULL DEFAULT NULL,
                  `bit_rate` int(11) DEFAULT NULL,
                  `sample_rate` int(11) DEFAULT NULL,
                  `rating` int(11) DEFAULT NULL,
                  `album_rating` int(11) DEFAULT NULL,
                  `album_rating_computed` tinyint(1) DEFAULT NULL,
                  `play_count` int(11) DEFAULT NULL,
                  `play_date` int(11) DEFAULT NULL,
                  `play_date_utc` timestamp NULL DEFAULT NULL,
                  `normalization` int(11) DEFAULT NULL,
                  `compilation` tinyint(1) DEFAULT NULL,
                  `podcast` tinyint(1) DEFAULT NULL,
                  `unplayed` tinyint(1) DEFAULT NULL COMMENT 'States whether a podcast has been played or not.',
                  `track_type` text,
                  `location` text,
                  `file_folder_count` int(11) DEFAULT NULL,
                  `library_folder_count` int(11) DEFAULT NULL,
                  `in_library_file_flag` tinyint(1) DEFAULT NULL COMMENT 'Used during import processed. Flagged as true if present in library file. All without true status are removed at end of import.',
                  PRIMARY KEY  (`persistent_id`(767))
               ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

      // Include the code required to perform the dbDelta
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      // Perform the dbDelta - this will create/update the table
      dbDelta($sql);

      // Make a note of this database version - this could come in handy
      // when we need to perform an update.
      add_option("music_library_db_version", $music_library_db_version);

   }


}

?>