<?php

namespace web;

/**
 * This class takes in a url or list of urls and archives them locally
 */
class Archiver {
  protected $urls, $folder, $verbose, $showFilePath, $timezone;

  /**
   * The constructor function is used to set up the whole class
   *
   * @param array $config Takes a list of params and sets up the library on them
   * @property array urls is used to set the list of urls (prefered over url)
   * @property string url is used to set the single url
   * @property string folder is used to set the folder of where the archiver will save to (Default: history/)
   * @property string timezone that is used in when stamping the archive (Default: UTC)
   * @property bool verbose is used to set verbose mode on or off (Default: off)
   * @property bool showFilePath is used to show the file path on or off (Default: off)
   * @property string file that is imported into the url list, put one url on a line
   */
  function __construct(array $config = []) {
    //if the config has urls in it, set them, if it has a url then cast it as array
    $this->urls = isset($config['urls']) ? (gettype($config['urls']) == "array" ? $config['urls'] : (gettype($config['urls']) == "string" ? [$config['urls']] : ($error = new \Exception("The param urls should be an array, it wasn't even a sting.. fix that.")))) : (isset($config['url']) ? (gettype($config['url']) == "string" ? [$config['url']] : (gettype($config['url']) == "array" ? $config['url'] : ($error = new \Exception("The param url should be a string, it wasn't even an array.. fix that.")))) : []);

    //if the config has a timezone set it, otherwise set the default
    $this->timezone = isset($config['timezone']) ? (in_array($config['timezone'], timezone_identifiers_list()) ? $config['timezone'] : ($error = new \Exception("The param timezone must be a timezone."))) : "UTC";

    //if the config has a folder set it, otherwise set the default
    $this->folder = isset($config['folder']) ? (gettype($config['folder'] == "string") ? $config['folder'] : ($error = new \Exception("The param folder must be a string."))) : "history/";

    //if verbose is toggled in the config, set it
    $this->verbose = isset($config['verbose']) ? filter_var($config['verbose'], FILTER_VALIDATE_BOOLEAN) : false;

    //if showFilePath is toggled in the config, set it
    $this->showFilePath = isset($config['showFilePath']) ? filter_var($config['showFilePath'], FILTER_VALIDATE_BOOLEAN) : false;

    //if there is a file set attempt to open it
    if (isset($config['file'])) {
      //checks that the file exists
      if (!file_exists($config['file'])) ($error = new \Exception("The file " . $config['file'] . " doesn't exist."));

      //since there is a file open it
      $f = fopen($config['file'],'rb');

      //loops through getting the urls, if theres a url on the link add it
      while (!feof($f)) if (preg_match("{(http\S+)}i", fgets($f), $m)) $this->add_urls($m[1]);

      //close the file
      fclose($f);
    }

    //checks to make sure the folder has a trailing slash
    if (substr($this->folder, -1) != "/" && substr($this->folder, -1) != "\\") $this->folder .= "/";

    //if there was an error throw it
    if (isset($error)) throw $error;
  }

  /**
   * Takes an array or just a single string of a url and sets the url
   * Sending no param results in resetting the urls to a blank array
   *
   * @param array|string $urls Accepts either a string or an array of strings to set as the urls (Default: [])
   * @return bool true on success, false on failure
   */
  function set_urls($urls = []) {
    //if the type of $urls in an array simply set it
    if (gettype($urls) == "array") $this->urls = $urls;
    //if the type of $urls is a string, set it to an array of that
    else if (gettype($urls) == "string") $this->urls = [$urls];
    //otherwise it failed
    else return false;
    //if it didn't fail it must have pased
    return true;
  }

