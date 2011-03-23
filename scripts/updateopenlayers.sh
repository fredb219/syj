#!/bin/sh
if [ -d public/openlayers ]; then
    cd public/openlayers
    git checkout master
    git pull -u
    git checkout syj
    git rebase master
else
    git clone http://github.com/ccnmtl/openlayers.git public/openlayers
    cd public/openlayers
    git checkout -b syj
    git am ../../patches/openlayers/000*
fi
