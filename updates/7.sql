alter table ahoysupport_tickets add column is_updated tinyint;
alter table ahoysupport_tickets add column email_hash varchar(100) default NULL;
create index email_hash on ahoysupport_tickets(email_hash);
    
