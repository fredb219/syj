<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_HeadBase extends Zend_View_Helper_Placeholder_Container_Standalone
{

    /* base uri */
    protected $href;

    /* string to use as an indent of output */
    protected $_indent = '';

    public function headBase($href = null) {
        $href = (string) $href;
        if ($href == '') {
            $serverUrl = rtrim($this->view->serverUrl(), '/');
            $baseUrl = trim($this->view->baseUrl(), '/');
            $href = $serverUrl . '/' . ($baseUrl ? ($baseUrl . '/'): '');
        }
        $this->href = $href;
        return $this;
    }

    public function toString($indent = null) {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        return $indent . '<base href="' . $this->href . '">';
    }

    /* following part of the file is copied from Zend_View_Helper_Placeholder_Container_Abstract */

    /**
     * Retrieve whitespace representation of $indent
     *
     * @param  int|string $indent
     * @return string
     */
    public function getWhitespace($indent)
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        return (string) $indent;
    }

    /**
     * Set the indentation string for __toString() serialization,
     * optionally, if a number is passed, it will be the number of spaces
     *
     * @param  string|int $indent
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function setIndent($indent)
    {
        $this->_indent = $this->getWhitespace($indent);
        return $this;
    }

    /**
     * Retrieve indentation
     *
     * @return string
     */
    public function getIndent()
    {
        return $this->_indent;
    }

}
