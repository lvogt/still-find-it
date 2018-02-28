<?

require_once("classes/CTemplate.inc.php");

class CFullEntry
{
  //properties
  var $id; //this is the internal ID not the CC code!
  var $ccode = 0;
  var $retrieved = false;
  var $entry = array();
  var $cacheLimit = 10;
  var $cif = "";

  //methods
  function CFullEntry($id, $type = "idnum")
  {
    if (!is_numeric($id) || $id < 1)
      return;

    if ($type == "idnum")
      $this->id = $id;
    else
      $this->ccode = $id;

    if (!$this->checkSessionCache($type === "idnum"))
      $this->fetchData();

    $this->retrieved = (array_key_exists("idnum", $this->entry) && ($this->entry["idnum"] == $this->id));
  }

  function checkSessionCache($useID)
  {
    if (session_status() != PHP_SESSION_ACTIVE)
      return false;

    if (!isset($_SESSION["entryCache"]))
      return false;

    if ($useID)
    {
      if (isset($_SESSION["entryCache"][$this->id.'']))
      {
        $this->entry = $_SESSION["entryCache"][$this->id.''];
        $this->ccode = $this->entry["coll_code"];
        $this->entry["cached"] = true;
        return true;
      }
    }
    else
    {
      foreach (array_keys($_SESSION["entryCache"]) as $key)
      {
        if ($_SESSION["entryCache"][$key]["coll_code"] == $this->ccode)
        {
          $this->entry = $_SESSION["entryCache"][$key];
          $this->id = $this->entry["idnum"];
          $this->entry["cached"] = true;
          return true;
        }
      }
    }

    return false;
  }

  function cache()
  {
    if (session_status() != PHP_SESSION_ACTIVE)
      return;

    $_SESSION["entryCache"][$this->id.''] = $this->entry;

    if (count($_SESSION["entryCache"]) > $this->cacheLimit)
    {
      reset($_SESSION["entryCache"]);
      unset($_SESSION["entryCache"][key($_SESSION["entryCache"])]);
    }
  }

