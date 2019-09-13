.. _audit-config-file:

The Audit Config File
=====================

This chapter is the specification of the audit config file.

For most projects the audit config file must added to the VCS and distributed to the production environment of your project (unless you have some other mechanism for maintaining audit tables and triggers).

The audit config file is a JSON file and consist out of four sections which we discuss in detail in the following sections.

.. code:: json

  {
    "database": {...},
    "audit_columns": [...],
    "additional_sql": [...],
    "tables": {...}
  }

.. _database-section:

The Database Section
--------------------

The ``database`` section holds the variables described below:

* ``credentials`` (optional)
  The filename relative to the path of the audit config file of a supplementary configuration file. Any configuration setting in the supplementary configuration file will override the setting in ``database`` section of the audit config file. You can choose your favorite configuration format for the credentials file: ini, json, xml, or yml. You can only store the password in the supplementary configuration file or all database settings.
* ``host`` (mandatory)
  The host were the MySQL server is running
* ``user``  (mandatory)
  The user that is the `owner` of the tables in the ``data schema`` and ``audit schema``.
* ``password``  (mandatory)
  The password of the `owner`.
* ``data_schema``  (mandatory)
  The schema (database) with your application tables.
* ``audit_schema``  (mandatory)
  The schema (database) for the audit tables.
  The ``data schema`` and the ``audit schema`` must be two different schemata (databases).
* ``port`` (optional)
  The port number for connecting to the MySQL server. Default value is 3306.

Convention
``````````

You are encouraged to follow this naming convention for the ``data schema`` and ``audit schema``.

Both schema (databases) names start with the name or abbreviation of your project followed by ``_data`` for the ``data schema`` and ``_audit`` for the ``audit schema``. For example ``foo_data`` and ``foo_audit``.

Examples
````````

Example 1
:::::::::

A basic example.

``audit.json``:

.. code:: json

  {
    "database": {
      "host": "localhost",
      "user": "foo_owner",
      "password": "s3cr3t",
      "data_schema": "foo_data",
      "audit_schema": "foo_audit"
    }
  }

Example 2
:::::::::

In this example the password stored in ``credentials.ini`` will be used.

``audit.json``:

.. code:: json

  {
    "database": {
      "credentials": "credentials.ini",
      "host": "localhost",
      "user": "foo_owner",
      "password": "foo_owner",
      "data_schema": "foo_data",
      "audit_schema": "foo_audit"
    }
  }

``credentials.ini``:

.. code:: ini

  [database]
  password =  s3cr3t

Example 3
:::::::::

In this example the user name and password stored in ``credentials.xml`` will be used.

``audit.json``:

.. code:: json

  {
    "database": {
      "credentials": "credentials.xml",
      "host": "localhost",
      "data_schema": "foo_data",
      "audit_schema": "foo_audit"
    }
  }

``credentials.xml``:

.. code:: xml

  <?xml version="1.0" encoding="UTF-8"?>
  <config>
      <database>
          <user>foo_owner</user>
          <password>s3cr3t</password>
      </database>
  </config>

Example 4
:::::::::

In this example only settings stored in ``credentials.json`` will be used.

``audit.json``:

.. code:: json

  {
    "database": {
      "credentials": "credentials.json"
    }
  }

``credentials.json``:

.. code:: json

  {
    "database": {
      "host": "127.0.0.1",
      "user": "foo_owner",
      "password": "foo_owner",
      "data_schema": "foo_data",
      "audit_schema": "foo_audit",
      "port": 3307
    }
  }

.. _audit-columns-section:

The Audit Columns Section
-------------------------

The audit columns section specifies the additional columns that will added to each audit table in the ``audit schema``.

The additional column specification become in two flavors:

* value is either the action (i.e. ``insert``, ``update``, or ``delete``) or the state of the row (i.e. ``NEW`` or ``OLD``),
* value is a valid SQL expression that can be used in an insert statement in a trigger.

