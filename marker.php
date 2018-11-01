<?php
include("functions.php");
// pre-define variables so the E_NOTICES do not show in webserver logs
$javascript = "";
$javascript.='{"type": "FeatureCollection",'."\n";
$javascript.='"features": ['."\n";

$sidebar['ok'] = Array();
$sidebar['critical'] = Array();
$sidebar['warning'] = Array();
$sidebar['unknown'] = Array();
$tid=0;
$stats['ok'] = 0;
$stats['critical'] = 0;
$stats['warning'] = 0;
$stats['unknown'] = 0;

// Get list of all Nagios configuration files into an array
$files = get_config_files();

// Read content of all Nagios configuration files into one huge array
foreach ($files as $file) {
  $raw_data[$file] = file($file);
}

$data = filter_raw_data($raw_data);

// hosts definition - we are only interested in hostname, parents and notes with position information
foreach ($data as $host) {
  if (((!empty($host["host_name"])) && (!preg_match("/^\\!/", $host['host_name']))) | ($host['register'] == 0)) {
    $hostname = 'x'.safe_name($host["host_name"]).'x';
    $hosts[$hostname]['host_name'] = $hostname;
    $hosts[$hostname]['nagios_host_name'] = $host["host_name"];
    $hosts[$hostname]['alias'] = "<i>(".$host["alias"].")</i>";
  
    // iterate for every option for the host
    foreach ($host as $option => $value) {
      // get parents information
      if ($option == "parents") {
        $parents = explode(',', $value); 
        foreach ($parents as $parent) {
          $parent = safe_name($parent);
          $hosts[$hostname]['parents'][] = "x".$parent."x";
        }
        continue;
      }
      // we are only interested in latlng values from notes
      if ($option == "notes") {
        if (preg_match("/latlng/",$value)) { 
          $value = explode(":",$value); 
          $hosts[$hostname]['latlng'] = trim($value[1]);
          continue;
        } else {
          continue;
        }
      };
      // another few information we are interested in
      if (($option == "address")) {
        $hosts[$hostname]['address'] = trim($value);
      };
      if (($option == "hostgroups")) {
        $hostgroups = explode(',', $value);
        foreach ($hostgroups as $hostgroup) {
          $hosts[$hostname]['hostgroups'][] = $hostgroup;
        }
      };
      // another few information we are interested in - this is a user-defined nagios variable
      if (preg_match("/^_/", trim($option))) {
        $hosts[$hostname]['user'][] = $option.':'.$value;
      };
      unset($parent, $parents);
    }
  }
}
unset($data);

if ($nagmap_filter_hostgroup) {
  foreach ($hosts as $host) {
    if (!in_array($nagmap_filter_hostgroup, $hosts[$host["host_name"]]['hostgroups'])) {
      unset($hosts[$host["host_name"]]);
    }
  }
}

// get host statuses
$s = nagmap_status();
// remove hosts we are not able to render and combine those we are able to render with their statuses 
foreach ($hosts as $h) {
  if ((isset($h["latlng"])) AND (isset($h["host_name"])) AND (isset($s[$h["nagios_host_name"]]['status']))) {
    $data[$h["host_name"]] = $h;
    $data[$h["host_name"]]['status'] = $s[$h["nagios_host_name"]]['status'];
    $data[$h["host_name"]]['status_human'] = $s[$h["nagios_host_name"]]['status_human'];
    $data[$h["host_name"]]['status_style'] = $s[$h["nagios_host_name"]]['status_style'];
  } else {
    if ($nagmap_debug) { 
      echo('// ignoring the following host:'.$h['host_name'].":".$h['latlng'].":".$s[$h["nagios_host_name"]]['status_human'].":\n");
    }
  }
}
unset($hosts);
unset($s);

