<?php

class AhoySupport_Helper
{
    public static function customer_has_paid_orders($customer_id)
    {
        return Db_DbHelper::scalar('
            select 
                count(*) 
            from 
                shop_order_status_log_records, 
                shop_order_statuses, 
                shop_orders
            where
                shop_order_statuses.id=shop_order_status_log_records.status_id 
                and shop_order_statuses.code=:code
                and shop_orders.id=shop_order_status_log_records.order_id
                and shop_orders.customer_id=:customer_id
            ',
            array(
                'customer_id'=>$customer_id,
                'code'=>Shop_OrderStatus::status_paid
            )
        );
    }
    
    public static function format_message_body($message)
    {
        // Add brush declaration for the syntax highligting
        $result = str_replace('<pre', '<pre class="brush:php; collapse: true"', $message);
        
        // Add new line characters before <br>
        $result = str_replace('<br', "\n<br", $result);
        
        // Remove line breaks before the closing PRE
        for ($i = 1; $i <= 10; $i++)
            $result = preg_replace(',\<br\s*/?\>\s*\</pre\>,m', "</pre>", $result);
            
        // Remove trailing paragraphs
        $result = preg_replace(',\<p\>&nbsp;\</p\>\s*$,m', '', $result);
        $result = preg_replace(',\<p\>\s*\</p\>\s*$,m', '', $result);
        $result = preg_replace(',\<p\>\s*\<br\s*/?\>\s*</p\>\s*$,m', '', $result);

        return $result;
    }

    public static function list_support_users()
    {
        return Users_User::list_users_having_permission('ahoysupport', 'manage_support');
    }
    
    public static function get_customer_avatar($customer, $size, $default = '/resources/i/default_avatar.jpg')
    {
        if ($customer->image->first)
            return $customer->image->first->getThumbnailPath($size, $size, false);
            
        $default = root_url($default, true);

        $protocol = Phpr::$request->protocol();
        if (strtolower($protocol) !== 'https')
            return "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $customer->email ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;
        else
            return "https://secure.gravatar.com/avatar/" . md5( strtolower( trim( $customer->email ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;
    }
}
