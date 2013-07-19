<?php

class AhoySupport_Ticket extends Db_ActiveRecord
{
    public $table_name = 'ahoysupport_tickets';

    public $is_guest_entry = false;

    public $implement = 'Db_AutoFootprints';
    public $auto_footprints_visible = true;
    public $auto_footprints_default_invisible = true;

    protected $api_added_columns = array();

    public $belongs_to = array(
        'priority'=>array('class_name'=>'AhoySupport_Ticket_Priority', 'foreign_key'=>'priority_id'),
        'status'=>array('class_name'=>'AhoySupport_Ticket_Status', 'foreign_key'=>'status_id'),
        'user'=>array('class_name'=>'Users_User', 'foreign_key'=>'user_id'),
        'customer'=>array('class_name'=>'Shop_Customer', 'foreign_key'=>'customer_id'),
        'primary_category'=>array('class_name'=>'AhoySupport_Category', 'foreign_key'=>'primary_category_id'),
    );

    public $has_and_belongs_to_many = array(
        'categories'=>array('class_name'=>'AhoySupport_Category', 'join_table'=>'ahoysupport_ticket_categories', 'order'=>'name'),
    );

    public $has_many = array(
        'files'=>array('class_name'=>'Db_File', 'foreign_key'=>'master_object_id', 'conditions'=>"master_object_class='AhoySupport_Ticket' and field='files'", 'order'=>'sort_order, id', 'delete'=>true),
        'notes'=>array('class_name'=>'AhoySupport_Ticket_Note', 'foreign_key'=>'ticket_id', 'order'=>'ahoysupport_ticket_notes.created_at asc'),
    );

    public $calculated_columns = array(
        'files_num'=>array('sql'=>"(select count(*) from db_files where db_files.master_object_id = ahoysupport_tickets.id and db_files.master_object_class='AhoySupport_Ticket' and db_files.field='files')", 'type'=>db_number),
        'note_num'=>array('sql'=>'(select count(*) from ahoysupport_ticket_notes where ahoysupport_ticket_notes.ticket_id = ahoysupport_tickets.id)', 'type'=>db_number),
        'status_code'=>array('sql'=>'(select code from ahoysupport_ticket_statuses where ahoysupport_ticket_statuses.id = ahoysupport_tickets.status_id)', 'type'=>db_varchar),
        'created_updated_at'=>array('sql'=>'ifnull(ahoysupport_tickets.updated_at,ahoysupport_tickets.created_at)', 'type'=>db_datetime),
    );

    public $custom_columns = array(
        'ticket_number' => db_varchar,
        'ticket_age' => db_varchar,
        'ticket_update_age' => db_varchar,
        'description_plain' => db_varchar,
        'category_string' => db_varchar,
        'last_reply_by'=>db_text,
        'minor_update'=>db_bool,
    );

    public $minor_update = false;

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $config_obj = AhoySupport_Config::create();

        $this->define_column('id', '#');
        $this->define_relation_column('priority', 'priority', 'Priority', db_varchar, '@name');
        $this->define_relation_column('status', 'status', 'Status', db_varchar, '@name')->defaultInvisible();

        $this->define_column('title', 'Title')->validation()->fn('trim')->required("Please specify the ticket title.");

        $field = $this->define_column('author_name', 'Author Name')->listTitle('Name')->defaultInvisible()->validation()->fn('trim');
        if ($config_obj->ticket_allow_guests && $this->is_guest_entry)
            $field->required("Please enter your name.");

        $field = $this->define_column('author_email', 'Author Email')->listTitle('Email')->defaultInvisible()->validation()->fn('trim')->fn('mb_strtolower')->email('Please specify a valid email address.');
        if ($config_obj->ticket_allow_guests && $this->is_guest_entry)
            $field->required("Please specify your email address.");
        $this->define_multi_relation_column('categories', 'categories', 'Categories', '@name')->defaultInvisible(); //->validation()->required("Please choose a category.");
        
        $this->define_relation_column('primary_category', 'primary_category', 'Primary Category', db_varchar, '@name')->defaultInvisible();

