#!/usr/bin/python

TARGET="build"

import shutil, os, sys, subprocess, tempfile, tarfile, glob, ConfigParser
pathjoin = os.path.join

def createdir():
    if os.path.isdir(TARGET):
        shutil.rmtree(TARGET, False)
    os.makedirs(TARGET)

def compress(path):
    print ("compressing " + path)
    tmpout = tempfile.TemporaryFile()
    subprocess.Popen(['yui-compressor', path], stdout=tmpout).communicate()
    tmpout.seek(0)
    with open(path, 'w') as output:
        output.write(tmpout.read())

def genscripts():
    tmpdir = tempfile.mkdtemp()

    # copy scripts OpenLayers.js
    for path in glob.glob('public/js/*.js'):
        shutil.copy(path, tmpdir)

    # build OpenLayers.js
    subprocess.call(['python', 'buildUncompressed.py',
                     pathjoin(os.getcwd(), "scripts/syj"), pathjoin(tmpdir, "OpenLayers.js")],
                     cwd = 'public/openlayers/openlayers/build')

    config = ConfigParser.ConfigParser()
    os.makedirs(pathjoin(TARGET, 'public/js'))
    config.readfp(open('application/configs/medias.ini'))
    for key, value in config.items('production'):
        if key.startswith('scripts.'):
            outpath = pathjoin(TARGET, 'public/js/' + key[len('scripts.'):] + '.js')
            with open(outpath, 'w') as output:
                for inpath in map(lambda p: pathjoin(tmpdir, p.strip() + '.js'), value.split(',')):
                    with open(inpath) as f:
                        output.write(f.read())
            compress(outpath)
    shutil.rmtree(tmpdir)

def genstyles():
    directory = pathjoin(TARGET, 'public/css')
    os.makedirs(directory)
    for path in glob.glob('public/css/*.css'):
        shutil.copy(path, directory)
        compress(pathjoin(TARGET, 'public/css', os.path.basename(path)))

def genicons():
    directory = pathjoin(TARGET, 'public/icons')
    os.makedirs(directory)
    for path in glob.glob('public/icons/*'):
        shutil.copy(path, directory)

def genolmisc():
    directory = pathjoin(TARGET, 'public/img')
    os.makedirs(directory)
    for path in glob.glob('public/js/img/*'):
        shutil.copy(path, directory)

def tarbuild():
    print "creating syj.tar.gz"
    targz = tarfile.open("build/syj.tar.gz", "w:gz")
    for path in ["application", "library", "public"]:
        targz.add(pathjoin(TARGET, path))
    targz.close()


def genlibrary():
    directory = pathjoin(TARGET, 'library')
    os.makedirs(directory)
    for path in glob.glob('library/*.php'):
        shutil.copy(path, directory)

    directory = pathjoin(TARGET, 'library/Zend')
    os.makedirs(directory)
    for path in glob.glob('library/Zend/*'): # will not take .git
        if (os.path.isdir(path)):
            shutil.copytree(path, pathjoin(directory, os.path.basename(path)))
        else:
            shutil.copy(path, directory)

def genmedias():
    genscripts()
    genstyles()
    genicons()
    genolmisc()
    genlibrary()
    shutil.copytree('application', pathjoin(TARGET, 'application'))
    shutil.copy('public/index.php', pathjoin(TARGET, 'public'))
    tarbuild()

createdir()
genmedias()
