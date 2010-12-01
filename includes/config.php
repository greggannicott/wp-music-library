<?php

	// Include pair classes
	require_once("Log.php");

   // Declare the root_dir
   $root_dir = $_SERVER["DOCUMENT_ROOT"];   // in work:  DOCUMENT_ROOT = C:/xampp/xampp/htdocs/music/www/
                                            // on homer: DOCUMENT ROOT = /home/gannicott.co.uk/music/www/

   // Include in-house classes

      # None

   // Site Wide Variables
   $SITE['site_name'] = 'Music Library';

	// Setup the logging
	# Browser output
	$conf = array('error_prepend' => '<font color="#ff0000"><b>Ooops!</b></font><br><font color="#666666">','error_append'  => '</font>');
	$log_browser = &Log::singleton('display', '', '', $conf, PEAR_LOG_NOTICE);
   # File output
   $conf = array('lineFormat' => '%{timestamp} [%{priority}]  %{message}');   # http://www.indelible.org/php/Log/guide.html#log-line-format
   $log_file = &Log::singleton('file', 'logs/debug_'.date('y_m_d').'.log', '', $conf, PEAR_LOG_DEBUG);
	# Combine logs together
	$logger = &Log::singleton('composite');
	$logger->addChild($log_browser);
	$logger->addChild($log_file);

	// Establish a MySQL Connection
	$db = new mysqli('localhost','greg','wooky711','music');

   // Create the header of the log entry for this session:
   $logger->log("===============================================================",PEAR_LOG_DEBUG);
   $logger->log("Script File Name: ".$_SERVER["SCRIPT_FILENAME"],PEAR_LOG_DEBUG);
   $logger->log("Query String: ".$_SERVER['QUERY_STRING'],PEAR_LOG_DEBUG);
   $logger->log("Request Method: ".$_SERVER['REQUEST_METHOD'],PEAR_LOG_DEBUG);
   $logger->log("HTTP Cookie: ".$_SERVER['HTTP_COOKIE'],PEAR_LOG_DEBUG);
   $logger->log("---------------------------------------------------------------",PEAR_LOG_DEBUG);
?>