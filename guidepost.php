<?php
#lat-y
#lon-x

$global_error_message = "";

################################################################################
function is_utf8($str)
################################################################################
{
  return (bool) preg_match('//u', $str);
}

################################################################################
function printdebug($x)
################################################################################
{
  //let it print when debugging
  //return;
//  print $x."<br>";
  $x = str_replace('%', '(percent)',    $x);
  $x = str_replace(';', '(semicolon)',  $x);
  $x = str_replace('*', '(asterisk)',   $x);
  $x = str_replace('/', '(slash)',      $x);
  $x = str_replace("\\", '(backslash)', $x);
  $x = str_replace('~', '(tilda)',      $x);
  $x = str_replace('>', '(GT)',         $x);
  $x = str_replace('<', '(LT)',         $x);
  $x = str_replace('?', '(question)',   $x);
  $x = str_replace('-', '(minus)',      $x);

  system ("/usr/bin/logger -t guidepostapiphp '$x'");
}

################################################################################
function get_param($param)
################################################################################
{
  if (isset($_GET[$param])) {return($_GET[$param]);}
  if (isset($_POST[$param])) {return($_POST[$param]);}
  return "";
}

################################################################################
function page_header()
################################################################################
{
  if (get_param("source") == "mobile") { return; }

  print "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n";
  print "<html>\n";
  print "  <head>\n";
  print "  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>\n";
  print "  <title>openstreetmap.cz image upload</title>\n";
  print "  <script src='OpenLayers.2.8.0.js'></script>\n";
  print "  <script src='jquery-1.11.3.min.js'></script>\n";
  print "  <script src='jquery-ui.min.js'></script>\n";
  print "  <script language='javascript' type='text/javascript' src='upload.js'></script>\n";
  print "  <link href='upload.css' rel='stylesheet' type='text/css'/>\n";

}


################################################################################
function page_footer()
################################################################################
{
  if (get_param("source") == "mobile") { return; }

  print "  </body>\n";
  print "</html>\n";
}

################################################################################
function show_upload_dialog()
################################################################################
{

  $PHP_SELF = $_SERVER['PHP_SELF'];

  print "  </head>
  <body onload='upload_init()'>\n";

$title_help = "Pokud víte že má obrázek Exif souřadnice, můžete nechat lat, lon na 0,0 (není nutno zatrhávat exif)";

  print "
<div id='map' class='mapmap'></div>
<div id='form' class='form'>
<form id='coord' name='coord' action='".$PHP_SELF."' method='post' enctype='multipart/form-data' target='upload_target' onsubmit='start_upload();'>
  <input type='hidden' name='action' value='file' />
  <input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
  <fieldset>
    <label>autor</label><input type='text' id='author' name='author' value='autor' size='9'>
    <input name='uploadedfile' type='file' id='guidepostfile' size='20'/><br>
    <label title='".$title_help."'>lat</label><input type='text' id='lat' name='lat' value='0' size='10' title='".$title_help."'>
    <label title='".$title_help."'>lon</label><input type='text' id='lon' name='lon' value='0' size='10' title='".$title_help."'>
    <label title='Fotka ma vlastni souradnice ulozene v EXIF'>souradnice v exif</label><input type=checkbox id='exif_checkbox' onchange='exif_checkbox_action()' title='Fotka ma vlastni souradnice ulozene v EXIF'>
  </fieldset>
  <fieldset>
    <label>Ref</label><input type='text' name='ref' value='' size='6'>
    <label>Poznámka</label><input type='text' name='note' value='' size='30'>
<br>
    <input type='radio' name='gp_type' value='gp' checked>Rozcestník
    <input type='radio' name='gp_type' value='map'>Mapa
    <input type='radio' name='gp_type' value='pano'>Panorama
    <input type='radio' name='gp_type' value='info'>Informační tabule

  </fieldset>
  <fieldset>
    <label for='license'>licence</label>
    <select id='license' name='license'>
      <option value='CCBYSA4' selected>Creative Commons Attribution ShareAlike 4.0</option>
      <option value='CCBYSA3'>Creative Commons Attribution ShareAlike 3.0</option>
      <option value='CCBY4'>Creative Commons Attribution 4.0</option>
      <option value='CCBY3'>Creative Commons Attribution 3.0</option>
      <option value='CCBYSA2plus'>Creative Commons Attribution ShareAlike 2.0 or later </option>
      <option value='CC0'>Creative Commons CC0 Waiver</option>
      <option value='C'>Zákon č. 121/2000 Sb.</option>
    </select>
  </fieldset>
  <fieldset>
    <input type='submit' name='submitBtn' class='sbtn' value='Nahrát soubor' />
  </fieldset>
</form>
</div>

<table><tr>
  <td><div id='upload_div'><p style='border:3px solid #aaaaaa;' id='upload_process'>Uploading...</p></div></td>
  <td><iframe id='upload_target' name='upload_target' src='#' style='width:200px;height:30px;border:3px solid #aaaaaa;'></iframe></td>
</tr></table>

\n";

}


