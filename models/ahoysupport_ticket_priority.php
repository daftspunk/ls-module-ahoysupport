<?php

class AhoySupport_Ticket_Priority extends Db_ActiveRecord
{
    const status_low = 'low';
    const status_normal = 'normal';
    const status_high = 'high';
    const status_urgent = 'urgent';
    const status_immediate = 'immediate';

    public $table_name = 'ahoysupport_ticket_priorities';

    public static function create()
    {
        return new self();
    }
}

