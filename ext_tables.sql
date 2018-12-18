#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
  auth0_user_id varchar(255) DEFAULT '' NOT NULL,
  auth0_metadata mediumtext,
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
  auth0_user_id varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
  auth0_user_group varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'tx_auth0_domain_model_application'
#
CREATE TABLE tx_auth0_domain_model_application (
  title varchar(255) DEFAULT '' NOT NULL,
  id varchar(255) DEFAULT '' NOT NULL,
  secret varchar(255) DEFAULT '' NOT NULL,
  domain varchar(255) DEFAULT '' NOT NULL,
  audience varchar(255) DEFAULT '' NOT NULL,
);
