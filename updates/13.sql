INSERT INTO `system_email_templates` (`code`, `subject`, `content`, `description`) VALUES
('ahoysupport:close_ticket',
  'Ticket {ticket_number} has been closed',
  '<p>Hello {ticket_author_name},</p>

   <p>Your ticket {ticket_number} has been closed by the support team.</p>
   <p>Please use the following link to preview the ticket: <a href="{ticket_link}">{ticket_link}</a></p>
   <p>We hope that we were able to provide you with quality support. Let us know if there is anything else we can do.</p>
', 'Message template used when a ticket is closed by support.'),
('ahoysupport:expire_ticket',
  'Ticket {ticket_number} has been closed automatically',
  '<p>Hello {ticket_author_name},</p>
   <p>Your ticket {ticket_number} has been closed by the system because we have not received any reply from you for {ticket_expire_period} days.</p>
   <p>Please use the following link to preview the ticket: <a href="{ticket_link}">{ticket_link}</a></p>
   <p>We hope that we were able to provide you with quality support. Let us know if there is anything else we can do.</p>
', 'Message template used when a ticket has expired and closed automatically.');
