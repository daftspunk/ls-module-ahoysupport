<?php

class AhoySupport_Ticket_Status_Log extends Db_ActiveRecord
{
    public $table_name = 'ahoysupport_ticket_status_log';
    
    public $belongs_to = array(
        'ticket'=>array('class_name'=>'Support_Ticket', 'foreign_key'=>'ticket_id'),
    );
    
    public static function create()
    {
        return new self();
    }
    
    public function define_columns($context = null)
    {
        $this->define_column('created_at', 'Created At');
    }       
    
    public static function find_last_status_change($status_id, $ticket_id)
    {
        $obj = self::create();
        $obj->where('status_id=?', $status_id);
        $obj->where('ticket_id=?', $ticket_id);
        $obj->order('id desc');

        return $obj->find();
    }
    
    public static function find_last_assignee_change($ticket_id)
    {
        $obj = self::create();
        $obj->where('assign_user_id is not null');
        $obj->where('ticket_id=?', $ticket_id);
        $obj->order('id desc');

        return $obj->find();
    }
}

