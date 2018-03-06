#
# Table structure for table 'sys_site'
#
CREATE TABLE sys_site (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	createdon int(11) unsigned DEFAULT '0' NOT NULL,
	updatedon int(11) unsigned DEFAULT '0' NOT NULL,
	createdby int(11) unsigned DEFAULT '0' NOT NULL,
	identifier varchar(255) DEFAULT '' NOT NULL,
	rootpageid int(11) DEFAULT '0' NOT NULL,
	base varchar(255) DEFAULT '' NOT NULL,
	default_language varchar(10) DEFAULT '' NOT NULL,
	available_languages tinytext,

	PRIMARY KEY (uid),
	KEY index_identifier (identifier),
	KEY index_rootpage (rootpageid),
);

CREATE TABLE sys_sitelanguage (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	createdon int(11) unsigned DEFAULT '0' NOT NULL,
	updatedon int(11) unsigned DEFAULT '0' NOT NULL,
	createdby int(11) unsigned DEFAULT '0' NOT NULL,
	language varchar(255) DEFAULT '' NOT NULL,
	site int(11) DEFAULT '0' NOT NULL,
	base varchar(255) DEFAULT '' NOT NULL,
	fallbacktype tinytext,
	fallbacks tinytext,

	PRIMARY KEY (uid)
);