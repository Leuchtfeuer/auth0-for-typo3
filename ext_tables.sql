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

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  id varchar(255) DEFAULT '' NOT NULL,
  secret varchar(255) DEFAULT '' NOT NULL,
  domain varchar(255) DEFAULT '' NOT NULL,
  audience varchar(255) DEFAULT '' NOT NULL,
	connections int(11) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tx_auth0_domain_model_connection'
#
CREATE TABLE tx_auth0_domain_model_connection (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  connection_type varchar(255) DEFAULT '' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tx_auth0_application_connection_mm'
#
CREATE TABLE tx_auth0_application_connection_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);
