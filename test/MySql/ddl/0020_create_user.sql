drop user if exists 'test'@'localhost';

create user 'test'@'localhost' identified by 'test';

grant all on test_data.*  to 'test'@'localhost';
grant all on test_audit.*  to 'test'@'localhost';
