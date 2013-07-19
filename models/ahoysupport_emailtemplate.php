<?php

// Bah, System_EmplateTemplate doesn't support attachments!
//
// Code below is duplicated from System_EmplateTemplate with
// the addition of $file_attachments.
//

class AhoySupport_EmailTemplate extends System_EmailTemplate
{

    // Populate this for attachments, eg:
    // array('/absolute/file/path/file.jpg' => 'filename.jpg')
    //
    public $file_attachments = array();

    public static function create($values = null)
    {
        return new self($values);
    }

    /**
     * Sends email message to a specified customer
     * @param Shop_Customer $customer Specifies a customer to send a message to
     * @param string $message_text Specifies a message text
     */
    public function send_to_customer($customer, $message_text, $sender_email = null, $sender_name = null, $customer_email = null, $customer_name = null, $custom_data = null)
    {
        try
        {
            $template = System_EmailLayout::find_by_code('external');
            $message_text = $template->format($message_text);

            $viewData = array('content'=>$message_text, 'custom_data'=>$custom_data);
            $reply_to = $this->get_reply_address($sender_email, $sender_name, $customer_email, $customer_name);

            Core_Email::send('system', 'email_message', $viewData, $this->subject, $customer->name, $customer->email, array(), null, $reply_to, $this->file_attachments);
        }
        catch (exception $ex)
        {
        }
    }

    /**
     * Sends email message to a a list of the store team members
     * @param mixed $users Specifies a list of users to send the message to
     * @param string $message_text Specifies a message text
     */
    public function send_to_team($users, $message_text, $sender_email = null, $sender_name = null, $customer_email = null, $customer_name = null, $throw_exceptions = false)
    {
        $reply_to = $this->get_reply_address($sender_email, $sender_name, $customer_email, $customer_name);

        try
        {
            $template = System_EmailLayout::find_by_code('system');
            $message_text = $template->format($message_text);

            $viewData = array('content'=>$message_text);
            Core_Email::sendToList('system', 'email_message', $viewData, $this->subject, $users, $throw_exceptions, $reply_to, $this->file_attachments);
        }
        catch (exception $ex)
        {
            if ($throw_exceptions)
                throw $ex;
        }
    }


    /**
     * Sends email message to a specified email address
     * @param string $email Specifies an email address to send the message to
     * @param string $message_text Specifies a message text
     * @param string $name Specifies a recipient name
     */
    public function send($email, $message_text, $name = null, $sender_email = null, $sender_name = null, $customer_email = null, $customer_name = null)
    {
        if (!$name)
            $name = $email;

        $template = System_EmailLayout::find_by_code('external');
        $message_text = $template->format($message_text);

        $viewData = array('content'=>$message_text);
        $reply_to = $this->get_reply_address($sender_email, $sender_name, $customer_email, $customer_name);

        Core_Email::send('system', 'email_message', $viewData, $this->subject, $name, $email, array(), null, $reply_to, $this->file_attachments);
    }

}