################################################################################
function show_iphone_upload_dialog()
################################################################################
{

  $PHP_SELF = $_SERVER['PHP_SELF'];

  print"<script>\n";
  print "function upbox_off()\n";
  print "{\n";
  print "  document.getElementById('upbox').style.display = 'none' ;\n";
  print "}\n";
  print "

  function exif_present()
  {
     document.getElementById('lat').value = 0;
     document.getElementById('lat').readOnly = true;
     document.getElementById('lon').value = 0;
     document.getElementById('lon').readOnly = true;
  }

  function no_exif()
  {
     document.getElementById('lat').readOnly = false;
     document.getElementById('lon').readOnly = false;
  }

  function exif_checkbox_action()
  {
    if (document.getElementById('exif_checkbox').checked) {
      exif_present();
    } else {
      no_exif();
    }
  }

  function send_data()
  {
    var boundary = this.generateBoundary();
    var xhr = new XMLHttpRequest;

    xhr.open('POST', this.form.action, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            alert(xhr.responseText);
        }
    };

    var contentType = 'multipart/form-data; boundary=' + boundary;
    xhr.setRequestHeader('Content-Type', contentType);

    for (var header in this.headers) {
        xhr.setRequestHeader(header, headers[header]);
    }

    // here's our data variable that we talked about earlier
    var data = this.buildMessage(this.elements, boundary);

    // finally send the request as binary data
        xhr.sendAsBinary(data);
  }

  ";

  print"</script>\n";

  print "  </head>
  <body onload='upload_init()'>\n";

$title_help = "Pokud má obrázek Exif souřadnice, můžete nechat lat, lon na 0,0";

  print "
    <div id='map' class='smallmap'></div>

<form name='coord' action='".$PHP_SELF."' method='post' enctype='multipart/form-data' target='upload_target' onsubmit='start_upload();'>
  <input type='hidden' name='action' value='file' />
  <input type='hidden' name='MAX_FILE_SIZE' value='5000000' />
  <fieldset>
    <label>autor</label><input type='text' id='author' name='author' value='autor' size='9'>
    <input name='uploadedfile' type='file' id='guidepostfile'  size='20'/><br>
    <label>lat</label><input type='text' id='lat' name='lat' value='0' size='10' title='".$title_help."'>
    <label>lon</label><input type='text' id='lon' name='lon' value='0' size='10' title='".$title_help."'>
    <label>exif </label><input type=checkbox id='exif_checkbox' onchange='exif_checkbox_action()'>
  </fieldset>
  <fieldset>
    <input type='reset' name='reset' value='Reset' />
    <input type='submit' name='submitBtn' class='sbtn' value='Nahrat soubor' />
  </fieldset>
</form>

