<?php
/**
 * ReportUsers
 *  
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 0.1
 */

class Model_ReportUsers extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'report_users';
    
    /**
     * get users by mood id
     * folowers and/or followings
     * 
     * @param int|array $moodIds
     * @param int $userId
     * @param int $followings 1|2|3
     * @param int $offset
     * @param int $count
     * @return array
     */
    public function getFriendUsersByMoodIds($moodIds,$userId,$followings=MyNetworkController::NETWORK_BOTH_FOLLOW,$offset=0,$count=MyNetworkController::NETWORK_PAGE_COUNT)
    {
        if(empty($moodIds)) {
            return array();
        } elseif (is_array($moodIds)) {
            $moodStr = "`latest_mood_id` IN (" . implode(",",$moodIds) . ")";
        } else {
            $moodStr = "`latest_mood_id` = {$moodIds}";
        }
        switch ($followings) {
            case MyNetworkController::NETWORK_THEY_FOLLOW:
                $where1 = "`ru`.`twitter_id` = `utf`.`twitter_id`";
                $where2 = "`utf`.`follower_id` = {$userId}";
                break;
            case MyNetworkController::NETWORK_I_FOLLOW:
                $where1 = "`ru`.`twitter_id` = `utf`.`follower_id`";
                $where2 = "`utf`.`twitter_id` = {$userId}";
                break;
            case MyNetworkController::NETWORK_BOTH_FOLLOW:
            default:
                $where1 = "`ru`.`twitter_id` = `utf`.`follower_id` OR
                         `ru`.`twitter_id` = `utf`.`twitter_id`";
                $where2 = "`utf`.`follower_id` = {$userId} OR
                         `utf`.`twitter_id` = {$userId}";
                break;
        }
        $sql = "
            SELECT
                *
            FROM
                (
                SELECT
                    `ru`.*
                FROM
                    `users_twitter_followers` AS `utf`
                    JOIN `report_users` AS `ru` ON
                        {$where1}
                WHERE
                    {$where2}
                GROUP BY
                    `ru`.`id`
                ) AS `ru`
            WHERE
                {$moodStr}
                AND `ru`.`twitter_id` != {$userId}
            LIMIT
                {$offset},{$count};
        ";
        $result = $this->getAdapter()->fetchAll($sql,null,Zend_Db::FETCH_ASSOC);
        return $result;
    }
    
    public function getMoodspinUsers($offset=0,$count=100,$sort='creation_date',$sortOrder="DESC")
    {
        $query = $this->select()
                      ->from(
                        array($this->_name),
                        array(
                            'login',
                            'creation_date',
                            'followers_on_moodspin',
                            'following_on_moodspin'
                        )
                      )
                      ->where('creation_date IS NOT NULL')
                      ->where('followers_on_moodspin > 0 OR following_on_moodspin > 0')
                      ->order($sort . " " . $sortOrder);
                      
        $oPaginator = Zend_Paginator::factory($query);
        $oPaginator->setCurrentPageNumber($offset);
        $oPaginator->setItemCountPerPage($count);
        
        return $oPaginator;
    }
    
    public function getMoodspinTweetsCount()
    {
        $query = $this->select()->setIntegrityCheck(false)
                      ->from(
                         array($this->_name),
                         array('tweetsCount' => new Zend_Db_Expr("SUM(status_count)"))
                      )
                      ->where('latest_mood_id <> ?',Model_Moods::EMPTY_MOOD_ID)
                      ->where("creation_date IS NOT NULL");
        return (int)$this->fetchRow($query)->tweetsCount;
    }

    /**
     * returns modspin users report
     *
     * @param $registered - whether to get registered users
     * @param $fromDate   - from date tweeted
     * @param $toDate     - to date tweeted
     * @param $page       - current page
     * @param $perPage    - count per page
     * @param $moods      - array of moods ids to search for
     * @param $minFollowers - min followers number
     * @param $maxFollowers - max followers number
     *
     * @return Zend_Paginator
     */
    public function getUsersReport(
        $registered,$fromDate,$toDate,$page=0,$perPage=10,
        // for potential users
        $moods=null,$minFollowers=null,$maxFollowers=null,
        // sorting
        $sort='login',$sortOrder='ASC')
    {
        $query = $this->getAdapter()->select()
                      ->from($this->_name);
        if ($registered) {
            $query->where('creation_date IS NOT NULL');
            $query->where('creation_date > ?', $fromDate);
            $query->where('creation_date <= ?', $toDate);
        } else {
            $query->where('creation_date IS NULL');
            $query->where('latest_status_date >= ?', $fromDate);
            $query->where('latest_status_date < ?', $toDate);
            if ($minFollowers)
                $query->where('followers_num >= ?',(int)$minFollowers);
            if ($maxFollowers)
                $query->where('followers_num < ?',(int)$maxFollowers);

            if ($moods) {
                $query->where('latest_mood_id IN (' . implode(',',$moods) . ')');
            } elseif (!$moods && !$registered) {
                $query->where('id = 0');
            }
        }

        if ($sort && $sortOrder) {
            $query->order($sort . ' ' . $sortOrder);
        }

        $oPaginator = Zend_Paginator::factory( $query );
        $oPaginator->setCurrentPageNumber( $page );
        $oPaginator->setItemCountPerPage( $perPage );

        return $oPaginator;
    }
}
