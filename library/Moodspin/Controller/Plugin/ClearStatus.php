<?php

class Moodspin_Controller_Plugin_ClearStatus extends Zend_Controller_Plugin_Abstract
{

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $flash = new Zend_Session_Namespace('status');
        if(!empty($flash->isViewed)) {
            $flash->type = null;
            $flash->message = null;
            $flash->statusMessage = null;
            $flash->currentMood = null;
            $flash->isViewed = false;
        }

        unset($flash);
    }

}
