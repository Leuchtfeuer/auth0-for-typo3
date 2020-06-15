.. include:: ../../Includes.txt

.. _admin-yaml:

==================
YAML Configuration
==================

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
           processing: bool-negate
   roles:
     default:
       frontend: 0
       backend: 0
     key: roles
     beAdmin: ''
