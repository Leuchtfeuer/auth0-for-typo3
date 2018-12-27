Auth0 for TYPO3
===============
[![Auth0TYPO3](https://www.bitmotion.de/fileadmin/github/auth0-for-typo3/TYPO3-Auth0.png "Auth0 for TYPO3")](https://www.bitmotion.de/)

[![Latest Stable Version](https://poser.pugx.org/bitmotion/auth0/v/stable)](https://packagist.org/packages/bitmotion/auth0)
[![Total Downloads](https://poser.pugx.org/bitmotion/auth0/downloads)](https://packagist.org/packages/bitmotion/auth0)
[![Latest Unstable Version](https://poser.pugx.org/bitmotion/auth0/v/unstable)](https://packagist.org/packages/bitmotion/auth0)
[![Code Climate](https://codeclimate.com/github/bitmotion/auth0-for-typo3/badges/gpa.svg)](https://codeclimate.com/github/bitmotion/auth0-for-typo3)
[![License](https://poser.pugx.org/bitmotion/auth0/license)](https://packagist.org/packages/bitmotion/auth0)

## About ##
This extension allows you to log into a TYPO3 backend or frontend via Auth0.

### Requirements ###
Currently we only support [TYPO3 8 LTS](https://get.typo3.org/version/8). You also need access to an [Auth0](https://auth0.com/) instance.

## For Administrators ##
### Installation ###
We recommend to get this extension via composer:
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
    <td>Enables or disables the Auth0 login in the TYPO3 backend.</td>
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
    <td>Page ID where your (dynamically created) frontend users should be stored.</td>
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

##### Backend Login ######
You have also the option to use your own template files for the
backend login. Just adapt the following TypoScript constants:
```
plugin.tx_auth0.settings.backend.view {
    layoutPath = EXT:your_key/Resources/Private/Layouts/
    templateFile = EXT:your_key/Resources/Private/Templates/Backend.html
    stylesheet = EXT:your_key/Resources/Public/Styles/Backend.css
}
```
Please make also sure that you configure the [mapping](#roles) from
Auth0 roles to TYPO3 user groups. Maybe you also want to set the admin
flag for backend users, depending on an Auth0 [property](#properties).

#### Login Behaviour ####
Configure whether disabled or deleted frontend or backend users should
be able to login by adapting the following TypoScript constants:

```
plugin.tx_auth0.settings.reactivateUsers {
    be_users {
        # if active, sets the disable flag to 0 when user tries to login again
        disabled = 0

        # if active, sets the deleted flag to 0 when user tries to login again
        deleted = 0
    }

    fe_users {
        # if active, sets the disable flag to 0 when user tries to login again
        disabled = 1

        # if active, sets the deleted flag to 0 when user tries to login again
        deleted = 1
    }
}
```

#### Mapping ####
##### Roles #####
Configure `fe_groups` and `be_groups` mappings to match Auth0 roles.
Use the Auth0 role identifier as key and the TYPO3 frontend or backend
user group ID as value.<br>
Keep in mind, that there is one special option for backend users: You
can set the admin flag by assigning the value `admin` to an Auth0 role.
```
plugin.tx_auth0.settings.roles {
    # be_group mappings for be_users
    be_users {
        #mapping for auth0 role to be_groups

        # special: sets the admin flag
        admin = admin
    }

    # fe_group mappings for fe_users
    fe_users {
        # mapping for auth0 role to fe_groups
        admin = 1
    }
}
```

##### Properties #####
Auth0 properties can be mapped to existing properties of TYPO3
backend or frontend users. You can configure this mapping via
TypoScript. In this case, the key is the name of the TYPO3 database
column and the value is the field key of the Auth0 user.<br>
You can access the `user_metadata` or `app_metadata` values via dot
syntax. Using the same way you can access arrays or objects within the
metadata property (e.g. `user_metadata.address.primary.zip`).
```
plugin.tx_auth0.settings.propertyMapping {
    be_users {
        username = nickname

        crdate = created_at
        crdate.parseFunc = strtotime

        tstamp = updated_at
        tstamp.parseFunc = strtotime

        disable = email_verified
        disable.parseFunc = bool|negate

        admin = user_metadata.admin
        admin.parseFunc = bool

        description = user_metadata.description
    }

    fe_users {
        crdate = created_at
        crdate.parseFunc = strtotime

        tstamp = updated_at
        tstamp.parseFunc = strtotime

        first_name = user_metadata.description
    }
}
```
**Parsing functions** (parseFunc) are used to change properties before
they are persisted in the database.<br/>
To apply multiple parsing functions you can simply use the pipe to
delimiter them. These functions will then be applied in the order you
have set them. For example, a `bool|negate` parseFunc will cast the
property to a boolean value and then negate it.


The following parsing functions are available:
<table>
  <tr>
    <th>Function</td>
    <th>Description</th>
  </tr>
  <tr>
    <td>bool</td>
    <td>Get the boolean value.</td>
  </tr>
  <tr>
    <td>strtotime</td>
    <td>Parse about any English textual datetime description into a Unix timestamp.</td>
  </tr>
  <tr>
    <td>negate</td>
    <td>Negate the value (only for booleans).</td>
  </tr>
</table>

### Command Controller ###
There is one command controller available which takes care of your
backend users. A user that is removed from Auth0 or who's access has
expired will be disabled or removed from the TYPO3 database.

You have the following options:
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

You can execute the command controller via CLI:<br>
`path/to/php bin/typo3 cleanup:cleanupusers --method="disable"`

### Access ###
You need to allow editors to modify the record type (*Tables (modify)*).<br/>
Editors can create or update plugins when they are allowed to modify the
page content type *Insert Plugin* and the page content plugin
*Auth0: Login form*.

## For Editors ##
### Application Record ###
There is one record, called *application*, which you can create/edit. It
contains the Auth0 server authorization configuration and it is used to
establish a connection with your Auth0-Server.

You can configure the following properties:
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
with. Afterwards you can configure where the user should be redirected
to. The configuration of that is similar to the standard TYPO3 frontend
login form plugin. Please take a look into the official TYPO3
[documentation](https://docs.typo3.org/typo3cms/extensions/felogin/LoginMechanism/RedirectModes/Index.html)
for more details.

### Scheduler Tasks ###
There is one scheduler task available which takes care of inactive or
removed Auth0 users. Please notice that this task affects only TYPO3
backend users (for now).<br/>
Please take a look at the [administration](#command-controller) section.


## For Developers ##
You can easily access the data of the current logged in user by calling
the following methods:
```
$sessionStore = new \Auth0\SDK\Store\SessionStore();
$userInfo = $sessionStore->get('user');
```
User metadata is also stored as plain JSON in the TYPO3 fe_user field
`auth0_metadata`.

If you want to enrich the user metadata or remove some information,
you can do it this way:
```
# Get the user Id
$sessionStore = new SessionStore();
$user = $store->get('user');
$userId = $user['sub'];

# Prepare data
$data = new \stdClass();
$data->favourite_color = 'blue';

# Update Auth0 user
$managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
$managementApi->users->update($userId, $data);
```

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

### Sponsors ###
[![MED-EL](https://www.bitmotion.de/fileadmin/github/auth0-for-typo3/MEDEL-Logo.svg "MED-EL")](https://www.medel.com/)

A big **THANK YOU** to our sponsor [MED-EL](https://www.medel.com/).
