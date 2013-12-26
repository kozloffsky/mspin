<?php

class Model_UsersTwitterLoggedIn extends Moodspin_Db_Trigger_Table
{
    protected $_name = 'users_twitter_logged_in';
    
    /**
     * get count of users in db
     * @return int
     */
    public function getUsersCount()
    {
        $query = $this->select()
                      ->from(
                            array($this->_name),
                            array('count' => new Zend_Db_Expr('count(id)'))
                      );        
        return (int)$this->fetchRow($query)->count;
    }
    /**
     * get users to process
     * 
     * @param int $offset
     * @return Zend_Db_Table_Rowset
     */
    public function getUsers($offset=1,$perPage=10)
    {
        $query = $this->select()
                      ->from(
                            array($this->_name)
                      )
                      ->limitPage($offset,$perPage);
        return $this->fetchAll($query);
    }
    public function deleteByTwitterId($twitterId)
    {
    	$this->delete('twitter_id = ' . (int)$twitterId);
    }
}