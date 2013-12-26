<?php

abstract class Moodspin_User_Manager_Abstract {

    /**
     * Instance of Model_Users
     *
     * @var Model_Users
     */
    protected $_usersModel;

    protected $_usersServiceModel;
    protected $_usersServiceFriendsModel;

    protected function __construct() {}
    protected function __clone() {}

    /**
     * Get Model_Users instance
     *
     * @return Model_Users
     */
    public function getUsersModel()
    {
        if($this->_usersModel == null){
            $this->_usersModel = new Model_Users();
        }

        return $this->_usersModel;
    }

    abstract public function getUsersServiceModel();
    abstract public function getUsersServiceFriendsModel();

    /**
     * Creates new or loads existing user
     *
     * @param array $data User data
     * @return Moodspin_User
     */
    abstract public function createUser(Array $data);

    /**
     * Return url of user's profile image
     *
     * @param Moodspin_User $identity User
     * @return string
     */
    abstract public function getProfileImageUrl(Moodspin_User $identity);

    /**
     * Save user's settings
     *
     * @param Moodspin_User $identity User
     * @param string $settingName Setting name
     * @param mixed $settingValue Setting value
     * @param boolean $serialize Serialize or not value of setting
     *
     */
    public function saveUserSettings(Moodspin_User $identity, $settingName, $settingValue, $serialize = false)
    {
        $model = Model_Manager::getModel('UsersSettings');
        $model->setUserSettings($identity->getUserId(), $settingName, $settingValue, $serialize);
    }

    /**
     * Fetch user's settings
     *
     * @param Moodspin_User $identity User
     *
     */
    public function fetchUserSettings(Moodspin_User $identity)
    {
        $model = Model_Manager::getModel('UsersSettings');
        $identity->userSettings = $model->getUserSettings($identity->getUserId());
        foreach ($identity->userSettings as $settings) {
            if ($settings['name'] == 'networkSettings') {
                $identity->setNetworkSettings(unserialize($settings['value']));
            }
        }
    }

    /**
     * Make user registered. set Creation date to now.
     *
     * @param Zend_Db_Table_Row $user
     */
    public function registerUser(Zend_Db_Table_Row $user)
    {
        if ($user->creation_date == null) {
            $user->creation_date = new Zend_Db_Expr('NOW()');
            // set default password
            $user->password = md5("password");
            $user->save();
        }
    }

    /**
     * Get big image url (or path) from user's info
     *
     * @param Moodspin_User $identity
     */
    abstract public function getLargeImage(Moodspin_User $identity);
}