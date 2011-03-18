<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Exception_InvalidEmail extends Syj_Exception_Request
{
     protected $_message = 'invalidemail';

     // Redefine the exception so message isn't optional
     public function __construct($msg = '', $code = 0, Exception $previous = null) {
        parent::__construct($msg ?: $this->_message, $code, $previous);
     }

}
