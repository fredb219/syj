To install:

- create a postgis-enabled database named syj for postgres user syj
- configure your webserver
    see a lighttpd configuration example at scripts/lighttpd.conf
- run
    $ sh scripts/init.sh

That should work


To develop:

- Install dependencies:

     $ sudo apt-get install lynx-cur php5-cgi php5-pgsql rake

 - Download the Zen Framework minimal and copy the libray/Zend directory in the syj library directory
 - Change 'production' to 'development' in public/init.php
 - Change db password is need in application/configs/application.ini, in developement section
 - In application/Version.php change $SYJVERSION$ to 'v0.4.1' for example
 - Generate prototype:

    $ cd public/prototype/
    $ rake dist

 - If you have a language warning :

    $ scripts/updatepo.sh
