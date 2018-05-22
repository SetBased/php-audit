alter table `TABLE1`
  modify `col1`   int(8),
  modify `col10`  timestamp not null default now() on update now(),
  modify `col100` varchar(10) character set ascii collate ascii_bin
;