<table><tr>
  <td><p style='border:10px solid #fff;'> id='upload_process'>Uploading...</p></td>
  <td><iframe id='upload_target' name='upload_target' src='#' style='width:200px;height:100px;border:10px solid #aaaaaa;'></iframe></td>
</tr></table>
\n";
  //set widht and height to display debug output

}

################################################################################
function insert_to_db($lat, $lon, $url ,$file, $author, $ref, $note, $license, $gp_type)
################################################################################
{
  global $global_error_message;
  $database = new SQLite3('guidepost');
  if (!$database) {
    $global_error_message = (file_exists('guidepost')) ? "Impossible to open, check permissions" : "Impossible to create, check permissions";
    return 0;
  }
  $q = "insert into guidepost values (NULL, '$lat', '$lon', '$url', '$file', '$author', '$ref', '$note', '$license')";

  if (!$database->exec($q)) {
    $global_error_message = "Error: " . $database->lastErrorMsg();
    printdebug("insert_to_db(): insert guidepost error: " . $database->lastErrorMsg());
    return 0;
  }

  $gp_id = $database->lastInsertRowID();

  if ( $ref != '' ) {
    $q = "insert into tags values (NULL, $gp_id, 'ref', '" . strtolower($ref) . "')";
    if (!$database->exec($q)) {
        $global_error_message = "Error: " . $database->lastErrorMsg();
        printdebug("insert_to_db(): insert tags.ref error: " . $database->lastErrorMsg());
        return 0;
    }
  }

  if ( $gp_type ) {
    switch ($gp_type) {
        case 'gp':
            $tag = 'rozcestnik';
            break;
        case 'map':
            $tag = 'mapa';
            break;
        case 'pano':
            $tag = 'panorama';
            break;
        case 'info':
            $tag = 'infotabule';
            break;
    }

    if ( $tag ) {
        $q = "insert into tags values (NULL, $gp_id, '$tag', '')";
        if (!$database->exec($q)) {
            $global_error_message = "Error: " . $database->lastErrorMsg();
            printdebug("insert_to_db(): insert tags.$tag error: " . $database->lastErrorMsg());
            return 0;
        }
    }
  }

  printdebug("insert_to_db(): insert successful");
  return 1;
}

################################################################################
 # Returns an array of latitude and longitude from the Image file
 #   ---- http://stackoverflow.com/a/19420991 ----
 # @param image $file
 # @return multitype:number |boolean
function read_gps_location($file){
################################################################################
    if (is_file($file)) {
        $info = exif_read_data($file);
        if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
            isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
            in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

            $GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
            $GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

            $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
            $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
            $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
            $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
            $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
            $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

            $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
            $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
            $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
            $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
            $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
            $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

            $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
            $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);

            //If the latitude is South, make it negative.
            //If the longitude is West, make it negative
            $GPSLatitudeRef  == 's' ? $lat *= -1 : '';
            $GPSLongitudeRef == 'w' ? $lng *= -1 : '';

            return array(
                'lat' => $lat,
                'lng' => $lng
            );
        }
    }
    return false;
}

