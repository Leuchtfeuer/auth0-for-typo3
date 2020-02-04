.. include:: ../Includes.txt

.. _administration:

==============
Administration
==============

Installation
============

There are several ways to require and install this extension. We recommend to get this extension via
`composer <https://getcomposer.org/>`__.

Via Composer
------------

If your TYPO3 instance is running in composer mode, you can simply require the extension by running:

.. code-block:: bash

   composer req bitmotion/auth0

Via Extension Manager
---------------------

Open the extension manager module of your TYPO3 instance and select "Get Extensions" in the select menu above the upload
button. There you can search for `auth0` and simply install the extension. Please make sure you are using the latest
version of the extension by updating the extension list before installing the Auth0 extension.

Via ZIP File
------------

You need to download the Auth0 extension from the `TYPO3 Extension Repository <https://extensions.typo3.org/extension/auth0/>`__
and upload the zip file to the extension manager of your TYPO3 instance and activate the extension afterwards.

.. important::

   Please make sure that you include all TypoScript files.

Global Configuration
====================

You have to add following parameters to the :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters']` configuration:
`code`, `state`, `error_description` and `error`.

On the first installation, the extension will do that for you.

Access Rights
=============

You need to allow editors to modify the record type (*Tables (modify)*).

Editors can create or update plugins when they are allowed to modify the page content type *Insert Plugin* and the page content
plugin *Auth0: Login form*.

Scheduler Task
==============

There is one scheduler task available which takes care of inactive or removed Auth0 users. Please notice that this task affects
only TYPO3 backend users (for now).

Please take a look at the :ref:`administration-command` section.



.. toctree::
    :maxdepth: 3
    :hidden:

    ConsoleCommand/Index
    DataType/Index
    ExtensionConfiguration/Index
    Plugin/Index
    TypoScript/Index
