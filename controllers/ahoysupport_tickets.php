<?php

class AhoySupport_Tickets extends Backend_Controller
{
    public $implement = 'Db_ListBehavior, Db_FormBehavior, Db_FilterBehavior, Backend_FileBrowser';
    public $list_model_class = 'AhoySupport_Ticket';
    public $list_record_url = null;
    public $list_custom_body_cells;
    public $list_custom_head_cells;
    public $list_no_pagination;
    public $list_cell_partial = false;
    public $list_options = array();
    public $list_custom_prepare_func = null;
    public $list_handle_row_click = false;
    public $list_columns;
    public $list_record_onclick = null;
    //public $list_sorting_column = 'created_updated_at';

    public $form_preview_title = 'Ticket';
    public $form_create_title = 'New Ticket';
    public $form_edit_title = 'Edit Ticket';
    public $form_model_class = 'AhoySupport_Ticket';
    public $form_not_found_message = 'Ticket not found';
    public $form_redirect = null;
    public $form_create_save_redirect;
    public $form_edit_save_redirect;
    public $form_delete_redirect;
    public $form_flash_id = 'form_flash';
    public $form_no_flash = false;

    public $form_edit_save_flash = 'The ticket has been successfully saved';
    public $form_create_save_flash = 'The ticket has been successfully added';
    public $form_edit_delete_flash = 'The ticket has been successfully deleted';
    public $form_edit_save_auto_timestamp = true;
    public $enable_concurrency_locking = true;

    public $filebrowser_onFileClick = null;
    public $filebrowser_dirs = array(
        'resources'=>array('path'=>'/resources', 'root_upload'=>false)
    );
    public $filebrowser_absoluteUrls = true;
    public $file_browser_file_list_class = 'ui-layout-anchor-window-bottom offset-24';

    public $list_search_enabled = true;
    public $list_search_fields = array('@title', '@author_name', '@author_email', '@description');
    public $list_search_prompt = 'find tickets by name, email, title or description';
    public $list_render_filters = true;

    public $filter_list_title = 'Filter tickets';
    public $filter_onApply = 'listReload();';
    public $filter_onRemove = 'listReload();';
    public $filter_filters = array(
        'user'=>array(
            'name'=>'User',
            'class_name'=>'AhoySupport_User_Filter',
            'prompt'=>'Please choose users you want to include to the list. Tickets assigned to other users will be hidden.',
            'added_list_title'=>'Added Users'
        ),
        'status'=>array(
            'name'=>'Status',
            'class_name'=>'AhoySupport_Ticket_Status_Filter',
            'prompt'=>'Please choose statuses you want to include to the list. Tickets with other statuses will be hidden.',
            'added_list_title'=>'Added Statuses'
        )
    );

    protected $required_permissions = array('ahoysupport:manage_support');

    protected $globalHandlers = array('onSave');

    public function __construct()
    {
        $this->filebrowser_dirs['resources']['path'] = '/'.Cms_SettingsManager::get()->resources_dir_path;

        parent::__construct();
        $this->app_tab = 'ahoysupport';
        $this->app_module_name = 'Support';

        if (Phpr::$router->action == 'edit')
        {
            $referer = Phpr::$router->param('param2');
            if ($referer != 'list')
                $this->form_edit_save_redirect = url('/ahoysupport/tickets/preview/%s').'?'.uniqid();
            else
                $this->form_edit_save_redirect = url('/ahoysupport/tickets/').'?'.uniqid();
        }

        $this->list_record_url = url('/ahoysupport/tickets/preview/');
        $this->form_redirect = url('/ahoysupport/tickets');
        $this->form_create_save_redirect = url('/ahoysupport/tickets/edit/%s/list').'?'.uniqid();
        $this->form_delete_redirect = url('/ahoysupport/tickets');
        $this->app_page = 'tickets';
    }

    public function listPrepareData()
    {
        $obj = AhoySupport_Ticket::create();
        $this->filterApplyToModel($obj);
        return $obj;
    }

    public function listGetRowClass($model)
    {
        if ($model instanceof AhoySupport_Ticket)
        {
            if ($model->status_code == AhoySupport_Ticket_Status::status_closed)
                return 'deleted';
            if ($model->status_code == AhoySupport_Ticket_Status::status_new)
                return 'new';

            return '';
        }
    }

    public function index()
    {
        $this->app_page_title = 'Tickets';

        $config = AhoySupport_Config::create();
        if ($config->use_smart_cron)
            AhoySupport_Module::fire_cron();
    }

    public function close($ticket_id)
    {
        $ticket = AhoySupport_Ticket::create()->find($ticket_id);
        $ticket->close_ticket();
        Phpr::$response->redirect(url('ahoysupport/tickets/preview/'.$ticket->id));
    }


