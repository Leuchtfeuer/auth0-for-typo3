.. include:: ../../Includes.txt

.. _application:

==================
Application Record
==================

There is one record, called *application*, which you can create/edit. It contains the Auth0 server authorization configuration
and it is used to establish a connection with your Auth0-Server.

You can configure the following properties:

=============  ============= =======================================================================================================
Property       Default Value Description
=============  ============= =======================================================================================================
Name                         A unique title for your application.
Domain                       The domain of your Auth0 server.
Client ID                    The client ID of your Auth0 application.
Client Secret                The client secret of your Auth0 application.
Audience       `api/v2/`     Audience for API calls.
Single Log Out `true`        Whether the user should be logged off in TYPO3 only (`false`) or logged of in Auth0 and TYPO3 (`true`).


Custom Domain
=============

If you are using a custom domain for your Auth0 tenant, than you have to do following configuration to log in using your custom
domain:

.. code-block::

   audience = https://tenant.region.auth0.com/api/v2/
   domain = your-custom.domain.de