        $this->define_column('private_note', 'Private Note')->defaultInvisible()->validation()->fn('trim');
        
        $this->define_column('description', 'Original Description')->invisible()->validation()->fn('trim')->required('Please provide the ticket description');

        $this->define_multi_relation_column('files', 'files', 'Attachments', '@name')->invisible();
        $this->define_relation_column('user', 'user', 'Assignee', db_varchar, "trim(concat(ifnull(@firstName, ''), ' ', ifnull(@lastName, ' '), ' ', ifnull(@middleName, '')))");

        $this->define_relation_column('customer', 'customer', 'Customer', db_varchar, "concat(@first_name, ' ', @last_name, ' (', @email, ')')")->defaultInvisible();

        $this->define_column('note_num', 'Total Messages')->defaultInvisible();
        $this->define_column('files_num', 'Total Files')->defaultInvisible();
        $this->define_column('is_updated', 'Unread Messages')->invisible()->order('desc');
        $this->define_column('created_updated_at', 'Last Update');

        $this->define_column('last_reply_by', 'Last Reply By');

        // Extensibility
        $this->defined_column_list = array();
        Backend::$events->fire_event('ahoysupport:on_extend_ticket_model', $this, $context);
        $this->api_added_columns = array_keys($this->defined_column_list);
    }

    public function define_form_fields($context = null)
    {

        if ($context != 'preview')
        {

            $this->add_form_field('title')->tab('Ticket')->comment('The post title will be shown in the post lists and on the post page.', 'above');

            // Description HTML field (inherits blog editor)
            $content_field = $this->add_form_field('description')->renderAs(frm_html)->size('giant')->tab('Ticket');
            $editor_config = System_HtmlEditorConfig::get('blog', 'blog_post_content');
            $editor_config->apply_to_form_field($content_field);
            $content_field->htmlPlugins .= ',save,fullscreen,inlinepopups';
            $content_field->htmlButtons1 = 'save,separator,'.$content_field->htmlButtons1.',separator,fullscreen';
            $content_field->saveCallback('save_code');
            $content_field->htmlFullWidth = true;

            $this->add_form_field('status', 'left')->tab('Properties')->referenceSort('id');
            $this->add_form_field('user', 'right')->tab('Properties')->emptyOption('-- no asignee --');

            $this->add_form_field('author_name', 'left')->tab('Properties');
            $this->add_form_field('author_email', 'right')->tab('Properties');            
            $this->add_form_field('priority', 'left')->tab('Properties')->referenceSort('id');

            $this->add_form_field('files', 'right')->renderAs(frm_file_attachments)
                ->tab('Properties')->renderFilesAs('file_list')
                ->addDocumentLabel('Add file attachment(s)')
                ->noAttachmentsLabel('There are no files uploaded')
                ->fileDownloadBaseUrl(url('ls_backend/files/get/'));

            $this->add_form_field('categories', 'right')->tab('Properties');

            // Customer record finder
            $this->add_form_field('customer', 'left')->tab('Properties')->
                renderAs(frm_record_finder, array(
                    'sorting'=>'first_name, last_name, email',
                    'list_columns'=>'first_name,last_name,email,guest,created_at',
                    'search_prompt'=>'Find customer by name or email',
                    'form_title'=>'Find Customer',
                    'display_name_field'=>'full_name',
                    'display_description_field'=>'email',
                    'prompt'=>'Click the Find button to find a customer'));


            $this->add_form_field('private_note', 'full')->renderAs(frm_textarea)->size('small')->tab('Properties');
        }
        else
        {
            $this->add_form_field('author_name', 'left');
            $this->add_form_field('author_email', 'right');

            //$this->add_form_field('title');
            $this->add_form_field('status', 'left')->previewNoRelation()->collapsable();
            $this->add_form_field('created_at', 'right')->collapsable();
            $this->add_form_field('priority', 'left')->previewNoRelation()->collapsable();
            $this->add_form_field('updated_at', 'right')->collapsable();
            $this->add_form_field('customer', 'left')->collapsable();
            $this->add_form_field('user', 'right')->collapsable();            
            $this->add_form_field('categories', 'left')->collapsable();
            $this->add_form_field('primary_category', 'right')->collapsable();
            $this->add_form_field('files', 'left')->collapsable();
        }

        // Extensibility
        Backend::$events->fire_event('ahoysupport:on_extend_ticket_form', $this, $context);
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
        $result = Backend::$events->fireEvent('ahoysupport:on_get_ticket_field_options', $db_name, $current_key_value);
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
        if ($this->customer)
        {
            $this->author_name = $this->customer->name;
            $this->author_email = $this->customer->email;
        }
        
        if (!$this->email_hash)
            $this->email_hash = md5($this->email.$this->title);
    }

    public function before_create($session_key = null)
    {
        // Credit management
        $config = AhoySupport_Config::create();
        $category = $this->primary_category;
        $credits_required = $this->primary_category->credits_required;
        if ($config->use_credits && $this->primary_category->credits_required > 0)
        {
            $customer = Shop_Customer::create()->find($this->customer_id);
            $credits_available = AhoySupport_Credit::get_customer_credits($customer);
            
            if ($credits_available < $credits_required)
                throw new Phpr_ApplicationException('Sorry you need '.$category->credits_required.' credit(s), you only have '.$customer->x_ahoysupport_credits. 'credit(s)');
                
            $this->credits_used = $credits_required;
        }

        // Set new status
        $this->status = AhoySupport_Ticket_Status::create()->find_by_code(AhoySupport_Ticket_Status::status_new);
        
        // Auto assign user
        if ($category && $category->auto_assign_user)
        {
            $this->assign_user_id = $type->auto_assign_user->id;
            $this->assign_user = $type->auto_assign_user;
        }

        // Default priority: normal
        if (!strlen($this->priority_id))
            $this->priority_id = 2; 

        // Set email hash
        if (!strlen($this->email_hash))
            $this->email_hash = md5($this->email.$this->title);    
    }

    public function after_delete()
    {
        Db_DbHelper::query('delete from ahoysupport_ticket_notes where ticket_id=:id', array('id'=>$this->id));
    }

    public function after_create_saved()
    {
        $status = AhoySupport_Ticket_Status::create()->find_by_code(AhoySupport_Ticket_Status::status_new);
        $this->set_status($status, $this->assign_user_id, true);
        
        /*
         * Send the email notfiication
         */
        
        AhoySupport_Notify::trigger_ticket_new($this);
        
        /*
         * Debit the credits
         */
        
        if ($this->credits_used)
        {
            $customer = Shop_Customer::create()->find($this->customer_id);
            Support_Credit::debit_customer($customer, $this->credits_used, 'Credit(s) used, ticket #'.$this->id);
        }
    }

    public function after_save()
    {
        $updated = isset($this->fetched['id']);
        if (!$updated)
            return;
            
        if ($this->minor_update)
            return;

        AhoySupport_Notify::trigger_ticket_update($this);
    }

    // Filters
    // 

    public function apply_status_code($code, $negative_search=false)
    {
        if ($negative_search)
            $this->where('(select code from ahoysupport_ticket_statuses where ahoysupport_ticket_statuses.id = ahoysupport_tickets.status_id)!=?', $code);
        else
            $this->where('(select code from ahoysupport_ticket_statuses where ahoysupport_ticket_statuses.id = ahoysupport_tickets.status_id)=?', $code);

        return $this;
    }


    // Service methods
    //

    public function set_status($status, $assign_user_id = null, $force = false)
    {
        if ($this->status_id == $status->id && !$force)
            return;        


        $this->status_id = $status->id;
        $this->status = $status;

        $record = AhoySupport_Ticket_Status_Log::create();
        $record->status_id = $status->id;
        $record->ticket_id = $this->id;
        $record->assign_user_id = $assign_user_id;

        $record->save();
    }

    public function get_author()
    {
        return ($this->customer) ? $this->customer : $this->user;
    }

    public function page_url($include_hostname=false)
    {
        $page_url = AhoySupport_Config::create()->get_cms_ticket_page_url();

        $url = "";

        if ($page_url)
            $url .= root_url($page_url, true) . '/' . $this->id;
        else
            $url .= root_url('ticket', true) . '/' . $this->id;

        if (!$this->customer_id)
            $url .= '/'.$this->email_hash;

        return $url;
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

    public static function get_rss($feed_name, $feed_description, $feed_url, $post_url, $category_url, $blog_url, $post_number = 20, $exclude_category_ids = array())
    {
        // RESERVED
        //

        // $posts = Blog_Post::create();
        // $posts->where('is_published is not null and is_published=1');
        // $posts->order('blog_posts.created_at desc');

        // if ($exclude_category_ids)
        // {
        //     $posts->where('(not exists (select * from blog_categories, blog_posts_categories where blog_categories.id=blog_posts_categories.blog_category_id and blog_posts_categories.blog_post_id=blog_posts.id and blog_categories.id in (?)))', array($exclude_category_ids));
        // }

        // $posts = $posts->limit($post_number)->find_all();

        // $rss = new Core_Rss( $feed_name, $blog_url, $feed_description, $feed_url );
        // foreach ( $posts as $post )
        // {
        //     $link = $post_url.$post->url_title;

        //     $category_links = array();
        //     foreach ($post->categories as $category)
        //     {
        //         $cat_url = $category_url.$category->url_name;
        //         $category_links[] = "<a href=\"$cat_url\">".h($category->name)."</a>";
        //     }

        //     $category_str = "<p>Posted in: ".implode(', ', $category_links)."</p>";

        //     $rss->add_entry( $post->title,
        //         $link,
        //         $post->id,
        //         $post->published_date,
        //         strlen($post->description) ? '<p>'.$post->description.'</p>'.$category_str : $post->content.$category_str,
        //         $post->published_date,
        //         $post->created_user_name,
        //         $post->content.$category_str );
        // }

        // return $rss->to_xml();
    }

    public static function get_comments_rss($feed_name, $feed_description, $feed_url, $post_url, $category_url, $blog_url, $comment_number = 20, $exclude_category_ids = array())
    {
        // RESERVED
        //

        // $status = Blog_Comment_Status::create()->find_by_code(Blog_Comment_Status::status_approved);
        // $comments = Blog_Comment::create()->where('status_id=?', $status->id)->order('created_at desc')->limit($comment_number);

        // if ($exclude_category_ids)
        // {
        //     $comments->where('(not exists (select * from blog_posts, blog_categories, blog_posts_categories where blog_posts.id=ahoysupport_ticket_notes.post_id and  blog_categories.id=blog_posts_categories.blog_category_id and blog_posts_categories.blog_post_id=blog_posts.id and blog_categories.id in (?)))', array($exclude_category_ids));
        // }

        // $comments = $comments->find_all();

        // $rss = new Core_Rss( $feed_name, $blog_url, $feed_description, $feed_url );
        // foreach ( $comments as $comment )
        // {
        //     $link = $post_url.$comment->displayField('post_url').'#comment'.$comment->id;

        //     $rss->add_entry( $comment->displayField('post'),
        //         $link,
        //         'comment_'.$comment->id,
        //         $comment->created_at,
        //         '<p>Comment by '.h($comment->author_name).': <blockquote>'.$comment->content_html.'</blockquote>',
        //         $comment->created_at,
        //         $comment->author_name,
        //         '<p>Comment by '.h($comment->author_name).': <blockquote>'.$comment->content_html.'</blockquote>' );
        // }

        // return $rss->to_xml();
    }

    public static function expire_tickets($days)
    {
        $result = array();
        $expire_date = Phpr_DateTime::now()->addDays(-$days);
        $tickets = self::create();
        $tickets->init_columns_info();
        $tickets->where('ifnull(ahoysupport_tickets.updated_at, ahoysupport_tickets.created_at) < :expire_date', array('expire_date'=>$expire_date))
            ->where('is_updated = 0 OR is_updated is null')
            ->apply_status_code(AhoySupport_Ticket_Status::status_closed, true);

        foreach ($tickets->find_all() as $ticket)
        {
            AhoySupport_Notify::trigger_ticket_expire($ticket);
            $result[] = "Expired ticket #".$ticket->id;
            $ticket->set_status(AhoySupport_Ticket_Status::status_closed);
            $ticket->save();
        }
        return implode(PHP_EOL, $result);
    }

    public function assign_to($user_id)
    {
        /*
         * Exit if the assignee is already assigned
         */

        if ($this->assign_user_id == $user_id)
            return;

        /*
         * Find the assigne in the DB
         */
        
        $user = Users_User::create()->find($user_id);
        if (!$user)
            throw new Phpr_ApplicationException('User not found');
            
        $this->assign_user = $user;

        /*
         * Change ticket status to "processing"
         */

        $status = Support_Ticket_Status::create()->find_by_code(Support_Ticket_Status::status_processing);
        $this->assign_status($status);
        $this->save();

        /*
         * Add assignent update history record
         */
        
        $record = Support_Ticket_Status_Log::create();
        $record->assign_user_id = $user_id;
        $record->ticket_id = $this->id;
        $record->save();
        
        /*
         * Send email notfiication to the assignee
         */
        
        $current_user = Phpr::$security->getUser();
        if (!$current_user || $current_user->id != $user_id)
        {
            $template = System_EmailTemplate::create()->find_by_code('support:assignment');
            if ($template)
            {
                $message = $this->set_email_variables($template->content);
                $template->subject = $this->set_email_variables($template->subject);
                $template->send_to_team(array($user), $message);
            }
        }
    }

    public function close_ticket()
    {
        if ($this->status_code == AhoySupport_Ticket_Status::status_closed)
            return; 
        
        AhoySupport_Notify::trigger_ticket_close($this);
        $this->set_status(AhoySupport_Ticket_Status::status_closed);
        $this->save();
    }

    public static function get_ticket_statistics()
    {
        return Db_DbHelper::object(
            "select
                (select count(*) from ahoysupport_tickets) as total_count,
                (select count(*) from ahoysupport_tickets left join ahoysupport_ticket_statuses on ahoysupport_ticket_statuses.id = ahoysupport_tickets.status_id where ahoysupport_ticket_statuses.code != 'closed') as open_count,
                (select count(*) from ahoysupport_tickets left join ahoysupport_ticket_statuses on ahoysupport_ticket_statuses.id = ahoysupport_tickets.status_id where ahoysupport_ticket_statuses.code = 'closed') as closed_count
            "
        );
    }

    // Custom columns
    //

    public function eval_category_string($categories=null)
    {
        if (!$categories)
            $categories = $this->categories;

        $str = "";
        if ($categories)
        {
            foreach ($categories as $key=>$category)
            {
                if (!isset($category->name))
                    continue; 
                
                if ($key == 0) // Is first
                    $str .= $category->name;
                else
                    $str .= ", " . $category->name;
            }
        }
        return $str;
    }

    public function eval_description_plain()
    {
        if (strlen($this->description))
            return Phpr_Html::deparagraphize($this->description);
        else
            return null;
    }

    public function eval_ticket_number()
    {
        return '#'.$this->id;
    }

    public function eval_ticket_age()
    {
        return Phpr_DateTime::now()->substractDateTime($this->created_at)->intervalAsString();
    }

    public function eval_ticket_update_age()
    {
        if (!$this->updated_at)
            return "Never";

        return Phpr_DateTime::now()->substractDateTime($this->updated_at)->intervalAsString();
    }

    public function eval_last_reply_by()
    {
        $last_message = Db_DbHelper::object(
            'select ahoysupport_ticket_notes.*, users.firstName as firsName, users.lastName as lastName from ahoysupport_ticket_notes 
                left join users on users.id=ahoysupport_ticket_notes.created_user_id 
                where ticket_id=:ticket_id and (is_internal is null or is_internal=0) order by ahoysupport_ticket_notes.id desc limit 0,1',
            array('ticket_id'=>$this->id));
            
        if (!$last_message)
            return '<nobody>';
            
        if (!$last_message->is_admin_comment)
            return 'Customer';
        
        return 'Support ('.$last_message->firsName.' '.$last_message->lastName.')';
    }

}

