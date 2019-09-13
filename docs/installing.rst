Installing & Uninstalling PhpAudit
==================================

Installing PhpAudit
-------------------

The preferred way to install PhpStratum is using composer_:

.. code:: sh

  composer require setbased/php-audit

Running PhpAudit
````````````````

You can run PhpAudit from the command line:

.. code:: sh

  ./vendor/bin/audit

If you have set ``bin-dir`` in the ``config`` section in ``composer.json`` you must use a different path.

For example:

.. code:: json

  {
    "config": {
      "bin-dir": "bin/"
    }
  }

then you can run PhpAudit from the command line:

.. code:: sh

  ./bin/audit

Uninstalling PhpAudit
---------------------

Before you uninstall PhpStratum you must delete all audit triggers from the tables in the ``data schema``. This can be done with the ``drop-triggers`` command:

.. code:: sh

  ./vendor/bin/audit drop-triggers etc/config.json


Remove PhpStratum from your project with composer_:

.. code:: sh

  composer remove setbased/php-audit



.. _composer: https://getcomposer.org/
