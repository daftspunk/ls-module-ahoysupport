<?php

class AhoySupport_Credit
{
    public static function get_customer_credits($customer)
    {
        $result = $customer->x_ahoysupport_credits;

        return strlen($result) ? $result : 0;
    }
    
    public static function credit_customer($customer, $quantity, $description = null)
    {
        Support_Credit_Operation::credit($customer->id, $quantity, $description);
    }

    public static function debit_customer($customer, $quantity, $description = null)
    {
        Support_Credit_Operation::debit($customer->id, $quantity, $description);
    }
}
