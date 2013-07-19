INSERT INTO `system_email_templates` (`code`, `subject`, `content`, `description`) VALUES
('ahoysupport:new_ticket_internal',
  '[Ticket {ticket_number}] ({ticket_status}) {ticket_title}',
  'Ticket {ticket_number} has been reported by {ticket_author_name}.
  <hr />
  <p><a href="{ticket_admin_link}">Ticket {ticket_number}: {ticket_title}</a></p>
  {support_ticket_info}

  <blockquote>{ticket_description}</blockquote>
', 'Internal message template used when a new ticket is created.'),
('ahoysupport:update_ticket_internal',
  '[Ticket {ticket_number}] ({ticket_status}) {ticket_title}',
  'Ticket {ticket_number} has been updated by {note_author_name}.
  <blockquote>{note_description}</blockquote>
  <hr />

  <p><a href="{ticket_admin_link}">Ticket {ticket_number}: {ticket_title}</a></p>
  {support_ticket_info}

  <blockquote>{ticket_description}</blockquote>
', 'Message template used when a new ticket is updated.'),
('ahoysupport:update_ticket',
  'Response to your ticket {ticket_number}',
  '<p>Hello {ticket_author_name},</p>
  There has been a response by {note_author_name} to your ticket <a href="{ticket_link}">{ticket_number}</a> - {ticket_title}.
  <blockquote>{note_description}</blockquote>
  <hr />

  <p><a href="{ticket_link}">Ticket {ticket_number}: {ticket_title}</a></p>
  {support_ticket_info}

  <blockquote>{ticket_description}</blockquote>
', 'Message template used when a new ticket is updated.');


INSERT INTO `system_compound_email_vars` (`code`, `content`, `scope`, `description`) VALUES
('support_ticket_info','<ul>
    <li>Author: <?=$ticket->author_name?></li>
    <li>Status: <?=$ticket->status->name?></li>
    <li>Priority: <?=$ticket->priority->name?></li>
    <li>Asignee: <?=($ticket->user)?$ticket->user->name:"Nobody"?></li>
    <li>Category: <?=$ticket->category_string?></li>
</ul>',
    'ahoysupport:ticket', 'Outputs a ticket summary');