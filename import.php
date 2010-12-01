<?php
   // Include the library used
   require_once("includes/config.php");
   require_once("includes/itunes_xml_parser_php5.php");

   // Set the timeout limit for this page
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
        $file_path = 'library.xml';

        // Check we have a file path
        if (isset($file_path)) {

           // Generate an array of songs based on the library provided
           $songs = iTunesXmlParser($file_path);

           // Disable autocommit
           $logger->debug("Disabling Auto Commit");
           $db->autocommit(FALSE);

           try {

              $updated_rows = 0;
              $deleted_rows = 0;

              // Loop through each song in the library
              foreach ($songs as $song) {

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
                         ) VALUES (
                            '".addslashes($song['Persistent ID'])."'
                            , ".$song['Track ID']."
                            , '".addslashes($song['Name'])."'
                            , '".addslashes($song['Artist'])."'
                            , '".addslashes($song['Album'])."'
                         ) ON DUPLICATE KEY UPDATE 
                            track_id = '".$song['Track ID']."'
                            , name = '".addslashes($song['Name'])."'
                            , artist = '".addslashes($song['Artist'])."'
                            , album = '".addslashes($song['Album'])."'";

                 $logger->debug("SQL being executed: ".$sql);
                 if (!$result = $db->query($sql)) {throw new Exception("Unable to insert/udpate entry '".$song['Persistent ID']."': ".$db->error);}

                 // Keep the stats up to date
//                 $affected_rows += $result;

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
//              $deleted_rows = $result->rowCount;

              // Reset the in_library_file flag
              if (!$result = $db->query("UPDATE songs SET in_library_file_flag = null")) {throw new Exception("Unable to reset in_library_file_flag: ".$db->error);}

              // If we got this far, we can commit the changes
              $logger->info("Committing changes to database.");
              $db->commit();

              // Re-enable autocommit to be on the safe side
              $logger->debug("Re-enabling Auto Commit");
              $db->autocommit(TRUE);
              
              print '<h2>Import Complete</h2>';
              print '<p>Updates/Inserted Row(s): '.$updated_rows.'</p>';
              print '<p>Deleted Row(s): '.$deleted_rows.'</p>';

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
