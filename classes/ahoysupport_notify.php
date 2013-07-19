<?php

class AhoySupport_Notify
{

    public static $config = null;

    public static function trigger_ticket_new($ticket, $session_key = null)
    {
        if (!self::$config)
            self::$config = AhoySupport_Config::create();

        $file_attachments = self::get_attachments($ticket, $session_key);

        // Internal template
        $template_admin = AhoySupport_EmailTemplate::create()->find_by_code('ahoysupport:new_ticket_internal');
        self::set_ticket_vars($template_admin, $ticket);

        // Attach files
        $file_attachments = self::get_attachments($ticket, $session_key);
        $template_admin->file_attachments = $file_attachments;

        if (self::$config->ticket_notifications_rule == AhoySupport_Config::notify_all)
        {
            $users = Users_User::list_users_having_permission('ahoysupport', 'manage_support');
            $template_admin->send_to_team($users, $template_admin->content);
        }
    }

    public static function trigger_ticket_update($ticket, $session_key = null)
    {
        //support:ticket_updated
    }

    public static function trigger_ticket_close($ticket, $session_key = null)
    {
        $template_public = AhoySupport_EmailTemplate::create()->find_by_code('ahoysupport:close_ticket');
        self::set_ticket_vars($template_public, $ticket);

        if ($ticket->customer)
            $template_public->send_to_customer($ticket->customer, $template_public->content);
        else
            $template_public->send($ticket->author_email, $template_public->content, $ticket->author_name);
    }

    public static function trigger_ticket_expire($ticket, $session_key = null)
    {
        $template_public = AhoySupport_EmailTemplate::create()->find_by_code('ahoysupport:expire_ticket');
        self::set_ticket_vars($template_public, $ticket);

        if ($ticket->customer)
            $template_public->send_to_customer($ticket->customer, $template_public->content);
        else
            $template_public->send($ticket->author_email, $template_public->content, $ticket->author_name);
    }

    public static function trigger_note_new($note, $session_key = null)
    {
        if (!self::$config)
            self::$config = AhoySupport_Config::create();

        // Internal template
        $template_admin = AhoySupport_EmailTemplate::create()->find_by_code('ahoysupport:update_ticket_internal');
        self::set_ticket_vars($template_admin, $note->ticket);
        self::set_note_vars($template_admin, $note);

        // Customer template
        $template_public = AhoySupport_EmailTemplate::create()->find_by_code('ahoysupport:update_ticket');
        self::set_ticket_vars($template_public, $note->ticket);
        self::set_note_vars($template_public, $note);

        // Attach files
        $file_attachments = self::get_attachments($note, $session_key);
        $template_public->file_attachments = $file_attachments;
        $template_admin->file_attachments = $file_attachments;

        switch (self::$config->note_notifications_rule)
        {
            case AhoySupport_Config::notify_all:

                $users = Users_User::list_users_having_permission('ahoysupport', 'manage_support');
                $template_admin->send_to_team($users, $template_admin->content);

                // Email customer
                if ((!self::$config->ticket_notifications_send_self) || (self::$config->ticket_notifications_send_self && $note->is_admin_comment))
                {
                    if ($note->ticket->customer)
                        $template_public->send_to_customer($note->ticket->customer, $template_public->content);
                    else
                        $template_public->send($note->ticket->author_email, $template_public->content, $note->ticket->author_name);
                }

            break;

            case AhoySupport_Config::notify_authors:

                // Email admin
                if ($note->ticket->user)
                    $admin_user = $note->ticket->user;
                else
                    $admin_user = Users_User::create()->find($note->ticket->updated_user_id);

                if ($admin_user && ((!self::$config->ticket_notifications_send_self) || (self::$config->ticket_notifications_send_self && !$note->is_admin_comment)))
                    $template_admin->send_to_team(array($admin_user), $template_admin->content);

                // Email customer
                if ((!self::$config->ticket_notifications_send_self) || (self::$config->ticket_notifications_send_self && $note->is_admin_comment))
                {
                    if ($note->ticket->customer)
                        $template_public->send_to_customer($note->ticket->customer, $template_public->content);
                    else
                        $template_public->send($note->ticket->author_email, $template_public->content, $note->ticket->author_name);
                }

            break;
        }
    }

    public static function set_ticket_vars(&$template, $ticket)
    {
        // Must call this as url() function does not support $add_host_name_and_protocol
        $backend_url = Phpr::$config->get('BACKEND_URL', 'backend');
        $config = AhoySupport_Config::create();

        $template->subject = str_replace('{ticket_number}', $ticket->ticket_number, $template->subject);
        $template->subject = str_replace('{ticket_status}', $ticket->status->name, $template->subject);
        $template->subject = str_replace('{ticket_title}', $ticket->title, $template->subject);
        $template->subject = str_replace('{ticket_expire_period}', $config->auto_expire_days, $template->subject);

        $template_text = $template->content;
        $template_text = str_replace('{ticket_number}', h($ticket->ticket_number), $template_text);
        $template_text = str_replace('{ticket_title}', h($ticket->title), $template_text);
        $template_text = str_replace('{ticket_link}', $ticket->page_url(true), $template_text);
        $template_text = str_replace('{ticket_admin_link}', root_url($backend_url.'/ahoysupport/tickets/preview/'.$ticket->id, true), $template_text);
        $template_text = str_replace('{ticket_author_name}', h($ticket->author_name), $template_text);
        $template_text = str_replace('{ticket_description}', $ticket->description, $template_text);
        $template_text = str_replace('{ticket_expire_period}', $config->auto_expire_days, $template_text);

        $email_scope_vars = array('ticket'=>$ticket);
        $template_text = System_CompoundEmailVar::apply_scope_variables($template_text, 'ahoysupport:ticket', $email_scope_vars);
        $template->content = $template_text;
    }

    public static function set_note_vars(&$template, $note)
    {
        $template_text = $template->content;
        $template_text = str_replace('{note_link}', $note->ticket->page_url(true).'#note'.$note->id, $template_text);
        $template_text = str_replace('{note_description}', $note->description, $template_text);
        $template_text = str_replace('{note_author_name}', h($note->author_name), $template_text);
        $template->content = $template_text;
    }

    public static function get_attachments($object, $session_key=null)
    {
        if (!self::$config)
            self::$config = AhoySupport_Config::create();

        if (!self::$config->include_email_attachments)
            return array();

        $file_attachments = array();
        $files = array();

        if (count($object->files))
            $files = $object->files;
        else if ($session_key)
            $files = $object->list_related_records_deferred('files', $session_key);

        foreach ($files as $file)
        {
            $path = PATH_APP . $file->getPath();
            $file_attachments[$path] = $file->name;
        }

        return $file_attachments;
    }

}