################################################################################
function process_file()
################################################################################
{
  global $_POST;
  global $global_error_message;

  $result = 0;

  printdebug("!!! START !!!");

  $filename = $_FILES['uploadedfile']['name'];
  $error_message = "OK";

  printdebug("name: $filename");
  printdebug("type: ".$_FILES['uploadedfile']['type']);
  printdebug("size: ".$_FILES['uploadedfile']['size']);
  printdebug("tmp: ".$_FILES['uploadedfile']['tmp_name']);
  printdebug("error: ".$_FILES['uploadedfile']['error']);

  $license = $_POST['license'];
  $lat = $_POST['lat'];
  $lon = $_POST['lon'];
  $author = $_POST['author'];
  if (isset($_POST['ref'])) {
    $ref = $_POST['ref'];
  } else {
    $ref = "none";
  }

  $note = $_POST['note'];

  $gp_type = $_POST['gp_type'];

  printdebug("ref: ".$ref);
  printdebug("note: ".$note);
  printdebug("gp_type: ".$gp_type);
  printdebug("lat:lon:author:license");
  printdebug("before $lat:$lon:$author:$license");

  $author = preg_replace('/[^-a-zA-Z0-9_ěščřžýáíéĚŠČŘŽÁÍÉúůÚľĽ .]/', '', $author);
  $note = preg_replace('/[^-a-zA-Z0-9_ěščřžýáíéĚŠČŘŽÁÍÉúůÚľĽ .]/', '', $note);
  $lat = preg_replace('/,/', '\.', $lat);
  $lon = preg_replace('/,/', '\.', $lon);
  $lat = preg_replace('/[^0-9.]/', '', $lat);
  $lon = preg_replace('/[^0-9.]/', '', $lon);
  $ref = preg_replace('/[^a-zA-Z0-9.,\/]/', '', $ref);
  $license = preg_replace('/[^CBYSA2340plus]/', '', $license);

  printdebug("after $lat:$lon:$author:$license");

  $file = basename($filename);
  $target_path = "uploads/" . $file;
  $final_path = "img/guidepost/" . $file;

  printdebug("target: $target_path");

#keep this as the first test
  if (file_exists($_FILES['uploadedfile']['tmp_name'])) {
    printdebug("soubor byl uspesne uploadnut do tmp\n");
    $result = 1;
  } else {
    $error_message = "nepodarilo se uploadnout soubor";
    printdebug("cannot upload file\n");
    $result = 0;
  }

  if ($_FILES['uploadedfile']['error'] == "1") {
    $error_message = "soubor je prilis velky";
    printdebug($error_message);
    $result = 0;
  }

  if (!is_utf8($author)) {
    $error_message = "author is not valid utf8";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && !is_utf8($note)) {
    $error_message = "note is not valid utf8";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && !is_utf8($license)) {
    $error_message = "license  is not valid utf8";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && $license === "") {
    $error_message = "license must be defined";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && $author === "") {
    $error_message = "author nezadan";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && $author === "android" or $author === "autor") {
    $error_message = "zmente vase jmeno";
    printdebug($error_message);
    $result = 0;
  }

  #sanitize filename

  if ($result && strpos($filename, ';') !== FALSE) {
    $error_message = "spatny soubor: znak strednik";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && strpos($filename, '&') !== FALSE) {
    $error_message = "spatny soubor: znak divnaosmicka";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && file_exists("img/guidepost/$file")) {
    $error_message = "file already exists ($file), please rename your copy";
    printdebug($error_message);
    $result = 0;
  }

  if ($result && !move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
    $error_message = "Chyba pri otevirani souboru, mozna je prilis velky";
    printdebug($error_message);
    $result = 0;
  }

  if ($result) {
    printdebug("File '$file' has been moved from tmp to $target_path");
  }

  if ($result && mime_content_type($target_path) != 'image/jpeg') {
    $error_message = "spatny soubor: ocekavan image/jpeg";
    printdebug($error_message);
    $result = 0;
  }


  // Check coordinates
  if ($result && $lat && $lon) {
    printdebug("soubor byl poslan se souradnicemi ve formulari");
  }

  // Missing coordinates
  if ($result && !$lat && !$lon){
    printdebug("soubor byl poslan se souradnicemi 0,0 -> exifme");
    $ll = read_gps_location($target_path);
    if (!$ll) {
      $result = 0;
      $error_message = "poslano latlon 0,0 a nepodarilo se zjistit souradnice z exif";
      printdebug("read_gps_location() error $error_message");
    } else {
      $lat = $ll['lat'];
      $lon = $ll['lng'];
      printdebug("read_gps_location() found coordinates: $lat, $lon");
    }
  }

  if ($result && $lat > 180 or $lon > 180 or $lat < -180 or $lon < -180) {
    $error_message = "bad coordinates";
    printdebug($error_message);
    $result = 0;
  }


  if ($result && !copy("uploads/$file","img/guidepost/$file")) {
    $error_message = "failed to copy $file to destination ... ";
    printdebug($error_message);
    $result = 0;
  }

  if ($result) {
    if (!insert_to_db($lat, $lon, $final_path, $file, $author, $ref, $note, $license, $gp_type)) {
      $error_message = "failed to insert to db" . $global_error_message;
      $result = 0;
      if (!unlink ("uploads/$file")) {
        printdebug("$file cannot be deleted from upload, inserted successfuly");
      }
    }
  }

  if ($result && !unlink ("uploads/$file")) {
        printdebug("$file cannot be deleted from upload, inserted successfuly");
  }

  if (!$result) {
      printdebug("Upload refused: ".$error_message);
  }

  if ($result == 0 and $error_message == "") {
    $error_message = "Divna chyba";
    printdebug($error_message);
  }

  if (get_param("source") == "mobile") {
    print "$result-$error_message";
  } else {
    print "  </head>\n";
    print "  <body>\n";
    print "
  <div id='x'></div>
  <script language='javascript' type='text/javascript'>
    \$('#x').text('*** OK ***');
    \$('#x').hide();
    \$('#x').show('highlight',{color: 'lightgreen'},'2000');

    parent.stop_upload(".$result.",'".$error_message."', '".$filename."');
  </script>\n";
  }

  printdebug("!!! END !!!");
  return $result;
}

