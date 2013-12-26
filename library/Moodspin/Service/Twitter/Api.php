<?php

class Moodspin_Service_Twitter_Api extends Moodspin_Service_Twitter {

    private $_username;
    private $_password;

    private $_zendConnector;

    public function setUsername($username)
    {
        $this->_username = $username;
    }

    protected function getZendConnector()
    {
        if($this->_zendConnector === null) {
            $this->_zendConnector = new Zend_Service_Twitter($this->_username, $this->_password);
        }

        return $this->_zendConnector;
    }

    protected function isCredentialsSet()
    {
        if(empty($this->_username) || empty($this->_password))
        {
            return false;
        }

        return true;
    }

    public function setPassword($password)
    {
        $this->_password = $password;
    }

    public function getFollowersIds($twitterId)
    {
        if(!$this->isCredentialsSet()) {
            return array();
        }

        if(empty($this->_followersIds)) {
            $result = $this->getZendConnector()->userFollowers(true);

            foreach($result as $item) {
                $this->_followersIds[] = (int) $item->id;
            }
        }

        return $this->_followersIds;
    }

    public function getFollowingIds($twitterId)
    {
        if(!$this->isCredentialsSet()) {
            return array();
        }

        if(empty($this->_followingIds)) {
            $result = $this->getZendConnector()->userFriends();

            foreach($result as $item) {
                $this->_followingIds[] = (int) $item->id;
            }
        }

        return $this->_followingIds;
    }

    public function followingSystem($twitterId) {
        if(!$this->isCredentialsSet())
        {
            throw new Exception('User credentials not set');
        }

        if(!$this->isFollowingSystem($twitterId)) {
            $result = $this->getZendConnector()->friendship->create($this->getSystemId());
            return $result->status;
        }

        return true;
    }

    public function unFollowingSystem($twitterId) {
        if(!$this->isCredentialsSet())
        {
            throw new Exception('User credentials not set');
        }

        if($this->isFollowingSystem($twitterId)) {
            $result = $this->getZendConnector()->friendship->destroy($this->getSystemId());
            return $result->status;
        }

        return true;
    }
}
