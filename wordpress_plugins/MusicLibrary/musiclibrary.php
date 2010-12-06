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

// register shortcodes

# [music_library]
add_shortcode ( 'music_library', 'musiclibrary_func');

/**
 * Outputs contents of music library when [music_library] shortcode is found
 * @param array $atts
 * @return string Content generated to replace shortcode
 */
function musiclibrary_func($atts) {

   // Create a var to handle the output
   $output = null;

   // Connect to the music library database
   $database = new wpdb('greg','wooky711','music','localhost');

   // If no artist or album is passed, display all artists
   if (!isset($_GET['artist']) && !isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE compilation != 1 AND podcast != 1  AND artist != 'null' GROUP BY artist ORDER BY song_artist", OBJECT_K);

      $output = '<h3>All Artists</h3>';

      $output .= '<ul>';

      foreach ($results as $song) {
         $output .= '<li><a href="'.$_SERVER["REQUEST_URI"].'&artist='.urlencode($song->artist).'">'.$song->artist.'</a></li>';
      }

      $output .= '</ul>';

   // If only artist is passed in, display list of albums
   } elseif (isset($_GET['artist']) && !isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE artist = '".$_GET['artist']."' and compilation != 1 AND podcast != 1 GROUP BY album ORDER BY album", OBJECT_K);

      $output = '<h3>'.$_GET['artist'].' Albums.</h3>';

      $output .= '<ul>';

      foreach ($results as $song) {
         $output .= '<li><a href="'.$_SERVER["REQUEST_URI"].'&artist='.urlencode($song->artist).'&album='.urlencode($song->album).'">'.$song->album.'</a></li>';
      }

      $output .= '</ul>';

   // If artist AND album are present, display track list
   } elseif (isset($_GET['artist']) && isset($_GET['album'])) {

      // Return a list of all artists
      $results = $database->get_results("SELECT * FROM songs WHERE artist = '".$_GET['artist']."' and album = '".$_GET['album']."' and compilation != 1 AND podcast != 1 ORDER BY track_number", OBJECT_K);

      $output = '<h3>'.$_GET['artist'].' - '.$_GET['album'].'</h3>';

      $output .= '<ul>';

      foreach ($results as $song) {
         $output .= '<li>'.$song->track_number.' - '.$song->name.'</li>';
      }

      $output .= '</ul>';
   } else {
      print '<p>Unable to display music library. Parameters passed in do not match requirements.</p>';
   }

   // Return the music library in place of the shortcode
   return $output;
}



?>