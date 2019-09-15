An Example
==========

In this section we give a real world example taken from a tournament on the `Nahouw`_. We have reduced the tournament table to two columns and changed some IDs for simplification.

.. code-block:: sql

  select *
  from   nahouw_data.NAH_TOURNAMENT
  where  trn_id = 4473

Output:

+--------+---------------+
| trn_id | trn_name      |
+========+===============+
| 4773   | Correct name  |
+--------+---------------+

The audit trail for this tournament:

.. code-block:: sql

  select *
  from   nahouw_audit.NAH_TOURNAMENT
  where  trn_id = 4473


+---------------------+-----------------+-------------+--------------------+--------------+--------------+--------------+--------+--------------+
| audit_timestamp     | audit_statement | audit_state | audit_uuid         | audit_rownum | audit_ses_id | audit_usr_id | trn_id | trn_name     |
+=====================+=================+=============+====================+==============+==============+==============+========+==============+
| 2012-05-05 08:36:06 | INSERT          | NEW         | 310616503508533789 | 2            | 34532889     | 65           | 4773   | Wrong name   |
+---------------------+-----------------+-------------+--------------------+--------------+--------------+--------------+--------+--------------+
| 2013-02-01 10:55:01 | UPDATE          | OLD         | 311037142136521378 | 5            | 564977477    | 107          | 4773   | Wrong name   |
+---------------------+-----------------+-------------+--------------------+--------------+--------------+--------------+--------+--------------+
| 2013-02-01 10:55:01 | UPDATE          | NEW         | 311037142136521378 | 5            | 564977477    | 107          | 4773   | Correct name |
+---------------------+-----------------+-------------+--------------------+--------------+--------------+--------------+--------+--------------+

Notice that the audit table has 7 additional columns. You can configure more or less columns and name them to your needs.

* ``audit_timestamp``: The time the statement was executed.
* ``audit_statement``: The type of statement. One of INSERT, UPDATE, OR DELETE.
* ``audit_sate``:      The state of the row. NEW or OLD.
* ``audit_uuid``:      A UUID per database connection. Using this ID we can track all changes made during a page request.
* ``audit_rownum``:    The number of the audit row within the UUID. Using this column we can track the order in which changes are made during a page request.
* ``audit_ses_id``:    The ID the session of the web application.
* ``audit_usr_id``:    The ID of the user has made the page request.

From the audit trail we can see that user 65 has initially entered the tournament with a wrong name.
We see that the tournament insert statement was the second statement executed. Using UUID 310616503508533789 we found the first statement was an insert statement of the tournament's location which is stored in another table.
Later user 107 has changed the tournament name to its correct name.

On table ``nahouw_data.NAH_TOURNAMENT`` we have three triggers, one for insert statements, one for update statements, and one for delete statements.
Below is the code for the update statement (the code for the other triggers look similar).

.. code-block:: sql

  create trigger `nahouw_data`.`trg_trn_update`
  after UPDATE on `nahouw_data`.`NAH_TOURNAMENT`
  for each row
  begin
    if (@audit_uuid is null) then
      set @audit_uuid = uuid_short();
    end if;
    set @audit_rownum = ifnull(@audit_rownum, 0) + 1;
    insert into `nahouw_audit`.`NAH_TOURNAMENT`(audit_timestamp,audit_type,audit_state,audit_uuid,rownum,audit_ses_id,audit_usr_id,trn_id,trn_name)
    values(now(),'UPDATE','OLD',@audit_uuid,@audit_rownum,@abc_g_ses_id,@abc_g_usr_id,OLD.`trn_id`,OLD.`trn_name`);
    insert into `nahouw_audit`.`NAH_TOURNAMENT`(audit_timestamp,audit_type,audit_state,audit_uuid,rownum,audit_ses_id,audit_usr_id,trn_id,trn_name)
    values(now(),'UPDATE','NEW',@audit_uuid,@audit_rownum,@abc_g_ses_id,@abc_g_usr_id,NEW.`trn_id`,NEW.`trn_name`);
  end

.. _Nahouw: https://www.nahouw.net