// put markers and bubbles onto a map
foreach ($data as $h) {
  $tid++;
    if ($nagmap_debug) {
      echo('<!--positioning host:'.$h['host_name'].":".$h['latlng'].":".$h['status'].":".$h['status_human']."-->\n");
    }
    // position the host on the map
    //$javascript .= ("window.".$h["host_name"]."_pos = new google.maps.LatLng(".$h["latlng"].");\n");

    // display different icons for the host (according to the status in nagios)
    // if host is in state OK
	$latlng=$h["latlng"];
        $nhn=$h["nagios_host_name"];
        $ss=$h["status_style"];
        $alias=$h["alias"];

    if ($h['status'] == 0) {
	//$javascript .= '.add(new ymaps.Placemark(['.$h["latlng"].'], {balloonContent: \''.$h["host_name"].'\'}, {iconLayout: \'default#image\', iconImageHref: \'images/ok.png\', iconImageSize: [27, 27], iconImageOffset: [-7, -25], zIndex: \'3000\',}))'."\n";
  $javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}, "options": {"preset": "islands#darkGreenIcon"}},'."\n";
        $stats['ok']++;
	$sidebar['ok'][]='<a href="#" onClick="go_host(['.$latlng.'],\''.$nhn.'\')" class=\''.$ss.'\'>'.$nhn.'</a><br>\n';
	//$sidebar['ok'][]='<a href="#" onClick="go_host(['.$h[\"latlng\"].'],\"'.$h[\"nagios_host_name\"]\".')" class="'.$h[\"status_style\"].'">'.$h["nagios_host_name"].' '.$h["alias"].'</a><br>\n';
	//$sidebar['ok'][]='<a href="#" onClick="go_host('.$h[\"latlng\"]'.,.$h[\"nagios_host_name\"].')" class="'.$h['status_style'].'">'.$h["nagios_host_name"]." ".$h["alias"]."</a><br>\n";
    // if host is in state WARNING
    } elseif ($h['status'] == 1) {
    $javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}, "options": {"preset": "islands#yellowIcon"}},'."\n";
    ##$javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}},'."\n";
    #$javascript .= '.add(new ymaps.Placemark(['.$h["latlng"].'], {balloonContent: \''.$h["host_name"].'\'}, {preset: \'islands#icon\', iconColor: \'#ffff1a\', zIndex: \'4000\' }))'."\n";
		$stats['warning']++;
		$sidebar['warning'][]='<a href="#" onClick="go_host(['.$latlng.'],\''.$nhn.'\')" class=\''.$ss.'\'>'.$nhn.'</a><br>\n';
		} elseif ($h['status'] == 2) {
		$javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}, "options": {"preset": "islands#redIcon"}},'."\n";
    #$javascript .= '.add(new ymaps.Placemark(['.$h["latlng"].'], {balloonContent: \''.$h["host_name"].'\'}, {preset: \'islands#icon\', iconColor: \'#ff3300\', zIndex: \'8000\' }))'."\n";
		$stats['critical']++;
		$sidebar['critical'][]='<a href="#" onClick="go_host(['.$latlng.'],\''.$nhn.'\')" class=\''.$ss.'\'>'.$nhn.'</a><br>\n';
		} elseif ($h['status'] == 3) {
		$javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}, "options": {"preset": "islands#darkOrangeIcon"}},'."\n";
    #$javascript .= '.add(new ymaps.Placemark(['.$h["latlng"].'], {balloonContent: \''.$h["host_name"].'\'}, {preset: \'islands#icon\', iconColor: \'#FF7F24\', zIndex: \'3000\'}))'."\n";
		$stats['unknown']++;
		$sidebar['unknown'][]='<a href="#" onClick="go_host(['.$latlng.'],\''.$nhn.'\')" class=\''.$ss.'\'>'.$nhn.'</a><br>\n';
		} else {
		$javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "Point", "coordinates": ['.$h["latlng"].']}, "properties": {"balloonContentHeader":"'.$h["host_name"].'", "hintContent": "'.$h["host_name"].'"}, "options": {"preset": "islands#darkOrangeIcon"}},'."\n";
    #$javascript .= '.add(new ymaps.Placemark(['.$h["latlng"].'], {balloonContent: \''.$h["host_name"].'\'}, {preset: \'islands#icon\', iconColor: \'#FF7F24\', zIndex: \'9000\'}))'."\n";
		$stats['unknown']++;
		}
		
    //generate google maps info bubble
};

$javascript.='';
// create (multiple) parent connection links between nodes/markers
$javascript.='';
foreach ($data as $h) {
  if (!isset($h["latlng"]) OR (!is_array($h["parents"]))) {
    continue;
  }
  foreach ($h["parents"] as $parent) {
    $tid++;
    if (isset($data[$parent]["latlng"])) {
      // default colors for links
      $stroke_color = "#00b34d";
      // links in warning state
      if ($h['status'] == 1) { $stroke_color ='#ffff00'; }
      // links in problem state
      if ($h['status'] == 2) { $stroke_color ='#ff0000'; }
      #$javascript .= '.add(new ymaps.Polyline([['.$data[$parent]["latlng"].'],['.$h["latlng"].']],{balloonContent: "'.$h["host_name"].'_to_'.$parent.'"},{strokeColor: \''.$stroke_color.'\', strokeWidth: 2, strokeOpacity: 0.8}))'."\n";
      $javascript.='{"type": "Feature", "id": '.$tid.', "geometry": {"type": "LineString", "coordinates": [['.$data[$parent]["latlng"].'],['.$h["latlng"].']]}, "properties": {"balloonContentHeader":"'.$h["host_name"].'_to_'.$parent.'", "hintContent": "'.$h["host_name"].'_to_'.$parent.'"}, "options": {"strokeWidth": 2, "strokeColor": "'.$stroke_color.'"}},'."\n";
      //$javascript .= ($h["host_name"].'_to_'.$parent.".setMap(map);\n\n");
    }
  }
}
$javascript=substr($javascript, 0, -2);
$javascript.="\n".']}'."\n";
$f = fopen("data.json", "w+");
fwrite($f, $javascript);
fclose($f);
/*$javascript .= "// generating links between hosts\n";
foreach ($data as $h) {
  // if we do not have any parents, just create an empty array
  if (!isset($h["latlng"]) OR (!is_array($h["parents"]))) {
    continue;
  }
  foreach ($h["parents"] as $parent) {
    if (isset($data[$parent]["latlng"])) {
      // default colors for links
      $stroke_color = "#ADDFFF";
      // links in warning state
      if ($h['status'] == 1) { $stroke_color ='#ffff00'; }
      // links in problem state
      if ($h['status'] == 2) { $stroke_color ='#ff0000'; }
      $javascript .= "\n";
      $javascript .= ('window.'.$h["host_name"].'_to_'.$parent.' = new google.maps.Polyline({'."\n".
        ' path: ['.$h["host_name"].'_pos,'.$parent.'_pos],'."\n".
        "  strokeColor: \"$stroke_color\",\n".
        "  strokeOpacity: 0.9,\n".
        "  strokeWeight: 2});\n");
      $javascript .= ($h["host_name"].'_to_'.$parent.".setMap(map);\n\n");
    }
  }
}
*/
?>
