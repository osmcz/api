# api
api, backend, scripts

You need to have mod_perl2 from http://perl.apache.org/ installed.

# apache configuration


```
  <DirectoryMatch "^/.*/\.git/">
    Order deny,allow
    Deny from all
  </DirectoryMatch>

  Header set Access-Control-Allow-Origin "*"
  PerlRequire /var/www/api/handler/startup.pl
  PerlSetVar ReloadAll Off

  <Location /table>
    SetHandler perl-script
    PerlResponseHandler Guidepost::Table
    PerlOptions +ParseHeaders
 </Location>

  <Location /commons>
    SetHandler perl-script
    PerlResponseHandler Guidepost::Commons
    PerlOptions +ParseHeaders
  </Location>
```

# sqlite3 schema

* guidepost db

```sql
CREATE TABLE changes (
  id integer primary key AUTOINCREMENT,
  gp_id integer,
  col varchar,
  value varchar,
  action varchar
);

CREATE TABLE guidepost (
  id integer primary key AUTOINCREMENT,
  lat numeric,
  lon numeric,
  url varchar,
  name varchar,
  attribution varchar,
  ref varchar,
  note varchar
);

create table tags (
  id integer primary key AUTOINCREMENT,
  gp_id integer,
  k varchar,
  v varchar
);
```
* commons db

```
CREATE TABLE commons(
id integer primary key AUTOINCREMENT,
lat numeric,
lon numeric,
name varchar,
desc varchar
);
```

# dirs
commons-scripts - bunch of scripts used to create commons db
handler - mod_perl handlers
vagrant - portable virtual software development environment, debian and others


# guidepost api
http://api.openstreetmap.cz/editor-help.html

