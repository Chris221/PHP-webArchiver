<?php

namespace web;

/**
 * This class saves the references from html
 */
class Reference {
  protected $tagType, $originalText, $oldReference, $newText, $domain, $path, $filepath, $originalFile;

  /**
   * The constructor function is used to set up the whole class
   *
   * @param array $config Takes a list of params and sets up the library on them
   * @property string tagType The tag that we are replacing
   * @property string originalText The text to be replaced
   * @property string reference The link to the file location from the page
   * @property string domain The domain it came from
   * @property string path The path to the save location
   * @property string filepath This is the path from the save location
   * @property string originalFile The original file reference from the site
   */
  function __construct(array $config = []) {
    //sets the tagType if set
    if (isset($config["tagType"])) $this->tagType = $config["tagType"];
    //otherwise error out
    else throw new \Exception("There must be a tag sent.");

    //sets the originalText if set
    if (isset($config["originalText"])) $this->originalText = $config["originalText"];
    //otherwise error out
    else throw new \Exception("There must be an original text sent.");

    //sets the reference if set
    if (isset($config["reference"])) $this->oldReference = $config["reference"];
    //otherwise error out
    else throw new \Exception("There must be a reference sent.");

    //sets the domain if set
    if (isset($config["domain"])) $this->domain = $config["domain"];
    //otherwise error out
    else throw new \Exception("There must be a domain sent.");

    //sets the path if set
    if (isset($config["path"])) $this->path = $config["path"];
    //otherwise error out
    else throw new \Exception("There must be a path sent.");

    //sets the filepath if set
    if (isset($config["filepath"])) $this->filepath = $config["filepath"];
    //otherwise error out
    else throw new \Exception("There must be a filepath sent.");

    //sets the originalFile if set
    if (isset($config["originalFile"])) $this->originalFile = $config["originalFile"];
    //otherwise error out
    else throw new \Exception("There must be a originalFile sent.");
  }

  /**
   * This function takes in a string and updates the reference variables
   *
   * @param string $string The new reference to replace the old one
   * @return void
   */
  protected function update_reference($string) {
    //updates the original
    $this->newText = str_replace($this->oldReference, $string, $this->originalText);

    //updates the oldReference variable
    $this->oldReference = $string;
  }

  /**
   * Get URL is a function to get the contents of the URL
   *
   * @param string $url The URL we are getting the contents of
   * @return string A string of the contents of the URL
   */
  protected function cURL($url) {
    //sets up cURL and sets the URL
    $ch = curl_init($url);
    //ignores poor certs
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //whether or not to output cURL 
    curl_setopt($ch, CURLOPT_VERBOSE, $this->verbose);
    //sets whether or not curl_exec will return
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //sets the user agent 
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0');
    //sets the encoding
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    //sets the headers to mimic an actual browser
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
      'Cache-Control: max-age=0',
      'Connection: keep-alive',
      'Keep-Alive: 300',
      'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
      'Accept-Language: en-US,en;q=0.5',
      'Accept-Encoding: gzip, deflate',
      'Pragma: '
    ]);
  
    //returns the contents of the page
    return curl_exec($ch);
  }

  /**
   * This function returns this reference
   *
   * @return object the results
   */
  protected function return() {
    //returns the results
    return (object) ["old" => $this->originalText, "new" => $this->newText];
  }

  /**
   * This function handles local references
   *
   * @return object the original text and new text to replace it with
   */
  protected function handle_local() {
    //gets the full original file
    preg_match("{^([^.]+)}", $this->originalFile, $m);

    //parses the reference
    preg_match("{((?:\.\.\/)*)(\/)?(.*)}", $this->oldReference, $matches);

    //if the tah is a img
    if ($this->tagType == "img") {
      //if the original path was a file path then, treat it that way
      if (strlen($m[1]) > strlen($this->filepath) && substr($m[1], -1) == "/" && $matches[1]) $this->filepath = $this->originalFile;
      //sets to move back one in the folders
      if (substr($m[1], -1) == "/") $one_less_back = true;
    }

    //splits on the slash
    $folders = explode("/", $this->filepath);
    //if the last element is empty, remove it
    if (end($folders) == "") array_pop($folders);

    //counts the number of backouts
    $backwards = substr_count($matches[1], "../");

    //if the current location is less then the backout number, error out
    if (($current = count($folders)) < $backwards) throw new \Exception("Attempting to back out ($backwards) folders while we are only ($current) folders deep, Failed.");
    //otherwise remove the folders to move out
    else if ($backwards > 0) $folders = array_splice($folders, 0, -$backwards);

    //if the file was reference is absolute
    if ($matches[2] == "/") $filepath = $this->path;
    //otherwise if the folder count is more then one add them to the path
    else if (count($folders) > 0) $filepath = $this->path . ($folders = implode("/", $folders)."/");
    //otherwise use the root folder
    else $filepath = $this->path . ($folders = "");

    //replaces the space in the url if there is one
    $url = str_replace(" ", "%20", (substr(($ref = $matches[3]), 0, 4) == "http" ? "" : $this->domain . "/") . ($matches[2] == "/" ? "" : $folders) . $ref);

    //parses the reference for the file and its path
    preg_match("{^([\S ]+\/)?([\w -]+(?:\.\w+)*)}", $ref, $reference_matches);

    //makes the folders from the reference
    if (!file_exists(($folder = $filepath . $reference_matches[1]))) mkdir($folder, 0777, true);

    //gets the file and save it
    file_put_contents($filepath . ($file_loc = $reference_matches[1] . $reference_matches[2]), $this->curl($url));

    //if one less backward ref is set, remove one
    if (isset($one_less_back)) $matches[1] = substr($matches[1], 3);

    //if the path was absolute, loop through backouts and add them
    if ($matches[2] == "/") if ($current > 0) foreach(range(1, $current) as $i) $matches[1] .= "../";

    //updates the reference
    $this->update_reference($matches[1] . $file_loc);

    //returns the structure to replace the text (if no new text, return the old)
    return (object) ["old" => $this->originalText, "new" => $this->newText];
  }

  /**
   * This function handles http references
   *
   * @return object the original text and new text to replace it with
   */
  protected function handle_HTTP() {
    //HANDLE HTTP

    //debug

    //returns the bypass
    return (object) ["old" => $this->originalText, "new" => $this->newText];
  }

  /**
   * This function downloads the reference files
   *
   * @return object the original text and new text to replace it with
   */
  function process() {
    //if theres a http reference without the http or https, add it back
    if (substr($this->oldReference, 0, 2) == "//" || substr($this->oldReference, 0, 3) == "://") $this->update_reference("http" . (substr($this->oldReference, 0, 2) == "//" ? ":" : "") . $this->oldReference);
    //otherwise set new to current
    else $this->update_reference($this->oldReference);
    
    //if this is a link, and rel="canonical" or rel="pingback" is there, or if an iframe bypass the results
    if (($this->tagType == "link" && (strpos($this->originalText,'rel="canonical"') !== false || strpos($this->originalText,'rel="pingback"') !== false || strpos($this->originalText,'rel="alternate"') !== false)) || $this->tagType == "iframe") return $this->return();

    //if the reference is a http reference
    if (substr($this->oldReference, 0, 4) == "http") {
      //handle http
      return $this->handle_HTTP();
    //otherwise
    } else {
      //handle local
      return $this->handle_local();
    }
  }
}
?>