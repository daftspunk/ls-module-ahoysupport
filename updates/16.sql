CREATE TABLE `ahoysupport_credit_log` (
  `id` int(11) NOT NULL auto_increment,
  `customer_id` int(11) default NULL,
  `created_at` datetime default NULL,
  `comment` varchar(255) default NULL,
  `created_user_id` int(11) default NULL,
  `credits` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

alter table ahoysupport_tickets add column credits_used int;

alter table ahoysupport_tickets add column primary_category_id int;
create index primary_category_id on ahoysupport_tickets(primary_category_id);