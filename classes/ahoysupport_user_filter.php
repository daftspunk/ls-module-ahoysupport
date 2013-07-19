<?php

class AhoySupport_User_Filter extends Db_DataFilter
{
    public $model_class_name = 'Users_User';
    public $list_columns = array('name');

    public function applyToModel($model, $keys, $context = null)
    {
        $model->where('user_id in (?) OR user_id is null', array($keys));
    }
}
?>