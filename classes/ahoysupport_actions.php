<?php

class AhoySupport_Actions extends Cms_ActionScope
{
    public function tickets()
    {
        if (!$this->customer)
            return $this->data['tickets'] = null;

        $tickets = AhoySupport_Ticket::create()->where('customer_id=?', $this->customer->id)->order('ahoysupport_tickets.created_at')->find_all();
        $this->data['tickets'] = $tickets;
    }

    public function submit_ticket()
    {
        $this->ticket();
    }

    public function ticket()
    {        
        $action_commands = array('get_file');

        if (post('ticket_submit'))
            $this->on_ticket_submit(false);

        if (post('note_submit'))
            $this->on_note_submit(false);

        // Categories
        $categories = AhoySupport_Category::create()->order('name')->find_all();
        $this->data['categories'] = $categories;

        $this->set_ticket_objects();

        // Ticket
        $ticket = AhoySupport_Ticket::create();
        $ticket_id = $this->request_param(0);

        if ($ticket_id=="sent")
            $ticket_id = null;

        if (!strlen($ticket_id))
            return $this->data['ticket'] = null;

        $email_hash = $this->request_param(1);
        if (in_array($email_hash, $action_commands))
            $email_hash = null;

        if (!$email_hash&&!$this->customer)
            return $this->data['ticket'] = null;

        if ($email_hash)
            $ticket->where('email_hash=?', $email_hash);
        else
            $ticket->where('customer_id=?', $this->customer->id);

        $ticket = $ticket->find($ticket_id);
        $this->data['ticket'] = $ticket;

        if (!$ticket)
            return Phpr::$session->flash['error'] = "That ticket could not be found or you may not have permission to view it. Make sure you are logged in and try again.";

        // File downloads
        $action = $this->request_param(1);
        if ($action == "get_file")
        {
            $file_id = $this->request_param(2);
            $note_id = $this->request_param(3);
            $this->on_get_file($ticket, $file_id, $note_id);
        }
    }

    public function on_category_update()
    {
        $category_id = post('category_id');
        if (!$category_id)
            return $this->data['category'] = null;

        $this->set_ticket_objects();

        $category = AhoySupport_Category::create()->find($category_id);
        $this->data['category'] = $category;
    }

    private function set_ticket_objects()
    {
        // Priorities
        $priorities = AhoySupport_Ticket_Priority::create()->find_all();
        $this->data['priorities'] = $priorities;

        // Config
        $config = AhoySupport_Config::create();
        $this->data['config'] = $config;

        // Credit product
        $product = Shop_Product::create()->find($config->credit_product_id);
        $this->data['credits_product'] = $product;
    }

    public function on_ticket_submit($ajax_mode = true)
    {
        if ($ajax_mode)
            $this->action();

        $session_key = post('ls_session_key');

        try
        {

            $config = AhoySupport_Config::create();
            $ticket = AhoySupport_Ticket::create();

            if (!$this->customer)
            {
                if (!$config->ticket_allow_guests)
                    throw new Exception('Sorry you must be logged in to submit a ticket');

                $data = array(
                    'email' => post('email'),
                    'name' => post('name'),
                );

                $validation = new Phpr_Validation();
                $validation->add('email', 'Email address')->fn('trim')->required('Please specify your email address.');
                $validation->add('name', 'Name')->fn('trim')->required('Please specify your name.');

                if (!$validation->validate($data))
                    $validation->throwException();

                $ticket->author_name = $data['name'];
                $ticket->author_email = $data['email'];
            }
            else
            {
                $ticket->customer_id = $this->customer->id;
                $ticket->author_name = $this->customer->name;
                $ticket->author_email = $this->customer->email;
            }

            $category_id = post('category_id');
            $category = AhoySupport_Category::create()->find($category_id);

            $ticket->primary_category = $category;
            $ticket->categories->add($category);

            if (array_key_exists('files', $_FILES))
            {
                $file_data = Phpr_Files::extract_mutli_file_info($_FILES['files']);

                foreach ($file_data as $file)
                {
                    $ticket->add_file_from_post($file, $session_key);
                }
            }

            // Format text
            if (!post('use_wysiwyg') && post('description'))
                $_POST['description'] = Phpr_Html::paragraphize(post('description'));

            $ticket->save($_POST, $session_key);

            if (!post('no_flash'))
            {
                $message = post('message', 'Ticket submitted successfully');
                Phpr::$session->flash['success'] = $message;
            }

            if ($redirect = post('redirect'))
                Phpr::$response->redirect(str_replace('%s', $ticket->id, $redirect));
        }
        catch (Exception $ex)
        {
            if ($ajax_mode)
                throw new Cms_Exception($ex->getMessage());
            else
                Phpr::$session->flash['error'] = $ex->getMessage();

        }
    }

