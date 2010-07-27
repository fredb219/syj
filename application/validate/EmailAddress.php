<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Validate_EmailAddress extends Zend_Validate_EmailAddress {
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->setMailValidatorMessage();
    }

    protected function setMailValidatorMessage($message = null) {
        if (!$message) {
            $message = __("Invalid email");
        }

        foreach (array(Zend_Validate_EmailAddress::INVALID,
                    Zend_Validate_EmailAddress::INVALID_FORMAT,
                    Zend_Validate_EmailAddress::INVALID_HOSTNAME,
                    Zend_Validate_EmailAddress::INVALID_MX_RECORD,
                    Zend_Validate_EmailAddress::INVALID_SEGMENT,
                    Zend_Validate_EmailAddress::DOT_ATOM,
                    Zend_Validate_EmailAddress::QUOTED_STRING,
                    Zend_Validate_EmailAddress::INVALID_LOCAL_PART,
                    Zend_Validate_EmailAddress::LENGTH_EXCEEDED,

                    Zend_Validate_Hostname::INVALID,
                    Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED,
                    Zend_Validate_Hostname::UNKNOWN_TLD,
                    Zend_Validate_Hostname::INVALID_DASH,
                    Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA,
                    Zend_Validate_Hostname::UNDECIPHERABLE_TLD,
                    Zend_Validate_Hostname::INVALID_HOSTNAME,
                    Zend_Validate_Hostname::INVALID_LOCAL_NAME,
                    Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED,
                    Zend_Validate_Hostname::CANNOT_DECODE_PUNYCODE) as $key) {
            $this->setMessage($message, $key);
        }
    }

    public function isValid($value) {
        $valid = parent::isValid($value);
        // only one error message
        if (!$valid) {
            $this->_messages = array(current($this->_messages));
            $this->_errors = array(current($this->_errors));
        }
        return $valid;
    }
}

?>
