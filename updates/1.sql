CREATE TABLE `ahoysupport_tickets` (
  `id` int(11) NOT NULL auto_increment,
  `author_name` varchar(255) default NULL,
  `author_email` varchar(100) default NULL,
  `created_at` datetime default NULL,
  `updated_at` datetime default NULL,
  `created_user_id` int(11) default NULL,
  `updated_user_id` int(11) default NULL,
  `title` varchar(255) default NULL,
  `description` text,
  `private_note` text,
  `category_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `customer_id` int(11) default NULL,
  `status_id` int(11) default NULL,
  `priority_id` int(11) default NULL,
  `is_admin_comment` tinyint(4) default NULL,
  PRIMARY KEY  (`id`),
  KEY `category_id` (`category_id`),
  KEY `priority_id` (`priority_id`),
  KEY `status_id` (`status_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `ahoysupport_ticket_statuses` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(30) default NULL,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `ahoysupport_ticket_statuses` (`code`, `name`) VALUES
('new', 'New'),
('processing', 'Processing'),
('closed', 'Closed');

CREATE TABLE `ahoysupport_ticket_priorities` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(30) default NULL,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `ahoysupport_ticket_priorities` (`code`, `name`) VALUES
('low', 'Low'),
('normal', 'Normal'),
('high', 'High'),
('urgent', 'Urgent'),
('immediate', 'Immediate');

CREATE TABLE `ahoysupport_categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` text,
  `code` varchar(50) default NULL,
  `created_at` datetime default NULL,
  `updated_at` datetime default NULL,
  `created_user_id` int(11) default NULL,
  `updated_user_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `ahoysupport_categories` (`id`, `name`, `description`, `code`, `created_at`) VALUES
(1, 'General enquiries', 'For all enquiries that do not fall in to any other category', 'general', now()),
(2, 'Order enquiries', 'Questions or comments realted to ordering and shipping', 'order', now()),
(3, 'Product questions', 'Questions about our product range', 'product', now()),
(4, 'Press enquiries', 'If you want to know more information about our business', 'press', now());

CREATE TABLE `ahoysupport_ticket_categories` (
  `ahoysupport_ticket_id` int(11) NOT NULL default '0',
  `ahoysupport_category_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ahoysupport_ticket_id`,`ahoysupport_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `ahoysupport_notes` (
  `id` int(11) NOT NULL auto_increment,
  `author_name` varchar(255) default NULL,
  `author_email` varchar(100) default NULL,
  `ticket_id` int(11) default NULL,
  `description` text,
  `description_plain` text,
  `private_note` text,
  `customer_id` int(11) default NULL,
  `assign_user_id` int(11) default NULL,
  `status_id` int(11) default NULL,
  `priority_id` int(11) default NULL,
  `is_admin_comment` tinyint(4) default NULL,
  `author_ip` varchar(15) default NULL,
  `created_at` datetime default NULL,
  `updated_at` datetime default NULL,
  `created_user_id` int(11) default NULL,
  `updated_user_id` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `author_ip` (`author_ip`),
  KEY `status_id` (`status_id`),
  KEY `priority_id` (`priority_id`),
  KEY `customer_id` (`customer_id`),
  KEY `assign_user_id` (`assign_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
