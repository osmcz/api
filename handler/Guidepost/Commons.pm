#
#   mod_perl handler, wikimedia commons gudeposts, part of openstreetmap.cz
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

package Guidepost::Commons;

use utf8;

use Apache2::Connection ();
use Apache2::Const;
use Apache2::Filter ();
use Apache2::Reload;
use Apache2::RequestIO ();
use Apache2::RequestRec ();
use Apache2::ServerRec;
use Apache2::URI ();
use APR::Brigade ();
use APR::Bucket ();
use APR::URI ();
use constant IOBUFSIZE => 8192;
use Data::Dumper;
use DBI;
use DBD::SQLite;
use Geo::JSON;
use Geo::JSON::Feature;
use Geo::JSON::FeatureCollection;
use Geo::JSON::Point;
use HTML::Entities;
use Scalar::Util qw(looks_like_number);
use Sys::Syslog;

my $dbh;

################################################################################
sub handler
################################################################################
{
  $r = shift;
  my $s = $r->server;
  $s->timeout(50_000_000);

  $dbpath = $r->dir_config("dbpath");

  openlog('commonsapi', 'cons,pid', 'user');

  my $uri = $r->uri; 
  &parse_query_string($r);

  if (exists $get_args{bbox}) {
    &parse_bbox($get_args{bbox});
  }

  $r->content_type('text/plain; charset=utf-8');
  $r->no_cache(1);
  $out = &output_geojson();

  if (!$r->print($out)) {
    syslog('info', "getdata " . $_ . "=" . $get_args{$_});
  }

  $dbh->disconnect;

  closelog();
  return Apache2::Const::OK;
}

################################################################################
sub parse_query_string
################################################################################
{
  my $r = shift;

  %get_args = map { split("=",$_) } split(/&/, $r->args);

  #sanitize
  foreach (sort keys %get_args) {
    $get_args{$_} =~ s/\%2C/,/g;
    $get_args{$_} =~ s/\%2F/\//g;
    if (lc $_ eq "bbox" ) {
      $get_args{$_} =~ s/[^A-Za-z0-9\.,-]//g;
    } elsif ($_ =~ /output/i ) {
      $get_args{$_} =~ s/[^A-Za-z0-9\.,-\/]//g;
    } else {
      $get_args{$_} =~ s/[^A-Za-z0-9 ]//g;
    }
    #
  }
}

################################################################################
sub parse_bbox
################################################################################
{
  my $b = shift;
#BBox=-20,-40,60,40

  @bbox = split(",", $b);
  $minlon = $bbox[0];
  $minlat = $bbox[1];
  $maxlon = $bbox[2];
  $maxlat = $bbox[3];
  $BBOX = 1;
}

################################################################################
sub output_geojson
################################################################################
{
  use utf8;

  my $query = "select * from commons";
  if ($BBOX) {
    $query .= " where lat < $maxlat and lat > $minlat and lon < $maxlon and lon > $minlon";
  }

  my $pt;
  my $ft;
  my @feature_objects;

  my $a;

  $dbh = DBI->connect(
      "dbi:SQLite:$dbpath/commons", "", "",
      {
          RaiseError     => 0,
          sqlite_unicode => 1,
          sqlite_open_flags => DBD::SQLite::OPEN_READONLY,
      }
  );

  $res = $dbh->selectall_arrayref($query) or do {
    syslog('info', "commons err " . $query. " " . $DBI::errstr);
    return "";
  };

  foreach my $row (@$res) {
    my ($id, $lat, $lon, $name, $desc) = @$row;

    my $fixed_lat = looks_like_number($lat) ? $lat : 0;
    my $fixed_lon = looks_like_number($lon) ? $lon : 0;

    $pt = Geo::JSON::Point->new({
      coordinates => [$fixed_lon, $fixed_lat],
      properties => ["prop0", "value0"],
    });

    my %properties = (
      'id' => $id,
      'name' => $name,
      'desc' => $desc,
    );

    $ft = Geo::JSON::Feature->new({
      geometry   => $pt,
      properties => \%properties,
    });

    push @feature_objects, $ft;
  }


  my $fcol = Geo::JSON::FeatureCollection->new({
     features => \@feature_objects,
  });


  return $fcol->to_json."\n";
}

1;
