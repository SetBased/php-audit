drop user if exists 'test'@'127.0.0.1';

create user 'test'@'127.0.0.1' identified by 'test';

grant all on test_data.*  to 'test'@'127.0.0.1';
grant all on test_audit.*  to 'test'@'127.0.0.1';
