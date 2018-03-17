<?php

//require_once ("classes/CTemplate.inc.php");

class CICSDSearch
{

  //properties
  var $sql;
  var $params = array();
  var $resultCount = 0;
  var $results = array();
  var $columns;
  var $rowLimit = 5000;
  var $cacheLimit = 5;
  var $hash;

  //methods
  function CICSDSearch($newSearch, $columns = "default", $invalidateCache = false)
  {
    $this->params = $newSearch;

    if ($columns != "default")
      $this->columns = json_decode($columns, true);

    $this->hash = md5(serialize($this->params).serialize($this->columns));

    if ($invalidateCache)
    {
      $this->clearCache();
      $this->buildSQL();
      $this->search();
    }
    else if (!$this->checkSessionCache())
    {
      if ($this->buildSQL())
        $this->search();
    }
  }

  function checkSessionCache()
  {
    if (session_status() != PHP_SESSION_ACTIVE)
      return false;

    if (!isset($_SESSION["searchHash"]))
      return false;

    $key = array_search($this->hash, $_SESSION["searchHash"]);
    if ($key === false)
      return false;

    $this->results = $_SESSION["searchCache"][$key];
    $this->results[count($this->results) - 1]["cached"] = true;
    return true;
  }

  function cache()
  {
    if (session_status() != PHP_SESSION_ACTIVE)
      return;

    if (isset($_SESSION["searchLastIndex"]))
    {
      $index = $_SESSION["searchLastIndex"];
      $index++;
      if ($index == $this->cacheLimit)
        $index = 0;
    }
    else
      $index = 0;

    $_SESSION["searchCache"][$index] = $this->results;
    $_SESSION["searchHash"][$index] = $this->hash;
    $_SESSION["searchLastIndex"] = $index;
  }

  function clearCache()
  {
    if (session_status() != PHP_SESSION_ACTIVE)
      return;

    unset($_SESSION["searchCache"]);
    unset($_SESSION["searchHash"]);
    unset($_SESSION["searchLastIndex"]);
  }

  function searchNumberRange($param, $sqlField, $integer = false)
  {
    if ($integer)
    {
      if (preg_match("/\s*([<>]=?)\s*([0-9]+)\s*/", $param, $match))
        return "($sqlField $match[1] $match[2])";

        if (preg_match("/\s*([0-9]+)(?:[\s\-to]+([0-9]+)?)?\s*/", $param, $match))
        {
          if (isset($match[2]))
            return "($sqlField BETWEEN $match[1] AND $match[2])";
          else
            return "($sqlField = $match[1])";
        }

        return "";
    }

    if (preg_match("/\s*([<>]=?)\s*([0-9.]+)\s*/", $param, $match))
      return "($sqlField $match[1] $match[2])";

    if (preg_match("/\s*([0-9.]+)(?:[\s\-to]+([0-9.]+)?)?\s*/", $param, $match))
    {
      if (isset($match[2]))
        return "($sqlField BETWEEN $match[1] AND $match[2])";
      else
        return "($sqlField = $match[1])";
    }

    return "";
  }

