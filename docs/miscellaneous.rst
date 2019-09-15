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

.. code:: sql

  create user `foo_audit`@`localhost`;
  grant lock tables, select, trigger on `foo_data`.* to `foo_audit`@`localhost`;
  grant create, drop, insert, select on `foo_audit`.* to `foo_audit`@`localhost`;

Remember a trigger is running under the definer, i.e. the user which the trigger is created.

Indexes
-------

PhpAudit does not create any indexes on tables in the ``audit schema``. Creating an audit trail is about inserting rows in audit tables only. Hence, PhpAudit does not requires any indexes.

If your application is querying on tables in the ``audit schema`` you are free to add indexes on the tables in the ``audit schema``. PhpAudit will not drop or alter any indexes in the ``audit schema``.

Be careful with unique indexes. A key of a table in the ``data schema`` will (very likely) not be a key of the corresponding table in the ``audit schema``.

Limitations
-----------

PhpAudit has the following limitations:

* A ``TRUNCATE TABLE`` statement will remove all rows from a table and does not activate any triggers. Hence, the removing of those rows will not be logged in the audit table.
* A delete or update of a child row caused by a cascaded foreign key action of a parent row will not activate triggers on the child table. Hence, the update or deletion of those rows will not be logged in the audit table.

Both limitations arise from the behavior of MySQL. In practice these limitations aren't of any concern. In applications where tables are "cleaned" with a ``TRUNCATE TABLE`` we never had the need to audit these tables. We found the same for child tables with a ``ON UPDATE CASCADE`` or ``ON UPDATE SET NULL`` reference option.
