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

.. note::
   Please note that this settings are considered deprecated and will be removed with version 4.0.0. Please use the newly
   introduced :ref:`generic callback <admin-callback>` instead.

You can configure generic logon and logoff URLs for your system so that the number of callbacks to be configured in Auth0 remains
manageable. You can specify individual page IDs and page types for login and logout. The configuration can be done with the
following TypoScript constants:

.. code-block:: typoscript

   plugin.tx_auth0.settings.frontend {
      callback {
         targetPageType = 1547536919
         targetPageUid = 1
      }

      logout {
         targetPageType = 0
         targetPageUid = 1
      }
   }

It is also possible to append additional parameters to the Auth0 login URL. For example, you can preselect a specific connection
or open the registration tab (instead of the login tab). This can be implemented by the following TypoScript setup:

.. code-block:: typoscript

   plugin.tx_auth0.settings.frontend.login.additionalAuthorizeParameters {
      # key = value
      login_hint = You will log in to our shop system.
      connection = google-oauth2
   }

.. _admin-typoscript-roleMapping:

Role Mapping
============

.. note::
   Please note that this settings are considered deprecated and will be removed with version 4.0.0. Please migrate the the newly
   introduced :ref:`YAML configuration <admin-yaml>` by following the :ref:`migration guide <admin-migrations-backendModule>`.

Configure `fe_groups` and `be_groups` mappings to match Auth0 roles. Use the Auth0 role identifier as key and the TYPO3 frontend
or backend user group ID as value. These settings must be made in your TypoScript setup (not constants). The Auth0 roles are
expected in the `app_metadata` of the user under the roles key. Anyhow, you can configure the key by setting the TypoScript
constant :typoscript:`plugin.tx_auth0.settings.roles.key` to a different value.

Keep in mind, that there is one special option for backend users: You can set the admin flag by assigning the value `admin` to
an Auth0 role.

.. code-block:: typoscript

   plugin.tx_auth0.settings.roles {
       key = roles

       # be_group mappings for be_users
       be_users {
           # mapping for Auth0 role to be_groups

           # special: sets the admin flag
           admin = admin
       }

       # fe_group mappings for fe_users
       fe_users {
           # mapping for Auth0 role to fe_groups
           admin = 1
       }
   }

.. _admin-typoscript-propertyMapping:

Property Mapping
================

.. note::
   Please note that this settings are considered deprecated and will be removed with version 4.0.0. Please migrate the the newly
   introduced :ref:`YAML configuration <admin-yaml>` by following the :ref:`migration guide <admin-migrations-backendModule>`.

Auth0 properties can be mapped to existing properties of TYPO3 backend or frontend users. You can configure this mapping via
TypoScript. In this case, the key is the name of the TYPO3 database column and the value is the field key of the Auth0 user.

You can access the `user_metadata` or `app_metadata` values via dot syntax. Using the same way you can access arrays or objects
within the metadata property (e.g. `user_metadata.address.primary.zip`).  These settings must be made in your TypoScript setup
(not constants).

.. code-block:: typoscript

   plugin.tx_auth0.settings.propertyMapping {
       be_users {
           username = nickname

           crdate = created_at
           crdate.parseFunc = strtotime

           tstamp = updated_at
           tstamp.parseFunc = strtotime

           disable = email_verified
           disable.parseFunc = bool|negate

           admin = user_metadata.admin
           admin.parseFunc = bool

           description = user_metadata.description
       }

       fe_users {
           crdate = created_at
           crdate.parseFunc = strtotime

           tstamp = updated_at
           tstamp.parseFunc = strtotime

           first_name = user_metadata.description
       }
   }

.. _admin-typoscript-propertyMapping-parsingFunctions:

Parsing Functions
-----------------

Parsing functions (parseFunc) are used to change properties before they are persisted in the database.

To apply multiple parsing functions you can simply use the pipe to delimiter them. These functions will then be applied in the
order you have set them. For example, a `bool|negate` parseFunc will cast the property to a boolean value and then negate it.

The following parsing functions are available:

=========== ===========================================================================
Function    Description
=========== ===========================================================================
`bool`      Get the boolean value.
`strtotime` Parse about any English textual datetime description into a Unix timestamp.
`negate`    Negate the value (only for booleans).
=========== ===========================================================================
