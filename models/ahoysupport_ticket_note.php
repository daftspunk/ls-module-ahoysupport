<?php

class AhoySupport_Ticket_Note extends Db_ActiveRecord
{
    public $table_name = 'ahoysupport_ticket_notes';

    public $is_guest_entry = false;
    public $session_key = false;

    public $implement = 'Db_AutoFootprints';
    public $auto_footprints_visible = true;
    public $auto_footprints_default_invisible = true;

    protected $api_added_columns = array();

    public $belongs_to = array(
        'ticket'=>array('class_name'=>'AhoySupport_Ticket', 'foreign_key'=>'ticket_id'),
        'priority'=>array('class_name'=>'AhoySupport_Ticket_Priority', 'foreign_key'=>'priority_id'),
        'status'=>array('class_name'=>'AhoySupport_Ticket_Status', 'foreign_key'=>'status_id'),
        'user'=>array('class_name'=>'Users_User', 'foreign_key'=>'created_user_id'),
        'assign_user'=>array('class_name'=>'Users_User', 'foreign_key'=>'assign_user_id'),
        'customer'=>array('class_name'=>'Shop_Customer', 'foreign_key'=>'customer_id'),
    );

    public $has_many = array(
        'files'=>array('class_name'=>'Db_File', 'foreign_key'=>'master_object_id', 'conditions'=>"master_object_class='AhoySupport_Ticket_Note' and field='files'", 'order'=>'sort_order, id', 'delete'=>true),
    );

    public $calculated_columns = array(
        'files_num'=>array('sql'=>"(select count(*) from db_files where db_files.master_object_id = ahoysupport_ticket_notes.id and db_files.master_object_class='AhoySupport_Ticket_Note' and db_files.field='files')", 'type'=>db_number),
    );

    public $custom_columns = array(
        'minor_update'=>db_bool
    );    

    public $minor_update = false;

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $config_obj = AhoySupport_Config::create();

        $field = $this->define_column('author_name', 'Name')->validation()->fn('trim');
        if ($config_obj->ticket_allow_guests && $this->is_guest_entry)
            $field->required("Please enter your name.");

        $field = $this->define_column('author_email', 'Email')->validation()->fn('trim')->fn('mb_strtolower')->email('Please specify a valid email address.');
        if ($config_obj->ticket_allow_guests && $this->is_guest_entry)
            $field->required("Please specify your email address.");

        $this->define_relation_column('customer', 'customer', 'Customer', db_varchar, "concat(@first_name, ' ', @last_name, ' (', @email, ')')")->defaultInvisible();

        $this->define_column('created_at', 'Added')->order('desc');
        $this->define_relation_column('ticket', 'ticket', 'Ticket', db_varchar, '@title');

        $this->define_multi_relation_column('files', 'files', 'Attachments', '@name')->invisible();
        
        $field = $this->define_column('description', 'Comment')->validation()->fn('trim');

        if ($context != 'assign')
            $field->required("Please enter the comment.");
        
        $this->define_column('description_plain', 'Comment');
        $this->define_column('private_note', 'Private Note')->defaultInvisible()->validation()->fn('trim');
        $this->define_column('author_ip', 'IP');

        $this->define_relation_column('assign_user', 'assign_user', 'Reassign Ticket', db_varchar, "trim(concat(ifnull(@firstName, ''), ' ', ifnull(@lastName, ' '), ' ', ifnull(@middleName, '')))");
        $this->define_relation_column('priority', 'priority', 'Change Priority', db_varchar, '@name');
        $this->define_relation_column('status', 'status', 'Change Status', db_varchar, '@name');

        $this->define_column('files_num', 'Total Files');

