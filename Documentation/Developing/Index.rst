.. include:: ../Includes.txt

.. _developing:

==============
For Developers
==============

You can easily access the data of the current logged in user by calling the following methods:

.. code-block:: php

   $sessionStore = new \Auth0\SDK\Store\SessionStore();
   $userInfo = $sessionStore->get('user');


User metadata is also stored as plain JSON in the TYPO3 fe_user field `auth0_metadata`. Beside of that, the last used application
is stored in the `auth0_last_application` property of the fe_user.

If you want to enrich the user metadata or remove some information, you can do it this way:

.. code-block:: php

   // Get the user Id
   $sessionStore = new SessionStore();
   $user = $store->get('user');
   $userId = $user['sub'];

   // Prepare data
   $data = new \stdClass();
   $data->favourite_color = 'blue';

   // Update Auth0 user
   $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
   $managementApi->users->update($userId, $data);
