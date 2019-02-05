<?php
declare(strict_types=1);
namespace Bitmotion\Auth0;

class Scope
{
    const APP_META_CREATE = 'create:users_app_metadata';
    const APP_META_DELETE = 'delete:users_app_metadata';
    const APP_META_READ = 'read:users_app_metadata';
    const APP_META_UPDATE = 'update:users_app_metadata';

    const BLACKLIST_TOKENS = 'blacklist:tokens';

    const CLIENT_CREATE = 'create:clients';
    const CLIENT_DELETE = 'delete:clients';
    const CLIENT_READ = 'read:clients';
    const CLIENT_UPDATE = 'update:clients';

    const CLIENT_GRANTS_CREATE = 'create:client_grants';
    const CLIENT_GRANTS_DELETE = 'delete:client_grants';
    const CLIENT_GRANTS_READ = 'read:client_grants';
    const CLIENT_GRANTS_UPDATE = 'update:client_grants';

    const CLIENT_KEYS_CREATE = 'create:client_keys';
    const CLIENT_KEYS_DELETE = 'delete:client_keys';
    const CLIENT_KEYS_READ = 'read:client_keys';
    const CLIENT_KEYS_UPDATE = 'update:client_keys';

    const DEVICE_CREDENTIALS_CREATE = 'create:device_credentials';
    const DEVICE_CREDENTIALS_DELETE = 'delete:device_credentials';
    const DEVICE_CREDENTIALS_READ = 'read:device_credentials';
    const DEVICE_CREDENTIALS_UPDATE = 'update:device_credentials';

    const CONNECTION_CREATE = 'create:connections';
    const CONNECTION_DELETE = 'delete:connections';
    const CONNECTION_READ = 'read:connections';
    const CONNECTION_UPDATE = 'update:connections';

    const CURRENT_USER_DEVICE_CREATE = 'create:current_user_device_credentials';
    const CURRENT_USER_DEVICE_DELETE = 'delete:current_user_device_credentials';
    const CURRENT_USER_IDENTITIES_UPDATE = 'update:current_user_identities';
    const CURRENT_USER_METADATA_CREATE = 'create:current_user_metadata';
    const CURRENT_USER_METADATA_DELETE = 'delete:current_user_metadata';
    const CURRENT_USER_METADATA_UPDATE = 'update:current_user_metadata';
    const CURRENT_USER_READ = 'read:current_user';

    const CUSTOM_DOMAIN_CREATE = 'create:custom_domains';
    const CUSTOM_DOMAIN_DELETE = 'delete:custom_domains';
    const CUSTOM_DOMAIN_READ = 'read:custom_domains';

    const EMAIL_PROVIDER_CREATE = 'create:email_provider';
    const EMAIL_PROVIDER_DELETE = 'delete:email_provider';
    const EMAIL_PROVIDER_READ = 'read:email_provider';
    const EMAIL_PROVIDER_UPDATE = 'update:email_provider';

    const EMAIL_TEMPLATE_CREATE = 'create:email_templates';
    const EMAIL_TEMPLATE_READ = 'read:email_templates';
    const EMAIL_TEMPLATE_UPDATE = 'update:email_templates';

    const GRANT_READ = 'read:grants';
    const GRANT_UPDATE = 'update:grants';

    const GUARDIAN_ENROLLMENT_DELETE = 'delete:guardian_enrollment';
    const GUARDIAN_ENROLLMENT_READ = 'read:guardian_enrollment';

    const GUARDIAN_ENROLLMENT_TICKET_CREATE = 'create:guardian_enrollment_tickets';

    const GUARDIAN_FACTOR_READ = 'read:guardian_factors';
    const GUARDIAN_FACTOR_UPDATE = 'update:guardian_factors';

    const LOG_READ = 'read:logs';

    const MFA_POLICIES_READ = 'read:mfa_policies';
    const MFA_POLICIES_UPDATE = 'update:mfa_policies';

    const PASSWORD_CHECKING_JOB_CREATE = 'create:passwords_checking_job';
    const PASSWORD_CHECKING_JOB_DELETE = 'delete:passwords_checking_job';

    const RESOURCE_SERVERS_CREATE = 'create:resource_servers';
    const RESOURCE_SERVERS_DELETE = 'delete:resource_servers';
    const RESOURCE_SERVERS_READ = 'read:resource_servers';
    const RESOURCE_SERVERS_UPDATE = 'update:resource_servers';

    const ROLE_CREATE = 'create:roles';
    const ROLE_DELETE = 'delete:roles';
    const ROLE_READ = 'read:roles';
    const ROLE_UPDATE = 'update:roles';

    const RULE_CREATE = 'create:rules';
    const RULE_DELETE = 'delete:rules';
    const RULE_READ = 'read:rules';
    const RULE_UPDATE = 'update:rules';

    const RULE_CONFIG_DELETE = 'delete:rules_configs';
    const RULE_CONFIG_READ = 'read:rules_configs';
    const RULE_CONFIG_UPDATE = 'update:rules_configs';

    const SHIELD_CREATE = 'create:shields';
    const SHIELD_DELETE = 'delete:shields';
    const SHIELD_READ = 'read:shields';

    const STATS_READ = 'read:stats';

    const TENANT_SETTINGS_READ = 'read:tenant_settings';
    const TENANT_SETTINGS_UPDATE = 'update:tenant_settings';

    const TRIGGER_READ = 'read:triggers';
    const TRIGGER_UPDATE = 'update:triggers';

    const USER_DELETE = 'delete:user';
    const USER_CREATE = 'create:users';
    const USER_IDP_TOKEN_READ = 'read:user_idp_tokens';
    const USER_READ = 'read_user';
    const USER_UPDATE = 'update:users';

    const TICKET_CREATE = 'create:user_tickets';

    const OPENID = 'openid';
    const PROFILE = 'profile';
    const EMAIL = 'email';
}
