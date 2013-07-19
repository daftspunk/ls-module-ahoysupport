<?php

class Support_Credit_Operation extends Db_ActiveRecord
{
    public $table_name = 'ahoysupport_credit_log';
    
    public static function create()
    {
        return new self();
    }
    
    public static function credit($customer_id, $credits, $comment = null)
    {
        return self::add_record($customer_id, $credits, $comment);
    }
    
    public static function debit($customer_id, $credits, $comment = null)
    {
        return self::add_record($customer_id, $credits*-1, $comment);
    }
    
    protected static function add_record($customer_id, $credits, $comment = null)
    {
        $obj = self::create();
        $obj->customer_id = $customer_id;
        $obj->credits = $credits;
        $obj->comment = $comment;
        $obj->save();
        
        Db_DbHelper::query('update shop_customers set x_ahoysupport_credits = ifnull(x_ahoysupport_credits, 0) + :credits where id=:id', array(
            'credits'=>$credits,
            'id'=>$customer_id
        ));
        
        return $obj;
    }
    
    public static function get_customer_history($customer)
    {
        $obj = self::create();
        $obj->where('customer_id=?', $customer->id);
        
        return $obj->order('created_at')->find_all();
    }
}