        // Extensibility
        $this->defined_column_list = array();
        Backend::$events->fire_event('ahoysupport:on_extend_note_model', $this, $context);
        $this->api_added_columns = array_keys($this->defined_column_list);
    }

    public function define_form_fields($context = null)
    {

        if ($context == 'quick'||$context == 'customer'|| 
            ($context != 'preview' && $context != 'assign')
        )
        {
            // Description HTML field (inherits blog editor)
            $content_field = $this->add_form_field('description')->renderAs(frm_html)->size('huge');
            $editor_config = System_HtmlEditorConfig::get('blog', 'blog_post_content');
            $editor_config->apply_to_form_field($content_field);
            $content_field->htmlPlugins .= ',save,fullscreen,inlinepopups';
            $content_field->htmlButtons1 = 'save,separator,'.$content_field->htmlButtons1.',separator,fullscreen';
            $content_field->saveCallback('save_code');
            $content_field->htmlFullWidth = true;
        }

        if ($context == 'assign')
        {
            $this->add_form_field('assign_user', 'left')->emptyOption('-- do not reassign --');
            $this->add_form_field('status', 'right')->emptyOption('-- do not change --');
            $this->add_form_field('private_note', 'left');
            $this->add_form_field('priority', 'right')->emptyOption('-- do not change --');
        }
        else if ($context == 'quick')
        {
            $this->add_form_field('assign_user', 'left')->emptyOption('-- do not reassign --');
            $this->add_form_field('status', 'right')->emptyOption('-- do not change --');
        }
        else if ($context == 'customer')
        {
            $content_field->comment('Update on behalf of customer. For example: Paste the contents of the customer email here.', 'above');
            $this->add_form_field('files', 'left')->renderAs(frm_file_attachments)
                ->renderFilesAs('file_list')
                ->addDocumentLabel('Add file attachment(s)')
                ->noAttachmentsLabel('There are no files uploaded')
                ->fileDownloadBaseUrl(url('ls_backend/files/get/'));

        }
        else if ($context != 'preview')
        {
            $this->add_form_field('author_name', 'left');
            $this->add_form_field('author_email', 'right');



            if ($this->is_new_record())
            {
                $this->add_form_field('status', 'left')->emptyOption('-- do not change --');
                $this->add_form_field('priority', 'right')->emptyOption('-- do not change --');
            }


            $this->add_form_field('files', 'left')->renderAs(frm_file_attachments)
                ->renderFilesAs('file_list')
                ->addDocumentLabel('Add file attachment(s)')
                ->noAttachmentsLabel('There are no files uploaded')
                ->fileDownloadBaseUrl(url('ls_backend/files/get/'));

            if ($this->is_new_record())
                $this->add_form_field('assign_user', 'right')->emptyOption('-- do not reassign --');
            else
                $this->add_form_field('private_note', 'right')->renderAs(frm_textarea)->size('small');
        }
        else
        {
            $this->add_form_field('author_name', 'left');
            $this->add_form_field('author_email', 'right');
            $this->add_form_field('status', 'left');
            $this->add_form_field('priority', 'right');
            $this->add_form_field('author_ip', 'left');
            $this->add_form_field('assign_user', 'right');
            $this->add_form_field('description');
            $this->add_form_field('files', 'left');
            $this->add_form_field('private_note', 'right');
        }

        // Extensibility
        Backend::$events->fire_event('ahoysupport:on_extend_note_form', $this, $context);
        foreach ($this->api_added_columns as $column_name)
        {
            $form_field = $this->find_form_field($column_name);
            if ($form_field)
                $form_field->optionsMethod('get_added_field_options');
        }
    }

    // Extensibility
    //

    public function get_added_field_options($db_name, $current_key_value = -1)
    {
        $result = Backend::$events->fireEvent('ahoysupport:on_get_note_field_options', $db_name, $current_key_value);
        foreach ($result as $options)
        {
            if (is_array($options) || (strlen($options && $current_key_value != -1)))
                return $options;
        }

        return false;
    }

    // Options
    // 
    
    public function get_customer_options($key_value = -1)
    {        
        if ($key_value == -1 || !$key_value)
            return array();

        $customer = Shop_Customer::create()->find($key_value);
        return ($customer) ? $customer->name : '';
    }
    
    // Events
    //

    public function before_save($key = null)
    {
        if (strlen($this->description))
            $this->description_plain = Phpr_Html::deparagraphize($this->description);

        if ($this->customer)
        {
            $this->author_name = $this->customer->name;
            $this->author_email = $this->customer->email;
        }
    }

    public function after_save()
    {
        if (!Cms_Controller::get_instance())
            return;
            
        if ($this->is_admin_message)
            return;

        $updated = isset($this->fetched['id']);
        if (!$updated)
            return;

        if ($this->minor_update)
            return;
            
        AhoySupport_Notify::trigger_note_new($this);
    }

    // Service methods
    //

    public function get_author()
    {
        return ($this->customer) ? $this->customer : $this->user;
    }

    public function add_file_from_post($file_info, $session_key = null)
    {
        if (!$session_key)
            $session_key = post('ls_session_key');

        if (!array_key_exists('error', $file_info) || $file_info['error'] == UPLOAD_ERR_NO_FILE)
            return;

        Phpr_Files::validateUploadedFile($file_info);

        $file = Db_File::create();
        $file->is_public = true;

        $file->fromPost($file_info);
        $file->master_object_class = get_class($this);
        $file->master_object_id = $this->id;
        $file->field = 'files';
        $file->save(null, $session_key);

        $this->files->add($file, $session_key);

        return $file;
    }

}

