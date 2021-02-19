-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 16, 2018 at 12:21 PM
-- Server version: 5.5.61-0ubuntu0.14.04.1
-- PHP Version: 5.6.37-1+ubuntu14.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `abcd-distro`
--

-- --------------------------------------------------------

--
-- Table structure for table `aclResources`
--

CREATE TABLE IF NOT EXISTS `aclResources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('controller','action','model') DEFAULT NULL,
  `dash` int(1) NOT NULL DEFAULT '0',
  `name` varchar(120) DEFAULT NULL,
  `description` varchar(140) DEFAULT NULL,
  `resourceClass` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

--
-- Dumping data for table `aclResources`
--

INSERT INTO `aclResources` (`id`, `type`, `dash`, `name`, `description`, `resourceClass`) VALUES
(13, 'controller', 1, 'reports', 'Run pre-formatted reports, or use the Report Generator', 10),
(12, 'controller', 1, 'forms', 'Create new forms; associate current forms with depts, programs and groups.', 15),
(11, 'controller', 1, 'users', 'Add new staff to the system; associate existing staff with departments and programs.', 30),
(14, 'controller', 0, 'notes', 'Record interactions with participants and groups by time and number; add notes.', 20),
(9, 'controller', 1, 'groups', 'Create new groups; record attendance and see past attendance', 15),
(5, 'controller', 0, 'depts', NULL, 40),
(7, 'controller', 0, 'my', NULL, 15),
(8, 'controller', 1, 'participants', 'Enroll new participants; associate with programs or groups; enter survey data.', 20),
(4, 'controller', 0, 'auth', NULL, 0),
(3, 'controller', 0, 'dash', NULL, 10),
(2, 'controller', 0, 'error', NULL, 0),
(1, 'controller', 0, 'index', NULL, 0),
(10, 'controller', 1, 'programs', 'Enroll participants in programs, set program-level requirements and reports.', 15),
(15, 'controller', 0, 'verify', NULL, 30),
(16, 'controller', 0, 'ajax', NULL, 10),
(17, 'controller', 0, 'funders', NULL, 40),
(18, 'controller', 0, 'files', NULL, 15),
(19, 'controller', 1, 'volunteers', 'Manage and track volunteers in your programs.', 15),
(21, 'controller', 1, 'schedule', 'Create and manage schedule sets for yourself and other resources.', 15);

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `eventID` int(11) DEFAULT NULL,
  `meetingID` int(11) DEFAULT NULL,
  `participantID` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `duration` decimal(4,2) NOT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `act-uid` (`userID`),
  KEY `act-eid` (`eventID`),
  KEY `act-mid` (`meetingID`),
  KEY `act-pid` (`participantID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tracks activity time for staff.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Pre-programmed and user-set alerts.' AUTO_INCREMENT=25 ;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `alert`) VALUES
(1, '{NAME} has not filled out required form {FORMNAME}'),
(2, '{NAME} is due to fill out {FORMNAME} again as of {DATE}');

-- --------------------------------------------------------

--
-- Table structure for table `alertsParticipants`
--