    public function flag($ticket_id, $remove_flag=false)
    {
        $ticket = AhoySupport_Ticket::create()->find($ticket_id);
        $ticket->is_updated = ($remove_flag) ? false : true;
        $ticket->save();
        Phpr::$response->redirect(url('ahoysupport/tickets/preview/'.$ticket->id));
    }


    protected function index_onResetFilters()
    {
        $this->filterReset();
        $this->listCancelSearch();
        Phpr::$response->redirect(url('ahoysupport/tickets'));
    }

    protected function onSave($id)
    {
        Phpr::$router->action == 'create' ? $this->create_onSave() : $this->edit_onSave($id);
    }

    protected function preview_onDeleteNote($ticket_id)
    {
        $this->set_comment_status($ticket_id, AhoySupport_Ticket_Note_Status::status_deleted);
    }

    public function formAfterCreateSave($model, $session_key)
    {
        if (post('create_close'))
        {
            $this->form_create_save_redirect = url('/ahoysupport/tickets').'?'.uniqid();
        }
    }

    public function formAfterEditSave($model, $session_key)
    {
        $model = $this->viewData['form_model'] = AhoySupport_Ticket::create()->find($model->id);
        $model->updated_user_name = $this->currentUser->name;

        $this->renderMultiple(array(
            'form_flash'=>flash(),
            'object-summary'=>'@_ticket_summary'
        ));

        return true;
    }

    public function formBeforeCreateSave($model)
    {
        $model->is_admin_comment = true;
    }

    // Quick post
    // 

    public function preview_onAddNewNote($id = null)
    {
        $context = post('open_context', 'quick');
        $ticket_id = post('ticket_id');
        $this->resetFormEditSessionKey();
        $note = AhoySupport_Ticket_Note::create();
        if (!$note)
            throw new Phpr_ApplicationException('Option not found');

        $note->define_form_fields($context);

        $this->viewData['ticket_id'] = $ticket_id;
        $this->viewData['note'] = $note;
        $this->viewData['context'] = $context;
        $this->renderPartial('note_form');        
    }

    public function preview_onCreateNote($id = null)
    {
        try
        {
            $context = post('context', 'quick');
            $ticket_id = post('ticket_id');
            $note = AhoySupport_Ticket_Note::create();
            
            $note->disable_column_cache($context, false);
            $note->init_columns_info($context);

            $note->define_form_fields($context);
            $note->ticket_id = $ticket_id;
            $note->author_name = $this->currentUser->firstName.' '.$this->currentUser->lastName;
            $note->author_email = $this->currentUser->email;
            $note->is_admin_comment = true;
            $note->save(post('AhoySupport_Ticket_Note'));
        }
        catch (Exception $ex)
        {
            Phpr::$response->ajaxReportException($ex, true, true);
        }

        $this->viewData['ticket_id'] = $ticket_id;
        
        // Change ticket properties
        $ticket = AhoySupport_Ticket::create()->find($note->ticket_id);
        if ($note->assign_user||$note->priority||$note->status)
        {

            if ($note->assign_user)
                $ticket->user_id = $note->assign_user->id;

            if ($note->priority)
                $ticket->priority_id = $note->priority->id;

            if ($note->status)
                $ticket->status_id = $note->status->id;

            $this->viewData['form_model'] = $ticket;
            $this->preparePartialRender('ticket_scoreboard');
            $this->renderPartial('ticket_scoreboard');
        }
        
        $ticket->is_updated = false;
        $ticket->save();

        $this->preparePartialRender('ticket_add_note');
        echo '';
        $this->preparePartialRender('note_list');
        $this->renderPartial('note_list');
    }

    // Add note from template
    // 

    public function preview_onLoadNoteTemplateForm($id = null)
    {
        try
        {
            $this->viewData['templates'] = System_EmailTemplate::create()->order('code')->where('(is_system is null or is_system=0)')->find_all();
            $this->viewData['ticket_id'] = $id;
        }
        catch (Exception $ex)
        {
            $this->handlePageError($ex);
        }
        
        $this->renderPartial('note_select_template');
    }

    // Service methods
    //

    protected function get_ticket_statistics()
    {
        return AhoySupport_Ticket::get_ticket_statistics();
    }

    private function find_note($id)
    {
        if (!strlen($id))
            throw new Phpr_ApplicationException('Note not found');

        $obj = AhoySupport_Ticket_Note::create()->where('id=?', $id)->find();
        if (!$obj)
            throw new Phpr_ApplicationException('Note not found');

        return $obj;
    }

}

