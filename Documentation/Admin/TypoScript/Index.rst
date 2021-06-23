.. include:: ../../Includes.txt

.. _admin-typoscript:

==========
TypoScript
==========

.. _admin-typoscript-templating:

Templating
==========

Set alternative Layout/Template/Partial path individually to use your own Fluid templates. There are some TypoScript
constants which you can simply override:

.. code-block:: typoscript

   plugin.tx_auth0.view {
       templateRootPath = EXT:your_key/Resources/Private/Templates/
       partialRootPath = EXT:your_key/Resources/Private/Partials/
       layoutRootPath = EXT:your_key/Resources/Private/Layouts/
   }

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

.. _admin-typoscript-frontendSettings:

Frontend Settings
=================

It is possible to append additional parameters to the Auth0 login URL. For example, you can preselect a specific connection
or open the registration tab (instead of the login tab). This can be implemented by the following TypoScript setup:

.. code-block:: typoscript

   plugin.tx_auth0.settings.frontend.login.additionalAuthorizeParameters {
      # key = value
      login_hint = You will log in to our shop system.
      connection = google-oauth2
   }
