<?php
class Miho_Cms_Event 
{
    private $_type;

    public function getType()
    {
        return $this->_type;
    }

    public function __construct($type)
    {
        $this->_type=$type;
    }
}

?>