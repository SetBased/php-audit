drop table if exists `TABLE1`;
drop table if exists `TABLE2`;

CREATE TABLE `TABLE1` (
  id int not null auto_increment,
  c int,
  primary key (id)
);

CREATE TABLE `TABLE2` (
  c1 varchar(10),
  c2 varchar(20),
  c3 varchar(30)
);

