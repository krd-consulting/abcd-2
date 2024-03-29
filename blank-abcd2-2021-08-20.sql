# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.32)
# Database: blank-abcd2
# Generation Time: 2021-08-20 18:01:15 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table aclResources
# ------------------------------------------------------------

DROP TABLE IF EXISTS `aclResources`;

CREATE TABLE `aclResources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('controller','action','model') DEFAULT NULL,
  `dash` int(1) NOT NULL DEFAULT '0',
  `name` varchar(120) DEFAULT NULL,
  `description` varchar(140) DEFAULT NULL,
  `resourceClass` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

LOCK TABLES `aclResources` WRITE;
/*!40000 ALTER TABLE `aclResources` DISABLE KEYS */;

INSERT INTO `aclResources` (`id`, `type`, `dash`, `name`, `description`, `resourceClass`)
VALUES
	(13,'controller',1,'reports','Run pre-formatted reports, or use the Report Generator',1),
	(12,'controller',1,'forms','Create new forms; associate current forms with depts, programs and groups.',2),
	(11,'controller',1,'users','Add new staff to the system; associate existing staff with departments and programs.',3),
	(14,'controller',0,'notes','Record interactions with participants and groups by time and number; add notes.',2),
	(9,'controller',1,'groups','Create new groups; record attendance and see past attendance',2),
	(5,'controller',0,'depts',NULL,3),
	(7,'controller',0,'my',NULL,2),
	(8,'controller',1,'participants','Enroll new participants; associate with programs or groups; enter survey data.',2),
	(4,'controller',0,'auth',NULL,0),
	(3,'controller',0,'dash',NULL,1),
	(2,'controller',0,'error',NULL,0),
	(1,'controller',0,'index',NULL,1),
	(10,'controller',1,'programs','Enroll participants in programs, set program-level requirements and reports.',2),
	(15,'controller',0,'verify',NULL,1),
	(16,'controller',0,'ajax',NULL,1),
	(17,'controller',0,'funders',NULL,4),
	(18,'controller',0,'files',NULL,1);

/*!40000 ALTER TABLE `aclResources` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table activities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activities`;

CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `eventID` int(11) DEFAULT NULL,
  `meetingID` int(11) DEFAULT NULL,
  `participantID` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `act-uid` (`userID`),
  KEY `act-eid` (`eventID`),
  KEY `act-mid` (`meetingID`),
  KEY `act-pid` (`participantID`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`eventID`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `activities_ibfk_3` FOREIGN KEY (`meetingID`) REFERENCES `groupMeetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `activities_ibfk_4` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tracks activity time for staff.';



# Dump of table alerts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alerts`;

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Pre-programmed and user-set alerts.';



# Dump of table alertsParticipants
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alertsParticipants`;

CREATE TABLE `alertsParticipants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alertID` int(11) NOT NULL,
  `participantID` int(11) NOT NULL,
  `formID` int(11) DEFAULT NULL,
  `startDate` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `al-pt-aid` (`alertID`),
  KEY `al-pt-pid` (`participantID`),
  KEY `al-pt-fid` (`formID`),
  CONSTRAINT `alertsParticipants_ibfk_3` FOREIGN KEY (`alertID`) REFERENCES `alerts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `alertsParticipants_ibfk_4` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `alertsParticipants_ibfk_5` FOREIGN KEY (`formID`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Alerts associated with participants.';



# Dump of table alertsUsers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alertsUsers`;

CREATE TABLE `alertsUsers` (
  `alertID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `startDate` date NOT NULL,
  PRIMARY KEY (`alertID`,`userID`),
  KEY `al-us-aid` (`alertID`),
  KEY `al-us-uid` (`userID`),
  CONSTRAINT `alertsUsers_ibfk_1` FOREIGN KEY (`alertID`) REFERENCES `alerts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `alertsUsers_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Alert destinations and schedules.';



# Dump of table communities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `communities`;

CREATE TABLE `communities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quadrant` varchar(140) NOT NULL,
  `name` varchar(140) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table customFormElements
# ------------------------------------------------------------

DROP TABLE IF EXISTS `customFormElements`;

CREATE TABLE `customFormElements` (
  `elementID` varchar(140) NOT NULL DEFAULT '',
  `formID` int(11) NOT NULL,
  `elementName` varchar(128) NOT NULL,
  `fsiiName` varchar(140) DEFAULT NULL,
  `elType` enum('text','num','date','radio','checkbox','matrix','textarea') NOT NULL,
  `options` text NOT NULL,
  PRIMARY KEY (`elementID`,`formID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table customValues
# ------------------------------------------------------------

DROP TABLE IF EXISTS `customValues`;

CREATE TABLE `customValues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descriptor` varchar(120) DEFAULT NULL,
  `value` varchar(123) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

LOCK TABLES `customValues` WRITE;
/*!40000 ALTER TABLE `customValues` DISABLE KEYS */;

INSERT INTO `customValues` (`id`, `descriptor`, `value`)
VALUES
	(1,'agency','Some Organization'),
	(2,'FCSS Agency Code',NULL),
	(3,'FCSS Code Book',NULL),
	(4,'voipip',NULL),
	(5,'voipport',NULL);

/*!40000 ALTER TABLE `customValues` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table departments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `departments`;

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deptName` varchar(128) NOT NULL,
  `fcssID` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deptname_UNIQUE` (`deptName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Highest level of program distinction.';



# Dump of table deptForms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `deptForms`;

CREATE TABLE `deptForms` (
  `formID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `defaultForm` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`deptID`),
  KEY `dept-f-fid` (`formID`),
  KEY `dept-f-deptid` (`deptID`),
  CONSTRAINT `deptForms_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `deptForms_ibfk_2` FOREIGN KEY (`deptID`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with departments';



# Dump of table events
# ------------------------------------------------------------

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventName` tinytext NOT NULL,
  `programID` int(11) DEFAULT NULL,
  `description` text,
  `date` date NOT NULL,
  `attendanceCount` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ev-prid` (`programID`),
  CONSTRAINT `ev-prid` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Program instances (public)';



# Dump of table flags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `flags`;

CREATE TABLE `flags` (
  `id` int(11) NOT NULL,
  `flagName` varchar(45) DEFAULT NULL,
  `flagDescription` tinytext,
  `flagColor` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flagNAME_UNIQUE` (`flagName`),
  UNIQUE KEY `flagColor_UNIQUE` (`flagColor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Flags for participants';



# Dump of table forms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `forms`;

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `fcssID` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `tableName` varchar(45) NOT NULL,
  `description` text,
  `type` enum('singleuse','prepost') DEFAULT NULL,
  `target` enum('participant','staff','group') DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tablename_UNIQUE` (`tableName`),
  UNIQUE KEY `formname_UNIQUE` (`name`),
  UNIQUE KEY `fcssID` (`fcssID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of default and user-generated ''forms'' (surveys, questio';



# Dump of table formsHTML
# ------------------------------------------------------------

DROP TABLE IF EXISTS `formsHTML`;

CREATE TABLE `formsHTML` (
  `id` int(11) NOT NULL,
  `editable` text,
  `display` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table funderForms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `funderForms`;

CREATE TABLE `funderForms` (
  `formID` int(11) NOT NULL,
  `funderID` int(11) NOT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`funderID`),
  KEY `funder-f-fid` (`formID`),
  KEY `funder-f-fundid` (`funderID`),
  CONSTRAINT `funderForms_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `funderForms_ibfk_2` FOREIGN KEY (`funderID`) REFERENCES `funders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with funders.';



# Dump of table funders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `funders`;

CREATE TABLE `funders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funderName_UNIQUE` (`name`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table groupForms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groupForms`;

CREATE TABLE `groupForms` (
  `formID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `frequency` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`groupID`),
  KEY `gr-f-fid` (`formID`),
  KEY `deptid` (`groupID`),
  CONSTRAINT `groupForms_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `groupForms_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with groups';



# Dump of table groupMeetings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groupMeetings`;

CREATE TABLE `groupMeetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupID` int(11) NOT NULL,
  `enrolledIDs` text,
  `unenrolledCount` int(11) DEFAULT '0',
  `volunteers` int(11) DEFAULT '0',
  `date` date NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid-date` (`groupID`,`date`),
  KEY `gr-mtg-gid` (`groupID`),
  CONSTRAINT `groupMeetings_ibfk_1` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Group meetings (dated instances)';



# Dump of table groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programID` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text,
  `beginDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gr-prid` (`programID`),
  CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Program Instances (ongoing)';



# Dump of table participantDepts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantDepts`;

CREATE TABLE `participantDepts` (
  `participantID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  PRIMARY KEY (`participantID`,`deptID`),
  KEY `pt-d-pid` (`participantID`),
  KEY `pt-d-deptid` (`deptID`),
  CONSTRAINT `participantDepts_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantDepts_ibfk_2` FOREIGN KEY (`deptID`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate participants with departments\n** USE THIS FOR REQU';



# Dump of table participantFlags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantFlags`;

CREATE TABLE `participantFlags` (
  `participantID` int(11) NOT NULL,
  `flagID` int(11) NOT NULL,
  PRIMARY KEY (`participantID`,`flagID`),
  KEY `part-fl-pid` (`participantID`),
  KEY `part-fl-flid` (`flagID`),
  CONSTRAINT `participantFlags_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantFlags_ibfk_2` FOREIGN KEY (`flagID`) REFERENCES `flags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table participantGroups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantGroups`;

CREATE TABLE `participantGroups` (
  `participantID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `enrollDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`participantID`,`groupID`),
  KEY `pt-g-pid` (`participantID`),
  KEY `pt-g-gid` (`groupID`),
  CONSTRAINT `participantGroups_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantGroups_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Participants enrolled in ongoing groups.';



# Dump of table participantMeetings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantMeetings`;

CREATE TABLE `participantMeetings` (
  `meetingID` int(11) NOT NULL,
  `participantID` int(11) NOT NULL,
  `participationLevel` enum('passive','contrib','leadrole') DEFAULT NULL,
  `volunteer` tinyint(1) DEFAULT '0',
  `note` tinytext,
  PRIMARY KEY (`meetingID`,`participantID`),
  KEY `pt-m-mid` (`meetingID`),
  KEY `pt-m-pid` (`participantID`),
  CONSTRAINT `participantMeetings_ibfk_1` FOREIGN KEY (`meetingID`) REFERENCES `groupMeetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantMeetings_ibfk_2` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Attendance and participation level of enrolled participants ';



# Dump of table participantNotes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantNotes`;

CREATE TABLE `participantNotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participantID` int(11) NOT NULL,
  `programID` int(11) DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `note` text NOT NULL,
  `datestamp` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pt-note-pid` (`participantID`),
  KEY `pt-note-prid` (`programID`),
  CONSTRAINT `participantNotes_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantNotes_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table participantPrograms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantPrograms`;

CREATE TABLE `participantPrograms` (
  `participantID` int(11) NOT NULL,
  `programID` int(11) NOT NULL COMMENT '\n',
  `enrollDate` date NOT NULL,
  `status` enum('active','leave','waitlist','concluded') NOT NULL DEFAULT 'waitlist',
  `prevStatus` enum('active','leave','waitlist','concluded') DEFAULT NULL,
  `statusDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statusNote` text,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`participantID`,`programID`,`statusDate`),
  KEY `pt-pr-pid` (`participantID`),
  KEY `pt-pr-prid` (`programID`),
  CONSTRAINT `participantPrograms_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantPrograms_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.';



# Dump of table participants
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participants`;

CREATE TABLE `participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(45) NOT NULL,
  `lastName` varchar(45) NOT NULL,
  `dateOfBirth` date NOT NULL,
  `createdOn` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `firstName` (`firstName`,`lastName`,`dateOfBirth`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Basic information for participants (volunteers, citizens, cl';



# Dump of table participantUsers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `participantUsers`;

CREATE TABLE `participantUsers` (
  `participantID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `enrollDate` date NOT NULL,
  `status` enum('active','leave','waitlist','concluded') NOT NULL DEFAULT 'waitlist',
  `prevStatus` enum('active','leave','waitlist','concluded') DEFAULT NULL,
  `statusDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statusNote` text,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`participantID`,`userID`,`statusDate`,`programID`),
  KEY `userID` (`userID`),
  CONSTRAINT `participantUsers_ibfk_1` FOREIGN KEY (`participantID`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participantUsers_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.';



# Dump of table programForms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `programForms`;

CREATE TABLE `programForms` (
  `formID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`programID`),
  KEY `pr-form-fid` (`formID`),
  KEY `deptid` (`programID`),
  CONSTRAINT `pr-form-fid` FOREIGN KEY (`formID`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `pr-form-prid` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with programs';



# Dump of table programFunders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `programFunders`;

CREATE TABLE `programFunders` (
  `programID` int(11) NOT NULL,
  `funderID` int(11) NOT NULL,
  PRIMARY KEY (`programID`,`funderID`),
  KEY `pr-fund-prid` (`programID`),
  KEY `pr-fund-fundid` (`funderID`),
  CONSTRAINT `programFunders_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `programFunders_ibfk_2` FOREIGN KEY (`funderID`) REFERENCES `funders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate programs with funders\n** USE THIS TO PULL IN FORMS';



# Dump of table programs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `programs`;

CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deptID` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prname_UNIQUE` (`name`),
  KEY `pr-deptid` (`deptID`),
  CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`deptID`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Programmatic efforts within departments.';



# Dump of table ptcpProgramArchive
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ptcpProgramArchive`;

CREATE TABLE `ptcpProgramArchive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participantID` int(11) NOT NULL,
  `programID` int(11) NOT NULL COMMENT '\n',
  `enrollDate` date NOT NULL,
  `status` enum('active','leave','waitlist','concluded') NOT NULL DEFAULT 'waitlist',
  `prevStatus` varchar(40) DEFAULT NULL,
  `statusDate` datetime NOT NULL,
  `statusNote` text,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pt-pr-pid` (`participantID`),
  KEY `pt-pr-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.';



# Dump of table ptcpSecureIDs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ptcpSecureIDs`;

CREATE TABLE `ptcpSecureIDs` (
  `ptcpID` int(11) NOT NULL,
  `anonID` varchar(140) NOT NULL,
  PRIMARY KEY (`ptcpID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='MD5 hash, compatible with FSII';



# Dump of table ptcpUserArchive
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ptcpUserArchive`;

CREATE TABLE `ptcpUserArchive` (
  `participantID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `enrollDate` date NOT NULL,
  `status` enum('active','leave','waitlist','concluded') NOT NULL DEFAULT 'waitlist',
  `prevStatus` enum('active','leave','waitlist','concluded') DEFAULT NULL,
  `statusDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statusNote` text,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`participantID`,`userID`,`statusDate`,`programID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.';



# Dump of table roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleName` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleName` (`roleName`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;

INSERT INTO `roles` (`id`, `roleName`)
VALUES
	(2,'staff'),
	(3,'manager'),
	(4,'admin'),
	(1,'evaluator');

/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table storedReports
# ------------------------------------------------------------

DROP TABLE IF EXISTS `storedReports`;

CREATE TABLE `storedReports` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report ID',
  `name` varchar(140) NOT NULL COMMENT 'Report Name',
  `frequency` varchar(16) NOT NULL,
  `recipients` text NOT NULL COMMENT 'Recipient Object',
  `includeOptions` text NOT NULL COMMENT 'Coded list of report options',
  `lastUpdated` date NOT NULL,
  `updatedBy` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table userDepartments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `userDepartments`;

CREATE TABLE `userDepartments` (
  `userID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  `manager` tinyint(1) NOT NULL DEFAULT '0',
  `homeDept` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`userID`,`deptID`),
  KEY `u-d-uid` (`userID`),
  KEY `u-d-deptid` (`deptID`),
  CONSTRAINT `userDepartments_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `userDepartments_ibfk_2` FOREIGN KEY (`deptID`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate staff with departments.';



# Dump of table userGroups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `userGroups`;

CREATE TABLE `userGroups` (
  `userID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `lead` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`userID`,`groupID`),
  KEY `u-g-uid` (`userID`),
  KEY `u-g-gid` (`groupID`),
  CONSTRAINT `userGroups_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `userGroups_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate users with groups';



# Dump of table userPrograms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `userPrograms`;

CREATE TABLE `userPrograms` (
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `lead` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`,`programID`),
  KEY `u-pr-uid` (`userID`),
  KEY `u-pr-prid` (`programID`),
  CONSTRAINT `userPrograms_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `userPrograms_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate users with programs';



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL,
  `lock` tinyint(1) NOT NULL DEFAULT '0',
  `eMail` varchar(45) NOT NULL,
  `firstName` varchar(45) NOT NULL,
  `lastName` varchar(45) NOT NULL,
  `createdDate` datetime NOT NULL,
  `lastLogin` datetime DEFAULT NULL,
  `role` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_UNIQUE` (`id`),
  UNIQUE KEY `username_UNIQUE` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Staff login, contact and ACL information';

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `userName`, `password`, `lock`, `eMail`, `firstName`, `lastName`, `createdDate`, `lastLogin`, `role`)
VALUES
	(1,'admin','c8a5362cd3006f7c6e4df976f548a3f9',0,'info@hellokrd.net','Admin','User','2021-02-22 02:33:26','2021-08-18 13:00:00','4');

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
