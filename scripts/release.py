#!/usr/bin/python

__BUILD__="build"

import shutil, os, sys, subprocess, tempfile, tarfile, glob, ConfigParser
pathjoin = os.path.join

def updateversion():
    try:
        version = None
        import git
        repo = git.Repo('.')
        master = repo.commits()[0]
        tag = (filter(lambda tag: tag.commit.id == master.id, repo.tags) or [""])[0]
        if tag:
            version = tag.name
    except ImportError:
        version = subprocess.Popen(['git', 'tag', '-l', '--contains', 'master'], stdout=subprocess.PIPE).communicate()[0][:-1]
    if not version:
        raise AssertionError, "master is not tagged"

    fd, fname = tempfile.mkstemp()
    f = os.fdopen(fd, 'w')
    versionfile = 'build/application/Version.php'
    with open('build/application/Version.php') as source:
        for line in source:
            f.write(line.replace('$SYJVERSION$', version))
    f.close()
    shutil.move(fname, versionfile)

def compress(path):
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
    os.makedirs(pathjoin(__BUILD__, 'public/js'))
    config.readfp(open('application/configs/medias.ini'))
    for key, value in config.items('production'):
        if key.startswith('scripts.'):
            outpath = pathjoin(__BUILD__, 'public/js/' + key[len('scripts.'):] + '.js')
            with open(outpath, 'w') as output:
                for inpath in map(lambda p: pathjoin(tmpdir, p.strip() + '.js'), value.split(',')):
                    with open(inpath) as f:
                        output.write(f.read())
            compress(outpath)
    shutil.rmtree(tmpdir)

def install(source, target):
    if not source:
        return

    if os.path.isdir(source):
        if not target:
            target = source
        buildtarget = pathjoin(__BUILD__, target)
        parentdir = os.path.normpath(pathjoin(buildtarget, '..'))
        if not os.path.isdir(parentdir):
            os.makedirs(parentdir)
        shutil.copytree(source, buildtarget)

    elif os.path.exists(source):
        if not target:
            target = os.path.dirname(source)
        buildtarget = os.path.normpath(pathjoin(__BUILD__, target))
        if not os.path.isdir(buildtarget):
            os.makedirs(buildtarget)
        shutil.copy(source, buildtarget)

    else:
        for item in glob.glob(source):
            install(item, target)

def main():
    if os.path.isdir(__BUILD__):
        shutil.rmtree(__BUILD__, False)

    genscripts()

    for path in glob.glob('public/css/*.css'):
        install(path, None)
        compress(pathjoin(__BUILD__, 'public/css', os.path.basename(path)))

    with open("scripts/syj.install") as f:
        for line in f:
            line = line.split('#')[0].strip()
            if not line:
                continue;

            parts = line.split(' ')
            if len(parts) > 1:
                source = parts[0]
                target = parts[1]
            else:
                source = line
                target = None

            install(source, target)

    updateversion()

    print "creating syj.tar.gz"
    targz = tarfile.open("build/syj.tar.gz", "w:gz")
    for path in glob.glob(pathjoin(__BUILD__, '*')):
        targz.add(path)
    targz.close()

main()