  function fetchData()
  {
    // prepare first SQL statements; icsd.idnum is needed for other statements
    // replace icsd.* with actual columns
    $sqlCSD = "SELECT icsd.*, CONVERT(icsd.au_title USING utf8) as au_title,
      CONVERT(icsd.authors_text USING utf8) as authors_text,
      ROUND(icsd.mol_mass, 4) as mol_mass,
      IF(ROUND(icsd.z, 0) = icsd.z, ROUND(icsd.z, 0), ROUND(icsd.z, 2)) as z,
      ROUND(icsd.dens, 2) as dens, ROUND(icsd.dens_calc, 2) as dens_calc,
      ROUND(icsd.temperature, 0) as temperature, icsd.pressure * 10 as pressure,
      ROUND(icsd.r_val, 4) as r_val,
      DATE_FORMAT(icsd.rec_date, '%Y/%m/%d') as rec_date,
      DATE_FORMAT(icsd.mod_date, '%Y/%m/%d') as mod_date,
      space_group.sgr_num, space_group.sgr_disp, NOT space_group.ctl_lazy as sgr_nonstd,
      space_group.smat_genrpos, space_group_number.cryst_sys_code, crystal_system.cryst_sys,
      standardization_method.text as std_text FROM icsd
      JOIN space_group ON icsd.sgr = space_group.sgr
      JOIN standardization_method ON icsd.standardization_tag = standardization_method.tag
      JOIN space_group_number ON space_group.sgr_num = space_group_number.sgr_num
      JOIN crystal_system ON crystal_system.cryst_sys_code = space_group_number.cryst_sys_code
      WHERE ".(isset($this->id) ? "icsd.idnum = $this->id;" : "icsd.coll_code = $this->ccode");

    global $mysqli;

    if ($result = $mysqli->query($sqlCSD))
    {
      // only a single row is expected here...
      if ($result->num_rows !== 1)
        return false;

      $this->entry = array_change_key_case($result->fetch_assoc(), CASE_LOWER);
      $this->id = $this->entry["idnum"];
      $this->ccode = $this->entry["coll_code"];
    }
    else
      return false;

    // remaining SQL statements
    //$sqlPOS = "SELECT p_record.* FROM p_record WHERE p_record.idnum = $this->id ORDER BY p_seq;";
    $sqlPOS = "SELECT p_seq, el_symbol, el_label, ox_state, ox_text, w_mult, w_lett,
      x_text, y_text, z_text, atf_code, sof_text, IFNULL(sof_text, '1.') as sof_text,
      IFNULL(itf_text, '0') as itf_text, IFNULL(tf11_text, '0') as tf11_text,
      IFNULL(tf22_text, '0') as tf22_text, IFNULL(tf33_text, '0') as tf33_text,
      IFNULL(tf12_text, '0') as tf12_text, IFNULL(tf13_text, '0') as tf13_text,
      IFNULL(tf23_text, '0') as tf23_text, IFNULL(SUBSTR(els_h, 2), '0') as els_h, h_d,
      IF(ox_state<0, CONCAT(el_symbol, SUBSTR(OX_TEXT, 2), '-'), CONCAT(el_symbol, OX_TEXT, '+')) as cif_symbol,
      x_val, y_val, z_val
      FROM p_record WHERE idnum = $this->id ORDER BY p_seq;";
    $sqlREF = "SELECT reference.doi, reference.year, reference.volume, reference.page_first,
      reference.page_last, reference.ref_seq, reference.issue, reference.coden,
      coden.j_title, coden.issn FROM reference
      JOIN coden ON reference.coden = coden.coden WHERE reference.idnum = $this->id ORDER BY ref_seq;";
    $sqlREM = "SELECT icsd_remarks.std_rem_code, icsd_remarks.add_rem, standard_remarks.std_remark
      FROM icsd_remarks JOIN standard_remarks
      ON icsd_remarks.std_rem_code = standard_remarks.std_rem_code WHERE icsd_remarks.idnum = $this->id;";
    $sqlCOM = "SELECT comments.com_seq, comments.comments FROM comments
      WHERE comments.idnum = $this->id ORDER BY com_seq;";
    $sqlTST = "SELECT icsd_tests.test_flag_code, test_flags.desc_new FROM icsd_tests
      JOIN test_flags ON icsd_tests.test_flag_code = test_flags.test_flag_code
      WHERE icsd_tests.idnum = $this->id ORDER BY test_flag_code;";
    //$sqlVER = "SELECT RELEASE_TAG, NUMBER_OF_ALL_RECORDS FROM `icsd_database_information` ORDER BY RELEASE_YEAR DESC, RELEASE_VERSION DESC LIMIT 1";

    if ($result = $mysqli->query($sqlPOS))
    {
      while ($row = $result->fetch_assoc())
        $this->entry["atom_list"][] = array_change_key_case($row);
    }
    else
      return false;

    if ($result = $mysqli->query($sqlREF))
    {
      while ($row = $result->fetch_assoc())
        $this->entry["references"][] = array_change_key_case($row);
    }
    else
      return false;

    if ($result = $mysqli->query($sqlCOM))
    {
      while ($row = $result->fetch_assoc())
        $this->entry["comments"][] = array_change_key_case($row);
    }
    else
      return false;

    if ($result = $mysqli->query($sqlREM))
    {
      while ($row = $result->fetch_assoc())
        $this->entry["remarks"][] = array_change_key_case($row);
    }
    else
      return false;

    if ($result = $mysqli->query($sqlTST))
    {
      while ($row = $result->fetch_assoc())
        $this->entry["tests"][] = array_change_key_case($row);
    }
    else
      return false;

    //fine tuning
    $atf = "";
    $itf = "";
    $elsH = "";
    $sym = array();

    for ($i = 0; $i < count($this->entry["atom_list"]); $i++)
    {
      $atf = isset($this->entry["atom_list"][$i]["atf_code"]) ? $this->entry["atom_list"][$i]["atf_code"] : $atf;
      $itf = isset($this->entry["atom_list"][$i]["itf_code"]) ? $this->entry["atom_list"][$i]["itf_code"] : $itf;
      $elsH = isset($this->entry["atom_list"][$i]["els_h"]) ? $this->entry["atom_list"][$i]["els_h"] : $elsH;

      $sym[$this->entry["atom_list"][$i]["cif_symbol"]] = array("symbol" => $this->entry["atom_list"][$i]["cif_symbol"], "ox" => $this->entry["atom_list"][$i]["ox_text"]);
    }

    $this->entry["itf_header"] = $itf;
    $this->entry["atf_header"] = $atf;
    $this->entry["elsH_header"] = $elsH;

    $sym_numeric = array();
    foreach ($sym as $val)
      $sym_numeric[] = $val;

    $this->entry["atom_type_symbols"] = $sym_numeric;

    $this->entry["cached"] = false;

    $this->cache();
  }

  function createCIF()
  {
    $tmpl = new CTemplate("cif", $this->entry);
    //TODO: long lines must be broken!
    $this->cif = $tmpl->text();
  }

  function getCIF()
  {
    if ($this->cif == "")
      $this->createCIF();

    header("Content-Type: chemical/x-cif");
    header("Content-Disposition: attachment; filename=\"".$this->ccode.".cif\"");
    header('Content-Length: '.strlen($this->cif));
    echo $this->cif;
  }

  function display()
  {
    $tmpl = new CTemplate("fullEntry", $this->entry, $this);
    $tmpl->printTemplate();
  }

  function displayJSmol()
  {
    $tmpl = new CTemplate("jsmolDisplay", $this->entry, $this);
    $tmpl->printTemplate();
  }

  function tmpl_cif()
  {
    if ($this->cif == "")
      $this->createCIF();

    return $this->cif;
  }

}