  /**
   * Takes an array or just a single string of a url and adds the urls
   *
   * @param array|string $urls Accepts either a string or an array of strings to add to the current urls
   * @return bool true on success, false on failure
   */
  function add_urls($urls) {
    //if the type of $urls in an array, merge them
    if (gettype($urls) == "array") $this->urls = array_merge($this->urls, $urls);
    //if the type of $urls is a string simply add it in
    else if (gettype($urls) == "string") $this->urls[] = $urls;
    //otherwise it failed
    else return false;
    //if it didn't fail it must have pased
    return true;
  }

  /**
   * A function to get the list of urls
   *
   * @return array of urls
   */
  function get_urls() {
    //returns the urls
    return $this->urls;
  }

  /**
   * A function to get the folder name
   *
   * @return string of the folder
   */
  function get_folder() {
    //returns the folder
    return $this->folder;
  }

  /**
   * A function to get the timezone
   *
   * @return string of the timezone
   */
  function get_timezone() {
    //returns the timezone
    return $this->timezone;
  }

  /**
   * A function to get the verbose bool
   *
   * @return bool for verbose
   */
  function get_verbose() {
    //returns the verbose bool
    return $this->verbose;
  }

  /**
   * A function to get the showFilePath bool
   *
   * @return bool for showFilePath
   */
  function get_showFilePath() {
    //returns the showFilePath
    return $this->showFilePath;
  }
  
  /**
   * This function toggles verbose mode
   * You can either send a bool to set it to or leave it blank to set it to the opposite
   *
   * @param bool $bool what to set verbose to (Default: the opposite of the current verbose bool)
   * @return bool of the new value of verbose
   */
  function toggle_verbose($bool = null) {
    //if no bool was sent, set to the opposite
    if ($bool == null) $bool = !$this->verbose;
    //returns the new bool
    return $this->verbose = $bool;
  }
  
  /**
   * This function toggles showing the file path
   * You can either send a bool to set it to or leave it blank to set it to the opposite
   *
   * @param bool $bool what to set showFilePath to (Default: the opposite of the current showFilePath bool)
   * @return bool of the new value of showFilePath
   */
  function toggle_path($bool = null) {
    //if no bool was sent, set to the opposite
    if ($bool == null) $bool = !$this->showFilePath;
    //returns the new bool
    return $this->showFilePath = $bool;
  }

  /**
   * This function runs the process on the urls that are set
   *
   * @param array $params = [
   *   'type'   => (string) array|string Sets how you want the results to be
   *               returned. Array returns the array. String returns them in
   *               a readable manor.
   *   'method' => (string) console|web|text Sets how you want the output 
   *               of each link to be organized and returned. Default: text.
   *               Only for type "string". 
   *               console = "\n"
   *               web = "<br/>"
   *               text = " "
   * ]
   * @return mixed If a type is sent then a response will be returned. If not
   *               then true will be returned saving it processed the urls. If
   *               there are no urls to process, then return false.
   */
  function process(array $config = []) {
    //if there are no urls, return false
    if (empty($this->urls)) return false;

    //processes the urls
    $res = $this->process_urls();

    //if the type is set to array then return the orginal array
    if (@$config["type"] == "array") return $res;

    //if the type is set to string, return them as a string
    else if (@$config["type"] == "string") {
      //if the console method is selected set it to use \n
      if (@$config['method'] == "console") $spacer = "\n";
      //if the web method is selected set it to use <br/>
      else if (@$config['method'] == "web") $spacer = "<br/>";
      //otherwise defult to the space for text
      else $spacer = " ";

      //defines the string to return
      $str = "";

      //loops through the response to build the string
      foreach($res as $r) $str .= $this->process_response($r) . $spacer;

      //returns the string
      return $str;
    }
    //returns true
    return true;
  }

  /**
   * This function runs the process on the urls that are set and seperates
   * the results with \n for console and file outputting.
   * 
   * @return string A string of the results with \n seperating them
   */
  function process_console() {
    //sends the params to get a \n seperator, returns it
    return $this->process([
      "type" => "string",
      "method" => "console"
    ]);
  }

