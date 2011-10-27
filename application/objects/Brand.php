<?php
class Brand extends Zend_Db_Table_Row
{
    function getTopMissions($topNum = 3)
    {
        $select = $this->select()->order('create_date desc')->limit($topNum);
        return $this->findDependentRowset('Missions', 'BelongTo', $select);
    }
}
