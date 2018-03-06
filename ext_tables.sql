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
	rootPageId int(11) DEFAULT '0' NOT NULL,
	base varchar(255) DEFAULT '' NOT NULL,
	defaultLanguage varchar(10) DEFAULT '' NOT NULL,
	availableLanguages tinytext,
	defaultLanguageLabel varchar(255) DEFAULT '' NOT NULL,
	defaultLocale varchar(255) DEFAULT '' NOT NULL,
	defaultFlag varchar(255) DEFAULT '' NOT NULL,
	errorHandling tinytext,

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
	fallbackType tinytext,
	fallbacks tinytext,

	PRIMARY KEY (uid)
);

CREATE TABLE sys_site_errorhandling (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	createdon int(11) unsigned DEFAULT '0' NOT NULL,
	updatedon int(11) unsigned DEFAULT '0' NOT NULL,
	createdby int(11) unsigned DEFAULT '0' NOT NULL,
	language varchar(255) DEFAULT '' NOT NULL,
	site int(11) DEFAULT '0' NOT NULL,
	errorCode char(3) DEFAULT '' NOT NULL,
	errorHandler varchar(255) DEFAULT '' NOT NULL,
	errorFluidTemplate varchar(255) DEFAULT '' NOT NULL,
	errorContentSource varchar(255) DEFAULT '' NOT NULL,
	errorPhpClassFQCN varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid)
);