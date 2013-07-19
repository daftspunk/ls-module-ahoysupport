<?php

class AhoySupport_Ticket_Status extends Db_ActiveRecord
{
    const status_new = 'new';
    const status_processing = 'processing';
    const status_closed = 'closed';

    public $table_name = 'ahoysupport_ticket_statuses';

    public static function create()
    {
        return new self();
    }
    
    public function define_columns($context = null)
    {
        $this->define_column('name', 'Name');
    }   
}

