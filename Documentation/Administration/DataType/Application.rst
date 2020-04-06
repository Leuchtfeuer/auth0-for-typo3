.. include:: ../../Includes.txt

.. _application:

==================
Application Record
==================

There is one record, called *application*, which you can create/edit. It contains the Auth0 server authorization configuration
and it is used to establish a connection with your Auth0 server.

Properties
==========

.. container:: ts-properties

   ============================== ========================= ==================
   Property                       Database Property         Type
   ============================== ========================= ==================
   Name                           title_                    string
   Domain                         domain_                   string
   Client Identifier              id_                       string
   Client Secret                  secret_                   string
   JWT Signature Algorithm        signature_algorithm_      string
   Client Secret Base64 Encoded   secret_base64_encoded_    boolean
   Audience                       audience_                 string
   Single Log Out                 single_log_out_           boolean
   Enabeld                        hidden_                   boolean
   ============================== ========================= ==================

.. ### BEGIN~OF~TABLE ###

.. _admin-configuration-title:

title
-----
.. container:: table-row

   Property
         title
   Data type
         string
   Default
         unset
   Description
         A unique, freely definable, name of your application.


.. _admin-configuration-domain:

domain
------
.. container:: table-row

   Property
         domain
   Data type
         string
   Default
         unset
   Description
         The domain of your Auth0 tenant. Your tenant is available under `tenant.region.auth0.com` by default. Please fill in this
         URL without the protocol (without `https://`). If you are using **custom domains** you can fill in the URL of your
         domain (e.g. `login.example.com`).


.. _admin-configuration-id:

id
--
.. container:: table-row

   Property
         id
   Data type
         string
   Default
         unset
   Description
         The client ID of your Auth0 application.


.. _admin-configuration-secret:

secret
------
.. container:: table-row

   Property
         secret
   Data type
         string
   Default
         unset
   Description
         The client secret of your Auth0 application.


.. _admin-configuration-signature_algorithm:

signature_algorithm
-------------------
.. container:: table-row

   Property
         signature_algorithm
   Data type
         string
   Default
         :code:`RS256`
   Description
         The signature algorithm of the used JSON Web Token. Possible values are `RS256` and `HS256`


.. _admin-configuration-secret_base64_encoded:

secret_base64_encoded
---------------------
.. container:: table-row

   Property
         secret_base64_encoded
   Data type
         boolean
   Default
         :code:`false`
   Description
         Set this property to true when your client secret is base64 encoded.


.. _admin-configuration-audience:

audience
--------
.. container:: table-row

   Property
         audience
   Data type
         string
   Default
         :code:`api/v2/`
   Description
         This property contains the path to the audience of your Auth0 application. If you are using your tenant ID, the default
         value should fit your needs. If you are using a **custom domain** you should adapt this configuration and fill in the
         full URL of your audience (e.g. `https://tenant.region.auth0.com/api/v2/`).


.. _admin-configuration-single_log_out:

single_log_out
--------------
.. container:: table-row

   Property
         single_log_out
   Data type
         boolean
   Default
         :code:`true`
   Description
         Whether the user should be logged off in TYPO3 only (`false`) or logged off in Auth0 and TYPO3 (`true`).


Custom Domain
=============

If you are using a custom domain for your Auth0 tenant, than you have to do following configuration to log in using your custom
domain:

.. code-block:: typoscript

   audience = https://tenant.region.auth0.com/api/v2/
   domain = login.example.com
