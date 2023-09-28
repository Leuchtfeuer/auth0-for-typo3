.. include:: ../../Includes.txt

.. _admin-yaml:

==================
YAML Configuration
==================

Since version 3.3.0 of this extension, the property mapping configuration is stored in a dedicated YAML configuration file in the
configuration folder of your TYPO3 installation. The file is updated when the configuration is saved in the backend module.
However, you can edit its contents directly if necessary.

Default Configuration
=====================

.. code-block:: yaml

   properties:
     fe_users:
       root:
         -
           auth0Property: created_at
           databaseField: crdate
           readOnly: true
           processing: strtotime
         -
           auth0Property: updated_at
           databaseField: tstamp
           readOnly: true
           processing: strtotime
     be_users:
       root:
         -
           auth0Property: created_at
           databaseField: crdate
           readOnly: true
           processing: strtotime
         -
           auth0Property: updated_at
           databaseField: tstamp
           readOnly: true
           processing: strtotime
         -
           auth0Property: email_verified
           databaseField: disable
           readOnly: true
           processing: negate-bool
   roles:
     default:
       frontend: 0
       backend: 0
     key: roles
     beAdmin: ''
