.. include:: ../../Includes.txt

.. _admin-migrations-backendModule:

============================
TypoScript to Backend Module
============================

If you update this extension from a version lower than 3.3.0, you must follow these steps to get rid of obsolete TypoScript
settings.

.. rst-class:: bignums-xxl

   1. Make your TypoScript available for the backend module

      The TypoScript settings of both :typoscript:`plugin.tx_auth0.settings.roles` and
      :typoscript:`plugin.tx_auth0.settings.propertyMapping` has to be available for the backend module. You can archive this by
      adding following lines of TypoScript at the bottom of your TypoScript template:

      .. code-block:: typoscript

         module.tx_auth0.settings.roles < plugin.tx_auth0.settings.roles
         module.tx_auth0.settings.propertyMapping < plugin.tx_auth0.settings.propertyMapping

   2. Migrate the role mapping

      Navigate into the Auth0 :ref:`backend module <admin-module>` and click on the "configure" button in the "Roles to Groups"
      card. There should be an info box on top of the content. Click on the "Import configuration from TypoScript" button. After
      the page refreshed, the module will output the configuration migrated from you TypoScript.

      .. figure:: ../../Images/migrate-backend-module.png
         :alt: The backend module.
         :class: with-shadow

         View of the backend module.

   3. Migrate the property mapping

      Select the "Property Mapping" option of the select box on top of the page and proceed as described in 2.

   4. Unset your TypoScript

      Open your TypoScript template and unset the former configuration:

      .. code-block:: typoscript

         plugin.tx_auth0.settings.roles >
         plugin.tx_auth0.settings.propertyMapping >

      Do not forget to remove the configuration you made in 1. Afterwards the info boxes in the backend module will disappear.
