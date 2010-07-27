<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

require_once 'Zend/Form/Decorator/Description.php';
require_once 'Zend/Form/Decorator/HtmlTag.php';

/*
 * wraps description content in a <td> tag then in a <tr> tag
 */
class Syj_Form_Decorator_TableDescription extends Zend_Form_Decorator_Description
{

    public function getClass()
    {
        $class = $this->getOption('class');
        if (null === $class) {
            $class = 'message';
            $this->setOption('class', $class);
        }

        return $class;
    }

    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $description = $element->getDescription();
        $description = trim($description);

        if (!empty($description) && (null !== ($translator = $element->getTranslator()))) {
            $description = $translator->translate($description);
        }

        if (empty($description)) {
            return $content;
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $class     = $this->getClass();
        $escape    = $this->getEscape();

        $options   = $this->getOptions();

        if ($escape) {
            $description = $view->escape($description);
        }

        $decorator = new Zend_Form_Decorator_HtmlTag(array('tag' => 'div',
                            'id'  => $this->getElement()->getName() . '-desc',
                            'class' => $class));
        $description = $decorator->render($description);
        $decorator = new Zend_Form_Decorator_HtmlTag(array('tag' => 'td'));
        $description = $decorator->render($description);
        $description = '<td>&nbsp;</td>' . $description;
        $decorator = new Zend_Form_Decorator_HtmlTag(array('tag' => 'tr'));
        $description = $decorator->render($description);

        switch ($placement) {
            case self::PREPEND:
                return $description . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $description;
        }
    }

}
