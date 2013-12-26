<?php

/**
 * MoodCloudController - displays moods as tag cloud
 *
 * @author
 * @version
 */

class MoodCloudController extends Zend_Controller_Action
{
    const MAX_SIZE = 3;
    const MIN_SIZE = 1;
    const ORDER_FIELD = 'name';
    
    /**
     * Displays moods as tag cloud.
     */
    public function indexAction()
    {
	    $this->view->moodId = $this->_request->getParam('mood_id');
        $useCache = false;
        if (Zend_Registry::isRegistered('cacheLong')) {
            $cache = Zend_Registry::get('cacheLong');
            $useCache = true;
        }
        if (!$useCache || ($useCache && !$cloud = $cache->load('m_moodCloud'))) {
	        
	        $modelMoods = new Model_Moods();
	        
	        $cloud = array();
	        
	        $minSize = -1;
	        $maxSize = 0;
	        
	        $query = $modelMoods->getAdapter()
	                            ->select()
	                            ->from(
	                                array(
	                                    'rm' => 'report_moods'
	                                ),
	                                array(
	                                    'id'   => 'mood_id',
	                                    'name' => 'mood_name',
	                                    'size' => 'used_num'
	                                )
	                            )
	                            ->order('mood_name');
	        $moods = $modelMoods->getAdapter()->fetchAll($query);
	
	        foreach ($moods as $mood) {
	            if ($mood['id'] != Model_Moods::EMPTY_MOOD_ID) {
	                if ($mood['size'] > $maxSize) {
	                    $maxSize = $mood['size'];
	                }
	                if ($mood['size'] < $minSize || $minSize == -1) {
	                    $minSize = $mood['size'];
	                }
	                $cloud[] = $mood;
	            }
	        }
	        
	        foreach ($cloud as &$item) {
	            if ($maxSize != $minSize) {
	                $size = self::MIN_SIZE + (self::MAX_SIZE - self::MIN_SIZE) * ($item['size'] - $minSize) / ($maxSize - $minSize);
	            } else {
	                $size = self::MIN_SIZE;
	            }
	            $item['size'] = sprintf('%.1f', $size);
	        }
	        if ($useCache) {
                $cache->save($cloud,'m_moodCloud');
	        }
        }
        
        $this->view->cloud = $cloud;
    }
    
}