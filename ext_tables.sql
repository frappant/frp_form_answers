#
# Table structure for table 'tx_frpformanswers_domain_model_formentry'
#
CREATE TABLE tx_frpformanswers_domain_model_formentry (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	submit_uid int(11) DEFAULT '0' NOT NULL,
	answers text NOT NULL,
	field_hash varchar(255) DEFAULT '' NOT NULL,
	form varchar(255) DEFAULT '' NOT NULL,
	exported tinyint(1) unsigned DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),

);
