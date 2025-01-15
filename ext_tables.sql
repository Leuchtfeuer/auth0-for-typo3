#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
  auth0_user_id varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
  auth0_user_group varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'tx_auth0_domain_model_application'
#
CREATE TABLE tx_auth0_domain_model_application (
  title varchar(255) DEFAULT '' NOT NULL,
  single_log_out SMALLINT(5) DEFAULT 1 NOT NULL,
  id varchar(255) DEFAULT '' NOT NULL,
  secret varchar(255) DEFAULT '' NOT NULL,
  domain varchar(255) DEFAULT '' NOT NULL,
  audience varchar(255) DEFAULT '' NOT NULL,
  signature_algorithm varchar(255) DEFAULT '' NOT NULL,
  api SMALLINT(1) DEFAULT 1 NOT NULL,
);