Example
```````

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "flavor 1",
        "column_type": "...",
        "value_type": "..."
      },
      {
        "column_name": "flavor 2",
        "column_type": "...",
        "expression": "..."
      }
    ]
  }

Both flavors have the fields ``column_name`` and ``column_type`` in common.

* ``column_name``
  The name of the additional column in the audit table. You must choose a name that is not been used in any of your tables in the ``data schema`` (for which auditing is enabled).
* ``column_type``
  The column type specification as used in a ``CREATE TABLE`` statement.
* ``value_type``
  Either ``ACTION`` or ``STATE``.

  * ``ACTION``
    The action of the SQL statement that has fired the audit trigger. Possible values are ``INSERT``, ``UPDATE``, or ``DELETE``.
  * ``STATE``
    The state of the row.

    * An insert statement will insert one row in the audit table with value ``NEW``.
    * A delete statement will insert one row in the audit table with value ``OLD``.
    * An update statement will insert two rows in the audit table: ``OLD`` with the values of the row (in the data table) before the update statement and ``NEW`` with the values of the row (in the data table) after the update statement.

* ``expression`` Any valid SQL expression that can be used in an insert statement in a trigger.

Convention
``````````

You free to choose any column name for an additional table column as long the column name does not collide with a column name in a data table.

You are encouraged to follow this naming convention for the additional table column: the name of an additional table column has prefix ``audit_``.

Examples
````````

In this section we provide several useful examples for additional columns.

Additional columns are optional, however, in practice additional columns given in examples 1, 2, and 3 are at least required to record a useful audit trail.

Examples 4 and 5 for recording all data changes made in a database session and the order in which they are made.

Example 1: Timestamp
::::::::::::::::::::

Recording the time of the data change.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_timestamp",
        "column_type": "timestamp not null default now()",
        "expression": "now()"
      }
    ]
  }

Example 2: Statement Type
:::::::::::::::::::::::::

Recording the statement type of the query.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_statement",
        "column_type": "enum('INSERT','DELETE','UPDATE') character set ascii collate ascii_general_ci not null",
        "value_type": "ACTION"
      }
    ]
  }

Example 3: Row State
::::::::::::::::::::

Recording the state of the row.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_type",
        "column_type": "enum('OLD','NEW') character set ascii collate ascii_general_ci not null",
        "value_type": "STATE"
      }
    ]
  }

.. _example_database_session:

Example 4: Database Session
:::::::::::::::::::::::::::

Recording the database session (a single connection by a client). See :ref:`additional-sql-section` for setting the variable ``@audit_uuid``.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_uuid",
        "column_type": "bigint(20) unsigned not null",
        "expression": "@audit_uuid"
      }
    ]
  }

.. _example_order:

Example 5: Order
::::::::::::::::

Recording the order of the data changes. See :ref:`additional-sql-section` for setting the variable ``@audit_rownum``.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_rownum",
        "column_type": "int(10) unsigned not null",
        "expression": "@audit_rownum"
      }
    ]
  }

Example 6: Database User
::::::::::::::::::::::::

Recording the database user connection to the server. This example is useful when different database user can connect to your database. For example you have an application with a HTML frontend connecting to the database with user ``web_user``, a REST API connecting to the database with user ``api_user``, and some background process connecting to the database with user ``mail_user``.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_user",
        "column_type": "varchar(80) character set utf8 collate utf8_bin not null",
        "expression": "user()"
      }
    ]
  }

On MariaDB the maximum length of a user name is 80 characters, on mysql the maximum length of a user name is 32 characters.

Example 7: Application Session
::::::::::::::::::::::::::::::

Recording the session ID. This example is useful tracking data changes made in multiple page request in a single session of a web application.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_ses_id",
        "column_type": "int(10) unsigned",
        "expression": "@abc_g_ses_id"
      }
    ]
  }

When retrieving the session you must set the variable MySQL ``@abc_g_ses_id`` in your web application.

Example 8: End User
:::::::::::::::::::

Recording the user ID. This example is useful recording the end user who has modified the data in your (web) application.

.. code:: json

  {
    "audit_columns": [
      {
        "column_name": "audit_usr_id",
        "column_type": "int(10) unsigned",
        "expression": "@abc_g_usr_id"
      }
    ]
  }

When retrieving the session and when signing in you must set the variable MySQL ``@abc_g_usr_id`` in your (web) application.

.. _additional-sql-section:

The Additional SQL Section
--------------------------

The additional SQL section specifies additional SQL statements that are placed at the beginning of the body of each created audit trigger.

Example
```````

This example show how to set the variables ``@audit_uuid`` and ``@audit_rownum`` mentioned in :ref:`example_database_session` and :ref:`example_order`.

.. code:: json

  {
     "additional_sql": [
        "if (@audit_uuid is null) then",
        "  set @audit_uuid = uuid_short();",
        "end if;",
        "set @audit_rownum = ifnull(@audit_rownum, 0) + 1;"
      ]
  }


.. _tables-section:

The Tables Section
------------------

The tables sections holds an entry for each table in the ``data schema``. New tables are automatically added to the tables section and obsolete tables are automatically removed from the tables section when your run PhpStratum with the ``audit`` command.

Foreach table in the table section there are three fields:

* ``audit`` The audit flag. A boolean indication auditing is enabled or disabled.

   * ``true`` Recording of an audit trail for this table is enabled.
   * ``false`` Recording of an audit trail for this table is disabled.
   * ``null`` Recording of an audit trail for this table is not specified. Each time  your run PhpStratum with the ``audit`` command PhpStratum will report that a new table has been found.

* ``alias`` An alias for the table. This alias must be unique and will be used in the names of the audit trigger for this table. If you don't specify a value PhpStratum will generate automatically an alias when auditing is enabled.

* ``skip`` An optional variable name. When the value of this variable is not null the audit trigger will skip recording data changes.

When you disable recording of an audit trail of a table the audit triggers will be removed, however, the audit table will remain in the ``audit schema``.

Examples
````````

Example 1: No audit trail
:::::::::::::::::::::::::

No audit trail will be recorded for table ``TMP_IMPORT``.

.. code:: json

  {
    "tables": {
        "TMP_IMPORT": {
          "audit": false,
          "alias": null,
          "skip": null
        }
      }
  }

Example 2: Audit trail
::::::::::::::::::::::

An audit trail will be recorded for table ``FOO_USER``.

.. code:: json

  {
    "tables": {
        "FOO_USER": {
          "audit": true,
          "alias": "usr",
          "skip": "@g_skip_foo_user"
        }
      }
  }

When MySQL variable ``@g_skip_foo_user`` no audit triggers will record a data change. In the SQL code below updating column ``usr_last_login`` will not be recorded.

.. code:: sql

  set @g_skip_foo_user = 1;

  update FOO_USER
  set    usr_last_login = now()
  where  usr_id = p_usr_id
  ;

  set @g_skip_foo_user = null;

