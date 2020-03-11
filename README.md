Auth0 for TYPO3
===============
[![Auth0TYPO3](https://www.bitmotion.de/fileadmin/github/auth0-for-typo3/TYPO3-Auth0.png "Auth0 for TYPO3")](https://www.bitmotion.de/)

[![Latest Stable Version](https://poser.pugx.org/bitmotion/auth0/v/stable)](https://packagist.org/packages/bitmotion/auth0)
[![Build Status](https://travis-ci.com/bitmotion/auth0-for-typo3.svg?branch=master)](https://travis-ci.com/bitmotion/auth0-for-typo3)
[![Total Downloads](https://poser.pugx.org/bitmotion/auth0/downloads)](https://packagist.org/packages/bitmotion/auth0)
[![Latest Unstable Version](https://poser.pugx.org/bitmotion/auth0/v/unstable)](https://packagist.org/packages/bitmotion/auth0)
[![Code Climate](https://codeclimate.com/github/bitmotion/auth0-for-typo3/badges/gpa.svg)](https://codeclimate.com/github/bitmotion/auth0-for-typo3)
[![License](https://poser.pugx.org/bitmotion/auth0/license)](https://packagist.org/packages/bitmotion/auth0)

This extension allows you to log into a TYPO3 backend or frontend via Auth0.  
The full documentation can be found [here](https://docs.typo3.org/p/bitmotion/auth0/master/en-us/).

## Requirements ##

You need access to an [Auth0](https://auth0.com/) instance.  
We are currently supporting following TYPO3 versions:<br><br>

| Extension Version | TYPO3 10.3 Support | TYPO3 9 Support | TYPO3 8 Support |
| :-: | :-: | :-: | :-: |
| 3.1.x             | x                  | x               | -               |
| 3.0.x             | -                  | x               | -               |
| 2.x               | -                  | x               | -               |
| 1.x               | -                  | -               | x               |

## About Auth0 ##
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

## Contributing ##
You can contribute by making a **pull request** to the master branch of
this repository. Or just send us some **beers**...

### Forms ###
There is a way to update users metadata using the TYPO3 form framework.
A registration and "password forgotten" form is also available. If
you are interested in that, do not hesitate to contact us.
