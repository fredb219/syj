#!/bin/sh

ZEND_VERSION=$(lynx -dump http://framework.zend.com/changelog | sed -n -e 's/^Changelog:\s*\([0-9\.]\+\)/\1/p')

if [ -f library/Zend/Version.php ]; then
    php -r "include('library/Zend/Version.php'); exit(Zend_Version::compareVersion(\"$ZEND_VERSION\"));"
    if [ $? -ne 1 ];then
        echo no new version
    fi
fi

echo downloading zend $ZEND_VERSION

wget http://framework.zend.com/releases/ZendFramework-$ZEND_VERSION/ZendFramework-$ZEND_VERSION-minimal.tar.gz
tar zxf ZendFramework-$ZEND_VERSION-minimal.tar.gz
rm -rf library/Zend
mv ZendFramework-$ZEND_VERSION-minimal/library/Zend library/
rm -rf ZendFramework-$ZEND_VERSION-minimal ZendFramework-$ZEND_VERSION-minimal.tar.gz
