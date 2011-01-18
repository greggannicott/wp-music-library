<?php
/*
Plugin Name: WP iLibrary
Description: Displays your iTunes music library on your blog.
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

///////////////////////////////// SYSTEM SETTINGS

// The following don't appear to be having an effect. Need to investigate.
// In the mean time, they can be set in php.ini
//@ini_set( 'upload_max_size' , '100M' );
//@ini_set( 'post_max_size', '105M');
//@ini_set( 'max_execution_time', '300' );
//@ini_set( 'memory_limit', '256M' );


///////////////////////////////// GLOBAL VARIABLES

global $ilibrary_db_version;
$ilibrary_db_version = "0.1";

///////////////////////////////// INCLUDES

require_once("itunes_xml_parser_php5.php");

///////////////////////////////// CONSTANTS

# Define the table that's going to hold the song data
global $wpdb;
define('SONGS_TABLE', $wpdb->prefix . "ilibrary_songs");

# Define the path to this plugin's dir
define('ILIBRARY_DIR_PATH', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)).'/');

# Define the url to this plugin's dir
define('ILIBRARY_DIR_URL',plugin_dir_url(__FILE__));

# Define the location where the library file will be handled. This file is
# deleted at the end of the process. The below is set to the directory of this
# plugin.
define('UPLOAD_DIR', ILIBRARY_DIR_PATH);

# Define the 'artist' used to represent compilations
define('COMPILATIONS_KEYWORD', 'compilations');



///////////////////////////////// REGISTER HOOKS

// activation hooks

register_activation_hook(__FILE__,'ilibrary_install_func');

// admin pages

add_action('admin_menu', 'ilibrary_replace_menu_func');

// register actions

# Calls a function to handle additions required to <head>
add_action('wp_head', 'addheadercode_func');

// register shortcodes

# [ilibrary]
add_shortcode ( 'ilibrary', 'display_ilibrary_func');

//////////////////////////////// SUPPORTING FUNCTIONS

/**
 * Handles any additions required to the <head>
 */

function addheadercode_func() {
   echo '<link type="text/css" rel="stylesheet" href="'.ILIBRARY_DIR_URL.'styles/generic.css" />' . "\n";
}

/**
 * Outputs contents of music library when [music_library] shortcode is found
 * @param array $atts
 * @return string Content generated to replace shortcode
 */
