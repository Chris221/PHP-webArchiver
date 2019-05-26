<?php

namespace web;

/**
 * This class saves the references from html
 */
class Reference {
  protected $tagType, $orginalText, $oldReference, $newText;

  function __construct(array $config = []) {
    //sets the tagType if set
    if (isset($config["tagType"])) $this->tagType = $config["tagType"];
    //otherwise error out
    else throw new \Exception("There must be a tag sent.");

    //sets the orginalText if set
    if (isset($config["orginalText"])) $this->orginalText = $config["orginalText"];
    //otherwise error out
    else throw new \Exception("There must be an orginal text sent.");

    //sets the reference if set
    if (isset($config["reference"])) $this->oldReference = $config["reference"];
    //otherwise error out
    else throw new \Exception("There must be a reference sent.");
  }

  function process() {



    

    //returns the structure to replace the text (if no new text, return the old)
    return (object) ["old" => $this->orginalText, "new" => isset($this->newText) ? $this->newText : $this->orginalText];
  }
}
?>