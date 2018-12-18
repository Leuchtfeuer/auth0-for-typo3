Auth0 for TYPO3
===============
[![Auth0TYPO3](https://www.bitmotion.de/fileadmin/github/auth0-for-typo3/TYPO3-Auth0.png "Auth0 for TYPO3")](https://www.bitmotion.de/)

**Attention: This extension is still under development.**

## About ##
This extension allows you to log into a TYPO3 backend or frontend via Auth0.

### Requirements ###
Currently we only support [TYPO3 8 LTS](https://get.typo3.org/version/8). You also need access to an [Auth0](https://auth0.com/) instance.

## For Administrators ##
### Installation ###
We recommend to require this extension via composer:
```
composer require bitmotion/auth0
```
If your TYPO3 is not in composer mode, you can install this extension in your extension manager or download the source code from the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/auth0/).

Please make sure that you include all TypoScript files.

### Extension Configuration ###
#### Backend ####
You should create an [application](#applicatioin-record) before you
enable the backend login via Auth0.
<table>
  <tr>
    <th>Key</th>
    <th>Default Value</th>
    <th>Description</th>
  </tr>
  <tr>
    <td>enableBackendLogin</td>
    <td>false</td>
    <td>Enables or disables Auth0 login in TYPO3 backend.</td>
  </tr>
  <tr>
    <td>backendConnection</td>
    <td>1</td>
    <td>The given ID of your application, which should be used for the backend login.</td>
  </tr>
</table>

#### Frontend ####
<table>
  <tr>
    <th>Key</th>
    <th>Default Value</th>
    <th>Description</th>
  </tr>
  <tr>
    <td>userStoragePage</td>
    <td>0</td>
    <td>Page ID where your (dynamic created) frontend users should be stored.</td>
  </tr>
</table>

### TypoScript ###
#### Templating ####
Set alternative Layout/Template/Partial path individually to use your
own Fluid templates. There are some TypoScript constants which you can
simply override:
```
plugin.tx_auth0.view {
    templateRootPath = EXT:your_key/Resources/Private/Templates/
    partialRootPath = EXT:your_key/Resources/Private/Partials/
    layoutRootPath = EXT:your_key/Resources/Private/Layouts/
}
```

##### Backend Login #####
You have also the possibility to use your own template files for the
backend login. Just adapt following parameters:
```
plugin.tx_auth0.settings.backend.view {
    layoutPath = EXT:your_key/Resources/Private/Layouts/
    templateFile = EXT:your_key/Resources/Private/Templates/Backend.html
    stylesheet = EXT:your_key/Resources/Public/Styles/Backend.css
}
```

#### Mapping ####
##### Roles #####

##### Properties #####

### Command Controller ###
There is one command controller available which takes care of your
backend users. A user, that is removed from Auth0 or which access has
expired will be disabled or removed from the TYPO3 database.

You have following options:
<table>
  <tr>
    <th>Method</th>
    <th>Description</th>
  </tr>
  <tr>
    <td>disable</td>
    <td>Disables the user (sets the disabled flag to true). This is the default value.</td>
  </tr>
  <tr>
    <td>delete</td>
    <td>Deletes the user (sets the deleted flag to true). The record still exists in the database.</td>
  </tr>
  <tr>
    <td>deleteIrrevocable</td>
    <td>Removes the user irrevocable from the database.</td>
  </tr>
</table>

### Access ###
You can grant editors access to the hidden property of the application
domain model. Every other property is configurable if the editor is
allowed to modify the record type (*Tables (modify)*).<br/>
Editors can crate or update plugins when they are allowed to modify the
page content type *Insert Plugin* and the page content plugin
*Auth0: Login form*.

## For Editors ##
### Application Record ###
There is one record, called *application*, you can create/edit. It
contains Auth0 server authorization configuration and it is used to
establish a connection with your Auth0-Server.

You can configure following properties:
<table>
  <tr>
    <th>Property</th>
    <th>Default Value</th>
    <th>Description</th>
  </tr>
  <tr>
    <td>Hide</td>
    <td>false</td>
    <td>Whether the application is active or not.</td>
  </tr>
  <tr>
    <td>Title</td>
    <td></td>
    <td>A unique title for your application.</td>
  </tr>
  <tr>
    <td>Domain</td>
    <td></td>
    <td>The domain of your Auth0 server.</td>
  </tr>
  <tr>
    <td>Client ID</td>
    <td></td>
    <td>The client ID of your Auth0 application.</td>
  </tr>
  <tr>
    <td>Client Secret</td>
    <td></td>
    <td>The client secret of your Auth0 application.</td>
  </tr>
  <tr>
    <td>Audience</td>
    <td>api/v2/</td>
    <td>Audience for API calls.</td>
  </tr>
</table>

### Plugin ###
This extensions comes with a login/logout plugin for frontend users.
It is located in a separate tab when creating a new content element.

#### Configuration ####
In general there are two configurations you have to care about. First of
all, you need to select one Auth0 application you want to communicate
with. Afterwards you can configure, where the user should be redirected
to. The configuration of that is similar to the standard TYPO3 frontend
login form plugin. Please take a look into the official TYPO3
[documentation](https://docs.typo3.org/typo3cms/extensions/felogin/LoginMechanism/RedirectModes/Index.html)
for more details.

### Scheduler Tasks ###
There is one scheduler task available, which takes care of inactive or
removed Auth0 users. Please notice that this task affects only TYPO3
backend users (for now).<br/>
Please take a look at the [administration](#command-controller) section.


## For Developers ##
ADD

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

### Sponsors ###
[![MED-EL](https://www.bitmotion.de/fileadmin/github/auth0-for-typo3/MEDEL-Logo.svg "MED-EL")](https://www.medel.com/uk/)

A big **THANK YOU** to our sponsor [MED-EL](https://www.medel.com/uk/).
