Welcome to PhpAudit's documentation!
====================================

PhpAudit is a tool for creating and maintaining audit tables and triggers for creating audit trails of data changes in MySQL and MariaDB databases.

PhpAudit has the following features:

* Creates audit tables for tables in your database for which auditing is required.
* Creates triggers on tables for recording inserts, updates, and deletes of rows.
* Helps you to maintain audit tables and triggers when you modify your application's tables.
* Reports differences in table structure between your application's tables and audit tables.
* Disabling triggers under certain conditions.
* Flexible configuration. You can define additional columns to audit tables, for example: logging user and session IDs.

Using the audit trail you track changes made to the data of your application by the users of the application.
Even of data that has been deleted or changed back to its original state. Also, you can track how your application manipulates data and find bugs if your application.

Table of Contents
-----------------

.. toctree::

   getting-started
   installing
   example
   audit-config-file
   php-audit-program
   schema-changes
   miscellaneous
   license
