.. include:: ../Includes.txt

.. _developer:

==============
For Developers
==============

You can easily access the data of the current logged in user by calling the following methods:

.. code-block:: php

   $session = (new Leuchtfeuer\Auth0\Factory\SessionFactory())->getSessionStoreForApplication();
   $userInfo = $session->getUserInfo();


User metadata is also stored as plain JSON in the TYPO3 fe_user field `auth0_metadata`. Beside of that, the last used application
is stored in the `auth0_last_application` property of the fe_user.

If you want to enrich the user metadata or remove some information, you can do it this way:

.. code-block:: php

   // Get the user Id
   $sessionStore = (new SessionFactory())->getSessionStoreForApplication();
   $user = $session->getUserInfo();
   $userId = $user['sub'];

   // Prepare data
   $data = new \stdClass();
   $data->favourite_color = 'blue';

   // Update Auth0 user
   $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
   $managementApi->users->update($userId, $data);

.. _developer-events:

Events
======

You can manipulate the actual redirect URI by listening to the :php:`RedirectPreProcessingEvent`. This event is called when a user
successfully logged in or logged off to / from your TYPO3 instance. Find more about event listening in the official
`TYPO3 documentation <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Hooks/EventDispatcher/Index.html>`__.
