<?php

class AhoySupport_Config extends Core_Configuration_Model
{
    const notify_nobody = 1;
    const notify_authors = 2;
    const notify_all = 3;

    public $record_code = 'ahoysupport_config';

    public static function create()
    {
        $configObj = new AhoySupport_Config();
        return $configObj->load();
    }

    protected function build_form()
    {
        // Placeholder
        $this->add_field('auto_expire_days', 'Close tickets automatically (days)', 'left', db_number)->tab('Tickets')->comment("Enter the amount of days to automatically close inactive tickets. Leave empty to disable this feature.");
        $this->add_field('ticket_allow_guests', 'Allow guests to submit tickets', 'right', db_bool)->tab('Tickets')->comment("If this checkbox is checked, visitors will be forced to register before submitting a ticket.");
        $this->add_field('use_smart_cron', 'Use "Smart Cron" for automation', 'left', db_bool)->tab('Tickets')->comment("This will prevent the need to use a cronjob for automated functionality and may slow down support page load times slightly. If disabled, you should set up a cronjob to trigger http://yoursite/ahoy_support_cron at least once a day.");
        $this->add_field('guest_auto_signup', 'Automatically register guests', 'right', db_bool)->tab('Tickets')->comment("If tickets can be submitted by guests, check this to automatically register them as a guest customer.");

        //$this->add_field('cms_ticket_page', 'Ticket CMS Page', 'full', db_text)->tab('Tickets')->renderAs(frm_dropdown)->optionsHtmlEncode(false)->comment('Select which page is used for viewing a ticket.', 'above');

        $this->add_field('use_credits', 'Use credits', 'left', db_bool)->tab('Credits')->comment("Tick this box if you want to charge credits for ticket submission");
        $this->add_field('credit_product_id', 'Product to use for credits', 'full', db_number)->renderAs(frm_dropdown)->tab('Credits')->cssClassName('checkbox_align')->comment("Select the product used for buying credits",'above');

        $this->add_field('ticket_notifications_send_self', 'Do not notify about self updates', 'full', db_bool)->tab('Notifications')->comment("If this checkbox is checked, users or customers will not receive notifications about their own updates.");
        $this->add_field('ticket_notifications_rule', 'New ticket notifications', 'left', db_number)->tab('Notifications')->renderAs(frm_radio)->comment('Please choose which users should receive notifications about new tickets.', 'above');
        $this->add_field('note_notifications_rule', 'Ticket update notifications', 'right', db_number)->tab('Notifications')->renderAs(frm_radio)->comment('Please choose which users should receive notifications about new ticket notes.', 'above');
        $this->add_field('include_email_attachments', 'Include email attachments', 'full', db_bool)->tab('Notifications')->comment("If this checkbox is checked, any file attachments will sent along with the notification email.");
    }

    protected function init_config_data()
    {
        $this->use_smart_cron = true;
        $this->include_email_attachments = false;
        $this->ticket_notifications_rule = self::notify_all;
        $this->note_notifications_rule = self::notify_authors;
        $this->ticket_notifications_send_self = true;
        $this->ticket_allow_guests = false;
        $this->guest_auto_signup = false;
        $this->use_credits = false;
        $this->credit_product_id = 1;
    }

    public function get_credit_product_id_options($key_value = -1)
    {
        return Shop_Product::create()
            ->where('shop_products.grouped is null')
            ->find_all()->as_array('name', 'id');
    }

    // public function get_cms_ticket_page_options($key_value = -1)
    // {
    //     return Cms_Page::create()->find_all()->as_array('id','name');
    // }


    public function get_cms_ticket_page_url()
    {
        $theme = Cms_Theme::get_active_theme();
        if ($theme)
            $theme_id = $theme->id;
        else
            $theme_id = 0;

        return Db_DbHelper::scalar('select url from pages where action_reference=:action and theme_id=:theme_id', array('action'=>'ahoysupport:ticket', 'theme_id'=>$theme_id));
    }

    public function get_note_notifications_rule_options()
    {
        return array(
            self::notify_nobody=>array('Nobody'=>'Do not send new note notifications.'),
            self::notify_authors=>array('Authors only'=>'Send new note notifications only to customer or ticket asignee.'),
            self::notify_all=>array('All users'=>'Notify all admin users who have permissions to receive support notifications.')
        );
    }

    public function get_ticket_notifications_rule_options()
    {
        return array(
            self::notify_nobody=>array('Nobody'=>'Do not send new ticket notifications.'),
            self::notify_all=>array('All users'=>'Notify all admin users who have permissions to receive support notifications.')
        );
    }
}

