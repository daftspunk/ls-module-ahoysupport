<?php

class AhoySupport_Ticket_Status_Filter extends Db_DataFilter
{
    public $model_class_name = 'AhoySupport_Ticket_Status';
    public $list_columns = array('name');

    public function applyToModel($model, $keys, $context = null)
    {
        $model->where('status_id in (?)', array($keys));
    }
}
?>