<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

namespace pwdgen;

class PwdGenerator {
    protected static $_allowed = "0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    public static function generate($length = 8, $allowed = null) {
        if (!$allowed or !is_string($allowed)) {
            $allowed = self::$_allowed;
        }

        return array_reduce(range(0, $length), function($res) use ($allowed) {
            return $res . $allowed[mt_rand(0,strlen($allowed) - 1)];
        }, "");
    }
}
