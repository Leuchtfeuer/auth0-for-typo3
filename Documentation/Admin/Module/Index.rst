.. include:: ../../Includes.txt

.. _admin-backendModule:

==============
Backend Module
==============
The backend module was introduced with version 3.3 of this extension. You can configure the mapping from Auth0 data to TYPO3
properties. Also you can configure your Application data records. The module is located in the admin tools section and is
available for backend admins / system maintainer only.

.. figure:: ../../Images/module-overview.png
   :alt: The backend module
   :class: with-shadow

   Cards overview of the backend module.

.. _admin-backendModule-applicationList:

Application List
================

.. figure:: ../../Images/module-applications.png
   :alt: List of application data records within the backend module
   :class: with-shadow

   List of application data records within the backend module.

.. _admin-backendModule-rolesToGroups:

Roles To Groups
===============

.. figure:: ../../Images/module-roles.png
   :alt: Roles mapping within the backend module
   :class: with-shadow

   Roles mapping within the backend module..

.. _admin-backendModule-propertyMapping:

Property Mapping
================

.. figure:: ../../Images/module-properties.png
   :alt: Property mapping within the backend module
   :class: with-shadow

   Property mapping within the backend module.

.. _admin-backendModule-propertyMapping-properties:

Add / Update Properties
-----------------------

.. _admin-backendModule-propertyMapping-properties-valueProcessing:

Value Processing
~~~~~~~~~~~~~~~~
Parsing functions (parseFunc) are used to change properties before they are persisted in the database.

These processing functions are available by default:

============== ===========================================================================
Function       Description
============== ===========================================================================
`bool`         Get the boolean value.
`negate bool`  Negates a boolean value.
`strtotime`    Parse about any English textual datetime description into a Unix timestamp.
============== ===========================================================================


