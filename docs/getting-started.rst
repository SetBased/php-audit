Getting Started
===============

In this chapter you will learn how to install PhpStratum and start creating audit trails on your application data.

Installing PhpAudit
-------------------

The preferred way to install PhpStratum is using composer_:

.. code:: sh

  composer require setbased/php-audit

Running PhpAudit
----------------

You can run PhpAudit from the command line:

.. code:: sh

  ./vendor/bin/audit

If you have set ``bin-dir`` in the ``config`` section in ``composer.json`` you must use a different path. For example:

.. code:: json

  "config": {
    "bin-dir": "bin/"
  }

then you can run PhpAudit from the command line:

.. code:: sh

  ./bin/audit

Throughout this manual we assume that PhpAudit is installed under ``bin/audit``.

Twin Schemata
-------------

PhpAudit requires two schemata (databases):

* One schema (database) for your application tables. We call this schema the ``data schema``.
* One schema (database) for the audit tables. We call this schema the ``audit schema``.

PhpAudit will create an audit table in the ``audit schema`` for recording the audit trail with the same name as the table in the ``data schema``. You can use any (valid) name for these two schemata (database).


The Audit Configuration File
----------------------------

The audit configuration file specification is described in detail in chapter xxx. In this section we provide an example audit configuration file.

.. code:: json

  {
    "database": {
      "host": "localhost",
      "user": "foo_owner",
      "password": "s3cr3t",
      "data_schema": "foo_data",
      "audit_schema": "foo_audit"
    },
    "audit_columns": [
      {
        "column_name": "audit_timestamp",
        "column_type": "timestamp not null default now()",
        "expression": "now()"
      },
      {
        "column_name": "audit_statement",
        "column_type": "enum('INSERT','DELETE','UPDATE') character set ascii collate ascii_general_ci not null",
        "value_type": "ACTION"
      },
      {
        "column_name": "audit_type",
        "column_type": "enum('OLD','NEW') character set ascii collate ascii_general_ci not null",
        "value_type": "STATE"
      },
      {
        "column_name": "audit_uuid",
        "column_type": "bigint(20) unsigned not null",
        "expression": "@audit_uuid"
      },
      {
        "column_name": "audit_rownum",
        "column_type": "int(10) unsigned not null",
        "expression": "@audit_rownum"
      }
    ],
    "additional_sql": [
      "if (@audit_uuid is null) then",
      "  set @audit_uuid = uuid_short();",
      "end if;",
      "set @audit_rownum = ifnull(@audit_rownum, 0) + 1;"
    ]
  }

The audit configuration file consists out of 3 sections:

* The ``database`` section, we will discuss this section below and in detail in section xxx.
* The ``audit_columns`` section. See section xxx for a detailed explanation.
* The ``additional_sql`` section. See section xxx for a detailed explanation.

The `` database`` section holds the variables described in the table below:

================ =======================================================================================
Name             Description
---------------- ---------------------------------------------------------------------------------------
``host``         The host were the MySQL server is running
``user``         The user that is the `owner` of the tables in the ``data schema`` and ``audit schema``.
                 See section xxx for an exact description of required grants.
``password``     The password of the `owner`. In section xxx we describe how to store the password
                 outside the audit configuration file.
``data_schema``  The schema (database) with your application tables.
``audit_schema`` The schema (database) for the audit tables. The ``data schema`` and the
                 ``audit schema`` must be two different schemata (databases).
================ =======================================================================================

Throughout this manual we assume that the audit configuration file is stored in ``etc/audit.json``. You are free to choose your preferred path.

Run PhpStratum with the ``audit`` command:

.. code:: sh

  ./bin/audit audit etc/audit.json

Output:

.. code:: text

  Found new table FOO_EMPLOYEE
   Wrote etc/audit.json

The first time you run the audit command PhpAudit will only report the tables found in the ``data schema`` and add the tables in de ``tables`` section in the audit configuration file. Suppose you application has a table ``FOO_EMPLOYEE``, the ``tables`` section will look like:

.. code:: json

  {
    "database": {...},
    "audit_columns": [...],
    "additional_sql": [...],
    "tables": {
      "FOO_EMPLOYEE": {
        "audit": null,
        "alias": null,
        "skip": null
      }
    }
  }

For all tables for which you want an audit trail you must set the audit flag to true. In our example:

.. code:: json

  {
    "database": {...},
    "audit_columns": [...],
    "additional_sql": [...],
    "tables": {
      "FOO_EMPLOYEE": {
        "audit": true,
        "alias": null,
        "skip": null
      }
    }
  }

and rerun PhpStratum with the ``audit`` command:

.. code:: sh

  ./bin/audit audit etc/audit.json

Output:

.. code:: text

  Creating audit table foo_audit.FOO_EMPLOYEE
  Wrote etc/audit.json

You can now insert, update, and delete rows in/from table ``foo_data.FOO_EMPLOYEE`` and see the recorded audit trail in table ``foo_audit.FOO_EMPLOYEE``.

Verbosity
---------

In verbose mode (``-v``) the ``audit`` command will show triggers dropped and created:

.. code:: sh

  ./bin/audit -v audit etc/audit.json

Output:

.. code:: text

  Creating audit table foo_audit.FOO_EMPLOYEE
  Creating trigger foo_data.trg_audit_5d7a1d1e18ada_insert on table foo_data.FOO_EMPLOYEE
  Creating trigger foo_data.trg_audit_5d7a1d1e18ada_update on table foo_data.FOO_EMPLOYEE
  Creating trigger foo_data.trg_audit_5d7a1d1e18ada_delete on table foo_data.FOO_EMPLOYEE
  Wrote etc/audit.json

In very verbose mode (``-vv``) PhpAudit will show each executed SQL statement also.

.. _composer: https://getcomposer.org/
