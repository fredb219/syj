#!/bin/sh
set -e

. ${0%/*}/db_auth.sh


##
# Script for creating and loading database
##

usage() {
    echo Usage: ${0##*/} [options]
    echo
    echo Options:
    echo "  " -h display this help message
    echo "  " -w load database with sample data
    echo "  " -g resets geographic informations \(geoip and geonames\)
    echo "  " -s be more silent \(show only warnings\)
}

# getopt
WITHDATA=""
SILENT=""
GEODATA=""
args=`getopt -o hwgs -l help,withdata,geo,silent -- "$@"`
eval set -- "$args"
while true; do
    case "$1" in
        -h|--help) usage; exit 0 ;;
        -w|--withdata) WITHDATA="1"; shift ;;
        -g|--geo) GEODATA="1"; shift ;;
        -s|--silent) SILENT="1"; shift ;;
        --) shift; break ;;
        *) echo "Invalid option: $1"; exit 1 ;;
    esac
done

if [ ${#SILENT} -ne 0 ]; then
    # we won't see all the index creation notices when creating tables
    export PGOPTIONS='--client_min_messages=warning'
fi

# load schema
psql --set "ON_ERROR_STOP=1" -f ${0%/*}/schema.postgres.sql

# optionally load sample data
if [ ${#WITHDATA} -ne 0 ]; then
    psql --set "ON_ERROR_STOP=1" -f ${0%/*}/data.postgres.sql
fi

if [ ${#GEODATA} -ne 0 ]; then
    sh ${0%/*}/updategeoip.sh "${@}"
    sh ${0%/*}/updategeonames.sh "${@}"
fi