  function buildSQL()
  {
    // add specific formating to columns
    if (isset($this->columns))
    {
      $sqlSELECT = "SELECT ";
      $utfArray = array("au_title", "authors_text");
      $first = true;
      foreach ($this->columns as $col)
      {
        if ($first)
          $first = false;
        else
          $sqlSELECT .= ",";

        if (in_array($col, $utfArray))
          $sqlSELECT .= " CONVERT(icsd.`$col` USING utf8) as $col";
        else
          $sqlSELECT .= " icsd.`$col`";
      }
    }
    else
      $sqlSELECT = "SELECT icsd.idnum, icsd.coll_code, icsd.mpy,
        CONVERT(icsd.au_title USING utf8) as au_title,
        CONVERT(icsd.authors_text using utf8) as authors_text,
        icsd.unit_cell_text, icsd.sum_form, icsd.struct_form, icsd.sgr";

    $sqlFROM = " FROM icsd";

    $sqlJOIN = "";

    $whereList = array();

    $sqlORDER = " ORDER BY icsd.coll_code ASC";
    $sqlLIMIT = "";//" LIMIT ".($this->rowLimit+1);

    // numbers or ranges
    //allowed examples: "1913", "2011-2012", "2013 to 2015", "< 1920", ">=2000"
    if (isset($this->params["year"])) // year
    {
      $range = $this->searchNumberRange($this->params["year"], "icsd.mpy", true);
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["elcount"])) // element count
    {
      $range = $this->searchNumberRange($this->params["elcount"], "icsd.el_count", true);
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["temperature"])) //temperature
    {
      $range = $this->searchNumberRange($this->params["temperature"], "icsd.temperature");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["pressure"])) //pressure
    {
      $range = $this->searchNumberRange($this->params["pressure"], "icsd.pressure");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["density"])) //calc. density
    {
      $range = $this->searchNumberRange($this->params["density"], "icsd.dens_calc");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["molmass"])) //molar mass
    {
      $range = $this->searchNumberRange($this->params["molmass"], "icsd.mol_mass");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["rval"])) //r value
    {
      $range = $this->searchNumberRange($this->params["rval"], "icsd.r_val");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["ca"])) // c/a ratio
    {
      $range = $this->searchNumberRange($this->params["ca"], "icsd.cdiva");
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["z"])) // Z (formula units in unit cell)
    {
      $range = $this->searchNumberRange($this->params["z"], "icsd.z", true);
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["csd"])) // Collection codes
    {
      $range = $this->searchNumberRange($this->params["csd"], "icsd.coll_code", true);
      if ($range != "")
        $whereList[] = $range;
    }

    if (isset($this->params["code_anx"])) //ANX structure code
    {
      if (preg_match('/\s*((?:[A-Za-z][0-9]*)+)\s*/', $this->params["code_anx"], $match))
        $whereList[] = "(icsd.anx_form = '$match[1]')";
    }

    if (isset($this->params["code_ab"])) //AB structure code
    {
      if (preg_match('/\s*((?:[A-Za-z][0-9]*)+)\s*/', $this->params["code_ab"], $match))
        $whereList[] = "(icsd.ab_form = '$match[1]')";
    }

    if (isset($this->params["code_pearson"])) // pearson code
    {
      if (preg_match('/\s*(?:((?:[aAmMoOtThHcC][PpAaBbCcIiFfRr][0-9]*)+))\s*/', $this->params["code_pearson"], $match))
        $whereList[] = "(icsd.pearson = '$match[1]')";
    }

    if (isset($this->params["code_wyck"])) //AB structure code
    {
      if (preg_match('/\s*((?:[a-zA-Z0-9\s]*)+[^\s])\s*/', $this->params["code_wyck"], $match))
        $whereList[] = "(icsd.wyck = '$match[1]')";
    }

    if (isset($this->params["pdf"])) // test flags
    {
      if (preg_match_all('/\s*([0-9]+-[0-9]+)\s*/', $this->params["pdf"], $match))
        $whereList[] = "((pdf_num = '".implode("') OR (pdf_num = '", $match[1])."'))";
    }

    if (isset($this->params["tests"])) // test flags
    {
      if (preg_match_all('/\s*([1-9][0-9])\s*/', $this->params["tests"], $match))
      {
        //flag list: 21, 22, 23, 51, 52, 53, 54, 55, 56, 60, 61, 62, 74, 75, 76
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, test_flag_code FROM icsd_tests WHERE"
                 ." test_flag_code IN (".implode(", ", $match[1]).") GROUP BY idnum"
                 .(count($match[0]) > 1 ? " HAVING COUNT(idnum) >= ".count($match[0]) : "")
                 .") tests ON icsd.idnum = tests.idnum";
        $whereList[] = "(test_flag_code IS NOT NULL)";
      }
    }

    // remarks:
    // ABC, ABIN, ADDM, AHT, APW, ATYP, CGD, CMB, COA, COR, CTO, DFT, DIS, EDP, EDS,
    // EMP, ESC, GEOM, HF, HYB, LCAO, MAG, MC, MD, MDIS, MFRM, MIN, MOD, MSD, MSO,
    // MSOF, MSQ, MTYP, NCD, NDP, NDS, NMR, ODS, OPT, PAW, PDC, PDF, PHF, POL, PRC,
    // PRD, PRE, PW, REF, RVP, SB, SDM, SEMP, SFP, SNP, SNS, SR, STP, TEM, TFA,
    // THE, TWI, TYP, XDP, XDS, ZCIF, ZCLA, ZDOI, ZMET, ZPRE, ZREM, ZTEM, ZTYP, ZTHE,
    // KEYW, LMTO
    if (isset($this->params["remarks"])) // test flags
    {
      if (preg_match_all('/\s*([A-Z]{2,4})\s*/', $this->params["remarks"], $match))
      {
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, std_rem_code FROM icsd_remarks WHERE"
                 ." std_rem_code IN (\"".implode("\", \"", $match[1])."\") GROUP BY idnum"
                 .(count($match[0]) > 1 ? " HAVING COUNT(idnum) >= ".count($match[0]) : "")
                 .") remarks ON icsd.idnum = remarks.idnum";
        $whereList[] = "(std_rem_code IS NOT NULL)";
      }
    }

    if (isset($this->params["min_dist"]))
    {
      if (preg_match_all("/([A-Za-z]{1,2})(?:[\s\-(?:to)]+)([A-Za-z]{1,2})\s*=\s*(?:([0-9.]+)(?:(?:[\s\-(?:to)]+)([0-9.]+)?)?)/", $this->params["min_dist"], $match))
      {
        $minDistList = array();
        for ($i = 0; $i < count($match[0]); $i++)
        {
          if ($match[1][$i] < $match[2][$i])
          {
            $el1 = $match[1][$i];
            $el2 = $match[2][$i];
          }
          else
          {
            $el1 = $match[2][$i];
            $el2 = $match[1][$i];
          }

          if ($match[4][$i] != "")
            $range = "(min_dist BETWEEN ".$match[3][$i]." AND ".$match[4][$i].")";
          else
            $range = "(min_dist <= ".$match[3][$i].")";

          $minDistList[] = "((atom1 = '$el1') AND (atom2 = '$el2') AND $range)";
        }
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, min_dist FROM minimal_distances WHERE "
                 .implode(" OR ", $minDistList)." GROUP BY idnum"
                 .(count($match[0]) > 1 ? " HAVING COUNT(idnum) >= ".count($match[0]) : "")
                 .") mindist ON icsd.idnum = mindist.idnum";
        $whereList[] = "(min_dist IS NOT NULL)";
      }
    }

    if (isset($this->params["ox"]))
    {
      if (preg_match_all("/([A-Za-z]{1,2})\s*=\s*(?:([\-\+]?[0-9.]+)(?:(?:\s*(?:\-|[tT][oO])\s*)([\-\+]?[0-9.]+)?)?)/", $this->params["ox"], $match))
      {
        for ($i = 0; $i < count($match[0]); $i++)
        {
          if ($match[3][$i] != "")
            $range = "(ox_state BETWEEN ".$match[2][$i]." AND ".$match[3][$i].")";
          else
            $range = "(ox_state = ".$match[2][$i].")";

          $sqlJOIN .= " LEFT JOIN (SELECT idnum, ox_state AS ox_state".$i." FROM p_record WHERE "
                   ."((el_symbol = '".$match[1][$i]."') AND $range) GROUP BY idnum"
                   .") ox".$i." ON icsd.idnum = ox".$i.".idnum";
          $whereList[] = "(ox_state".$i." IS NOT NULL)";
        }
      }
    }

    if (isset($this->params["journal"]))
    {
      //check for 6 letter CODEN
      if (preg_match("/\s*([A-Z]{6})\s*/", $this->params["journal"], $match))
      {
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, coden FROM reference WHERE coden = '"
                .$match[1]."' GROUP BY idnum) ref ON icsd.idnum = ref.idnum";
        $whereList[] = "(coden IS NOT NULL)";
      }
      else if (preg_match("/\s*([%A-Za-z0-9\s.,]+[^\s])\s*/", $this->params["journal"], $match))
      {
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, j_title FROM reference "
                ."LEFT JOIN coden ON coden.coden = reference.coden WHERE j_title LIKE '%"
                .$match[1]."%' GROUP BY idnum) ref ON icsd.idnum = ref.idnum";
        $whereList[] = "(j_title IS NOT NULL)";
      }
    }

    if (isset($this->params["chem"]))
    {
      if (preg_match("/\s*([%A-ZÄÖÜäöüßa-z0-9\(\)\[\]\-\s.,]+[^\s])\s*/", $this->params["chem"], $match))
        $whereList[] = "(chem_name LIKE '%$match[1]%')";
    }

    if (isset($this->params["title"]))
    {
      if (preg_match("/\s*([%A-ZÄÖÜäöüßa-z0-9\s.,]+[^\s])\s*/", $this->params["title"], $match))
        $whereList[] = "(au_title LIKE '%$match[1]%')";
    }

    if (isset($this->params["authors"]))
    {
      if (preg_match("/\s*([%A-ZÄÖÜäöüßa-z0-9\s.,]+[^\s])\s*/", $this->params["authors"], $match))
        $whereList[] = "(authors_text LIKE '%$match[1]%')";
    }

    if (isset($this->params["comments"]))
    {
      if (preg_match("/\s*([%A-ZÄÖÜäöüßa-z0-9\s.,]+[^\s])\s*/", $this->params["comments"], $match))
      {
        $sqlJOIN .= " LEFT JOIN (SELECT idnum, comments FROM comments WHERE comments LIKE '%"
                .$match[1]."%' GROUP BY idnum) comments ON icsd.idnum = comments.idnum";
        $whereList[] = "(comments IS NOT NULL)";
      }
    }

    if (isset($this->params["mineral"]))
    {
      if (preg_match("/\s*([\"\'])?([%A-ZÄÖÜäöüßa-z0-9\s.,]+[^\s\"\'])([\"\'])?\s*/", $this->params["mineral"], $match))
        if ($match[1] == "")
          $whereList[] = "((min_name LIKE '%$match[2]%') OR (add_name LIKE '%$match[2]%'))";
        else
          $whereList[] = "((min_name = '$match[2]') OR (add_name = '$match[2]'))";
    }

    if (isset($this->params["type"]))
    {
      if (preg_match("/\s*([\"\'])?([%A-Za-z0-9\(\)\-\s.,]*[^\s\"\'])([\"\'])?\s*/", $this->params["type"], $match))
        if ($match[1] == "")
          $whereList[] = "(struct_type LIKE '%$match[2]%')";
        else
          $whereList[] = "(struct_type = '$match[2]')";
    }

    $needSpaceGroupNumber = false;

    if (isset($this->params["laue"])) // Laue group
    {
      if (preg_match_all("/\s*([12346\/m-]+)\s*/", $this->params["laue"], $match))
      {
        $sqlSELECT .= ", space_group_number.laue";
        $orList = array();
        for ($i = 0; $i < count($match[1]); $i++)
          $orList[] = "(laue = '".$match[1][$i]."')";
        $whereList[] = "(".implode(" OR ", $orList).")";

        $needSpaceGroupNumber = true;
      }
    }

    if (isset($this->params["class"])) // crystal class
    {
      if (preg_match_all("/\s*([12346\/m-]+)\s*/", $this->params["class"], $match))
      {
        $sqlSELECT .= ", space_group_number.hm_not";
        $orList = array();
        for ($i = 0; $i < count($match[1]); $i++)
          $orList[] = "(hm_not = '".$match[1][$i]."')";
        $whereList[] = "(".implode(" OR ", $orList).")";

        $needSpaceGroupNumber = true;
      }
    }

    if (isset($this->params["pol_cent"])) // crystal class
    {
      if (preg_match_all("/\s*(acen|pol|cent)\s*/", strtolower($this->params["pol_cent"]), $match))
      {
        $sqlSELECT .= ", space_group_number.pol_cent";
        $orList = array();
        for ($i = 0; $i < count($match[1]); $i++)
        {
          switch ($match[1][$i])
          {
            case "acen": $orList[] = '(pol_cent = "-")'; break;
            case "pol":  $orList[] = '(pol_cent = "p")'; break;
            case "cent": $orList[] = '(pol_cent = "c")'; break;
            default: break;
          }
        }
        $whereList[] = "(".implode(" OR ", $orList).")";

        $needSpaceGroupNumber = true;
      }
    }

    if (isset($this->params["system"])) // crystal class
    {
      if (preg_match_all("/\s*(tric|mono|ortho|tetra|trig|hex|cubic)\s*/", strtolower($this->params["system"]), $match))
      {
        $sqlSELECT .= ", space_group_number.cryst_sys_code";
        $orList = array();
        for ($i = 0; $i < count($match[1]); $i++)
        {
          switch ($match[1][$i])
          {
            case "tric":  $orList[] = '(cryst_sys_code = "TC")'; break;
            case "mono":  $orList[] = '(cryst_sys_code = "MO")'; break;
            case "ortho": $orList[] = '(cryst_sys_code = "OR")'; break;
            case "tetra": $orList[] = '(cryst_sys_code = "TE")'; break;
            case "trig":  $orList[] = '(cryst_sys_code = "TG")'; break;
            case "hex":   $orList[] = '(cryst_sys_code = "HE")'; break;
            case "cubic": $orList[] = '(cryst_sys_code = "CU")'; break;
            default: break;
          }
        }
        $whereList[] = "(".implode(" OR ", $orList).")";

        $needSpaceGroupNumber = true;
      }
    }

    if (isset($this->params["sgr_num"])) // space group number (range)
    {
      $range = $this->searchNumberRange($this->params["sgr_num"], "space_group.sgr_num", true);
      if ($range != "")
      {
        if (!$needSpaceGroupNumber)
        {
          $sqlSELECT .= ", space_group.sgr_num";
          $sqlJOIN .= " JOIN space_group ON space_group.sgr = icsd.sgr";
        }
        $whereList[] = $range;
      }
    }

    // this needs to be placed after laue group, crystal class
    // polar/centric space groups and crystal system!
    if ($needSpaceGroupNumber)
    {
      $sqlSELECT .= ", space_group.sgr_num";
      $sqlJOIN .= " JOIN space_group ON space_group.sgr = icsd.sgr JOIN space_group_number ON space_group.sgr_num = space_group_number.sgr_num";
    }

    if (isset($this->params["sgr"]))
    {
      //space group in quotes => match exactly
      if (preg_match('/\s*[\'\"]([PABCFIR][12346mabcnd\-\/]+)[\'\"]\s*/', $this->params["sgr"], $match))
      {
        $whereList[] = "(icsd.sgr = '$match[1]')";
      }
      //space group as HM symbol or part of
      else if (preg_match("/\s*([PABCFIR])?([12346mabcnd\-\/]+)?\s*/", $this->params["sgr"], $match))
      {
        if (count($match) >= 2)
        {
          if ($match[1] != "") // bravais type specified
            $whereList[] = "(icsd.sgr LIKE '$match[0]%')";
          else
            $whereList[] = "(icsd.sgr LIKE '%$match[0].%')";
        }
      }
    }

    if (isset($this->params["unit_cell"]))
    {
      $this->params["unit_cell"] = strtolower($this->params["unit_cell"]);
      //check if lattice constants are labelled e.g. a=3.4 alpha=98-99
      if ((preg_match("/\s*(?:a=([0-9.-]+))?(?:\s*b=([0-9.-]+))?(?:\s*c=([0-9.-]+))?(?:\s*alpha=([0-9.-]+))?(?:\s*beta=([0-9.-]+))?(?:\s*gamma=([0-9.-]+))?(?:\s*volume=([0-9.-]+))?s*/", $this->params["unit_cell"], $match)) && (count($match) > 1))
      {
        for ($i = 1; $i < count($match); $i++)
        {
          if ($match[$i] == "")
            continue;

          if (strpos($match[$i], '-') === false) // no range
          {
            $num = floatval($match[$i]);

            if (($i <= 3) || ($i >= 7))
            {
              $low = round($num * 0.97, 2);
              $high = round($num * 1.03, 2);
            }
            else
            {
              if (($num == 90) || ($num == 120))
              {
                $angle = "= $num)";
              }
              else
              {
                $low = round($num * 0.985, 2);
                $high = round($num * 1.015, 2);
                $angle = "BETWEEN $low AND $high)";
              }
            }
          }
          else
          {
            list($low, $high) = explode('-', $match[$i]);
            $low = round(floatval($low), 2);
            $high = round(floatval($high), 2);
          }

          switch ($i)
          {
            case 1: $whereList[] = "(icsd.a_len BETWEEN $low AND $high)"; break;
            case 2: $whereList[] = "(icsd.b_len BETWEEN $low AND $high)"; break;
            case 3: $whereList[] = "(icsd.c_len BETWEEN $low AND $high)"; break;
            case 4: $whereList[] = "(icsd.alpha ".$angle; break;
            case 5: $whereList[] = "(icsd.beta ".$angle; break;
            case 6: $whereList[] = "(icsd.gamma ".$angle; break;
            case 7: $whereList[] = "(icsd.c_vol BETWEEN $low AND $high)"; break;
            default: break;
          }
        }
      }

      //check for either 3 (a,b,c), 4 (+beta) or 6 numbers (+alpha,+gamma)
      //all numbers can be ranges - without spaces: e.g. 2.03-2.10
      //if no range is supplied 6% (+- 3%) for axes and 3% for angles will be used
      else if (preg_match("/\s*([0-9.]+(?:-[0-9.]+)?)\s+([0-9.]+(?:-[0-9.]+)?)\s+([0-9.]+(?:-[0-9.]+)?)(?:\s+([0-9.]+(?:-[0-9.]+)?))?(?:\s+([0-9.]+(?:-[0-9.]+)?)\s+([0-9.]+(?:-[0-9.]+)?))?\s*/", $this->params["unit_cell"], $match))
      {
        if (count($match) >= 4)
        {
          for ($i = 1; $i < count($match); $i++)
          {
            if (strpos($match[$i], '-') === false) // no range
            {
              $num = floatval($match[$i]);

              if ($i <= 3)
              {
                $low = round($num * 0.97, 2);
                $high = round($num * 1.03, 2);
              }
              else
              {
                if (($num == 90) || ($num == 120))
                {
                  $angle = "= $num)";
                }
                else
                {
                  $low = round($num * 0.985, 2);
                  $high = round($num * 1.015, 2);
                  $angle = "BETWEEN $low AND $high)";
                }
              }
            }
            else
            {
              list($low, $high) = explode('-', $match[$i]);
              $low = round(floatval($low), 2);
              $high = round(floatval($high), 2);
            }

            switch ($i)
            {
              case 1: $whereList[] = "(icsd.a_len BETWEEN $low AND $high)"; break;
              case 2: $whereList[] = "(icsd.b_len BETWEEN $low AND $high)"; break;
              case 3: $whereList[] = "(icsd.c_len BETWEEN $low AND $high)"; break;
              case 4: if (count($match) == 5)
                        $whereList[] = "(icsd.beta BETWEEN $low AND $high) and (icsd.alpha = 90) and (icsd.gamma = 90)";
                      else
                        $whereList[] = "(icsd.alpha ".$angle;
                      break;
              case 5: $whereList[] = "(icsd.beta ".$angle; break;
              case 6: $whereList[] = "(icsd.gamma ".$angle; break;
              default: break;
            }
          }
        }
      }
    }

    if (isset($this->params["composition"]))
    {
      $comp = $this->params["composition"];
      //find quoted fragments and match them against the structured formula
      //the remaining string is handled further
      if (preg_match_all("/.*([\'\"]([^\'\";]*)[\'\"]).*/", $comp, $match))
      {
        //delete the matched groups
        $comp = str_replace($match[1], "", $comp);

        for ($i = 0; $i < count($match[2]); $i++)
          $whereList[] = '(icsd.struct_form LIKE "%'.$match[2][$i].'%")';
      }

      //will be set to false if element group or OR-group is found
      $exclusivePossible = true;

      //find elements in brackets
      // example: Au (Cl or F) or Au (HAL3 or F12)
      if (preg_match_all("/\(([^\)\'\"]*)\)/", $comp, $match))
      {
        $exclusivePossible = false;

        //delete the matched groups
        $comp = str_replace($match[0], "", $comp);

        for ($i = 0; $i < count($match[1]); $i++)
        {
          if (preg_match_all("/\s*(?:([A-Za-z]{1,3})([0-9]*)(?:\s+[oO][rR])?)+\s*/", $match[1][$i], $orGroup))
          {
            $orList = array();
            for ($j = 0; $j < count($orGroup[1]); $j++)
            {
              if ($grp = $this->elementGroupToList($orGroup[1][$j]))
                $elQuery = "(el_symbol IN ('".implode("', '", $grp)."'))";
                else
                $elQuery = "(el_symbol = '".$orGroup[1][$j]."')";

              if ($orGroup[2][$j] == "")
                $orList[] = $elQuery;
              else
                $orList[] = "(".$elQuery." AND (el_subscript = '".$orGroup[2][$j]."'))";
            }
            $sqlJOIN .= " LEFT JOIN (SELECT idnum, el_symbol as el_or".$i." FROM element WHERE "
                     .implode(" OR ", $orList)." GROUP BY idnum"
                     .") el_orTab".$i." ON icsd.idnum = el_orTab".$i.".idnum";
            $whereList[] = "(el_or".$i." IS NOT NULL)";
          }
        }
      }

      //match strings like Au Cl3 or Au HAL3
      if (preg_match_all("/\s*([A-Za-z]{1,3}[0-9.]*)+\s*/",$comp, $match))
      {
        $elementCount = 0;
        $orList = array();
        for ($i = 0; $i < count($match[1]); $i++)
        {
          if (preg_match("/([A-Za-z]+)([0-9.]+)*/", $match[1][$i], $el))
          {
            if ($grp = $this->elementGroupToList($el[1]))
            {
              $exclusivePossible = false;

              $groupQuery = "(el_symbol IN ('".implode("', '", $grp)."'))";

              if (count($el) > 2)
                $groupQuery = "($groupQuery AND (el_subscript = '$el[2]'))";

              $sqlJOIN .= " LEFT JOIN (SELECT DISTINCT idnum, el_symbol as el_group".$el[1].$i
                       ." FROM element WHERE ".$groupQuery.") el_".$el[1].$i."GroupTab ON icsd.idnum = el_".$el[1].$i."GroupTab.idnum";
              $whereList[] = "(el_group".$el[1].$i." IS NOT NULL)";
            }
            else
            {
              $elementCount++;
              if (count($el) <= 2)
                $orList[] = "(el_symbol = '$el[1]')";
              else
                $orList[] = "((el_symbol = '$el[1]') AND (el_subscript = '$el[2]'))";
            }
          }
        }
        if ($elementCount)
        {
          $sqlJOIN .= " LEFT JOIN (SELECT idnum, COUNT(idnum) AS comp_count FROM element WHERE "
                  .implode(" OR ", $orList)." GROUP BY idnum"
                  .($elementCount > 1 ? " HAVING comp_count >= ".$elementCount : "")
                  .") el_tab ON icsd.idnum = el_tab.idnum";
          if ($exclusivePossible && isset($this->params["exclude"]) && ($this->params["exclude"] == "ALL"))
            $whereList[] = "(comp_count = el_count)";
          else
            $whereList[] = "(comp_count IS NOT NULL)";
        }
      }
    }

    //switch this to LEFT JOIN element table ?
    //if exclude == "ALL" composition search is exclusive if no groups are used
    if (isset($this->params["exclude"]))
    {
      //match list of elements or groups
      if (($this->params["exclude"] != "ALL")
        && (preg_match_all("/\b[A-Za-z]{1,3}\b/", $this->params["exclude"], $match)))
      {
        $orList = array();
        $grp = array();
        $tmpEl = "";
        for ($i = 0; $i < count($match[0]); $i++)
        {
          if ($grp = $this->elementGroupToList($match[0][$i]))
            $orList = array_merge($orList, $grp);
          else
            $orList[] = $match[0][$i];
        }
        $whereList[] = '(icsd.sum_form NOT REGEXP "(^|[^[:alpha:]])('.implode('|', $orList).')[0-9]")';
      }
    }

    if (count($whereList))
    {
      $this->sql = $sqlSELECT.$sqlFROM.$sqlJOIN." WHERE ".implode(" AND ", $whereList).$sqlORDER.$sqlLIMIT.";";
      return true;
    }

    $this->results[] = array("fetchTime" => 0.0,
                             "cached" => false,
                             "hash" => $this->hash,
                             "sql" => "",
                             "rows" => 0,
                             "success" => false,
                             "error" => "No (valid) search criteria were supplied!",
                             "limit" => $this->rowLimit);

    return false;
  }

  function search()
  {
    global $mysqli;

    $startTime = microtime(true);

    if ($result = $mysqli->query($this->sql))
    {
      $this->execTime = microtime(true) - $startTime;
      $this->resultCount = $result->num_rows;

      if ($this->resultCount < $this->rowLimit)
      {
        while ($row = $result->fetch_assoc())
          $this->results[] = array_change_key_case($row);

        $this->execTime = microtime(true) - $startTime;

        $this->results[] = array("fetchTime" => $this->execTime,
                                 "cached" => false,
                                 "hash" => $this->hash,
                                 "sql" => $this->sql,
                                 "rows" => $this->resultCount,
                                 "success" => ($this->resultCount > 0 ? true : false),
                                 "error" => ($this->resultCount == 0 ? "No matching entries found. :-(" : ""),
                                 "limit" => $this->rowLimit);

        $this->cache();
      }
      else
      {
        $this->execTime = microtime(true) - $startTime;
        $this->results[] = array("fetchTime" => $this->execTime,
                                 "cached" => false,
                                 "hash" => $this->hash,
                                 "sql" => $this->sql,
                                 "rows" => $this->resultCount,
                                 "success" => false,
                                 "error" => "Row limit exceeded!",
                                 "limit" => $this->rowLimit);
      }
    }
    else
    {
      $this->execTime = microtime(true) - $startTime;
      $this->results[] = array("fetchTime" => $this->execTime,
                               "cached" => false,
                               "hash" => $this->hash,
                               "sql" => $this->sql,
                               "rows" => $this->resultCount,
                               "success" => false,
                               "error" => $mysqli->error,
                               "limit" => $this->rowLimit);
    }
  }

  // function displayResults()
  // {
  //   $tmpl = new CTemplate("searchList", array("results" => $this->results));
  //   $tmpl->printTemplate();
  // }

  function elementGroupToList($el)
  {
    switch ($el)
    {
      //main group elements
      case "LIG":
      case "ALK": return array("Li", "Na", "K", "Rb", "Cs", "Fr");
      case "BEG":
      case "ALE": return array("Be", "Mg", "Ca", "Sr", "Ba", "Ra");
      case "BG":  return array("B", "Al", "Ga", "In", "Tl", "Nh");
      case "CG":
      case "TET": return array("C", "Si", "Ge", "Sn", "Pb", "Fl");
      case "NG":
      case "PNC": return array("N", "P", "As", "Sb", "Bi", "Mc");
      case "OG":
      case "CHA": return array("O", "S", "Se", "Te", "Po", "Lv");
      case "FG":
      case "HAL": return array("F", "Cl", "Br", "I", "At", "Ts");
      case "NGS":
      case "HEG": return array("He", "Ne", "Ar", "Kr", "Xe", "Rn", "Og");

      //transition metal groups
      case "SCG": return array("Sc", "Y", "La", "Ac");
      case "TIG": return array("Ti", "Zr", "Hf", "Rf");
      case "VG":  return array("V", "Nb", "Ta", "Ha");
      case "CRG": return array("Cr", "Mo", "W", "Sg");
      case "MNG": return array("Mn", "Tc", "Re", "Bh");
      case "FEG": return array("Fe", "Ru", "Os", "Hs");
      case "COG": return array("Co", "Rh", "Ir", "Mt");
      case "NIG": return array("Ni", "Pd", "Pt", "Ds");
      case "CUG": return array("Cu", "Ag", "Au", "Rg");
      case "ZNG": return array("Zn", "Cd", "Hg", "Cn");

      //period
      case "2PE": return array("Li", "Be", "B", "C", "N", "O", "F", "Ne");
      case "3PE": return array("Na", "Mg", "Al", "Si", "P", "S", "Cl", "Ar");
      case "4PE": return array("K", "Ca", "Sc", "Ti", "V", "Cr", "Mn", "Fe", "Co",
                               "Ni", "Cu", "Zn", "Ga", "Ge", "As", "Se", "Br", "Kr");
      case "5PE": return array("Rb", "Sr", "Y", "Zr", "Nb", "Mo", "Tc", "Ru", "Rh",
                               "Pd", "Ag", "Cd", "In", "Sn", "Sb", "Te", "I", "Xe");
      case "6PE": return array("Cs", "Ba", "Hf", "Ta", "W", "Re", "Os", "Ir", "Pt",
                               "Au", "Hg", "Tl", "Pb", "Bi", "Po", "At", "Rn");
      case "7PE": return array("Fr", "Ra", "Ac", "Rf", "Ha", "Sg", "Bh", "Hs", "Mt",
                               "Ds", "Rg", "Cn", "Nh", "Fl", "Mc", "Lv", "Ts", "Og");

      //special groups
      case "LAN": return array("La", "Ce", "Pr", "Nd", "Pm", "Sm", "Eu", "Gd",
                               "Tb", "Dy", "Ho", "Er", "Tm", "Yb", "Lu");
      case "ACT": return array("Ac", "Th", "Pa", "U", "Np", "Pu", "Am", "Cm",
                               "Bk", "Cf", "Es", "Fm", "Md", "No", "Lr");
      case "PTM": return array("Ru", "Rh", "Pd", "Os", "Ir", "Pt");
      case "TRM": return array("Sc", "Ti", "V", "Cr", "Mn", "Fe", "Co", "Ni", "Cu",
                               "Zn", "Y", "Zr", "Nb", "Mo", "Tc", "Ru", "Rh", "Pd",
                               "Ag", "Cd", "La", "Hf", "Ta", "W", "Re", "Os", "Ir",
                               "Pt", "Au", "Hg", "Ac");
      case "NOM": return array("H", "D", "B", "C", "Si", "N", "P", "AS", "O", "S",
                               "Se", "Te", "F", "Cl", "Br", "I", "At", "He", "Ne",
                               "Ar", "Kr", "Xe", "Rn");
      case "MET": return array("Li", "Be", "Na", "Mg", "Al", "K", "Ca", "Sc", "Ti",
                               "V", "Cr", "Mn", "Fe", "Co", "Ni", "Cu", "Zn", "Ga",
                               "Ge", "Rb", "Sr", "Y", "Zr", "Nb", "Mo", "Tc", "Ru",
                               "Rh", "Pd", "Ag", "Cd", "In", "Sn", "Sb", "Cs", "Ba",
                               "La", "Ce", "Pr", "Nd", "Pm", "Sm", "Eu", "Gd", "Tb",
                               "Dy", "Ho", "Er", "Tm", "Yb", "Lu", "Hf", "Ta", "W",
                               "Re", "Os", "Ir", "Pt", "Au", "Hg", "Tl", "Pb", "Bi",
                               "Po", "Fr", "Ra", "Ac", "Th", "Pa", "U", "Np", "Pu",
                               "Am", "Cm", "Bk", "Cf", "Es", "Fm", "Md", "No", "Lr");
      default: return array();
    }
  }

}
