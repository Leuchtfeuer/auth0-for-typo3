.. include:: ../Includes.txt

.. _developer:

==============
For Developers
==============

You can easily access the data of the current logged in user by calling the following methods:

.. code-block:: php

   $session = (new Bitmotion\Auth0\Factory\SessionFactory())->getSessionStoreForApplication();
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

.. _developer-hooks:

Hooks
=====

.. note::

   Please note that this hook is considered deprecated and will be removed with version 4.0.0. Please use the PSR-14 event
   instead (when possible).

The hook is available in the :php:`$GLOBALS['TYPO3_CONF_VARS']` array:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['auth0']['redirect_pre_processing']


The called method receives the redirect URL (string) as argument. For more information you can read the following event section.

.. _developer-events:

Events
======

.. note::

   Events are available in TYPO3 v10 only.

You can manipulate the actual redirect URI by listening to the :php:`RedirectPreProcessingEvent`. This event is called when a user
successfully logged in or logged off to / from your TYPO3 instance. Find more about event listening in the official
`TYPO3 documentation <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Hooks/EventDispatcher/Index.html>`__.
