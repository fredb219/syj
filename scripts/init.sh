#!/bin/sh

git submodule update --init

git submodule -q foreach "
    if ls -A $PWD/patches/\${name##*/} >/dev/null 2>&1; then
        git checkout master
        git am $PWD/patches/\${name##*/}/*
    fi
"

sh scripts/updatezend.sh
sh scripts/reset.postgres.sh -g
