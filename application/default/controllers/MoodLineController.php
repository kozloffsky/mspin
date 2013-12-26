<?php
require_once ('Moodspin/Controller/Action/Json.php');
class MoodLineController extends Moodspin_Controller_Action_Json
{

    public function indexAction()
    {
        $username = (string)$this->_getParam('username');

        $modelUsers = Model_Manager::getModel('Users');
        $user = $modelUsers->fetchRow($modelUsers->select()->where('login=?',$username));
        if ($user == null) {
        	$this->_redirect('/');
        }


        $page = (int)$this->_getParam('page',1);

        $modelMoods = Model_Manager::getModel('Moods');

        $dateRange = $this->_getDateRange($page);
        $moodLine = new Moodspin_Moodline();
        $moodLine->setEndDate(date('Y-m-d H:i:s',$dateRange['end'][0]));

        $select = $modelMoods->select()->setIntegrityCheck(false)
                             ->joinLeft(
                                 array('s'=>'statuses'),'moods.id=s.mood_id'
                             )
                             ->joinLeft(
                                 array('m'=>'moods'),'s.mood_id=m.id'
                             )
                             ->where('s.user_id=?',$user->id)
                             ->where('s.date > ?', date('Y-m-d H:i:s',$dateRange['start'][0]))
                             ->where('s.date < ?', date('Y-m-d H:i:s',$dateRange['end'][0]))
                             ->order('s.date');
         $result = $modelMoods->fetchAll($select);

         $prevWeekMood = null;

         $select = $modelMoods->select()->setIntegrityCheck(false)
                ->joinLeft(array('m'=>'moods'),'moods.id=m.id')
                ->joinLeft(array('s'=>'statuses'),'m.id=s.mood_id')
                ->where('s.user_id=?',$user->id)
                ->where('s.date < ?', date('Y-m-d H:i:s',$dateRange['start'][0]))
                ->order('s.date DESC')->limit(1);

             $result1 = $modelMoods->fetchRow($select);
             if ($result1 != null) {
                 if($result1->mood_category_id == 1){
                     $prevWeekMood = $result1;
                 }
             }

         $weekText = substr($dateRange['start']['month'],0,3) . ' ';
         $weekText.= $dateRange['start']['mday'] . '-';
         $weekText.= substr($dateRange['end']['month'],0 ,3) . ' ';
         $weekText.= $dateRange['end']['mday'] . ' ' . $dateRange['end']['year'];

        if($prevWeekMood != null) {
            $prevWeekMood->date = date('Y-m-d H:i:s', $dateRange['start'][0]);
            $moodLine->addMood($prevWeekMood, false);
            $prevWeekMood = null;
        }

        foreach ($result as $row) {
            $moodLine->addMood($row);
        }

         // we didn`t find any results, now we need to know is last status is color, so we will remove one
         // where from select and will find it.
         if ($result->count() == 0) {
             $select = $modelMoods->select()->setIntegrityCheck(false)
                ->joinLeft(array('m'=>'moods'),'moods.id=m.id')
                ->joinLeft(array('s'=>'statuses'),'m.id=s.mood_id')
                ->where('s.user_id=?',$user->id)
                ->where('s.date < ?', date('Y-m-d H:i:s',$dateRange['end'][0]))
                ->order('s.date DESC')->limit(1);

             $result = $modelMoods->fetchRow($select);
             if ($result != null) {
                 if ($result->mood_category_id == 1) {
                     $result->date = date('Y-m-d H:i:s',$dateRange['start'][0]-3600);
                     $moodLine->showMoods(false);
                     $moodLine->addMood($result);
                 }
             }
         }

         $this->view->data = array(
                     $moodLine->toArray(),
                     array(
                     	'page'=>$page,
                     	'date'=>$weekText,
                     	'current'=> $dateRange['current']
                     )
                 );
    }

    protected function _getDateRange($page){
        $seconds_in_day = 24 * 60 * 60;

        $currentTimestamp = time() - $page * 7 * $seconds_in_day;
        $cd = getdate($currentTimestamp);

        $start = getdate($currentTimestamp - ($cd['wday'] * $seconds_in_day) - $cd['seconds'] - $cd['minutes']*60 - $cd['hours'] * 3600);
        $end = getdate($currentTimestamp + (6 - $cd['wday']) * $seconds_in_day + (59 - $cd['seconds']) + (59 -$cd['minutes'])*60 + (23 - $cd['hours']) * 3600);

        if($start['hours'] !== 0 || $start['minutes'] !== 0  || $start['seconds'] !== 0 ) {
            $start = getdate(strtotime($start['year'] . '-' . $start['mon'] . '-' . $start['mday'] . ' 00:00:00'));
        }

        $currentDay = -1;
        if($page == 0){
            $currentDay = $cd['wday'];
        }

        return array('start' => $start, 'end' => $end, 'current'=>$currentDay);
    }
}
?>