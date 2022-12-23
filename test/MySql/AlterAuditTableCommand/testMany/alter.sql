alter table `TABLE1`
  modify `col1`   bigint(20),
  modify `col10`  timestamp not null default now() on update now(),
  modify `col100` varchar(10) character set ascii collate ascii_bin,
  engine InnoDB character set utf8 collate utf8_general_ci
;


