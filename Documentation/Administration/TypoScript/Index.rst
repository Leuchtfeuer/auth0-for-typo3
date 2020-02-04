.. include:: ../../Includes.txt

.. _typoscript:

==========
TypoScript
==========

Templating
----------

Set alternative Layout/Template/Partial path individually to use your own Fluid templates. There are some TypoScript
constants which you can simply override:

.. code-block:: typoscript

   plugin.tx_auth0.view {
       templateRootPath = EXT:your_key/Resources/Private/Templates/
       partialRootPath = EXT:your_key/Resources/Private/Partials/
       layoutRootPath = EXT:your_key/Resources/Private/Layouts/
   }


Backend Login
~~~~~~~~~~~~~

You have also the option to use your own template files for the backend login. Just adapt the following TypoScript constants:

.. code-block:: typoscript

   plugin.tx_auth0.settings.backend.view {
       layoutPath = EXT:your_key/Resources/Private/Layouts/
       templateFile = EXT:your_key/Resources/Private/Templates/Backend.html
       stylesheet = EXT:your_key/Resources/Public/Styles/Backend.css
   }

Please make also sure that you configure the roles__ from Auth0 roles to TYPO3 user groups. Maybe you also want to set the admin
flag for backend users, depending on an Auth0 properties__.

Login Behaviour
---------------

Configure whether disabled or deleted frontend or backend users should be able to login by adapting the following TypoScript
constants:

.. code-block:: typoscript

   plugin.tx_auth0.settings.reactivateUsers {
       be_users {
           # if active, sets the disable flag to 0 when user tries to login again
           disabled = 0

           # if active, sets the deleted flag to 0 when user tries to login again
           deleted = 0
       }

       fe_users {
           # if active, sets the disable flag to 0 when user tries to login again
           disabled = 1

           # if active, sets the deleted flag to 0 when user tries to login again
           deleted = 1
       }
   }

Mapping
-------

Roles
~~~~~

Configure `fe_groups` and `be_groups` mappings to match Auth0 roles. Use the Auth0 role identifier as key and the TYPO3 frontend
or backend user group ID as value.

Keep in mind, that there is one special option for backend users: You can set the admin flag by assigning the value `admin` to
an Auth0 role.

.. code-block:: typoscript

   plugin.tx_auth0.settings.roles {
       # be_group mappings for be_users
       be_users {
           #mapping for auth0 role to be_groups

           # special: sets the admin flag
           admin = admin
       }

       # fe_group mappings for fe_users
       fe_users {
           # mapping for auth0 role to fe_groups
           admin = 1
       }
   }

Properties
~~~~~~~~~~

Auth0 properties can be mapped to existing properties of TYPO3 backend or frontend users. You can configure this mapping via
TypoScript. In this case, the key is the name of the TYPO3 database column and the value is the field key of the Auth0 user.

You can access the `user_metadata` or `app_metadata` values via dot syntax. Using the same way you can access arrays or objects
within the metadata property (e.g. `user_metadata.address.primary.zip`).

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

Parsing Functions
"""""""""""""""""

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
