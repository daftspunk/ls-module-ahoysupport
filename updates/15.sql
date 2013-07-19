alter table ahoysupport_categories 
    add column is_hidden tinyint(4),
    add column auto_assign_id int(11) default null;

rename table ahoysupport_notes to ahoysupport_ticket_notes;
alter table ahoysupport_ticket_notes add column is_internal tinyint(4);
create index is_internal on ahoysupport_ticket_notes(is_internal);

CREATE TABLE `ahoysupport_ticket_status_log` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_id` int(11) default NULL,
  `status_id` int(11) default NULL,
  `assign_user_id` int(11) default NULL,
  `created_at` datetime default NULL,
  `created_by` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `assign_user_id` (`assign_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
