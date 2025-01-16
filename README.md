Auth0 for TYPO3
===============
[![Auth0TYPO3](https://www.Leuchtfeuer.com/fileadmin/github/auth0-for-typo3/TYPO3-Auth0.png "Auth0 for TYPO3")](https://www.Leuchtfeuer.com/)

[![Latest Stable Version](https://poser.pugx.org/leuchtfeuer/auth0/v/stable)](https://packagist.org/packages/leuchtfeuer/auth0)
[![Build Status](https://github.com/Leuchtfeuer/auth0-for-typo3/workflows/Continous%20Integration/badge.svg)](https://github.com/Leuchtfeuer/auth0-for-typo3)
[![Total Downloads](https://poser.pugx.org/leuchtfeuer/auth0/downloads)](https://packagist.org/leuchtfeuer/auth0)
[![Latest Unstable Version](https://poser.pugx.org/leuchtfeuer/auth0/v/unstable)](https://packagist.org/leuchtfeuer/auth0)
[![Code Climate](https://codeclimate.com/github/Leuchtfeuer/auth0-for-typo3/badges/gpa.svg)](https://codeclimate.com/github/Leuchtfeuer/auth0-for-typo3)
[![Code Coverage](https://codecov.io/gh/Leuchtfeuer/auth0-for-typo3/branch/master/graph/badge.svg?token=pclJ2SpboL)](https://codecov.io/gh/Leuchtfeuer/auth0-for-typo3)

This extension allows you to log into a TYPO3 backend via Auth0.  
The full documentation for the latest releases can be found [here](https://docs.typo3.org/p/leuchtfeuer/auth0/master/en-us/).

*If you are searching for the documentation for version 3.2.1 and below, you can take a look at the former
[bitmotion/auth0 documentation](https://docs.typo3.org/p/bitmotion/auth0/master/en-us/).*

## Requirements

You need access to an [Auth0](https://auth0.com/) instance.  
We are currently supporting following TYPO3 versions:<br><br>

| Extension Version | TYPO3 v13 Support | TYPO3 v12 Support | TYPO3 v11 Support | TYPO3 v10 Support | TYPO3 v9 Support | TYPO3 v8 Support |
|:-----------------:|:-----------------:|:-----------------:|:-----------------:|:-----------------:|:----------------:|:----------------:|
|       13.x        |         x         |         -         |         -         |         -         |        -         |         -        |
|        5.x        |         -         |         x         |         x         |         -         |        -         |        -         |
|        4.x        |         -         |         -         |         x         |         x         |        -         |        -         |
|        3.x        |         -         |         -         |         -         |         x         |        x         |                  |
|        2.x        |         -         |         -         |         -         |         -         |        x         |                  |
|        1.x        |         -         |         -         |         -         |         -         |        -         |        x         |

_Alpha support for TYPO3 v12.4 is available since version 5.0.0-alpha._

_Please note that support for frontend login was dropped with version 13.0.0._

## About Auth0
Auth0 helps you to:

* Add authentication with [multiple authentication sources](https://auth0.com/docs/identityproviders),
  either social like **Google, Facebook, Microsoft Account, LinkedIn,
  GitHub, Twitter, Box, Salesforce, among others**, or enterprise
  identity systems like Windows Azure AD, Google Apps, Active Directory,
  ADFS or any SAML Identity Provider.
* Add authentication through more traditional [username/password databases](https://auth0.com/docs/connections/database/custom-db).
* Add support for [linking different user accounts](https://auth0.com/docs/link-accounts)
  with the same user.
* Support for generating signed [JSON Web Tokens](https://auth0.com/docs/jwt)
  to call your APIs and flow the user identity securely.
* Analytics of how, when, and where users are logging in.
* Pull data from other sources and add it to the user profile, through
  [JavaScript rules](https://auth0.com/docs/rules/current).

## Contributing
You can contribute by making a **pull request** to the master branch of
this repository. Or just send us some **beers**...

### Forms
There is a way to update users metadata using the TYPO3 form framework.
A registration and "password forgotten" form is also available. If
you are interested in that, do not hesitate to contact us.

### TYPO3 as Identity Provider
It is possible to use your existing TYPO3 instance as identity provider for Auth0. This is a comfortable way to integrate Auth0
into an existing environment where all user data and passwords are already stored in your TYPO3 instance. Other applications can
easily connect to your Auth0 tenant. You will not lose any existing user data or passwords.
