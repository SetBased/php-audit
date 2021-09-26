alter table `TABLE1` add column `c3` timestamp default CURRENT_TIMESTAMP after `c2`;
alter table `TABLE1` add column `c4` timestamp null default null after `c3`;
