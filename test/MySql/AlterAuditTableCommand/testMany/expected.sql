alter table `test_audit`.`TABLE1` engine InnoDB default character set utf8 default collate utf8_general_ci;

alter table `test_audit`.`TABLE1`
  change column `col1`   `col1`   bigint(20) null,
  change column `col10`  `col10`  timestamp null default null,
  change column `col100` `col100` varchar(10) character set ascii collate ascii_bin null
;