function display_ilibrary_func($atts) {

   global $wpdb;  # used for interacting with database

   // Created required vars
   $output = null;
   $custom_request_uri = null;

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
      $results = $wpdb->get_results("SELECT * FROM ".SONGS_TABLE." WHERE compilation != 1 AND podcast != 1  AND artist != 'null' GROUP BY artist ORDER BY song_artist", OBJECT_K);

      // Determine whether we have any compilation albums
      $compilations = $wpdb->get_results("SELECT persistent_id FROM ".SONGS_TABLE." WHERE compilation = 1", OBJECT_K);
      $compilations_count = count($compilations);

      // Sub Title
      $output = '<h3>All Bands & Artists</h3>';

      // Check that we have results
      if (count($results) > 0 || $compilations_count > 0) {

         // Intro Text
         $output .= '<p>This page contains a list of all the bands and artists in my music collection. To view the list of albums I own per artist/band, just click the relevant artist/band.</p>';

         // Include a compilation albums section if required
         if ($compilations_count > 0) {
            $output .= '<h4 class="initial_letter">Compilations</h4>';
            $output .= '<ul>';
            $output .= '   <li><a href="'.$custom_request_uri.'artist='.COMPILATIONS_KEYWORD.'">Compilation Albums</a></li>';
            $output .= '</ul>';
         }

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

      // If there were no entries in the library, state as much
      } else {
         $output .= '<p>There are currently no songs present in the library.</p>';
      }

   // If only artist is passed in, display list of albums
   } elseif (isset($_GET['artist']) && !isset($_GET['album'])) {

      $output = null;

      if (strtolower($_GET['artist']) != COMPILATIONS_KEYWORD) {
         // Return a list of all artists
         $results = $wpdb->get_results("SELECT * FROM ".SONGS_TABLE." WHERE artist = '".$_GET['artist']."' and compilation != 1 AND podcast != 1 GROUP BY album ORDER BY album", OBJECT_K);

         $output = '<h3>'.$_GET['artist'].' Albums in my collection:</h3>';

      } else {
         $results = $wpdb->get_results("SELECT * FROM ".SONGS_TABLE." WHERE compilation = 1 AND podcast != 1 GROUP BY album ORDER BY album", OBJECT_K);
         $output = '<h3>Compilation albums in my collection:</h3>';
      }

      $output .= '<ul>';

      if (count($results) > 0) {
         foreach ($results as $song) {
            $output .= '<li><a href="'.$custom_request_uri.'artist='.urlencode($_GET['artist']).'&album='.urlencode($song->album).'">'.$song->album.'</a></li>';
         }
      } elseif (count($results) == 0 && strtolower($_GET['artist']) == COMPILATIONS_KEYWORD) {
        $output .= '<p>There are no compilations present in this collection.</p>';
      }

      $output .= '</ul>';

   // If artist AND album are present, display track list
   } elseif (isset($_GET['artist']) && isset($_GET['album'])) {

      // Return a list of all artists
      if ($_GET['artist'] != COMPILATIONS_KEYWORD) {
         $results = $wpdb->get_results("SELECT * FROM ".SONGS_TABLE." WHERE artist = '".$_GET['artist']."' and album = '".$_GET['album']."' and compilation != 1 AND podcast != 1 ORDER BY track_number", OBJECT_K);
      } else {
         $results = $wpdb->get_results("SELECT * FROM ".SONGS_TABLE." WHERE album = '".$_GET['album']."' and compilation = 1 AND podcast != 1 ORDER BY track_number", OBJECT_K);
      }

      // Define the HTML for a star
      $star_html = '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-ilibrary/images/star_on.gif">';

      if ($_GET['artist'] != COMPILATIONS_KEYWORD) {
         $output = '<h3>'.stripslashes($_GET['artist']).' - '.stripslashes($_GET['album']).'</h3>';
      } else {
         $output = '<h3>'.stripslashes($_GET['album']).'</h3>';
      }

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
function ilibrary_replace_menu_func() {
   add_options_page('Library Import', 'WP iLibrary', 'manage_options', basename(__FILE__), 'ilibrary_options_page');
}

/**
 * Handles the display of the options page for this plugin
 */
function ilibrary_options_page() {

   global $wpdb;  # used for interacting with database

   // IMPORT LIBRARY
   // Check to see whether the import form has been used
   if (isset($_POST['action']) && $_POST['action'] == 'import') {

      try {

         // Validate the file

         # Check the user has uploaded a file
         if ($_FILES['library_file']['tmp_name'] == '') {throw new Exception("Unable to import file. No library file provided. Please try again.");}

         # Check that the file being uploaded has an xml extension
         $ext = substr($_FILES['library_file']['name'], strrpos($_FILES['library_file']['name'], '.') + 1);
         if (strtolower($ext) != 'xml') {throw new Exception("Unable to import file. File provided does not have an '.xml' file extension.");}

         // Move the uploaded file to the 'plugins' directory

         if(!move_uploaded_file($_FILES['library_file']['tmp_name'], UPLOAD_DIR.basename( $_FILES['library_file']['name']))) {
            throw new Exception("There was an error uploading the file. Please try again.");
         }

         // Generate an array of songs based on the library provided
         $songs = iTunesXmlParser(UPLOAD_DIR.basename( $_FILES['library_file']['name']));

         // Start the transaction
         @mysql_query("BEGIN", $wpdb->dbh);

         // Enable displaying of errors
         $wpdb->hide_errors();

         // Prepare some variables to hold stats
         $rows_inserted = array();
         $rows_updated = array();
         $rows_deleted = 0;

         // Loop through each song in the library
         foreach ($songs as $song) {

            // Handle the true/false fields
            // If they exist in the song's entry, it implies 'true'.
            $compilation = isset($song['Compilation']) ? 1 : 0;
            $podcast = isset($song['Podcast']) ? 1 : 0;
            $unplayed = isset($song['Unplayed']) ? 1 : 0;
            $album_rating_computed = isset($song['Album Rated Computed']) ? 1 : 0;

            // take care of:
            // - values that might not exist for the popular song in the library (eg. default to nulls)
            // - timestamp conversion
            $track_id = isset($song['Track ID']) ? $song['Track ID'] : 'null';
            $name = isset($song['Name']) ? $song['Name'] : 'null';
            $artist = isset($song['Artist']) ? $song['Artist'] : 'null';
            $song_artist = isset($song['Song Artist']) ? $song['Song Artist'] : $song['Artist'];
            $album = isset($song['Album']) ? $song['Album'] : 'null';
            $kind = isset($song['Kind']) ? $song['Kind'] : 'null';
            $size = isset($song['Size']) ? $song['Size'] : 'null';
            $total_time = isset($song['Total Time']) ? $song['Total Time'] : 'null';
            $track_number = isset($song['Track Number']) ? $song['Track Number'] : 'null';
            $track_count = isset($song['Track Count']) ? $song['Track Count'] : 'null';
            $year = isset($song['Year']) ? $song['Year'] : 'null';
            $date_modified = isset($song['Date Modified']) ? $song['Date Modified'] : 'null';
            $date_added = isset($song['Date Added']) ? $song['Date Added'] : 'null';
            $bit_rate = isset($song['Bit Rate']) ? $song['Bit Rate'] : 'null';
            $sample_rate = isset($song['Sample Rate']) ? $song['Sample Rate'] : 'null';
            $rating = isset($song['Rating']) ? $song['Rating'] : 'null';
            $album_rating = isset($song['Album Rating']) ? $song['Album Rating'] : 'null';
            $play_count = isset($song['Play Count']) ? $song['Play Count'] : 'null';
            $play_date = isset($song['Play Date']) ? $song['Play Date'] : 'null';
            $play_date_utc = isset($song['Play Date UTC']) ? $song['Play Date UTC'] : 'null';
            $normalization = isset($song['Normalization']) ? $song['Normalization'] : 'null';
            $track_type = isset($song['Track Type']) ? $song['Track Type'] : 'null';
            $location = isset($song['Location']) ? $song['Location'] : 'null';
            $file_folder_count = isset($song['File Folder Count']) ? $song['File Folder Count'] : 'null';
            $library_folder_count = isset($song['Library Folder Count']) ? $song['Library Folder Count'] : 'null';

            // Update existing entries, and add new ones

            // Create the query
            $sql = "INSERT INTO
                      ".SONGS_TABLE."
                   (
                      persistent_id
                      , track_id
                      , name
                      , artist
                      , song_artist
                      , album
                      , compilation
                      , podcast
                      , unplayed
                      , kind
                      , size
                      , total_time
                      , track_number
                      , track_count
                      , year
                      , date_modified
                      , date_added
                      , bit_rate
                      , sample_rate
                      , rating
                      , album_rating
                      , album_rating_computed
                      , play_count
                      , play_date
                      , play_date_utc
                      , normalization
                      , track_type
                      , location
                      , file_folder_count
                      , library_folder_count
                   ) VALUES (
                      '".addslashes($song['Persistent ID'])."'
                      , ".$track_id."
                      , '".addslashes($name)."'
                      , '".addslashes($artist)."'
                      , '".addslashes($song_artist)."'
                      , '".addslashes($album)."'
                      , ".$compilation."
                      , ".$podcast."
                      , ".$unplayed."
                      , '".addslashes($kind)."'
                      , ".$size."
                      , ".$total_time."
                      , ".$track_number."
                      , ".$track_count."
                      , ".$year."
                      , '".$date_modified."'
                      , '".$date_added."'
                      , ".$bit_rate."
                      , ".$sample_rate."
                      , ".$rating."
                      , ".$album_rating."
                      , ".$album_rating_computed."
                      , ".$play_count."
                      , ".$play_date."
                      , '".$play_date_utc."'
                      , ".$normalization."
                      , '".addslashes($track_type)."'
                      , '".addslashes($location)."'
                      , ".$file_folder_count."
                      , ".$library_folder_count."
                   ) ON DUPLICATE KEY UPDATE
                      track_id = ".$track_id."
                      , name = '".addslashes($name)."'
                      , artist = '".addslashes($artist)."'
                      , song_artist = '".addslashes($song_artist)."'
                      , album = '".addslashes($album)."'
                      , compilation = ".$compilation."
                      , podcast = ".$podcast."
                      , unplayed = ".$unplayed."
                      , kind = '".addslashes($kind)."'
                      , size = ".$size."
                      , total_time = ".$total_time."
                      , track_number = ".$track_number."
                      , track_count = ".$track_count."
                      , year = ".$year."
                      , date_modified = '".$date_modified."'
                      , date_added = '".$date_added."'
                      , bit_rate = ".$bit_rate."
                      , sample_rate = ".$sample_rate."
                      , rating = ".$rating."
                      , album_rating = ".$album_rating."
                      , album_rating_computed = ".$album_rating_computed."
                      , play_count = ".$play_count."
                      , play_date = ".$play_date."
                      , play_date_utc = '".$play_date_utc."'
                      , normalization = ".$normalization."
                      , track_type = '".addslashes($track_type)."'
                      , location = '".addslashes($location)."'
                      , file_folder_count = ".$file_folder_count."
                      , library_folder_count = ".$library_folder_count
            ;

            // Execute the query
            $result = $wpdb->query($sql);

            // Check for a failure
            if ($result === FALSE) {throw new Exception("Unable to insert/update entry '".$song['Persistent ID']."': ".$wpdb->last_error);}

            // Keep the stats up to date
            switch ($result) {
               case 1:
                   array_push($rows_inserted,$song);
                   break;
               case 2:
                   array_push($rows_updated,$song);
                   break;
            }

            // Tidy up following the query
            unset($result);
            unset($sql);

            // Update the database to state that this file exists in the library
            // This will be used at the end of the import to remove entries
            // that do not exist (ie. have been deleted from iTunes).

            // Create the string
            $sql = "UPDATE ".SONGS_TABLE." SET in_library_file_flag = 1 WHERE persistent_id = '".$song['Persistent ID']."'";

            // Execute the query
            $result = $wpdb->query($sql);
            
            // Check for a failure
            if ($result === FALSE) {throw new Exception("Unable to mark entry '".$song['Persistent ID']."' as existing in library: ".$wpdb->last_error);}

            // Tidy up following the query
            unset($result);
            unset($sql);

         }

         // Now delete all entries not present in the library file

         // Create the sql
         $sql = "DELETE FROM ".SONGS_TABLE." WHERE in_library_file_flag is null";

         // Execute the query
         $result = $wpdb->query($sql);

         // Check for a failure
         if ($result === FALSE) {throw new Exception("Unable to removed deleted entries: ".$wpdb->last_error);}

         // Keep the stats up to date
         $rows_deleted = $result;

         // Tidy up following the query
         unset($result);
         unset($sql);

         // Reset the 'in_library_file_flag' flag

         // Create the SQL
         $sql = "UPDATE ".SONGS_TABLE." SET in_library_file_flag = null";

         // Execute the query
         $result = $wpdb->query($sql);
         
         // Check for a failure
         if ($result === FALSE) {throw new Exception("Unable to reset in_library_file_flag: ".$wpdb->last_error);}

         // Tidy up following the query
         unset($result);
         unset($sql);

         // Commit the transaction
         @mysql_query("COMMIT", $wpdb->dbh);

         // Tidy up and remove the library file
         unlink(UPLOAD_DIR.basename( $_FILES['library_file']['name']));

         // Output confirmation
         echo "<div id=\"message\" class=\"updated fade\">
                  <p>The file ".  basename( $_FILES['library_file']['name'])." has been imported:</p>
                  <p>
                  - Song(s) Inserted: ".count($rows_inserted)."<br/>
                  - Song(s) Updated: ".count($rows_updated)."<br/>
                  - Song(s) Deleted: ".$rows_deleted."<br/>
                  </p>
               </div>";

      } catch (Exception $e) {

         // Rollback the transaction
         @mysql_query("ROLLBACK", $wpdb->dbh);

         // Output the error
         echo "<div id=\"message\" class=\"error fade\"><p>".$e->getMessage()."</p></div>";
      }

   // NEW PAGE
   } elseif (isset($_POST['action']) && $_POST['action'] == 'add_page') {
      if (isset($_POST['page_title']) && $_POST['page_title'] != '') {

         // Build up the details for the page
         $_p = array();
         $_p['post_title'] = $_POST['page_title'];
         $_p['post_content'] = "[ilibrary]";
         $_p['post_status'] = 'publish';
         $_p['post_type'] = 'page';
         $_p['comment_status'] = 'closed';
         $_p['ping_status'] = 'closed';
         $_p['post_category'] = array(1); // the default 'Uncatrgorised'

         // Insert the post into the database
         $page_id = wp_insert_post( $_p );

         // Grab a link to the post
         $permalink = get_permalink( $page_id );

         // Output confirmation
         echo "<div id=\"message\" class=\"updated fade\"><p>New page has been created. <a href=\"$permalink\" target=\"_blank\">View your library here</a>.</p></div>";

      } else {
         echo "<div id=\"message\" class=\"error fade\"><p>Unable to create page. No title provided.</p></div>";
      }
   }

   echo '<div class="wrap">';

      // Title (wp standard is 'h2')
      echo '<h2>iLibrary Options</h2>';

      // Start the form
      echo '<form name="ilibrary_options" method="post" enctype="multipart/form-data">';

         // Include two hidden fields which automatically help to check that the user can update options and also redirect the user back
         wp_nonce_field('update-options');

         // IMPORT LIBRARY

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
         print '<p><input type="submit" class="button-primary" value="Update Library" /></p>';

     echo '</form>';

     // Start the form
     echo '<form name="ilibrary_options" method="post" enctype="multipart/form-data">';

         // ADD PAGE

         echo '<h3>Add Library Page</h3>';
         echo '<p>In order to display your library, you need a page that contains the text [ilibrary].</p>';
         echo '<p>You can perform this manually. However, a quick way to achieve this is to use the form below.</p>';
         echo '<p>Once this page has been created, you are free to treat this page as you would any other WordPress page.</p>';
         echo '<p>Please note: This will create a page even if a page already exists with the text [ilibrary].</p>';

         // Start the table -- this uses a standard look n feel for WP
         print '<table class="form-table">';

         print '<tr valign="top">';
            print '<th scope="row">Page Name:</th>';
            print '<td><input size="50" type="text" name="page_title" /></td>';
         print '</tr>';

         print '</table>';

         print '<input type="hidden" name="action" value="add_page" />';
         print '<p><input type="submit" class="button-primary" value="Add Library Page" /></p>';


      echo '</form>';

   print '</div>';

}

/**
 * This function is called when the plugin is activated
 * @global <type> $wpdb
 * @global string $ilibrary_db_version
 */

function ilibrary_install_func() {
   global $wpdb;  # used for interacting with database
   global $ilibrary_db_version;

   // Check to see if the songs table already exists. If it doesn't,
   // create it.
   if($wpdb->get_var("show tables like '".SONGS_TABLE."'") != SONGS_TABLE) {

      // Specify the sql to create the table. Note, that in order for it to work
      // using dbDelta, the following rules must be obeyed:
      // - You have to put each field on its own line in your SQL statement.
      // - You have to have two spaces between the words PRIMARY KEY and the definition of your primary key.
      // - You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
      $sql = "CREATE TABLE " . SONGS_TABLE . " (
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
      add_option("ilibrary_db_version", $ilibrary_db_version);

   }


}

?>