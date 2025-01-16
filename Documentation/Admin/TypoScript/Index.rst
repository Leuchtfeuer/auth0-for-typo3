.. include:: ../../Includes.txt

.. _admin-typoscript:

==========
TypoScript
==========

.. _admin-typoscript-templating:

Templating
==========

.. _admin-typoscript-templating-backendLogin:

Backend Login
-------------

You have also the option to use your own template files for the backend login. Just adapt the following TypoScript constants:

.. code-block:: typoscript

   module.tx_auth0 {
       view {
           layoutPath = EXT:your_key/Resources/Private/Layouts/
           templatePath = EXT:your_key/Resources/Private/Templates/
       }

       settings.stylesheet = EXT:your_key/Resources/Public/Styles/Backend.css
   }

Please make also sure that you configure the :ref:`role mapping <admin-typoscript-roleMapping>` from Auth0 roles to TYPO3 user
groups. Maybe you also want to set the admin flag for backend users, depending on an Auth0
:ref:`role mapping <admin-typoscript-propertyMapping>`.
