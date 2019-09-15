.. _schema-changes-and-deployment:

Schema Changes and Deployment
=============================

During the life time of your application there will schema changes:

* new tables will be created,
* obsolete tables will be dropped,
* tables will be renamed,
* table options will change,
* new columns will added to a table,
* obsolete columns will be drop from a table,
* columns will be renamed,
* column types will change.

In this chapter we discuss how to handle all these types of changes. Also, we discuss how to deploy schema changes on the production environment.

PhpAudit becomes with two commands that helps you to compare the ``data schema`` with the ``audit schema``:

* The ``diff`` command, see :ref:`diff-command`.
* The ``alter-audit-table`` command, see :ref:`alter-audit-table-command`.

Schema Changes
--------------

In this section we discuss all possible schema changes one by one. You can combine many schema changes on one go.

New Table
`````````

When adding a new table to the database of your application you must decide whether auditing is required for this table.

* Run the `DDL`_ statements for creating the new table.
* Run the ``audit`` command of PhpAudit. PhpAudit will report that it has found a new table.

  * Auditing is not required for the new table:

    * Set the `audit flag`_ for the new table to ``false``.

  * Auditing is required for the new table:

    * Set the `audit flag`_ for the new table to ``true``.

    * Run the ``audit`` command of PhpAudit again. This time an audit table and audit triggers will be created for the new table.

* Commit the changes in the audit config file to your VCS.


Obsolete Table
``````````````

* Run the `DDL`_ statements for dropping the obsolete table.
* Run the ``audit`` command of PhpAudit. PhpAudit will report that it has found an obsolete table.

  * PhpAudit will remove the obsolete table from the `tables section`_.

  * PhpAudit will not drop the table from the ``audit schema``. The corresponding table in the ``audit schema`` is still a part of your application's audit trail.

* Commit the changes in the audit config file to your VCS.

If you decide now or later that the corresponding table in the ``audit schema`` is not longer required you must drop the corresponding table in the ``audit schema`` your self. This does not affect PhpAudit at all nor requires any action by PhpAudit.

Renamed Table
`````````````

When you rename a table in the ``data schema`` there is no reliable way for PhpAudit to detect a table has been renamed. PhpAudit will see an obsolete and a new table.

* Run the `DDL`_ statements for renaming the table in the ``data schema``.
* Run similar `DDL`_ statements for renaming the corresponding table in the ``audit schema``.
* Rename the table in the `tables section`_ of the audit config file.

  * At this moment the audit triggers on the table in ``data schema`` are still using the old table name in the ``audit schema``.

* Run the ``audit`` command of PhpAudit.

  * The audit triggers on the table in ``data schema`` are using new table name in the ``audit schema`` now.
* Commit the changes in the audit config file to your VCS.

If you omit renaming the table in the audit config file, PhpAudit will report an obsolete table and a new table. I this case you must restore the table configuration (i.e. the audit file, alias and skip variable) in the aut config file.

If you omit renaming the corresponding table in the ``audit schema``, PhpAudit will create a new table in the ``audit schema``.

Table Options
`````````````

* Run the `DDL`_ statements for altering the table options of the table in the ``data schema``.
* Run similar `DDL`_  statements for altering the table options of the corresponding table in the ``audit schema``.

PhpAudit is unaware of most table options. It only considers the following table options:

* CHARACTER SET
* COLLATE
* ENGINE

Running the ``audit`` command of PhpAudit will not affect the table options of any table in the ``audit schema``.

See XXX for a discussing about transactional and non transaction storage engines.

New Column
``````````

* Run the `DDL`_ statements for adding the new column to the table ``data schema``.
* Run the ``audit`` command of PhpAudit.

  * The new column will be added to the corresponding table in the ``audit schema`` and added to the queries in the audit triggers.


Obsolete Column
```````````````

* Run the `DDL`_ statements for dropping the obsolete column from the table ``data schema``.
* Run the ``audit`` command of PhpAudit.

  * The obsolete column will be removed from the queries in the audit triggers.
  * The obsolete column in the corresponding table in the ``audit schema`` is still a part of your application's audit trail and will not be dropped.

If you decide now or later that the obsolete column in the corresponding table in the ``audit schema`` is not longer required you must drop the obsolete column in the corresponding table in the ``audit schema`` your self. This does not affect PhpAudit at all nor requires any action by PhpAudit.

Renamed Column
``````````````

When you rename a column of a table in the ``data schema`` there is no reliable way for PhpAudit to detect a  column has been renamed. PhpAudit will see an obsolete and a new column.

* Run the `DDL`_ statements for renaming the column of the table in the ``data schema``.
* Run similar `DDL`_ statements for renaming the column of the corresponding table in the ``audit schema``.

  * At this moment the audit triggers on the table in ``data schema`` are still using the old column name.
* Run the ``audit`` command of PhpAudit.

  * The audit triggers on the table in the ``data schema`` are using the new column name now.

.. _changed-column-type:

