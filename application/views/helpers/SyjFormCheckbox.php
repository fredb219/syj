<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

require_once 'Zend/View/Helper/FormCheckbox.php';

class Syj_View_Helper_SyjFormCheckbox extends Zend_View_Helper_FormCheckbox
{
    public function SyjFormCheckbox($name, $value = null, $attribs = null, array $checkedOptions = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable

        $checked = false;
        if (isset($attribs['checked']) && $attribs['checked']) {
            $checked = true;
            unset($attribs['checked']);
        } elseif (isset($attribs['checked'])) {
            $checked = false;
            unset($attribs['checked']);
        }

        $checkedOptions = self::determineCheckboxInfo($value, $checked, $checkedOptions);

        // is the element disabled?
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }

        // build the element
        $xhtml = '';
        if (!$disable && !strstr($name, '[]')) {
            //XXX: we have just copied Zend_View_Helper_FormCheckbox, and
            // commented this line.
//            $xhtml = $this->_hidden($name, $checkedOptions['uncheckedValue']);
        }
        $xhtml .= '<input type="checkbox"'
                . ' name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . ' value="' . $this->view->escape($checkedOptions['checkedValue']) . '"'
                . $checkedOptions['checkedString']
                . $disabled
                . $this->_htmlAttribs($attribs)
                . $endTag;

        return $xhtml;

    }
}
