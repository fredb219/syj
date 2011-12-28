<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Canvas {
    protected $picture;

    protected $width;
    protected $height;
    protected $margint;
    protected $marginr;
    protected $marginb;
    protected $marginl;

    protected $minx;
    protected $miny;
    protected $maxx;
    protected $maxy;
    protected $xdelta;
    protected $ydelta;

    protected $fontfile;
    protected $fontsize;

    function __construct($width, $height, $margint, $marginr, $marginb, $marginl) {
        $this->width = $width;
        $this->height = $height;
        $this->margint = $margint;
        $this->marginr = $marginr;
        $this->marginb = $marginb;
        $this->marginl = $marginl;

        $this->minx = 0;
        $this->miny = 0;
        $this->maxx = $this->width;
        $this->maxy = $this->height;

        $this->picture = imagecreatetruecolor ($this->width, $this->height);
    }

    function __destruct() {
        if ($this->picture) {
            imagedestroy ($this->picture);
        }
    }

    function setViewBox($minx, $miny, $maxx, $maxy) {
        $this->xdelta = pow(10, floor(log10($maxx)));
        $this->maxx = ceil ($maxx / $this->xdelta) * $this->xdelta;
        $this->minx = floor ($minx / $this->xdelta) * $this->xdelta;

        $this->ydelta = pow(10, floor(log10($maxy)));
        $this->maxy = ceil ($maxy / $this->ydelta) * $this->ydelta;
        $this->miny = floor ($miny / $this->ydelta) * $this->ydelta;

        return $this;
    }

    function getViewBox() {
        return array($this->minx, $this->miny, $this->maxx, $this->maxy);
    }

    function setThickness($thickness) {
        imagesetthickness ($this->picture, $thickness);

        return $this;
    }

    function setFont($fontfile, $fontsize) {
        $this->fontfile = $fontfile;
        $this->fontsize = $fontsize;

        return $this;
    }

    function text($x, $y, $text, $color, $pos = "lt", $limits = array(null, null, null, null)) {
        $bbox = imagettfbbox($this->fontsize, 0, $this->fontfile, $text);

        switch (substr($pos, 0, 1)) {
            case "r":
                $x -= $bbox[2] - $bbox[0];
            break;
            case "c":
                $x -= ($bbox[2] - $bbox[0]) / 2;
            break;
            case "l":
            default:
            break;
        }

        switch (substr($pos, 1, 1)) {
            case "b":
                $y -= $bbox[7] - $bbox[1];
            break;
            case "c":
                $y -= ($bbox[7] - $bbox[1]) / 2;
            break;
            case "t":
            default:
            break;
        }

        list($limtop, $limright, $limbottom, $limleft) = $limits;

        if (!is_null($limtop)) {
            $y = max($limtop, $y);
        }
        if (!is_null($limright)) {
            $x = min($limright, $x);
        }
        if (!is_null($limbottom)) {
            $y = min($limbottom, $y);
        }
        if (!is_null($limleft)) {
            $x = max($limleft, $x);
        }

        imagettftext($this->picture, $this->fontsize, 0, $x, $y, $this->color($color), $this->fontfile, $text);
    }

    function rect($x1, $y1, $x2, $y2, $color, $translate = false) {
        imagefilledrectangle ($this->picture, $x1, $y1, $x2, $y2, $this->color($color));

        return $this;
    }

    function line($x1, $y1, $x2, $y2, $color) {
        imageline ($this->picture, $x1, $y1, $x2, $y2, $this->color($color));

        return $this;
    }

    function draw($filename = null) {
        imagepng($this->picture, $filename);

        return $this;
    }

    private $colorcache = array();
    function color($str) {
        if (!isset ($this->colorcache[$str])) {
            $r = "0x" . substr($str, 0, 2);
            $g = "0x" . substr($str, 2, 2);
            $b = "0x" . substr($str, 4, 2);
            $this->colorcache[$str] = imagecolorallocate ($this->picture, $r, $g, $b);
        }
        return $this->colorcache[$str];
    }

    function canvasXPos($x) {
        return round(($x - $this->minx) * ($this->width - $this->marginr - $this->marginl) / ($this->maxx - $this->minx)) + $this->marginl;
    }
    function canvasYPos($y) {
        return $this->height - round(($y - $this->miny) * ($this->height - $this->marginb - $this->margint) / ($this->maxy - $this->miny)) - $this->margint;
    }
}