CREATE TABLE IF NOT EXISTS `alertsParticipants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alertID` int(11) NOT NULL,
  `participantID` int(11) NOT NULL,
  `formID` int(11) DEFAULT NULL,
  `startDate` date NOT NULL,
  `doNotDisplay` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `al-pt-aid` (`alertID`),
  KEY `al-pt-pid` (`participantID`),
  KEY `al-pt-fid` (`formID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Alerts associated with participants.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `alertsUsers`
--

CREATE TABLE IF NOT EXISTS `alertsUsers` (
  `alertID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `doNotDisplay` tinyint(1) NOT NULL,
  PRIMARY KEY (`alertID`,`userID`),
  KEY `al-us-aid` (`alertID`),
  KEY `al-us-uid` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Alert destinations and schedules.';

-- --------------------------------------------------------

--
-- Table structure for table `alertsVolunteers`
--

CREATE TABLE IF NOT EXISTS `alertsVolunteers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alertID` int(11) NOT NULL,
  `volID` int(11) NOT NULL,
  `formID` int(11) DEFAULT NULL,
  `startDate` date NOT NULL,
  `doNotDisplay` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `al-pt-aid` (`alertID`),
  KEY `al-pt-pid` (`volID`),
  KEY `al-pt-fid` (`formID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Alerts associated with volunteers.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE IF NOT EXISTS `communities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quadrant` varchar(140) NOT NULL,
  `name` varchar(140) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `customFormElements`
--

CREATE TABLE IF NOT EXISTS `customFormElements` (
  `elementID` varchar(140) NOT NULL DEFAULT '',
  `formID` int(11) NOT NULL,
  `elementName` varchar(128) NOT NULL,
  `fsiiName` varchar(140) DEFAULT NULL,
  `elType` enum('text','num','date','radio','checkbox','matrix','textarea') NOT NULL,
  `options` text NOT NULL,
  PRIMARY KEY (`elementID`,`formID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `customValues`
--

CREATE TABLE IF NOT EXISTS `customValues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descriptor` varchar(120) DEFAULT NULL,
  `value` varchar(123) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `customValues`
--

INSERT INTO `customValues` (`id`, `descriptor`, `value`) VALUES
(1, 'agency', 'CHANGE ME'),
(2, 'FCSS Agency Code', '000'),
(3, 'FCSS Code Book', '2012/09/13'),
(4, 'voipip', ''),
(5, 'voipport', '');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deptName` varchar(128) NOT NULL,
  `fcssID` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deptname_UNIQUE` (`deptName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Highest level of program distinction.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `deptForms`
--

CREATE TABLE IF NOT EXISTS `deptForms` (
  `formID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `defaultForm` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`deptID`),
  KEY `dept-f-fid` (`formID`),
  KEY `dept-f-deptid` (`deptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with departments';

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventName` tinytext NOT NULL,
  `programID` int(11) DEFAULT NULL,
  `description` text,
  `date` date NOT NULL,
  `attendanceCount` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ev-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Program instances (public)' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doNotDisplay` tinyint(1) NOT NULL,
  `entityID` int(11) NOT NULL,
  `entityType` varchar(140) NOT NULL,
  `description` text NOT NULL,
  `location` text NOT NULL,
  `createdOn` date NOT NULL,
  `createdBy` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `id` int(11) NOT NULL,
  `flagName` varchar(45) DEFAULT NULL,
  `flagDescription` tinytext,
  `flagColor` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flagNAME_UNIQUE` (`flagName`),
  UNIQUE KEY `flagColor_UNIQUE` (`flagColor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Flags for participants';

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE IF NOT EXISTS `forms` (
  `id` int(11) NOT NULL,
  `fcssID` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `tableName` varchar(45) NOT NULL,
  `description` text,
  `type` enum('singleuse','prepost') DEFAULT NULL,
  `target` enum('participant','staff','group','volunteer') DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tablename_UNIQUE` (`tableName`),
  UNIQUE KEY `formname_UNIQUE` (`name`),
  UNIQUE KEY `fcssID` (`fcssID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of default and user-generated ''forms'' (surveys, questio';

-- --------------------------------------------------------

--
-- Table structure for table `formsHTML`
--

CREATE TABLE IF NOT EXISTS `formsHTML` (
  `id` int(11) NOT NULL,
  `editable` text,
  `display` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `funderForms`
--

CREATE TABLE IF NOT EXISTS `funderForms` (
  `formID` int(11) NOT NULL,
  `funderID` int(11) NOT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`funderID`),
  KEY `funder-f-fid` (`formID`),
  KEY `funder-f-fundid` (`funderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with funders.';

-- --------------------------------------------------------

--
-- Table structure for table `funders`
--

CREATE TABLE IF NOT EXISTS `funders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funderName_UNIQUE` (`name`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `groupForms`
--

CREATE TABLE IF NOT EXISTS `groupForms` (
  `formID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `frequency` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`groupID`),
  KEY `gr-f-fid` (`formID`),
  KEY `deptid` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with groups';

-- --------------------------------------------------------

--
-- Table structure for table `groupMeetings`
--

CREATE TABLE IF NOT EXISTS `groupMeetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupID` int(11) NOT NULL,
  `eventName` varchar(140) DEFAULT NULL,
  `enrolledIDs` text,
  `volunteerIDs` text,
  `guestCount` int(11) DEFAULT '0',
  `nonVolVols` int(11) DEFAULT '0',
  `date` date NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid-date` (`groupID`,`date`),
  KEY `gr-mtg-gid` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Group meetings (dated instances)' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programID` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text,
  `beginDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gr-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Program Instances (ongoing)' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `participantDepts`
--

CREATE TABLE IF NOT EXISTS `participantDepts` (
  `participantID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  PRIMARY KEY (`participantID`,`deptID`),
  KEY `pt-d-pid` (`participantID`),
  KEY `pt-d-deptid` (`deptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate participants with departments** USE THIS FOR REQU';

-- --------------------------------------------------------

--
-- Table structure for table `participantFlags`
--

CREATE TABLE IF NOT EXISTS `participantFlags` (
  `participantID` int(11) NOT NULL,
  `flagID` int(11) NOT NULL,
  PRIMARY KEY (`participantID`,`flagID`),
  KEY `part-fl-pid` (`participantID`),
  KEY `part-fl-flid` (`flagID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `participantGroups`
--

CREATE TABLE IF NOT EXISTS `participantGroups` (
  `participantID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `enrollDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  PRIMARY KEY (`participantID`,`groupID`),
  KEY `pt-g-pid` (`participantID`),
  KEY `pt-g-gid` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Participants enrolled in ongoing groups.';

-- --------------------------------------------------------

--
-- Table structure for table `participantMeetings`
--

CREATE TABLE IF NOT EXISTS `participantMeetings` (
  `meetingID` int(11) NOT NULL,
  `participantID` int(11) NOT NULL,
  `participationLevel` enum('passive','contrib','leadrole') DEFAULT NULL,
  `volunteer` tinyint(1) DEFAULT '0',
  `note` tinytext,
  PRIMARY KEY (`meetingID`,`participantID`),
  KEY `pt-m-mid` (`meetingID`),
  KEY `pt-m-pid` (`participantID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Attendance and participation level of enrolled participants ';

-- --------------------------------------------------------

--
-- Table structure for table `participantNotes`
--

CREATE TABLE IF NOT EXISTS `participantNotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participantID` int(11) NOT NULL,
  `programID` int(11) DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `note` text NOT NULL,
  `datestamp` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pt-note-pid` (`participantID`),
  KEY `pt-note-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `participantPrograms`
--

CREATE TABLE IF NOT EXISTS `participantPrograms` (
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
  KEY `pt-pr-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.';

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE IF NOT EXISTS `participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `lastName` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `dateOfBirth` date NOT NULL,
  `createdOn` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `firstName` (`firstName`,`lastName`,`dateOfBirth`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Basic information for participants (volunteers, citizens, cl' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `participantUsers`
--

CREATE TABLE IF NOT EXISTS `participantUsers` (
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

-- --------------------------------------------------------

--
-- Table structure for table `programEvents`
--

CREATE TABLE IF NOT EXISTS `programEvents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programID` int(11) NOT NULL,
  `startDate` datetime NOT NULL,
  `endDate` datetime NOT NULL,
  `name` varchar(140) NOT NULL,
  `description` text NOT NULL,
  `jobsNeeded` text,
  `location` text,
  `createdBy` int(11) NOT NULL,
  `updatedBy` int(11) NOT NULL,
  `updatedOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `programID` (`programID`),
  KEY `createdBy` (`createdBy`),
  KEY `updatedBy` (`updatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `programEventSignups`
--

CREATE TABLE IF NOT EXISTS `programEventSignups` (
  `eventID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `jobID` int(11) NOT NULL,
  PRIMARY KEY (`eventID`,`userID`,`jobID`),
  KEY `eventID` (`eventID`),
  KEY `userID` (`userID`),
  KEY `jobID` (`jobID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `programForms`
--

CREATE TABLE IF NOT EXISTS `programForms` (
  `formID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `frequency` varchar(20) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`formID`,`programID`),
  KEY `pr-form-fid` (`formID`),
  KEY `deptid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Forms associated with programs';

-- --------------------------------------------------------

--
-- Table structure for table `programFunders`
--

CREATE TABLE IF NOT EXISTS `programFunders` (
  `programID` int(11) NOT NULL,
  `funderID` int(11) NOT NULL,
  PRIMARY KEY (`programID`,`funderID`),
  KEY `pr-fund-prid` (`programID`),
  KEY `pr-fund-fundid` (`funderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate programs with funders\n** USE THIS TO PULL IN FORMS';

-- --------------------------------------------------------

--
-- Table structure for table `programJobs`
--

CREATE TABLE IF NOT EXISTS `programJobs` (
  `programID` int(11) NOT NULL,
  `jobID` int(11) NOT NULL,
  PRIMARY KEY (`programID`,`jobID`),
  KEY `pr-job-prid` (`programID`),
  KEY `pr-prog-jobid` (`jobID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate programs with funders\n** USE THIS TO PULL IN FORMS';

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE IF NOT EXISTS `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deptID` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `volunteerType` varchar(140) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prname_UNIQUE` (`name`),
  KEY `pr-deptid` (`deptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Programmatic efforts within departments.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ptcpProgramArchive`
--

CREATE TABLE IF NOT EXISTS `ptcpProgramArchive` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Enroll participants in programs.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ptcpSecureIDs`
--

CREATE TABLE IF NOT EXISTS `ptcpSecureIDs` (
  `ptcpID` int(11) NOT NULL,
  `anonID` varchar(140) NOT NULL,
  PRIMARY KEY (`ptcpID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='MD5 hash, compatible with FSII';

-- --------------------------------------------------------

--
-- Table structure for table `ptcpUserArchive`
--

CREATE TABLE IF NOT EXISTS `ptcpUserArchive` (
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

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleName` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleName` (`roleName`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=41 ;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `roleName`) VALUES
(20, 'staff'),
(30, 'manager'),
(40, 'admin'),
(10, 'evaluator'),
(15, 'volunteer');

-- --------------------------------------------------------

--
-- Table structure for table `scheduleDepts`
--

CREATE TABLE IF NOT EXISTS `scheduleDepts` (
  `setID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  PRIMARY KEY (`setID`,`deptID`),
  KEY `pt-d-pid` (`setID`),
  KEY `pt-d-deptid` (`deptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate schedule sets with departments';

-- --------------------------------------------------------

--
-- Table structure for table `scheduledEvents`
--

CREATE TABLE IF NOT EXISTS `scheduledEvents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setID` int(11) NOT NULL,
  `startDate` datetime NOT NULL,
  `endDate` datetime NOT NULL,
  `name` varchar(280) NOT NULL,
  `resourceType` varchar(140) DEFAULT NULL,
  `resourceID` int(11) DEFAULT NULL,
  `linkType` text,
  `linkID` int(11) NOT NULL,
  `createdBy` int(11) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `setID` (`setID`,`createdBy`),
  KEY `createdBy` (`createdBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scheduleSets`
--

CREATE TABLE IF NOT EXISTS `scheduleSets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(140) NOT NULL,
  `fromTime` varchar(140) DEFAULT NULL,
  `toTime` varchar(140) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `resources` text,
  `createdBy` int(11) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_userID-createdBy` (`createdBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `storedReports`
--

CREATE TABLE IF NOT EXISTS `storedReports` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report ID',
  `name` varchar(140) NOT NULL COMMENT 'Report Name',
  `frequency` varchar(16) NOT NULL,
  `recipients` text NOT NULL COMMENT 'Recipient Object',
  `includeOptions` text NOT NULL COMMENT 'Coded list of report options',
  `lastUpdated` date NOT NULL,
  `updatedBy` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `userDepartments`
--

CREATE TABLE IF NOT EXISTS `userDepartments` (
  `userID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  `manager` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`,`deptID`),
  KEY `u-d-uid` (`userID`),
  KEY `u-d-deptid` (`deptID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate staff with departments.';

-- --------------------------------------------------------

--
-- Table structure for table `userGroups`
--

CREATE TABLE IF NOT EXISTS `userGroups` (
  `userID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `lead` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`userID`,`groupID`),
  KEY `u-g-uid` (`userID`),
  KEY `u-g-gid` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate users with groups';

-- --------------------------------------------------------

--
-- Table structure for table `userPrograms`
--

CREATE TABLE IF NOT EXISTS `userPrograms` (
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `lead` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`,`programID`),
  KEY `u-pr-uid` (`userID`),
  KEY `u-pr-prid` (`programID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Associate users with programs';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Staff login, contact and ACL information' AUTO_INCREMENT=359 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `userName`, `password`, `lock`, `eMail`, `firstName`, `lastName`, `createdDate`, `lastLogin`, `role`) VALUES
(1, 'admin', 'c8a5362cd3006f7c6e4df976f548a3f9', 0, 'changeme@changeme.org', 'Admin User', 'Change Password', '2018-10-01 00:00:00', NULL, '40');

-- --------------------------------------------------------

--
-- Table structure for table `volunteerActivities`
--

CREATE TABLE IF NOT EXISTS `volunteerActivities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `volunteerID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `type` varchar(140) NOT NULL,
  `typeID` int(11) NOT NULL COMMENT 'ID of target (i.e. ptcp, group, supervisor)',
  `groupMeetingID` int(11) DEFAULT NULL,
  `jobID` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `fromTime` varchar(140) NOT NULL,
  `toTime` varchar(140) NOT NULL,
  `duration` decimal(4,2) NOT NULL,
  `description` text NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedBy` int(11) NOT NULL,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `child_volunteer_id` (`volunteerID`),
  KEY `child_program_ID` (`programID`),
  KEY `updatedBy` (`updatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `volunteerGroups`
--

CREATE TABLE IF NOT EXISTS `volunteerGroups` (
  `volunteerID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `enrollDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`volunteerID`,`groupID`),
  KEY `pt-g-pid` (`volunteerID`),
  KEY `pt-g-gid` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Participants enrolled in ongoing groups.';

-- --------------------------------------------------------

--
-- Table structure for table `volunteerJobs`
--

CREATE TABLE IF NOT EXISTS `volunteerJobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(240) NOT NULL,
  `description` text,
  `updatedBy` int(11) NOT NULL,
  `updatedOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `doNotDisplay` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `updatedBy` (`updatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
