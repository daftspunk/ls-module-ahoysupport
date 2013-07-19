<?php

class AhoySupport_Settings extends Backend_Controller
{
    public $implement = 'Db_FormBehavior, Cms_PageSelector';

    public $form_edit_title = 'Support Settings';
    public $form_model_class = 'AhoySupport_Config';
    public $form_redirect = null;

    protected $required_permissions = array('ahoysupport:manage_support');

    public function __construct()
    {
        parent::__construct();
        $this->app_tab = 'ahoysupport';
        $this->app_module_name = 'Support';

        $this->app_page = 'settings';
    }

    public function index()
    {
        try
        {
            $this->app_page_title = 'Settings';

            $obj = new AhoySupport_Config();
            $this->viewData['form_model'] = $obj->load();
        }
        catch (exception $ex)
        {
            $this->handlePageError($ex);
        }
    }

    protected function index_onSave()
    {
        try
        {
            $obj = new AhoySupport_Config();
            $obj = $obj->load();

            $obj->save(post($this->form_model_class, array()), $this->formGetEditSessionKey());

            Phpr::$session->flash['success'] = 'Support configuration have been successfully saved.';
            Phpr::$response->redirect(url('system/settings'));
        }
        catch (Exception $ex)
        {
            Phpr::$response->ajaxReportException($ex, true, true);
        }
    }
}

?>