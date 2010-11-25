#!/bin/sh
set -e

GEOIPDB=http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip

. ${0%/*}/db_auth.sh

usage() {
    echo Usage: ${0##*/} [options]
    echo
    echo Options:
    echo "  " -h display this help message
    echo "  " -s be more silent \(show only warnings\)
}

# getopt
SILENT=""
args=`getopt -o sh -l silent,help -- "$@"`
eval set -- "$args"
while true; do
    case "$1" in
        -h|--help) usage; exit 0 ;;
        -s|--silent) SILENT="1"; shift ;;
        --) shift; break ;;
        *) echo "Invalid option: $1"; exit 1 ;;
    esac
done

if [ ${#SILENT} -ne 0 ]; then
    # we won't see all the index creation notices when creating tables
    export PGOPTIONS='--client_min_messages=warning'
fi

psql --set "ON_ERROR_STOP=1" -f - <<EOF
BEGIN;

DROP TABLE IF EXISTS geoip CASCADE;
CREATE TABLE geoip (
    id SERIAL PRIMARY KEY,
    begin_ip BIGINT,
    end_ip BIGINT,
    country CHAR(2)
);

DROP FUNCTION IF EXISTS inet_to_bigint(INET);
CREATE OR REPLACE FUNCTION inet_to_bigint(ip INET)
    RETURNS BIGINT AS
\$\$
DECLARE
    w TEXT;
    x TEXT;
    y TEXT;
    z TEXT;
    sp TEXT[];
BEGIN
    sp := regexp_split_to_array(ip::text, E'\\\\.');
    w := sp[1];
    x := sp[2];
    y := sp[3];
    z := substring(sp[4], 0, strpos(sp[4], '/'));
    return 16777216*w::bigint + 65536*x::bigint + 256*y::bigint + z::bigint;
END;
\$\$ LANGUAGE plpgsql IMMUTABLE;

COMMIT;
EOF


DIR=`mktemp -d`
trap "rm -rf $DIR;" EXIT
cd $DIR
wget $GEOIPDB
GEOIPCVS=$(zipinfo -1 ${GEOIPDB##*/} | grep '\.csv$')
if [ $(echo $GEOIPCVS | wc -w) -lt "1" ]; then
    echo There is no csv file in the archive. Canceling
elif [ $(echo $GEOIPCVS | wc -w) -gt "1" ]; then
    echo There is more than one csv file in the archive. Which one should I pick ?
fi
unzip ${GEOIPDB##*/} $GEOIPCVS

# insert all values from csv to database
sed -e 's/"\([^"]\+\)","\([^"]\+\)","\([^"]\+\)","\([^"]\+\)","\([^"]\+\)","\([^"]\+\)"/INSERT INTO geoip (begin_ip, end_ip, country) VALUES ('\''\3'\'','\''\4'\'','\''\5'\'');/' $GEOIPCVS | psql --set "ON_ERROR_STOP=1" -f -
psql --set "ON_ERROR_STOP=1" -c "VACUUM ANALYZE geoip;"
