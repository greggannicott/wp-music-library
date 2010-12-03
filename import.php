<?php
   // Include the library used
   require_once("includes/config.php");
   require_once("includes/itunes_xml_parser_php5.php");

   // Set the timeout limit for this page
   ini_set("memory_limit", "256M");
   set_time_limit(60 * 5);
   
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Importer</title>
    </head>
    <body>
        <?php

        // Output to log that we're about to begin an import
        $logger->info("About to begin import of library.");

        // Set the file path. In future this would likely be handled by
        // an upload mech. However, for now we'll assume we know where the file
        // is stored.
        $file_path = '../library.xml';

        // Check we have a file path
        if (isset($file_path)) {

           // Generate an array of songs based on the library provided
           $songs = iTunesXmlParser($file_path);

           // Disable autocommit
           $logger->debug("Disabling Auto Commit");
           $db->autocommit(FALSE);

           try {

              // Prepare some variables to hold stats
              $rows_inserted = array();
              $rows_updated = array();
              $rows_deleted = 0;

              $logger->debug("Starting inserts and updates.");

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
                 $sql = "INSERT INTO
                            songs
                         (
                            persistent_id
                            , track_id
                            , name
                            , artist
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

                 $logger->debug("SQL being executed: ".$sql);
                 if (!$result = $db->query($sql)) {throw new Exception("Unable to insert/udpate entry '".$song['Persistent ID']."': ".$db->error);}

                 // Keep the stats up to date
                 switch ($db->affected_rows) {
                     case 1:
                         array_push($rows_inserted,$song);
                         break;
                     case 2:
                         array_push($rows_updated,$song);
                         break;
                 }

                 unset($sql);

                 // Update the database to state that this file exists in the library
                 // This will be used at the end of the import to remove entries
                 // that do not exist (ie. have been deleted from iTunes).
                 $logger->debug("Updating table to reflect that entry exists in library file.");
                 if (!$result = $db->query("UPDATE songs SET in_library_file_flag = 1 WHERE persistent_id = '".$song['Persistent ID']."'")) {throw new Exception("Unable to mark entry '".$song['Persistent ID']."' as existing in library: ".$db->error);}

              }

              $logger->debug("Inserts and updates completed.");

              // Now delete all entries not present in the library file
              $logger->debug("Deleting entries that were not present in library file.");
              if (!$result = $db->query("DELETE FROM songs WHERE in_library_file_flag is null")) {throw new Exception("Unable to removed deleted entries: ".$db->error);}

              // Keep the stats up to date
              $rows_deleted = $db->affected_rows;

              // Reset the in_library_file flag
              $logger->debug("Reseting all in_library_file flags to null.");
              if (!$result = $db->query("UPDATE songs SET in_library_file_flag = null")) {throw new Exception("Unable to reset in_library_file_flag: ".$db->error);}

              // If we got this far, we can commit the changes
              $logger->debug("Committing changes to database.");
              if ($db->commit()) {
                 $logger->info("Changes committed.");
              } else {
                 throw new Exception("Failed to commit changes. Error unknown");
              }

              // Re-enable autocommit to be on the safe side
              $logger->debug("Re-enabling Auto Commit");
              $db->autocommit(TRUE);

              // Output results to screen
              
              print '<h1>Import Results</h1>';
              print '<h2>Overview</h2>';
              print '<p>Song(s) Inserted: '.count($rows_inserted).'</p>';
              print '<p>Song(s) Updated: '.count($rows_updated).'</p>';
              print '<p>Song(s) Deleted: '.$rows_deleted.'</p>';
              
              print '<h2>Breakdown</h2>';
              
              print '<h3>Songs Inserted</h3>';
              
              if (count($rows_inserted) > 0) {
                  print '<ul>';
                  foreach($rows_inserted as $song) {
                      print '<li>'.$song['Artist'].' - '.$song['Album'].' - '.$song['Name'].'</li>';
                  }
                  print '</ul>';
              } else {
                  print '<p>None</p>';
              }
              print '<h3>Songs Updated</h3>';

              if (count($rows_updated) > 0) {
                  print '<ul>';
                  foreach($rows_updated as $song) {
                      print '<li>'.$song['Artist'].' - '.$song['Album'].' - '.$song['Name'].'</li>';
                  }
                  print '</ul>';
              } else {
                  print '<p>None</p>';
              }

              // Output results to log
              $logger->debug('--------------');
              $logger->debug('Import Outcome:');
              $logger->debug('--------------');
              $logger->debug('Song(s) Inserted: '.count($rows_inserted));
              $logger->debug('Song(s) Updated: '.count($rows_updated));
              $logger->debug('Song(s) Deleted: '.$rows_deleted);

           } catch (Exception $e) {

               $logger->info("Error encountered.");

               // Rollback any database changes
               $logger->info("Performing rollback of database changes.");
               $db->rollback();

               // Re-enable autocommit to be on the safe side
               $logger->debug("Re-enabling Auto Commit");
               $db->autocommit(TRUE);

               // Output the error message
               $logger->err($e->getMessage());
           }

        } else {
           $logger->debug("No file path provided. Would display upload form but it hasn't been coded yet.");
        }
        ?>
    </body>
</html>
