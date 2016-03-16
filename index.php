<?php
error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
$page = $_SERVER['PHP_SELF'];
$sec = "300";
header("Refresh: $sec; url=$page");
$nagmap_version = '1.3';
include('config.php');
include('marker.php');

if ($javascript == "") {
  echo "There is no data to display. You either did not set NagMap properly or there is a software bug.<br>".
       "Please contact maco@blava.net for free assistance.";
  die("Cannot continue");
}

?>
<html>
  <head>
    <link rel="shortcut icon" href="favicon.ico" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <link rel=StyleSheet href="style.css" type="text/css" media=screen>
    <title>NagMap <?php echo $nagmap_version ?></title>
	<script src="http://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    <script type="text/javascript">

    //static code from index.pnp
	ymaps.ready(init);
	function init () {
    var myMap = new ymaps.Map("map", {
            center: [<?php echo $nagmap_map_centre; ?>],
            zoom: 14
        }, {
            searchControlProvider: 'yandex#search'
        }),
		myGeoObject = new ymaps.GeoObject({});
		myMap.geoObjects
        .add(myGeoObject)

// generating dynamic code from here
// if the page ends here, there is something seriously wrong, please contact maco@blava.net for help

<?php
  // print the body of the page here
  echo $javascript;
  echo ';}'; //end of initialize function
  echo '
    </script>
    </head>
    <body style="margin:0px; padding:0px;">';
  if ($nagmap_sidebar == '1') {
    sort($sidebar['ok']);
    sort($sidebar['warning']);
    sort($sidebar['critical']);
    sort($sidebar['unknown']);
    echo '<div id="map_canvas" style="width:85%; height:100%; float: left"></div>';
    echo '<div id="sidebar" class="sidebar" style="padding-left: 10px; background: black; height:100%; overflow:auto;">';
    if ($nagmap_sidebar_top_extra) {
        echo $nagmap_sidebar_top_extra;
    }
    echo '<span class="ok">ok:'.$stats['ok']
        ." (".round((100/($stats['warning']+$stats['critical']+$stats['unknown']+$stats['ok']))*($stats['ok']))."%)</span><br>"
        .'<span class="problem">problem:'.($stats['warning']+$stats['critical']+$stats['unknown'])
        ." (".round((100/($stats['warning']+$stats['critical']+$stats['unknown']+$stats['ok']))*($stats['warning']+$stats['critical']+$stats['unknown']))."%)</span><hr noshade>";
    foreach (array('critical','unknown','warning','ok') as $severity) {
      foreach ($sidebar[$severity] as $entry) {
        echo $entry;
      }
    }
    echo '</div>';
  } else {
    echo '<div id="map" style="width:100%; height:100%; float: left"></div>';
  }

?>

</body>
</html>
