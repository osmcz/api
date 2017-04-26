#
#   mod_perl handler, upload, part of openstreetmap.cz
#   Copyright (C) 2016, 2017 Michal Grezl
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
#

package Guidepost::Upload;

use utf8;
use JSON;

use Apache2::Connection ();
use Apache2::Const -compile => qw(OK SERVER_ERROR NOT_FOUND);
use Apache2::Filter ();
use Apache2::Reload;
use Apache2::Request;
use Apache2::RequestIO ();
use Apache2::RequestRec ();
use Apache2::URI ();
use Apache2::Upload;

use APR::Brigade ();
use APR::Bucket ();
use APR::Const -compile;
use APR::URI ();
use constant IOBUFSIZE => 8192;
use APR::Request;

use DBI;

use Data::Dumper;
use Scalar::Util qw(looks_like_number);

use Geo::JSON;
use Geo::JSON::Point;
use Geo::JSON::Feature;
use Geo::JSON::FeatureCollection;

use Sys::Syslog;
use HTML::Entities;

use File::Copy;
use Encode;

use Image::ExifTool;
#use Image::ExifTool::Location;

use LWP::Simple;

use Geo::Inverse;
use Geo::Distance;

use jQuery::File::Upload;
use Inline::Files;

my $dbh;
my $BBOX = 0;
my $LIMIT = 0;
my $minlon;
my $minlat;
my $maxlon;
my $maxlat;
my $error_result;
my $remote_ip;
my $dbpath;

################################################################################
sub handler
################################################################################
{
  $BBOX = 0;
  $LIMIT = 0;

  $r = Apache2::Request->new(shift,
                             POST_MAX => 10 * 1024 * 1024, # in bytes, so 10M
                             DISABLE_UPLOADS => 0,
                             TEMP_DIR => "/tmp"
                            );
  $r->no_cache(1);

  $dbpath = $r->dir_config("dbpath");

  openlog('upload', 'pid', 'user');

  my $uri = $r->uri;      # what does the URI (URL) look like ?
  $r->no_cache(1);

  $r->content_type('text/html; charset=utf-8');

  syslog("info", "uri:".$r->uri);

  if ($uri =~ "phase1") {
    $r->print(&phase1());
  }

  if ($uri =~ "form") {
    &generate_html();
  }

  if ($uri =~ "info") {
    &i();
  }

  if ($uri =~ "phase2") {
    &phase2();
  }

  closelog();

  return Apache2::Const::OK;
}

sub form
{
  $r->print(<<EOF);
  <html><body>
  <form enctype="multipart/form-data" name="files" action="/test/y" method="POST">
    File 1 <input type="file" name="file1"><br>
    File 2 <input type="file" name="file2"><br><br>
    <input type="submit" name="submit" value="Upload these files">
  </form>
 </body></html>
EOF
}

################################################################################
sub phase1
################################################################################
{
  my $req1 = Apache2::Request->new($r) or die;
  my $d = Dumper(\$req1);

  @uploads = $r->upload();

  my @a = (status => "success");
  my %file;

  foreach $file (@uploads) {
    $error = "";
    $upload = $r->upload($file);

# file content
#    my $io = $upload->io;

    $file{name} = $upload->filename();
    $file{size} = $upload->size();

    $final = "/var/www/api/uploads/" . $upload->filename();

    $error = "file exist" unless -f $final;

    if (!$upload->link($final)) {
     $error = "cannot link";
    }

    my ($lat, $lon, $time) = &exif($final);

    $file{"lat"} = $lat;
    $file{"lon"} = $lon;
    $file{"time"} = $time;

    if ($error ne "" ) {
      $file{"error"} = $error;
    }

    push @a, \%file;

  }

  $files{files}= \@a;
  $out = encode_json(\%files);
  return $out;
}

