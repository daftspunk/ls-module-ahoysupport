INSERT INTO `system_email_templates` (`code`,`subject`,`content`,`description`,`is_system`,`reply_to_mode`,`reply_to_address`) VALUES 
('ahoysupport:assignment', 
  'Support ticket assignment',
  '<p>Hello,</p>
  <p>You have been assigned for the support ticket #{support_ticket_id}.</p>
  <p>Ticket subject: {support_ticket_subject}<br />Customer: {support_customer_name}</p>
  <p>To preview the ticket please <a href=\"{support_ticket_preview_url}\">click here</a>.</p>',
  'The support ticket assignment notification', 
  1,'default',NULL);