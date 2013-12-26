<?php

class TwitterIPhoneUsersTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{

    public function run()
    {
        $twitter  = Moodspin_Service::getInstance()->getService('Twitter');
        $query = 'moodspin';
        $parameters = array('page' => 1, 'rpp' => 100);

        $users = array();
        $total_updated = 0;
        while(true) {
            try {
                $this->getLogger()->info('Page: ' .$parameters['page']);
                $result = $twitter->search($query, $parameters);
                if(!empty($result['results'])) {
                    foreach($result['results'] as $info) {
                        if(!empty($info['source']) && strpos($info['source'], 'API') !== false && strpos($info['text'], 'http://moodspin.com/') !== false) {
                            $users[$info['from_user']] = true;
                        }
                    }
                }

                if(!empty($result['next_page'])) {
                    parse_str(parse_url($result['next_page'], PHP_URL_QUERY), $parameters);
                    unset($parameters['q']);
                } else {
                    break;
                }
            } catch (Exception $e) {
                $this->getLogger()->info('Exception while search: ' . $e->getMessage());
            }
        }

        $total_updated = $this->_updateUsers(array_keys($users));

        $this->getLogger()->info('Total users founded:  ' . count($users));
        $this->getLogger()->info('Total users updated:  ' . $total_updated);
    }

    private function _updateUsers($users) {
        $total_updated = 0;
        $modelUser = Model_Manager::getModel('Users');
        foreach ($users as $user) {
            try {
                $row = $modelUser->fetchRow($modelUser->select()->where('login=?',$user));
                if($row) {
                    $row->using_iphone = 1;
                    $row->save();
                    $total_updated++;
                }
            } catch(Exception $e) {
                $this->getLogger()->info("Can't update user: " . $user . ' Exception: ' . $e->getMessage());
                continue;
            }
        }

        return $total_updated;
    }
}


