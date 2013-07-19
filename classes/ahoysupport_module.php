<?php

class AhoySupport_Module extends Core_ModuleBase
{
    /**
     * Creates the module information object
     * @return Core_ModuleInfo
     */
    protected function createModuleInfo()
    {
        return new Core_ModuleInfo(
            "Support",
            "Manage support tickets from customers",
            "Frag Networks",
            "http://frag.net.au/" );
    }

    /**
     * Events
     */

    public function subscribeEvents()
    {
        Backend::$events->addEvent('shop:onExtendCustomerModel', $this, 'extend_customer_model');
        Backend::$events->addEvent('shop:onExtendCustomerForm', $this, 'extend_customer_form');
        Backend::$events->addEvent('shop:onOrderStatusChanged', $this, 'after_order_status');
        Backend::$events->addEvent('shop:onExtendCustomerPreviewTabs', $this, 'add_customer_preview_tabs');
    }

    public function extend_customer_model($customer)
    {
        $customer->add_relation('has_many', 'tickets', array('class_name'=>'AhoySupport_Ticket', 'foreign_key'=>'customer_id', 'delete'=>true));        
        $customer->define_column('x_ahoysupport_credits', 'Support Credits')->invisible();
    }

    public function extend_customer_form($customer)
    {
        $customer->add_form_field('x_ahoysupport_credits', 'left')->tab('Customer');
    }

    public function after_order_status($order, $status, $prev_status)
    {
        $config_obj = AhoySupport_Config::create();
        $total_credits = 0;

        if (!$config_obj->use_credits||!$config_obj->credit_product_id)
            return;

        // If for whatever reason the order status reaches paid again,
        // and the payment has been processed, don't add credits again
        if ($order->payment_processed)
            return;

        if ($status->code != Shop_OrderStatus::status_paid)
            return;

        foreach ($order->items as $item)
        {
            if ($item->product->id != $config_obj->credit_product_id)
                continue;

            $total_credits += $item->quantity;
        }

        if ($total_credits > 0 && $order->customer)
        {
            $order->customer->x_ahoysupport_credits += $total_credits;
            $order->customer->save();
        }
    }

    /**
     * Returns a list of the module back-end GUI tabs.
     * @param Backend_TabCollection $tabCollection A tab collection object to populate.
     * @return mixed
     */
    public function listTabs($tabCollection)
    {
        $stats = AhoySupport_Ticket::get_ticket_statistics();
        $caption = !$stats->open_count ? 'Tickets' : 'Tickets ('.$stats->open_count.')';

        $user = Phpr::$security->getUser();
        $tabs = array(
            'tickets'=>array('tickets', $caption, 'manage_support'),
            'categories'=>array('categories', 'Categories', 'manage_support'),
            'settings'=>array('settings', 'Settings', 'manage_support'),
        );

        $first_tab = null;
        foreach ($tabs as $tab_id=>$tab_info)
        {
            if (($tabs[$tab_id][3] = $user->get_permission('ahoysupport', $tab_info[2])) && !$first_tab)
                $first_tab = $tab_info[0];
        }

        if ($first_tab)
        {
            $tab = $tabCollection->tab('ahoysupport', 'Support', $first_tab, 17); // Before CMS (20), After Reports (15)
            foreach ($tabs as $tab_id=>$tab_info)
            {
                if ($tab_info[3])
                    $tab->addSecondLevel($tab_id, $tab_info[1], $tab_info[0]);
            }
        }
    }

    /**
     * Builds user permissions interface
     * For drop-down and radio fields you should also add methods returning
     * options. For example, of you want to have "Access Level" drop-down:
     * public function get_access_level_options();
     * This method should return array with keys corresponding your option identifiers
     * and values corresponding its titles.
     *
     * @param $host_obj ActiveRecord object to add fields to
     */
    public function buildPermissionsUi($host_obj)
    {
        $host_obj->add_field($this, 'manage_support', 'Manage support')->renderAs(frm_checkbox)->comment('Manage entire support section.', 'above');
    }

    public function listSettingsItems()
    {
        $result = array(
            array(
                'icon'=>'/modules/ahoysupport/assets/images/support_settings.png',
                'title'=>'Support Settings',
                'url'=>'/ahoysupport/settings',
                'description'=>'Set up and manage support notifications.',
                'sort_id'=>50,
                'section'=>'Miscellaneous'
                )
            );

        return $result;
    }

    /**
     * Returns a list of module email variable scopes
     * array('order'=>'Order')
     */
    public function listEmailScopes()
    {
        return array('ticket'=>'Support ticket variables');
    }

    /**
     * Returns a list of dashboard reports in format
     * array('report_code'=>array('partial'=>'partial_name.htm', 'name'=>'Report Name')).
     * Partials must be placed to the module dashboard directory:
     * /modules/cms/dashboard
     */
    public function listDashboardReports()
    {
        return array(
            'new_tickets'=>array('partial'=>'newtickets_report.htm', 'name'=>'Recent Support Tickets'),
            'my_tickets'=>array('partial'=>'mytickets_report.htm', 'name'=>'My Open Support Tickets')
        );
    }

    public function add_customer_preview_tabs($controller, $customer)
    {
        return array('Tickets'=>PATH_APP.'/modules/ahoysupport/partials/_customer_tickets.htm');
    }

    // Cron jobs
    // 

    public function register_access_points()
    {
        return array('ahoy_support_cron'=>'process_cron');
    }

    public static function fire_cron()
    {
        $config = AhoySupport_Config::create();
        if (!$config->auto_expire_days)
            return;        
        
        return AhoySupport_Ticket::expire_tickets($config->auto_expire_days);
    }

    public function process_cron()
    {
        if (Core_CronManager::access_allowed())
        {
            echo self::fire_cron();
        }
    }
}