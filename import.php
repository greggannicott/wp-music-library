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

              // Loop through each song in the library
              foreach ($songs as $song) {

                  // Handle the true/false fields
                  $compilation = isset($song['Compilation']) ? 1 : 0;
                  $podcast = isset($song['Podcast']) ? 1 : 0;
                  $unplayed = isset($song['Unplayed']) ? 1 : 0;
                  
                 // Update existing entries, and add new ones
                 //if (!$result = $db->query("SELECT persistent_id FROM songs WHERE persistent_id = '".$song['Persistent ID']."'")) {$logger->error("Error checking the existence of song ".$song['Persistent ID'].": ".$db->error);}
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
                         ) VALUES (
                            '".addslashes($song['Persistent ID'])."'
                            , ".$song['Track ID']."
                            , '".addslashes($song['Name'])."'
                            , '".addslashes($song['Artist'])."'
                            , '".addslashes($song['Album'])."'
                            , ".$compilation."
                            , ".$podcast."
                            , ".$unplayed."
                         ) ON DUPLICATE KEY UPDATE 
                            track_id = '".$song['Track ID']."'
                            , name = '".addslashes($song['Name'])."'
                            , artist = '".addslashes($song['Artist'])."'
                            , album = '".addslashes($song['Album'])."'
                            , compilation = ".$compilation."
                            , podcast = ".$podcast."
                            , unplayed = ".$unplayed;

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
                 $logger->debug("Updating table to reflect that entry exists in library.");
                 if (!$result = $db->query("UPDATE songs SET in_library_file_flag = 1 WHERE persistent_id = '".$song['Persistent ID']."'")) {throw new Exception("Unable to mark entry '".$song['Persistent ID']."' as existing in library: ".$db->error);}

              }

              // Now delete all entries not present in the library file
              if (!$result = $db->query("DELETE FROM songs WHERE in_library_file_flag is null")) {throw new Exception("Unable to removed deleted entries: ".$db->error);}

              // Keep the stats up to date
              $rows_deleted = $db->affected_rows;

              // Reset the in_library_file flag
              if (!$result = $db->query("UPDATE songs SET in_library_file_flag = null")) {throw new Exception("Unable to reset in_library_file_flag: ".$db->error);}

              // If we got this far, we can commit the changes
              $logger->info("Committing changes to database.");
              $db->commit();

              // Re-enable autocommit to be on the safe side
              $logger->debug("Re-enabling Auto Commit");
              $db->autocommit(TRUE);
              
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

           } catch (Exception $e) {

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
