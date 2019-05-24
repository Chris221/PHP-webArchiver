<?php
require('../vendor/autoload.php');

//defines an array of the urls to archive, Requires http
$config = [
  "file" => "urls.txt",
  "verbose" => false,
  "showFilePath" => false,
  "timezone" => "America/New_York"
];

//defines the archiver
$archiver = new web\Archiver($config);

//processes the urls with the params for console output
echo $archiver->process_console();
?>