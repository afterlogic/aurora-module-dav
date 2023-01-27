<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        * Attention! 'uri' and 'uid' fields need to be at least 255 length
        */
        $sPrefix = Capsule::connection()->getTablePrefix();

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_addressbooks` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    displayname VARCHAR(255),
    uri VARBINARY(255),
    description TEXT,
    synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
    UNIQUE(principaluri(100), uri(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_addressbookchanges` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(255) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    addressbookid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX addressbookid_synctoken (addressbookid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_cache` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(255) default NULL,
  `calendaruri` varchar(255) default NULL,
  `type` tinyint(4) default NULL,
  `time` int(11) default NULL,
  `starttime` int(11) default NULL,
  `eventid` varchar(45) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarinstances` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`calendarid` INT(10) UNSIGNED NOT NULL,
	`principaluri` VARBINARY(100) NULL DEFAULT NULL,
	`access` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
	`displayname` VARCHAR(100) NULL DEFAULT NULL,
	`uri` VARBINARY(255) NULL DEFAULT NULL,
	`description` TEXT NULL,
	`calendarorder` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`calendarcolor` VARBINARY(10) NULL DEFAULT NULL,
	`timezone` TEXT NULL,
	`transparent` TINYINT(1) NOT NULL DEFAULT '0',
	`share_href` VARBINARY(100) NULL DEFAULT NULL,
	`share_displayname` VARCHAR(100) NULL DEFAULT NULL,
	`share_invitestatus` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
	`public` TINYINT(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `principaluri` (`principaluri`, `uri`),
	UNIQUE INDEX `calendarid` (`calendarid`, `principaluri`),
	UNIQUE INDEX `calendarid_2` (`calendarid`, `share_href`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarobjects` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendardata MEDIUMBLOB,
    uri VARBINARY(255),
    calendarid INTEGER UNSIGNED NOT NULL,
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL,
    componenttype VARBINARY(8),
    firstoccurence INT(11) UNSIGNED,
    lastoccurence INT(11) UNSIGNED,
    uid VARBINARY(255),
    UNIQUE(calendarid, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendars` (
    	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`synctoken` INT(10) UNSIGNED NOT NULL DEFAULT '1',
	`components` VARBINARY(21) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarchanges` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(255) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    calendarid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX calendarid_synctoken (calendarid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_calendarsubscriptions` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(255) NOT NULL,
    principaluri VARBINARY(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    striptodos TINYINT(1) NULL,
    stripalarms TINYINT(1) NULL,
    stripattachments TINYINT(1) NULL,
    lastmodified INT(11) UNSIGNED,
    UNIQUE(principaluri, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_schedulingobjects` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    calendardata MEDIUMBLOB,
    uri VARBINARY(255),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_cards` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    addressbookid INT(11) UNSIGNED NOT NULL,
    carddata MEDIUMBLOB,
    uri VARBINARY(255),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_groupmembers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `principal_id` int(11) unsigned NOT NULL,
  `member_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `%PREFIX%ADAV_GROUPMEMBERS_MEMBER_ID_PRINCIPAL_ID_INDEX` (`principal_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_locks` (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    owner VARCHAR(100),
    timeout INTEGER UNSIGNED,
    created INTEGER,
    token VARBINARY(100),
    scope TINYINT,
    depth TINYINT,
    uri VARBINARY(1000),
    INDEX(token),
    INDEX(uri(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_propertystorage` (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path VARBINARY(1024) NOT NULL,
    name VARBINARY(100) NOT NULL,
    valuetype INT UNSIGNED,
    value MEDIUMBLOB,
	UNIQUE INDEX path_property (path(600), name(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        Capsule::connection()->statement($sSql);

        $sSql = str_replace(
            "%PREFIX%",
            $sPrefix,
            "CREATE TABLE IF NOT EXISTS `%PREFIX%adav_reminders` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` varchar(100) NOT NULL,
  `calendaruri` varchar(255) default NULL,
  `eventid` varchar(255) default NULL,
  `time` int(11) default NULL,
  `starttime` int(11) default NULL,
  `allday` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
        Capsule::connection()->statement($sSql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('adav_addressbooks');
        Capsule::schema()->dropIfExists('adav_addressbookchanges');
        Capsule::schema()->dropIfExists('adav_cache');
        Capsule::schema()->dropIfExists('adav_calendarinstances');
        Capsule::schema()->dropIfExists('adav_calendarobjects');
        Capsule::schema()->dropIfExists('adav_calendars');
        Capsule::schema()->dropIfExists('adav_calendarchanges');
        Capsule::schema()->dropIfExists('adav_calendarsubscriptions');
        Capsule::schema()->dropIfExists('adav_schedulingobjects');
        Capsule::schema()->dropIfExists('adav_cards');
        Capsule::schema()->dropIfExists('adav_groupmembers');
        Capsule::schema()->dropIfExists('adav_locks');
        Capsule::schema()->dropIfExists('adav_propertystorage');
        Capsule::schema()->dropIfExists('adav_reminders');
    }
}
