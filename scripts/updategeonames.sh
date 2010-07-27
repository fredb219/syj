#!/bin/sh
set -e

GEONAMESXML=http://ws.geonames.org/countryInfo

EXECDIR=$PWD/${0%/*}
. $EXECDIR/db_auth.sh

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
DROP TABLE IF EXISTS geonames CASCADE;
CREATE TABLE geonames (
    country CHAR(2) UNIQUE PRIMARY KEY,
    minlon real,
    minlat real,
    maxlon real,
    maxlat real
);
COMMIT;
EOF

TMPDIR=`mktemp -d`
TMPFILE=`mktemp --tmpdir=$TMPDIR`
trap "rm -rf $TMPDIR;" EXIT
cd $TMPDIR
wget $GEONAMESXML

for line in $(cat ${GEONAMESXML##*/}); do
    if echo $line | grep -qi '<country>'; then
        COUNTRY=""; MINLON=""; MINLAT=""; MAXLON=""; MAXLAT=""
    elif echo $line | grep -qi '<countryCode>.*</countryCode>'; then
        COUNTRY=$(echo $line | sed -e 's/^\s*<countryCode>\(.*\)<\/countryCode>\s*/\1/i')
    elif echo $line | grep -qi '<bBoxWest>.*</bBoxWest>'; then
        MINLON=$(echo $line | sed -e 's/^\s*<bBoxWest>\(.*\)<\/bBoxWest>\s*/\1/i')
    elif echo $line | grep -qi '<bBoxSouth>.*</bBoxSouth>'; then
        MINLAT=$(echo $line | sed -e 's/^\s*<bBoxSouth>\(.*\)<\/bBoxSouth>\s*/\1/i')
    elif echo $line | grep -qi '<bBoxEast>.*</bBoxEast>'; then
        MAXLON=$(echo $line | sed -e 's/^\s*<bBoxEast>\(.*\)<\/bBoxEast>\s*/\1/i')
    elif echo $line | grep -qi '<bBoxNorth>.*</bBoxNorth>'; then
        MAXLAT=$(echo $line | sed -e 's/^\s*<bBoxNorth>\(.*\)<\/bBoxNorth>\s*/\1/i')
    elif echo $line | grep -qi '<\/country>'; then
        if [ ${#COUNTRY} -ne 0 -a ${#MINLON} -ne 0 -a ${#MINLAT} -ne 0 -a ${#MAXLON} -ne 0 -a ${#MAXLAT} -ne 0 ]; then
            echo "INSERT INTO geonames (country, minlon, minlat, maxlon, maxlat) VALUES ('$COUNTRY', $MINLON, $MINLAT, $MAXLON, $MAXLAT);" >> $TMPFILE
        fi
    fi
done
echo "INSERT INTO geonames (country, minlon, minlat, maxlon, maxlat) VALUES ('EU', -26, 34, 40, 68);" >> $TMPFILE
echo "INSERT INTO geonames (country, minlon, minlat, maxlon, maxlat) VALUES ('AP', 90, -20, -140, 68);" >> $TMPFILE
psql --set "ON_ERROR_STOP=1" -f $TMPFILE
