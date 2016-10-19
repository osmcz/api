#
#   mod_perl handler, upload, part of openstreetmap.cz
#   Copyright (C) 2015, 2016 Michal Grezl
#   Copyright (C) 2016 Marian Kyral
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

package Guidepost::DirectUpload;

use utf8;
use JSON;

use Apache2::Connection ();
use Apache2::Response ();
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

use LWP::Simple;

use Geo::Inverse;
use Geo::Distance;

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

my $upload_dir = "/var/www/api/uploads/";

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

  openlog('directupload', 'pid', 'user');

  my $uri = $r->uri;      # what does the URI (URL) look like ?
  my $unparsed_uri = $r->unparsed_uri;
#   my $args = $r->args();
  $r->no_cache(1);

  $r->content_type('text/html; charset=utf-8');

  syslog("info", "dbpath:".$dbpath);

  syslog("info", "uri:".$uri);
  syslog("info", "unparsed_uri:".$unparsed_uri);
#   syslog("info", "args:".$args);

  $r->print("<html>");
  $r->print("<body>");
  $r->print("uri: ".$uri."<br/>");
  $r->print("unparsed_uri: ".$unparsed_uri."<br/>");
#   $r->print("args: ".$args."<br/>");
  $r->print("</body>");
  $r->print("</html>");


#   if ($uri =~ "uploadimage") {
#     $r->print(&uploadImage());
#   }

  closelog();

  return Apache2::Const::OK;
}


################################################################################
sub uploadImage
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

    $final = "$upload_dir" . $upload->filename();

    $error = "file exist" unless -f $final;

    if (!$upload->link($final)) {
     $error = "cannot link";
    }

#     my ($lat, $lon, $time) = &exif($final);

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
sub phase2()
################################################################################
{
#move coords
#move photo to final location
#create db entry

}

sub insert_file
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
################################################################################
################################################################################
################################################################################

