<?

class CTemplate
{
  //properties
  var $prefix;
  var $suffix;
  var $arrayTag;
  var $arrayCount;
  var $source = array();
  var $callback;
  var $tmplDir;
  var $file;
  var $variables = array();

  //methods
  function CTemplate($templateName, $contents = array(), $callback = NULL,
      $templateDir = "tmpl/", $prefix = "<#", $suffix = "\/>")
  {
    $this->prefix = $prefix;
    $this->suffix = $suffix;
    $this->tmplDir = $templateDir;
    $this->file = $templateName.".tmpl";
    $this->callback = $callback;

    $this->variables = $contents;
    $this->source = file($this->tmplDir.$this->file);

    $this->fillTemplate();
  }

  function checkSkip($field, $match = NULL, $loop = false)
  {
    //if field isn't even set => skip
    if (!$loop)
    {
      if (!isset($this->variables[$field]))
        return true;
      $val = $this->variables[$field];
    }
    else
    {
      if (!isset($this->variables[$this->arrayTag][$this->arrayCount][$field]))
        return true;
      $val = $this->variables[$this->arrayTag][$this->arrayCount][$field];
    }

    // $match not supplied or not with even data just check whether the field var is "false"
    if ((($match === NULL) || (count($match) < 4)) && (!$val))
      return true;

    //not enough data for anything else => no skip
    if (count($match) < 4)
      return false;

    //print "<pre>"; print_r($match); print $val; print "</pre>";

    if ($match[2] == "=")
    {
      if ("$val" == $match[3])
        return true;
    }
    elseif ($match[2] == "!=")
    {
      if ("$val" != $match[3])
        return true;
    }

    return false;
  }

  function fillTemplate()
  {
    for ($i = 0; $i < count($this->source); $i++)
    {
      //check for skip tag
      if (preg_match("/".$this->prefix."skip\:([A-Za-z0-9_\-\.\:\/]+)(!?=)?([A-Za-z0-9_\-]+)?$this->suffix/", $this->source[$i], $match))
      {
        //print "<pre>"; print_r($match); print "</pre>";
        if ($this->checkSkip($match[1], (count($match) > 3 ? $match : NULL)))
        {
          $this->source[$i] = "";
          continue;
        }
      }

      //check for arrayTag and capture array variable as $match[1]
      if (preg_match("/".$this->prefix."loop\:([A-Za-z0-9_\-\.\:\/]+)$this->suffix/", $this->source[$i], $match))
      {
        $this->arrayTag = $match[1];

        //find loop end
        $loopLength = 0;
        for ($j = 1; $j+$i < count($this->source); $j++)
        {
          if (preg_match("/".$this->prefix."loop$this->suffix/", $this->source[$j+$i]))
          {
            $loopLength = $j-1;
            break;
          }
        }

        //no end marker found skip loop instruction
        if ($loopLength <= 0)
          continue;

        //check if array is set
        if (!isset($this->variables[$this->arrayTag]))
        {
          for ($j = 0; $j <= $loopLength; $j++)
          {
            $this->source[$i+$j] = "";
          }
          continue;
        }

        //loop over array
        $str = "";
        for ($this->arrayCount = 0; $this->arrayCount < count($this->variables[$this->arrayTag]); $this->arrayCount++)
        {
          for ($j = 0; $j < $loopLength; $j++)
          {
            //check for skip again...
            $skip = false;
            if (preg_match("/".$this->prefix."skip\:([A-Za-z0-9_\-\.\:\/]+)(!?=)?([A-Za-z0-9_\-]+)?$this->suffix/", $this->source[$i+$j+1], $match))
            {
              //print "<pre>"; print_r($match); print "</pre>";
              if (substr($match[1], 0, 1) === ":")
              {
                $skip = $this->checkSkip(substr($match[1], 1), (count($match) > 3 ? $match : NULL));
              }
              else
              {
                if (!isset($this->variables[$this->arrayTag][$this->arrayCount][$match[1]]))
                  $skip = true;
                else
                {
                  $skip = $this->checkSkip($match[1], (count($match) > 3 ? $match : NULL), true);
                }
              }
            }

            if ($skip)
              continue;

            $str .= preg_replace_callback("/$this->prefix([A-Za-z0-9_\-\.\:\/\=!]+)$this->suffix/",
                                      array($this, "replaceLoopText"), $this->source[$i+$j+1]);
          }
        }

        $this->source[$i] = $str;

        for ($j = 0; $j < $loopLength; $j++)
        {
          $this->source[$i+$j+1] = "";
        }
      }
      else
      {
        $this->source[$i] = preg_replace_callback("/$this->prefix([A-Za-z0-9_\-\.\:\/\=!]+)$this->suffix/",
                                    array($this, "replaceText"), $this->source[$i]);
      }
    }
  }

  function replaceText($match)
  {
    if ($this->callback !== NULL)
      if (strpos($match[1], "tmpl_") === 0) // special command found
      {
        if (method_exists($this->callback, $match[1]))
          return $this->callback->$match[1]();
        else
          return "";
      }

    return isset($this->variables[$match[1]]) ? $this->variables[$match[1]] : "";
  }

  function replaceLoopText($match)
  {
    if (substr($match[1], 0, 1) === ":")
      return isset($this->variables[substr($match[1], 1)]) ? $this->variables[substr($match[1], 1)] : "";
    else
      return isset($this->variables[$this->arrayTag][$this->arrayCount][$match[1]])
        ? $this->variables[$this->arrayTag][$this->arrayCount][$match[1]] : "";
  }

  function printTemplate()
  {
    foreach($this->source as $line)
      print $line;
  }

  function text()
  {
    return (implode("", $this->source));
  }
}
