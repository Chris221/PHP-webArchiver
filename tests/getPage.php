<?php
require('../vendor/autoload.php');

//defines the archiver
$archiver = new web\Archiver([
  "file" => "urls.txt",
  "verbose" => false,
  "showFilePath" => false,
  "timezone" => "America/New_York"
]);

//processes the urls with the params for console output
echo $archiver->process_console();
?>