Changed Column Type
```````````````````

We consider two types of column type changes:

* Changing the column type to a more comprehensive column type. For example:

  * ``varchar(10) charset utf8 collation utf8_general_ci`` => ``varchar(20) charset utf8 collation utf8_general_ci``
  * ``varchar(80) charset ascii collation ascii_general_ci`` => ``varchar(80) charset utf8 collation utf8_general_ci``
  * ``smallint(4)`` => ``int(6)``

* Changing the column type to a less comprehensive or incompatible column type: For example:

  * ``varchar(10) charset utf8 collation utf8_general_ci`` => ``int(10)``
  * ``varchar(80) charset utf8 collation utf8_general_ci`` => ``varchar(80) charset latin1 collation latin1_general_ci``
  * ``longblob`` => ``medium text``

Currently, automatically modification of columns of tables in the ``audit schema`` is not implemented and planned for a future release.

We consider three kinds of less comprehensive or incompatible column types:

* The audit trail does not contain any data that cannot be converted to the new column type. For example:

  * A ``varchar(10)`` that holds only integers (as strings) in both the data and audit table can be modified to an ``int(10)`` without any issues.
  * A ``varchar(80) charset utf8 collation utf8_general_ci`` that holds only latin1 characters in both the data and audit table can be modified to an ``varchar(80) charset latin1 collation latin1_general_ci`` without any issues.

* The audit trail does contain data that cannot be converted to the new column type however a more comprehensive column type (for the actual data in both columns in the ``data schema`` and ``audit schema``) is available. For example:

  * A ``varchar(10) charset utf8 collation utf8_general_ci`` (that must be modified to ``varchar(30) charset latin1 collation latin1_general_ci``) that holds only latin1 characters in the data table, but the audit table holds data outside the latin1 character set. In this case the column in the ``data schema`` can be converted to ``varchar(30) charset latin1 collation latin1_general_ci`` and the column in the ``audit schema`` can be converted to ``varchar(30) charset utf8 collation utf8_general_ci``.

* The audit trail does contain data that cannot be converted to the new column type and a more comprehensive column type is not available. For example:

  * A ``varbinary(10)`` (that must be modified to ``int(10)``) column holding binary in the audit trail but not any more in the data table.

  In this case to only solution is to rename the column in the audit table. The ``audit`` command of PhpAudit will create a new column in the audit table with the new column type.

Deployment
----------

In the above section we discuss all possible schema changes one by one. of course you can combine all schema changes in one go. The basic rules are simple:

* Renaming tables:

  * Rename the tables in the ``data schema``.
  * Rename the corresponding tables in the ``audit schema``.
  * Rename the tables in the audit config file.

* Renaming columns:

  * Rename the columns in the ``data schema``.
  * Rename the columns in the corresponding tables in the ``audit schema``.

* Changing column types:

  * Change the column types in the ``data schema``.
  * Change the column types  in the corresponding tables in the ``audit schema``.
  * See :ref:`changed-column-type` for incompatible column type changes.

* Run the ``audit`` command of PhpStratum.

Simple Deployment
``````````````````

If your deployment script has only `DDL`_ statements (affecting tables that require auditing), followed by only (or none) `DML`_ statements (affecting tables that require auditing), it is called a simple deployment. You must your deployment as scripts as follows:

* Run the `DDL`_ statements.
* Run the ``audit`` command of PhpAudit.

  * Use the latest version of your audit config file.
  * All audit tables and triggers are in a proper state to capture the data changes caused by the following `DML`_ statements.

* Run the `DML`_ statements.

Complex Deployment
``````````````````

If your deployment script has only `DDL`_ statements (affecting tables that require auditing), followed by only (or none) `DML`_ statements (affecting tables that require auditing), followed by only `DDL`_ statements (affecting tables that require auditing), followed by only (or none) `DML`_ statements (affecting tables that require auditing) and so on, it is called a complex deployment. You must your deployment as scripts as follows:

* Run `DDL`_ statements.
* Run the ``audit`` command of PhpAudit (with the latest version of you audit config file).

  * Use the latest version of your audit config file.
  * Make sure that the `audit flags`_ for are still correct.
  * All audit tables and triggers are in a proper state to capture the data changes caused by the following `DML`_ statements.

* Run `DML`_ statements.
* Run `DDL`_ statements.
* Run the ``audit`` command of PhpAudit (with the latest version of you audit config file).

  * Use the latest version of your audit config file.
  * Make sure that the `audit flags`_ for are still correct.
  * All audit tables and triggers are in a proper state to capture the data changes caused by the following `DML`_ statements.

* Run `DML`_ statements.
* and so on

.. _audit flag: audit-config-file.html#audit-flag
.. _audit flags: audit-config-file.html#audit-flag
.. _tables section: audit-config-file.html#tables-section
.. _DDL: https://en.wikipedia.org/wiki/Data_definition_language
.. _DML: https://en.wikipedia.org/wiki/Data_manipulation_language
