drop table if exists `TABLE1`;

create table `TABLE1` (
  c int
) engine = innodb, character set = big5, collate big5_bin;


drop table if exists `TABLE2`;

create table `TABLE2` (
  c int
) engine = myisam, character set = latin1, collate latin1_spanish_ci;


drop table if exists `TABLE3`;

create table `TABLE3` (
  c int
) engine = memory, character set = geostd8, collate geostd8_bin;
