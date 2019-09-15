Installing & Uninstalling PhpAudit
==================================

Installing PhpAudit
-------------------

The preferred way to install PhpAudit is using composer_:

.. code-block:: sh

  composer require setbased/php-audit

Running PhpAudit
````````````````

You can run PhpAudit from the command line:

.. code-block:: sh

  ./vendor/bin/audit

If you have set ``bin-dir`` in the ``config`` section in ``composer.json`` you must use a different path.

For example:

.. code-block:: json

  {
    "config": {
      "bin-dir": "bin/"
    }
  }

then you can run PhpAudit from the command line:

.. code-block:: sh

  ./bin/audit

Uninstalling PhpAudit
---------------------

Before you uninstall PhpAudit you must delete all audit triggers from the tables in the ``data schema``. This can be done with the ``drop-triggers`` command:

.. code-block:: sh

  ./vendor/bin/audit drop-triggers etc/config.json


Remove PhpAudit from your project with composer_:

.. code-block:: sh

  composer remove setbased/php-audit



.. _composer: https://getcomposer.org/
