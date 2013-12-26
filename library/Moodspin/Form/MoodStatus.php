<?php
class Moodspin_Form_MoodStatus extends Zend_Form
{
    protected $_message;
    protected $_moodId;
    
    public function init ()
    {
        $this->removeDecorator('DtDdWrapper');
        $this->_message = new Zend_Form_Element_Textarea('message');
        $this->_moodId = new Zend_Form_Element_Hidden('mood_id');

        $this->_message->addValidator(new Zend_Validate_StringLength(1, 140));
        
        $this->_message->setAttribs(array('cols' => 50 , 'rows' => 2));
        
        $this->_removeDecorators($this->_message);
        $this->_removeDecorators($this->_moodId);
        
        $this->addElement($this->_message);
        $this->addElement($this->_moodId);
    }

    public function getMessageElement ()
    {
        return $this->_message;
    }

    public function getMoodIdElement ()
    {
        return $this->_moodId;
    }

    protected function _removeDecorators (Zend_Form_Element $element)
    {
        $element->removeDecorator('DtDdWrapper');
        $element->removeDecorator('Label');
        $element->removeDecorator('HtmlTag');
    }
}
