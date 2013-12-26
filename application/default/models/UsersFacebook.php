<?php

class Model_UsersFacebook extends Moodspin_Db_Trigger_Table
{
    /**
     * Table name
     *
     * @var string
     */
    protected $_name = 'users_facebook';

    protected $_referenceMap = array(
        'User' => array(
            'columns'        => array('user_id'),
            'refTableClass'  => 'Model_Users',
            'refColumns'     => array('id'),
        )
    );

    /**
     * Checks, if user with $facebookId is in database, then returns object of this user
     * else returns empty user
     *
     * @param int $facebookId
     * @return Zend_Db_Table_Row
     */
    public function isUserIdRegistered($facebookId)
    {
    	$rowset = $this->find($facebookId);

    	if($rowset->count() < 1){
    		return $this->createRow(array('user_id'=> 0 ));
    	}

    	return $rowset->current();
    }

    public function getUserIdByFacebookId($id)
    {
        return $this->find($id)->current()->user_id;
    }
}