################################################################################
sub exif
################################################################################
{
  my $image_location = "/home/walley/www/mapy/img/guidepost";
  my ($image) = @_;

  syslog("info", "exif: " . $image);
  my $exifTool = new Image::ExifTool;
  $exifTool->Options(Unknown => 1);
  $exifTool->Options(CoordFormat => '%.6f');

  my $info = $exifTool->ImageInfo($image );
  my $group = '';
  my $tag;

  foreach $tag ($exifTool->GetFoundTags('Group0')) {
    if ($group ne $exifTool->GetGroup($tag)) {
      $group = $exifTool->GetGroup($tag);
    }
    my $val = $info->{$tag};
    if (ref $val eq 'SCALAR') {
      if ($$val =~ /^Binary data/) {
        $val = "($$val)";
      } else {
        my $len = length($$val);
        $val = "(Binary data $len bytes)";
      }
    }
    $exif{$group}{$exifTool->GetDescription($tag)} = $val;
  }


  if (defined $exif{Composite}{"GPS Date/Time"}) {
    $gps_time = $exif{Composite}{"GPS Date/Time"};
  } else {
    $gps_time = "";
  }

  if (defined $exif{"Composite"}{"GPS Latitude"})  {
    syslog("info", "yes");
    my $exifgps_lat = $exif{"Composite"}{"GPS Latitude"};
    my @ea = split(" ", $exifgps_lat);
    $gps_lat = (($ea[1] eq "N")?"":"-") . $ea[0];
    syslog("info", "yes ". $ea[0] ." ". $ea[1] ." ". $gps_lat);
  } else {
    syslog("info", "no");
    $gps_lat = "nope";
  }

  if (defined $exif{Composite}{"GPS Longitude"})  {
    $gps_lon = $exif{Composite}{"GPS Longitude"};
    my @ea = split(" ", $exifgps_lon);
    $gps_lon = (($ea[1] eq "E")?"":"-") . $ea[0];
  } else {
    $gps_lon = "nope";
  }

  return ($gps_lat, $gps_lon, $gps_time);
}

################################################################################
sub phase2()
################################################################################
{
#move coords

#move photo to final location

#create db entry

}

#moved from php
################################################################################
#function insert_to_db($lat, $lon, $url ,$file, $author, $ref, $note, $license)
sub insert_to_db()
################################################################################
{
my ($lat, $lon, $url ,$file, $author, $ref, $note, $license) = @_;
#  global $global_error_message;
#  $database = new SQLite3('guidepost');;
#  if (!$database) {
#    $global_error_message = (file_exists('guidepost')) ? "Impossible to open, check permissions" : "Impossible to create, check permissions";
#    return 0;
#  }
  $q = "insert into guidepost values (NULL, '$lat', '$lon', '$url', '$file', '$author', '$ref', '$note', '$license')";
#  $query = $database->exec($q);
#  if (!$query) {
#    $global_error_message = "Error: $query_error"; 
#    return 0;
#  }
#  printdebug("insert_to_db(): insert successful");
#  return 1;
}

################################################################################
sub insert_file
################################################################################
{
  my $out = "";
  ($fn, $tag) = @_;

  open(FILE, "<", "$fn") or die;

  $out .= "<!-- $fn -->\n";
  $out .= "<$tag>\n";
  while (<FILE>) {
    $out .= $_;
  }
  close(FILE);
  $out .= "</$tag>\n";

  return $out;
}

################################################################################
sub generate_html
################################################################################
{
  my $out = "";

  $out .= "<!DOCTYPE html>\n";
  $out .= "<html>\n";
  $out .= "  <head>\n";
  $out .= "    <meta charset='utf-8'/>\n";
  $out .= "    <title>openstreetmap.cz upload form</title>\n";
  $out .= "    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>";

  $out .= &insert_file("/home/walley/www/api/js/jquery.ui.widget.js"       ,"script");
  $out .= &insert_file("/home/walley/www/api/js/jquery.knob.js"            ,"script");
  $out .= &insert_file("/home/walley/www/api/js/jquery.iframe-transport.js","script");

  $out .= &insert_file("/home/walley/www/api/js/jquery.fileupload.js"      ,"script");
  $out .= &insert_file("/home/walley/www/api/js/jquery.fileupload-ui.js"   ,"script");
  $out .= &insert_file("/home/walley/www/api/js/script.js"                 ,"script");

  $out .= &insert_file("/home/walley/www/api/html/upload.css.tmpl"         ,"style");

  $out .= "  </head>\n";

  $out .= &body();

  $out .= "</html>\n";

  $r->print($out);

}

1;


################################################################################
sub body
################################################################################
{
my $out = "
  <body>
    <form id='upload' method='post' action='http://api.openstreetmap.cz/upload/phase1/' enctype='multipart/form-data'>
      <input type='text' name='author' value='autor'>
      <div id='drop'>
        Drop Here
        <a>Browse b</a>
        <input type='file' name='upl' multiple />
      </div>

      <ul>
        <!-- The file uploads will be shown here -->
      </ul>

    </form>
  </body>
";
return $out;
}

################################################################################
################################################################################
################################################################################
################################################################################

