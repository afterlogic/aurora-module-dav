CREATE TABLE IF NOT EXISTS `%PREFIX%adav_addressbooks` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `principaluri` varchar(255) default NULL,
  `displayname` varchar(255) default NULL,
  `uri` varchar(200) default NULL,
  `description` text,
  `ctag` int(11) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_cache` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(255) default NULL,
  `calendaruri` varchar(255) default NULL,
  `type` tinyint(4) default NULL,
  `time` int(11) default NULL,
  `starttime` int(11) default NULL,
  `eventid` varchar(45) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarobjects` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `calendardata` mediumtext,
  `uri` varchar(255) default NULL,
  `calendarid` int(11) unsigned NOT NULL,
  `lastmodified` int(11) default NULL,
  `etag` varchar(32) NOT NULL default '',
  `size` int(11) unsigned NOT NULL default '0',
  `componenttype` varchar(8) NOT NULL default '',
  `firstoccurence` int(11) unsigned default NULL,
  `lastoccurence` int(11) unsigned default NULL,
  `orderID` int(11) default NULL,
  `orderPosID` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `%PREFIX%calendarid_2` (`calendarid`,`componenttype`,`lastoccurence`,`firstoccurence`),
  KEY `%PREFIX%calendarid` (`calendarid`,`uri`),
  KEY `%PREFIX%calendarid_3` (`calendarid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendars` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `principaluri` varchar(255) default NULL,
  `displayname` varchar(100) default NULL,
  `uri` varchar(255) default NULL,
  `ctag` int(11) unsigned NOT NULL default '0',
  `description` text,
  `calendarorder` int(11) unsigned NOT NULL default '0',
  `calendarcolor` varchar(10) default NULL,
  `timezone` text,
  `components` varchar(20) default NULL,
  `transparent` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarshares` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `calendarid` int(11) unsigned default NULL,
  `member` int(11) unsigned default NULL,
  `status` tinyint(2) default NULL,
  `readonly` tinyint(1) NOT NULL default '0',
  `summary` varchar(150) default NULL,
  `displayname` varchar(100) default NULL,
  `color` varchar(10) default NULL,
  `principaluri` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_cards` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `addressbookid` int(11) unsigned NOT NULL,
  `carddata` mediumtext,
  `uri` varchar(255) default NULL,
  `lastmodified` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `%PREFIX%ADAV_CARDS_ADDRESSBOOKID_INDEX` (`addressbookid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_groupmembers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `principal_id` int(11) unsigned NOT NULL,
  `member_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `%PREFIX%ADAV_GROUPMEMBERS_MEMBER_ID_PRINCIPAL_ID_INDEX` (`principal_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_locks` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner` varchar(100) default NULL,
  `timeout` int(11) unsigned default NULL,
  `created` int(11) default NULL,
  `token` varchar(100) default NULL,
  `scope` tinyint(4) default NULL,
  `depth` tinyint(4) default NULL,
  `uri` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_principals` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `uri` varchar(255) NOT NULL,
  `email` varchar(80) default NULL,
  `vcardurl` varchar(80) default NULL,
  `displayname` varchar(80) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `%PREFIX%ADAV_PRINCIPALS_URI_INDEX` (`uri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%adav_reminders` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` varchar(100) NOT NULL,
  `calendaruri` varchar(255) default NULL,
  `eventid` varchar(255) default NULL,
  `time` int(11) default NULL,
  `starttime` int(11) default NULL,
  `allday` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