  /**
   * This function runs the process on the urls that are set and seperates
   * the results with <br/> for the web.
   * 
   * @return string A string of the results with <br/> seperating them
   */
  function process_web() {
    //sends the params to get a <br/> seperator, returns it
    return $this->process([
      "type" => "string",
      "method" => "web"
    ]);
  }

  /**
   * This function runs the process on the urls that are set and seperates
   * the results with a space for a string.
   * 
   * @return string A string of the results with a space seperating them
   */
  function process_text() {
    //sends the params to get a space seperator, returns it
    return $this->process([
      "type" => "string"
    ]);
  }

  /**
   * This function processes the urls through a loop getting the response from them
   *
   * @return array of strings with information about the urls
   */
  protected function process_urls() {
    //if there aren't any urls return an error
    if (empty($this->urls)) return ["error" => "No urls found."];
    //sets the array for holding the response
    $res = [];

    //loops through the urls array
    foreach ($this->urls as $url)
      //tries to archive
      try {
        //get the path and set pass to true
        if ($path = $this->archive_url($url)) $pass = true;
        //otherwise set pass to false
        else $pass = false;
        //sets the response
        $res[] = [
          "url" => $url,
          "pass" => $pass,
          "path" => $path
        ];
      //catch any Exceptions
      } catch (\Exception $e) {
        //adds the error
        $res[] = ["error" => $e->getMessage()];
      }

    //return the $res
    return $res;
  }

  /**
   * This function handles parsing the respomse to a string
   *
   * @param array $a An array of the response from the site
   * @return string A string with the parsed response 
   */
  protected function process_response($a) {
    //if there was an error return that
    if ($a["error"]) return "Error: " . $a["error"];

    //returns the string of the details
    return "URL: ".$a["url"]." Was: ".($a["pass"] ? "Succesful" : "Failed") . ($this->showFilePath ? " Path: " . $a["path"]: "");
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
   * This function saves the contents of a url to a file for archiving
   * 
   * @param string $url The URL of the file
   * @return bool Weither or not the file was saved
   */
  protected function archive_url($url) {
    //gets the URL contents
    $res = $this->cURL($url);

    //if there is no reponse then throw an Exception
    if (!$res) throw new \Exception("Failed to get the content of the page: $url.");

    //if the folder doesn't exist for the archive location, make it
    if (!file_exists($this->folder)) mkdir($this->folder);

    //matches out the domain and the page
    if (preg_match("{^(?:https?:\/\/)(?:www\.)?([^\/]+)\/?([^?]+)(\S*)$}i", $url, $matches)) {

      //gets the domain, replaces characters that aren't safe in filenames with '_'
      $site = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', "|"], "_", $matches[1]).'/';

      //if the folder doesn't exist for the site, make it
      if (!file_exists($this->folder . $site)) mkdir($this->folder . $site);

      //if the folder doesn't exist for the timestamp, make it
      if (!file_exists($this->folder . $site . ($time_folder = (new \DateTime("now", new \DateTimeZone($this->timezone)))->format('M_d_Y___H_i_s_v')."/"))) mkdir($this->folder . $site . $time_folder);

      //matches the file path from the site
      preg_match("{^(\S+\/)?(\w+)}i", $matches[2], $filepath_matches);

      //if the folder doesn't exist for the filepath, make it
      if (!file_exists($this->folder . $site . $time_folder . ($filepath = (isset($filepath_matches[1]) ? $filepath_matches[1] : "")))) mkdir($this->folder . $site . $time_folder . $filepath);

      //gets the page, replaces characters that aren't safe in filenames with '_', adds the current timestamp in UTC and then adds .html
      $file = (isset($filepath_matches[2]) ? str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', "|"], "_", $filepath_matches[2]) : "index") . ".html";

      //returns the file location or false
      if (file_put_contents($loc = ($this->folder . $site . $time_folder . $filepath . $file), $res)) return $loc;
      else return false;
    }
  }
}
?>