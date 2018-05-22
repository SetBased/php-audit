drop table if exists `AUT_COMPANY`;

create table `AUT_COMPANY` (
  `cmp_id` SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL,
  `cmp_abbr` VARCHAR(15) NOT NULL,
  `cmp_label` VARCHAR(20) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  CONSTRAINT `PRIMARY_KEY` PRIMARY KEY (`cmp_id`)
) engine = innodb;