class ProfileController extends Zend_Controller_Action {
    public function init() {
        if (!extension_loaded('gd')) {
            throw new Syj_Exception_NotImplemented("gd is not installed");
        }
        $this->_helper->SyjNoRender->disableRender();
    }

    public function indexAction() {
        $url = $this->getRequest()->getUserParam('url');
        if (!isset($url)) {
            throw new Syj_Exception_NotFound('Not Found', 404);
        }

        $pathMapper = new Syj_Model_PathMapper();
        $path = new Syj_Model_Path();
        if (!$pathMapper->findByUrl($url, $path)) {
            if (is_numeric($url) and $pathMapper->hasexisted($url)) {
                throw new Syj_Exception_NotFound('Gone', 410);
            } else {
                throw new Syj_Exception_NotFound('Not Found', 404);
            }
        }

        $size = $this->getRequest()->getQuery('size', 'big');
        if ($size == 'small') {
            $width = 300;
            $height = 225;
        } else {
            $width = 800;
            $height = 600;
            $size = 'big';
        }

        $file = $path->getProfileCache($size);
        if (file_exists($file)) {
            if (filesize($file) == 0) {
                throw new Syj_Exception_NotImplemented("could not compute altitude profile");
            }
            $this->sendFile($file);
            return;
        }

        try {
            $service = $this->_helper->SyjAltiService->service();
            /* we accept 2% of invalid values in the profile */
            $profile = $path->getAltiProfile($service, 2 / 100);
        } catch(Syj_Exception_NotImplemented $e) {
            @touch($file);
            throw $e;
        }

        $canvas = $this->drawProfile($profile, $width, $height);
        $canvas->draw($file);
        $this->sendFile($file);
    }

