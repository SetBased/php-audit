#!/bin/bash -e -x

mysql -v -uroot -h127.0.0.1 < test/MySql/ddl/0010_create_database.sql
mysql -v -uroot -h127.0.0.1 < test/MySql/ddl/0020_create_user.sql
./bin/phpunit