    public function on_ticket_edit()
    {
        $ticket_id = post('ticket_id', false);
        if (!$ticket_id)
            throw new Cms_Exception("Missing a Ticket ID");

        $email_hash = post('email_hash');
        if (!$email_hash&&!$this->customer)
            throw new Cms_Exception("Missing a Ticket ID or you are not logged in");

        // Ticket
        $ticket = AhoySupport_Ticket::create();

        if (!$this->customer)
            $ticket->where('email_hash=?', $email_hash);
        else
            $ticket->where('customer_id=?', $this->customer->id);

        $ticket = $ticket->find($ticket_id);

        if (!$ticket)
            throw new Cms_Exception("Could not find message with ID (".$ticket_id.")");

        if ($ticket->status->code == AhoySupport_Ticket_Status::status_closed)
            throw new Cms_Exception("Sorry, this message is closed and cannot be edited");

        if (post('mode') == "save")
        {
            $ticket->disable_column_cache();
            $ticket->init_columns_info();
            $ticket->validation->focusPrefix = null;
            $ticket->save($_POST);
        }
        else if (post('mode') == "close")
        {
            $ticket->close_ticket();
            $ticket->save();
        }

        if (post('flash'))
            Phpr::$session->flash['success'] = post('flash');

        if ($redirect = post('redirect'))
        {
            $redirect = ($email_hash) ? $redirect.'/'.$email_hash : $redirect;
            Phpr::$response->redirect(str_replace('%s', $ticket->id, $redirect));
        }

        $this->data['ticket'] = $ticket;
    }

    public function on_note_submit($ajax_mode = true)
    {
        if ($ajax_mode)
            $this->action();

        $session_key = post('ls_session_key');

        try
        {
            $ticket_id = post('ticket_id');
            if (!$ticket_id)
                throw new Exception('Missing ticket id');

            $email_hash = post('email_hash');
            if (!$email_hash&&!$this->customer)
                throw new Exception('Sorry you must be logged in to reply');

            // Ticket
            $ticket = AhoySupport_Ticket::create();

            if ($email_hash)
                $ticket->where('email_hash=?', $email_hash);
            else
                $ticket->where('customer_id=?', $this->customer->id);

            $ticket = $ticket->find($ticket_id);

            if (!$ticket)
                throw new Exception('Sorry something went wrong, unable to find ticket');

            // Found a customerless ticket with a logged in user, bind them!
            if (!$ticket->customer_id && $this->customer)
            {
                $ticket->customer_id = $this->customer->id;
                $ticket->customer = $this->customer;
                $ticket->save();
            }

            $note = AhoySupport_Ticket_Note::create();
            $note->ticket = $ticket;
            $note->ticket_id = $ticket->id;
            
            if ($this->customer)
            {
                $note->customer_id = $this->customer->id;
                $note->author_name = $this->customer->name;
                $note->author_email = $this->customer->email;
            }
            else 
            {
                $note->author_name = $ticket->author_name;
                $note->author_email = $ticket->author_email;                
            }

            if (array_key_exists('files', $_FILES))
            {
                $file_data = Phpr_Files::extract_mutli_file_info($_FILES['files']);

                foreach ($file_data as $file)
                {
                    $note->add_file_from_post($file, $session_key);
                }
            }

            // Format text
            if (!post('use_wysiwyg') && post('description'))
                $_POST['description'] = Phpr_Html::paragraphize(post('description'));

            $note->save($_POST, $session_key);

            $ticket->updated_at = Phpr_DateTime::now();
            $ticket->is_updated = true;
            $ticket->save();
            
            if (!post('no_flash'))
            {
                $message = post('message', 'Your message has been added successfully');
                Phpr::$session->flash['success'] = $message;
            }

            if ($redirect = post('redirect'))
            {
                $redirect = ($email_hash) ? $redirect.'/'.$email_hash : $redirect;
                Phpr::$response->redirect(str_replace('%s', $ticket->id, $redirect));
            }

        }
        catch (Exception $ex)
        {
            if ($ajax_mode)
                throw new Cms_Exception($ex->getMessage());
            else
                Phpr::$session->flash['error'] = $ex->getMessage();
        }

    }

    public function on_note_edit()
    {
        $note_id = post('note_id', false);
        if (!$note_id)
            throw new Cms_Exception("Missing a Note ID");

        $note = AhoySupport_Ticket_Note::create()->where('customer_id=?', $this->customer->id)->find($note_id);

        if (!$note)
            throw new Cms_Exception("Could not find note with ID (".$note_id.")");

        if ($note->ticket->status->code == AhoySupport_Ticket_Status::status_closed)
            throw new Cms_Exception("Sorry, this ticket is closed an cannot be edited");

        if (post('mode') == "save")
        {
            $note->disable_column_cache();
            $note->init_columns_info();
            $note->validation->focusPrefix = null;
            $note->save($_POST);
        }

        if (post('flash'))
            Phpr::$session->flash['success'] = post('flash');

        $redirect = post('redirect');
        if ($redirect)
            Phpr::$response->redirect(str_replace('%s', $ticket->id, $redirect));

        $this->data['note'] = $note;
    }

    public function on_get_file($ticket=null, $file_id=null, $note_id=null, $mode=null)
    {
        if (!$ticket||!strlen($file_id))
            die("File not found");

        if ($note_id)
            $object = $ticket->notes->find_by('id', $note_id);
        else
            $object = $ticket;

        if (!$object)
            die("File not found");

        try
        {
            if ($mode != 'inline' || $mode != 'attachment')
                $mode = 'attachment';

            $file = Db_File::create()
                        ->where('master_object_id=?',$object->id)
                        ->where('master_object_class=?', get_class($object))
                        ->find($file_id);

            if ($file)
            {
                $file->output($mode);
                die();
            }
            else
                die("File not found");
        }
        catch (Exception $ex)
        {
            echo $ex->getMessage();
        }

        die();
    }

}