    protected function drawProfile($profile, $width, $height) {
        $margint = 50;
        $marginr = 50;
        $marginb = 50;
        $marginl = 50;

        $last = end($profile);
        $maxdist = $last[0];

        $minalti = array(array(0, INF));
        $maxalti = array(array(0, -INF));
        foreach ($profile as $coords) {
            $alti = $coords[1];

            if ($alti == $minalti[0][1]) {
                $minalti []= array($coords[0], $alti);
            } else if ($alti < $minalti[0][1]) {
                $minalti = array(array($coords[0], $alti));
            }

            if ($alti == $maxalti[0][1]) {
                $maxalti []= array($coords[0], $alti);
            } else if ($alti > $maxalti[0][1]) {
                $maxalti = array(array($coords[0], $alti));
            }
        }

        $canvas = new Canvas($width, $height, $margint, $marginr, $marginb, $marginl);
        $canvas->setViewBox(0, $minalti[0][1], $maxdist, $maxalti[0][1]);

        $canvas->rect(0, 0, $width, $height, "FFFFFF");

        $slopecolor = function ($slope) {
            if ($slope > 18) {
                return "FF0000"; // red
            } else if ($slope > 12) {
                return "FF4000";
            } else if ($slope > 7) {
                return "FF8000";
            } else if ($slope > 3) {
                return "FFC000";
            } else if ($slope > 0) {
                return "FFFF00";  // yellow
            } else if ($slope > -3) {
                return "00FFFF";
            } else if ($slope > -7) {
                return "00C0FF";
            } else if ($slope > -12) {
                return "0080FF";
            } else if ($slope > -18) {
                return "0040FF";
            } else {
                return "0000FF"; // blue;
            }
        };

        $canvas->setThickness (2);

        $prev = null;
        foreach ($profile as $coord) {
            if (!is_null($prev)) {
                $dist = $coord[0];
                $alti = $coord[1];
                $prevdist = $prev[0];
                $prevalti = $prev[1];

                $slope = 100 * ($alti - $prevalti) / ($dist - $prevdist) ;
                $canvas->line($canvas->canvasXPos($prevdist), $canvas->canvasYPos($prevalti),
                              $canvas->canvasXPos($dist), $canvas->canvasYPos($alti),
                              $slopecolor($slope), true);
            }
            $prev = $coord;
        }

        $canvas->setThickness (1);
        $canvas->setFont(APPLICATION_PATH . '/resources/' . 'DejaVuSans.ttf', 10);

        list ($minx, $miny, $maxx, $maxy) = $canvas->getViewBox();

        $deltax = pow(10, floor(log10($maxx - $minx)));
        foreach (range($minx, $maxx, $deltax) as $x) {
            $canvas->line($canvas->canvasXPos($x), $height - $marginb + 10, $canvas->canvasXPos($x), $height - $marginb, "000000");
            if ($deltax < 1000) {
                $text = $x . "m";
            } else {
                $text = round($x / 1000) . "km";
            }
            $canvas->text($canvas->canvasXPos($x), $height - $marginb + 10 + 3, $text, "000000", "cb");
        }
        $canvas->line($marginl, $height - $marginb, $width - $marginr, $height - $marginb, "000000");

        $deltay = pow(10, floor(log10($maxy - $miny)));
        foreach (range($miny, $maxy, $deltay) as $y) {
            $canvas->line($marginl - 10, $canvas->canvasYPos($y), $marginl, $canvas->canvasYPos($y), "000000");
            $text = $y . "m";
            $canvas->text($marginl - 10 - 3, $canvas->canvasYPos($y), $text, "000000", "rc");
        }
        $canvas->line($marginl, $height - $marginb, $marginr, $margint, "000000");

        list($minx, $miny, $maxx, $maxy) = $canvas->getViewBox();
        $limits = array($canvas->canvasYPos($maxy) + 3, $canvas->canvasXPos($maxx) - 3, $canvas->canvasYPos($miny) - 3, $canvas->canvasXPos($minx) + 3);

        $prev = null;
        foreach ($minalti as $coords) {
            list ($dist, $alti) = $coords;
            if (!is_null($prev)) {
                $xdist = $canvas->canvasXPos($dist) - $canvas->canvasXPos($prev[0]);
                /* second label would be less than 10px from previous one. Do
                not display it */
                if ($xdist < 10) {
                    continue;
                }
            }
            $text = round($alti) . "m";
            $xpos = $canvas->canvasXPos($dist);
            $ypos = $canvas->canvasYPos($alti);
            $canvas->text($xpos, $ypos + 5, $text, "000000", "cb", $limits);
            $canvas->rect($xpos - 1, $ypos - 1, $xpos + 1, $ypos + 1, "000000");
            $prev = $coords;
        }

        $prev = null;
        foreach ($maxalti as $coords) {
            list ($dist, $alti) = $coords;
            if (!is_null($prev)) {
                $xdist = $canvas->canvasXPos($dist) - $canvas->canvasXPos($prev[0]);
                /* second label would be less than 10px from previous one. Do
                not display it */
                if ($xdist < 10) {
                    continue;
                }
            }
            $text = round($alti) . "m";
            $xpos = $canvas->canvasXPos($dist);
            $ypos = $canvas->canvasYPos($alti);
            $canvas->text($xpos, $ypos - 5, $text, "000000", "ct", $limits);
            $canvas->rect($xpos - 1, $ypos - 1, $xpos + 1, $ypos + 1, "000000");
            $prev = $coords;
        }

        return $canvas;
    }

    protected function sendFile($file) {
        $lastmodified = filemtime($file);

        $request = $this->getRequest();
        $response = $this->getResponse();

        $response->setHeader('Content-Type', 'image/png', true)
                 ->setHeader('Content-Length', filesize($file), true);

        if ($request->getServer("HTTP_IF_MODIFIED_SINCE")) {
            if ($lastmodified <= strtotime($request->getServer("HTTP_IF_MODIFIED_SINCE"))) {
                $response->setHttpResponseCode(304);
                return;
            }
        }

                 // no-cache is needed otherwise IE does not try to get new version.
        $response->setHeader ('Cache-control', 'no-cache, must-revalidate', true)
                 ->setHeader("Last-Modified", gmdate("D, d M Y H:i:s", filemtime($file)) . " GMT", true);

        readfile($file);
    }
}
