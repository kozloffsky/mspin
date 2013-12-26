<?php

require_once 'Moodspin/Db/Table.php';

class Model_Themes extends Moodspin_Db_Trigger_Table 
{

    protected $_name = 'themes';

    protected $_referenceMap = array(
        'Mood' => array(
            'columns'        => array('mood_id'),
            'refTableClass'  => 'Model_Moods',
            'refColumns'     => array('id'), 
        )
    );

}