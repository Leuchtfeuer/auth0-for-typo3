.. include:: ../../Includes.txt

.. _configuration:

=======================
Extension Configuration
=======================

All configuration is made in the "Extension Configuration" section of the "Settings" module beneath the "Admin Tools".

Properties
==========

.. container:: ts-properties

   ==================================== ==================================== ==================
   Property                             Tab                                  Type
   ==================================== ==================================== ==================
   enableBackendLogin_                  Backend                              boolean
   backendConnection_                   Backend                              positive integer
   reactivateDisabledBackendUsers_      Backend                              boolean
   reactivateDeletedBackendUsers_       Backend                              boolean
   softLogout_                          Backend                              boolean
   additionalAuthorizeParameters_       Backend                              string
   enableFrontendLogin_                 Frontend                             boolean
   userStoragePage_                     Frontend                             positive integer
   reactivateDisabledFrontendUsers_     Frontend                             boolean
   reactivateDeletedFrontendUsers_      Frontend                             boolean
   ==================================== ==================================== ==================

.. ### BEGIN~OF~TABLE ###

.. _admin-configuration-securedDirs:

enableBackendLogin
------------------
.. container:: table-row

   Property
         enableBackendLogin
   Data type
         boolean
   Default
         :code:`false`
   Description
         Enable Auth0 log in for TYPO3 backend.


.. _admin-configuration-backendConnection:

backendConnection
-----------------
.. container:: table-row

   Property
         backendConnection
   Data type
         positive integer
   Default
         :code:`1`
   Description
         Application identifier for backend login.


.. _admin-configuration-reactivateDisabledBackendUsers:

reactivateDisabledBackendUsers
------------------------------
.. container:: table-row

   Property
         reactivateDisabledBackendUsers
   Data type
         boolean
   Default
         :code:`false`
   Description
         Allow log in for disabled backend users.


.. _admin-configuration-reactivateDeletedBackendUsers:

reactivateDeletedBackendUsers
-----------------------------
.. container:: table-row

   Property
         reactivateDeletedBackendUsers
   Data type
         boolean
   Default
         :code:`false`
   Description
         Allow log in for deleted backend users.


.. _admin-configuration-softLogout:

softLogout
------------------
.. container:: table-row

   Property
         softLogout
   Data type
         boolean
   Default
         :code:`false`
   Description
         Log off from TYPO3 only (not from Auth0).


.. _admin-configuration-additionalAuthorizeParameters:

additionalAuthorizeParameters
-----------------------------
.. container:: table-row

   Property
         additionalAuthorizeParameters
   Data type
         string
   Default
         :code:``
   Description
         Additional query parameters for backend authentication (e.g. `access_type=offline&connection=google-oauth2`).


.. _admin-configuration-enableFrontendLogin:

enableFrontendLogin
-------------------
.. container:: table-row

   Property
         enableFrontendLogin
   Data type
         boolean
   Default
         :code:`true`
   Description
         Enable Auth0 log in for TYPO3 frontend.


.. _admin-configuration-userStoragePage:

userStoragePage
---------------
.. container:: table-row

   Property
         userStoragePage
   Data type
         positive integer
   Default
         :code:`0`
   Description
         Storage page for frontend user.


.. _admin-configuration-reactivateDisabledFrontendUsers:

reactivateDisabledFrontendUsers
-------------------------------
.. container:: table-row

   Property
         reactivateDisabledFrontendUsers
   Data type
         boolean
   Default
         :code:`true`
   Description
         Allow log in for disabled frontend users.


.. _admin-configuration-reactivateDeletedFrontendUsers:

reactivateDeletedFrontendUsers
------------------------------
.. container:: table-row

   Property
         reactivateDeletedFrontendUsers
   Data type
         boolean
   Default
         :code:`true`
   Description
         Allow log in for deleted frontend users.
