#!/bin/sh

git submodule update --init

OLDPWD=$PWD
cd public/prototype
rake
cd $OLDPWD

sh scripts/updatezend.sh
sh scripts/updateopenlayers.sh
sh scripts/reset.postgres.sh -g
