<?php

class AhoySupport_Categories extends Backend_Controller
{
    public $implement = 'Db_ListBehavior, Db_FormBehavior';
    public $list_model_class = 'AhoySupport_Category';
    public $list_record_url = null;

    public $form_preview_title = 'Category';
    public $form_create_title = 'New Category';
    public $form_edit_title = 'Edit Category';
    public $form_model_class = 'AhoySupport_Category';
    public $form_not_found_message = 'Category not found';
    public $form_redirect = null;

    public $form_edit_save_flash = 'The category has been successfully saved';
    public $form_create_save_flash = 'The category has been successfully added';
    public $form_edit_delete_flash = 'The category has been successfully deleted';
    public $form_edit_save_auto_timestamp = true;

    protected $required_permissions = array('ahoysupport:manage_support');

    public function __construct()
    {
        parent::__construct();
        $this->app_tab = 'ahoysupport';
        $this->app_module_name = 'Support';

        $this->list_record_url = url('/ahoysupport/categories/edit/');
        $this->form_redirect = url('/ahoysupport/categories');
        $this->app_page = 'categories';
    }

    public function index()
    {
        $this->app_page_title = 'Categories';
    }
}

