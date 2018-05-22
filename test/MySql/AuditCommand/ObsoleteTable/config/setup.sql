drop table if exists `TABLE1`;
drop table if exists `TABLE2`;

create table `TABLE1` (
  id int not null auto_increment,
  c int,
  primary key (id)
);

create table `TABLE2` (
  c1 varchar(10),
  c2 varchar(20),
  c3 varchar(30)
);

