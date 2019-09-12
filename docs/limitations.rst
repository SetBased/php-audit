Limitations
===========

PhpAudit has the following limitations:

* A ``TRUNCATE TABLE`` statement will remove all rows from a table and does not execute any triggers. Hence, the removing of those rows will not be logged in the audit table.
* A delete or update of a child row caused by a cascaded foreign key action of a parent row will not activate triggers on the child table. Hence, the update or deletion of those rows will not be logged in the audit table.

Both limitations arise from the behavior of MySQL. In practice these limitations aren't of any concern. In applications where tables are "cleaned" with a ``TRUNCATE TABLE`` we never had the need to audit these tables. We found the same for child tables with a ``ON UPDATE CASCADE`` or ``ON UPDATE SET NULL`` reference option.