################################################################################
function create_db()
################################################################################
{
  global $db;
  global $create_query;

$create_query = "CREATE TABLE guidepost (
  id int primary key AUTOINCREMENT,
  lat numeric,
  lon numeric,
  url varchar,
  name varchar,
  attribution varchar,
  ref varchar,
  note varchar,
  license varchar
);";

  $db->queryExec($create_query);

  $db->queryExec("insert into guidepost values (NULL, 50.1, 17.1, 'x', 'znacka', 'autor', 'XB001','poznamka1', 'C');");
  $db->queryExec("insert into guidepost values (NULL, 50.2, 17.2, 'x', 'znacka', 'autor', 'XB002','poznamka2', 'C');");
  $db->queryExec("insert into guidepost values (NULL, 50.3, 17.3, 'x', 'znacka', 'autor', 'XB003','poznamka3', 'C');");
  $db->queryExec("insert into guidepost values (NULL, 50.4, 17.4, 'x', 'znacka', 'autor', 'XB004','poznamka4', 'C');");

$create_query = "CREATE TABLE tags (
  id integer primary key AUTOINCREMENT,
  gp_id integer,
  k varchar,
  v varchar
);";

  $db->queryExec($create_query);

$create_query = "CREATE TABLE changes (
  id integer primary key AUTOINCREMENT,
  gp_id integer,
  col varchar,
  value varchar,
  action varchar
);";

  $db->queryExec($create_query);

}


$action = get_param("action");

switch ($action) {
  case "show_dialog":
    page_header();
    show_upload_dialog();
    page_footer();
    break;
  case "file":
    page_header();
    process_file();
    page_footer();
    break;
  case "":
    $bbox = get_param('bbox');
    if ($bbox == "") {
      printdebug("no bbox");
      die("No bbox provided\n");
    } else {
      printdebug("bbox: " . $bbox);
    }

    list($minlon, $minlat, $maxlon, $maxlat) = preg_split('/,/', $bbox, 4);

    $db = new SQLite3('guidepost');

    if ($db) {
      $i = 0;
      $result = array();
      $query = "select * from guidepost where lat < $maxlat and lat > $minlat and lon < $maxlon and lon > $minlon";

      printdebug("query " . $query);

      $results = $db->query($query);
      while ($row = $results->fetchArray()) {
        $result[$i++] = $row;
      }
      print json_encode($result);
    } else {
      printdebug("db open error: " + $err);
      die($err);
    }
  break;
}

?>
