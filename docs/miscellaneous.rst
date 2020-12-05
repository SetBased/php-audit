Miscellaneous
=============

In this chapter we discuss miscellaneous aspects of PhpAudit.

.. _required-grants:

Required Grants
---------------

The (MySQL) user under which PhpAudit is connecting to the database instance requires the following grants:

* ``data schema``:

  * ``lock tables``
  * ``select``
  * ``trigger``

* ``audit schema``:

  * ``create``
  * ``drop``
  * ``insert``
  * ``select``

For example:

.. code-block:: sql

  create user `foo_audit`@`localhost`;
  grant lock tables, select, trigger on `foo_data`.* to `foo_audit`@`localhost`;
  grant create, drop, insert, select on `foo_audit`.* to `foo_audit`@`localhost`;

Remember a trigger is running under the definer, i.e. the user which the trigger is created.

Indexes
-------

PhpAudit does not create any indexes on tables in the ``audit schema``. Creating an audit trail is about inserting rows in audit tables only. Hence, PhpAudit does not requires any indexes.

If your application is querying on tables in the ``audit schema`` you are free to add indexes on the tables in the ``audit schema``. PhpAudit will not drop or alter any indexes in the ``audit schema``.

Be careful with unique indexes. A key of a table in the ``data schema`` will (very likely) not be a key of the corresponding table in the ``audit schema``.

.. _setting-user-defined-variables-in-mysql:

Setting User Defined Variables in MySQL
---------------------------------------

There are several ways for setting user defined variables in MySQL from your PHP application. In this section we discuss two methods. More information about user defined variables in MySQL can be found at `https://mariadb.com/kb/en/user-defined-variables/ <https://mariadb.com/kb/en/user-defined-variables/>`_ and `https://dev.mysql.com/doc/refman/8.0/en/user-variables.html <https://dev.mysql.com/doc/refman/8.0/en/user-variables.html>`_

Explicit Query From PHP
```````````````````````

The PHP snippet below is an example of setting a user defined variable in MySQL from a PHP application.

.. code-block:: PHP

  // User has signed in and variable $usrId holds the ID of the user and
  // $mysql is the connection to MySQL.
  $mysql->real_query(sprintf('set @audit_usr_id = %s', $usrId ?? 'null'));

Implicit in SQL Query
`````````````````````

The SQL statement below is an example of setting user defined variables in MySQL in a SQL statement (in this example session data is stored in table `FOO_SESSION`).

.. code-block:: php

  select @audit_ses_id := ses_id
  ,      @audit_usr_id := usr_id
  ,      ses_data
  from   FOO_SESSION
  where  ses_token = 'the-long-token-stored-in-the-session-cookie-of-the-user-agent'
  ;

Limitations
-----------

PhpAudit has the following limitations:

* A ``TRUNCATE TABLE`` statement will remove all rows from a table and does not activate any triggers. Hence, the removing of those rows will not be logged in the audit table.
* A delete or update of a child row caused by a cascaded foreign key action of a parent row will not activate triggers on the child table. Hence, the update or deletion of those rows will not be logged in the audit table.

Both limitations arise from the behavior of MySQL. In practice these limitations aren't of any concern. In applications where tables are "cleaned" with a ``TRUNCATE TABLE`` we never had the need to audit these tables. We found the same for child tables with a ``ON UPDATE CASCADE`` or ``ON UPDATE SET NULL`` reference option.
