<?php

class AhoySupport_Ticket_Notes extends Backend_Controller
{
    public $implement = 'Db_ListBehavior, Db_FormBehavior, Backend_FileBrowser';

    public $form_preview_title = 'Message';
    public $form_create_title = 'New Message';
    public $form_edit_title = 'Edit Message';
    public $form_model_class = 'AhoySupport_Ticket_Note';
    public $form_not_found_message = 'Message not found';
    public $form_redirect = null;
    public $form_create_save_redirect = null;
    public $form_delete_redirect = null;
    public $form_create_context_name = null;

    public $form_edit_save_flash = 'Message has been successfully saved';
    public $form_create_save_flash = 'Message has been successfully added';
    public $form_edit_delete_flash = 'Message has been successfully deleted';
    public $form_edit_save_auto_timestamp = true;
    public $enable_concurrency_locking = true;

    public $filebrowser_onFileClick = null;
    public $filebrowser_dirs = array(
        'resources'=>array('path'=>'/resources', 'root_upload'=>false)
    );
    public $filebrowser_absoluteUrls = true;

    protected $required_permissions = array('ahoysupport:manage_support');
    public $file_browser_file_list_class = 'ui-layout-anchor-window-bottom offset-24';

    public function __construct()
    {
        parent::__construct();
        $this->app_tab = 'ahoysupport';
        $this->app_module_name = 'Support';
        $this->app_page = 'tickets';
        $this->form_redirect = url(sprintf('/ahoysupport/tickets/preview/%s', Phpr::$router->param('param2')));
        $this->form_delete_redirect = url(sprintf('/ahoysupport/tickets/preview/%s', Phpr::$router->param('param2')));

        if (post('create_mode'))
        {
            $this->form_redirect = url(sprintf('/ahoysupport/tickets/preview/%s', Phpr::$router->param('param2')));
        }
    }

    public function create_formBeforeRender($model)
    {
        if (!$this->currentUser->get_permission('ahoysupport', 'manage_support'))
            Phpr::$response->redirect(url('/'));

        $ticket_id = Phpr::$router->param('param2');
        if (!strlen($ticket_id))
            throw new Phpr_ApplicationException('Ticket not found');

        $ticket = AhoySupport_Ticket::create()->find($ticket_id);
        if (!$ticket)
            throw new Phpr_ApplicationException('Ticket not found');

        $model->author_name = $this->currentUser->firstName.' '.$this->currentUser->lastName;
        $model->author_email = $this->currentUser->email;

        // Apply email template
        $context = Phpr::$router->param('param1');
        if ($context == 'template')
        {
            $template_id = Phpr::$router->param('param3');
            $template  = System_EmailTemplate::create()->find($template_id);
            if (!$template)
                throw new Phpr_ApplicationException('Template not found');

            $model->description = $this->apply_variables($template->content, $ticket);
        }
    }

    public function edit_formBeforeRender()
    {
        if (!$this->currentUser->get_permission('ahoysupport', 'manage_notes'))
            Phpr::$response->redirect(url('/'));
    }

    public function formBeforeCreateSave($model)
    {
        $ticket_id = Phpr::$router->param('param2');
        $context = post('form_context');
        
        $ticket = AhoySupport_Ticket::create()->find($ticket_id);
        if ($context == 'customer')
        {
            $model->is_admin_comment = false;
            if ($ticket->customer_id)
            {
                $model->customer = $ticket->customer;
                $model->customer_id = $ticket->customer_id;
            }
            $model->author_name = $ticket->author_name;
            $model->author_email = $ticket->author_email;
        }
        else
        {
            $model->is_admin_comment = true;
        }
        $ticket->save();
        $model->ticket_id = $ticket_id;
    }

    public function formAfterCreateSave($model, $session_key)
    {
        // Change ticket properties
        if ($model->assign_user||$model->priority||$model->status)
        {
            $ticket = AhoySupport_Ticket::create()->find($model->ticket_id);

            if ($model->assign_user)
                $ticket->user_id = $model->assign_user->id;

            if ($model->priority)
                $ticket->priority_id = $model->priority->id;

            if ($model->status)
                $ticket->status_id = $model->status->id;
            
            $ticket->save();
        }
    }

    protected function create_onInsertVariable()
    {
        try
        {
            $ticket_id = Phpr::$router->param('param2');
            if (!strlen($ticket_id))
                throw new Phpr_ApplicationException('Ticket not found');

            $ticket = AhoySupport_Ticket::create()->find($ticket_id);
            if (!$ticket)
                throw new Phpr_ApplicationException('Ticket not found');

            $var = '{'.post('variable').'}';
            echo $this->apply_variables($var, $ticket);
        }
        catch (Exception $ex)
        {
            Phpr::$response->ajaxReportException($ex, true, true);
        }
    }

    protected function apply_variables($message, $ticket)
    {
        if ($ticket->customer)
        {
            $message = $ticket->customer->set_customer_email_vars($message);
        }
        else 
        {
            $message = str_replace('{customer_name}', $ticket->author_name, $message);
            $message = str_replace('{customer_email}', $ticket->author_email, $message);
        }
        return $message;
    }    
}

