drop table if exists `ABC_AUTH_COMPANY`;

create table `ABC_AUTH_COMPANY` (
  `cmp_id` SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `cmp_abbr` VARCHAR(15) NOT NULL,
  `cmp_label` VARCHAR(20) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  CONSTRAINT `PRIMARY_KEY` PRIMARY KEY (`cmp_id`)
) engine = innodb;
