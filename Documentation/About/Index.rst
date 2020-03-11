.. include:: ../Includes.txt

.. _about:

=====
About
=====

This extension allows you to log on to a TYPO3 front- or backend with `Auth0 <https://auth0.com/>`__.

Requirements
============

You need access to an Auth0 instance. We are currently supporting following TYPO3 versions:

.. table:: Version Matrix
   :align: left

   ================= ================== =============== ===============
   Extension Version TYPO3 10.3 Support TYPO3 9 Support TYPO3 8 Support
   ================= ================== =============== ===============
   3.1.x             x                  x               -
   3.0.x             -                  x               -
   2.x               -                  x               -
   1.x               -                  -               x


About Auth0
===========

Auth0 helps you to:

* Add authentication with `multiple authentication sources <https://auth0.com/docs/identityproviders>`__, either social like
  **Google, Facebook, Microsoft Account, LinkedIn, GitHub, Twitter, Box, Salesforce, among others**, or enterprise identity
  systems like Windows Azure AD, Google Apps, Active Directory, ADFS or any SAML Identity Provider.
* Add authentication through more traditional
  `username/password databases <https://auth0.com/docs/connections/database/custom-db>`__.
* Add support for `linking different user accounts <https://auth0.com/docs/link-accounts>`__ with the same user.
* Support for generating signed `JSON Web Tokens <https://auth0.com/docs/jwt>`__ to call your APIs and flow the user
  identity securely.
* Analytics of how, when, and where users are logging in.
* Pull data from other sources and add it to the user profile, through
  `JavaScript rules <https://auth0.com/docs/rules/current>`__.

Next Steps
==========

* Allow to override TYPO3 User Avatar with the Auth0 profile avatar
* Move connection parameter from TypoScript :typoscript:`plugin.tx_auth0.settings.frontend.login.additionalAuthorizeParameters` to
  application data record or plugin setting
* Support custom domains (different audience in class Auth0 constructor)
* Backend Module for Auth0 Administration



.. toctree::
    :maxdepth: 3
    :hidden:

    Changelog/Index
