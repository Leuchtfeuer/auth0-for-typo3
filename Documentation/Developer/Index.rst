.. include:: ../Includes.txt

.. _developer:

==============
For Developers
==============

Support for frontend users has been removed from version 13.

.. _developer-api:

API Notes
=========

This section documents public APIs that extension developers may use or override.

.. _developer-api-applicationFactory:

ApplicationFactory
------------------

``ApplicationFactory::build(int $applicationId, string $context, ?ServerRequestInterface $request = null): Auth0``

Pass the current PSR-7 request as the third argument whenever one is available. The factory uses
the request to derive the OAuth redirect URI via ``NormalizedParams``. If no request is passed (e.g.
in CLI commands or early-boot contexts), ``$_SERVER`` is used as a last resort — the redirect URI
is constructed but never used in an actual OAuth flow in those contexts.

.. _developer-api-tokenUtility:

TokenUtility
------------

``TokenUtility::buildToken(string $issuer): UnencryptedToken``

Builds a signed JWT for the OAuth state callback. The issuer (request host) must be passed
explicitly. Derive it from the request: ``$request->getAttribute('normalizedParams')->getRequestHost()``.

``TokenUtility::verifyToken(string $token, string $issuer): bool``

Verifies a callback token. Pass the same issuer that was used when the token was built.

.. note::

   The former ``getIssuer()`` / ``setIssuer()`` methods have been removed in version 14.0.0.
   The issuer is no longer stored as object state; pass it at call time instead.

.. _developer-api-modeUtility:

ModeUtility
-----------

``ModeUtility::getModeFromRequest(ServerRequestInterface $request): string``

Returns ``ModeUtility::BACKEND_MODE`` when the request is a backend request, otherwise
``ModeUtility::UNKNOWN_MODE``. The method no longer reads from ``$GLOBALS['TYPO3_REQUEST']``;
pass the request explicitly.

``ModeUtility::isBackend(?string $mode = null, ?ServerRequestInterface $request = null): bool``

When ``$mode`` is ``null`` and a ``$request`` is provided, the mode is derived from the request.
When neither is provided, the method returns ``false`` (unknown mode is not backend).

.. note::

   The constant ``UNKONWN_MODE`` (typo) was renamed to ``UNKNOWN_MODE`` in version 14.0.0.

.. _developer-api-userRepository:

UserRepository
--------------

``UserRepository::insertUser(array $values): int``

Inserts a user record into the database. Starting with version 14.0.0, this method returns the
``uid`` of the newly created record. Custom implementations overriding this method must update
their return type to ``int``.