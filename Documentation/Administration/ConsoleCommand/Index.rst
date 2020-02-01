.. include:: ../../Includes.txt

.. _command:

================
Console Commands
================

There is one symfony command available which takes care of your backend users. A user that is removed from Auth0 or whose access
has expired will be disabled or removed from the TYPO3 database.

You have the following options:

================= ==========================================================================================
Method            Description
================= ==========================================================================================
disable           Disables the user (sets the disabled flag to true). This is the default value.
delete            Deletes the user (sets the deleted flag to true). The record still exists in the database.
deleteIrrevocable Removes the user irrevocable from the database.

You can execute the command controller via CLI:

.. code-block:: bash

   path/to/php bin/typo3 auth:cleanupusers disable
