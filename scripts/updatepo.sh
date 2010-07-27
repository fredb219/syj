#!/bin/sh
POTFILE=`mktemp /tmp/XXXXXXXXXX.pot`
GITROOT=`git rev-parse --show-cdup`
GITROOT=${GITROOT:-.}
APPROOT=$GITROOT/application
find $APPROOT -name '*.php' -o -name '*.phtml' | xgettext -L php --keyword=translate --keyword=__ --from-code=UTF-8 -o $POTFILE -f -
for lang in en fr; do
    echo $lang language
    msgmerge -U $APPROOT/languages/lang_${lang}.po $POTFILE
    msgfmt --statistics -o $APPROOT/languages/lang_${lang}.mo $APPROOT/languages/lang_${lang}.po
done
rm -f $POTFILE
