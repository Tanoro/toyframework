SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- DROP TABLES --------------------------------------------------------

DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `jobtickets`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `timetrack`;
DROP TABLE IF EXISTS `users`;

-- CREATE TABLES --------------------------------------------------------

CREATE TABLE `attachments` (
  `id` int(10) NOT NULL,
  `jid` int(10) NOT NULL DEFAULT '0',
  `sid` int(10) NOT NULL DEFAULT '0',
  `original` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` int(10) NOT NULL DEFAULT '0',
  `dateadded` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `jid` (`jid`),
  KEY `sid` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `customers` (
  `cid` int(10) NOT NULL,
  `customer` varchar(250) NOT NULL DEFAULT '',
  `contact` varchar(250) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  `address` varchar(250) DEFAULT '',
  `city` varchar(250) DEFAULT '',
  `state` varchar(2) DEFAULT '',
  `zip` varchar(15) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `priority` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `jobtickets` (
  `jid` int(10) NOT NULL,
  `pid` int(10) NOT NULL DEFAULT '0',
  `addedby` int(10) NOT NULL DEFAULT '0',
  `claimedby` int(10) DEFAULT '0',
  `opened` int(10) NOT NULL DEFAULT '0',
  `closed` int(10) DEFAULT '0',
  `priority` int(2) NOT NULL DEFAULT '0',
  `subject` varchar(255) NOT NULL DEFAULT 'Untitled',
  `description` text,
  `filename` varchar(250) DEFAULT NULL,
  `jobtype` int(1) NOT NULL DEFAULT '1',
  `website` varchar(250) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `estimatedhours` decimal(10,2) NOT NULL DEFAULT '0.00',
  `testhours` int(4) NOT NULL DEFAULT '0',
  `researchhours` int(4) NOT NULL DEFAULT '0',
  `complexity` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`jid`),
  KEY `pid` (`pid`),
  KEY `addedby` (`addedby`),
  KEY `opened` (`opened`),
  KEY `closed` (`closed`),
  KEY `jobtype` (`jobtype`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `projects` (
  `pid` int(10) NOT NULL,
  `cid` int(10) NOT NULL DEFAULT '0',
  `addedby` int(10) NOT NULL DEFAULT '0',
  `dateadded` int(10) NOT NULL DEFAULT '0',
  `priority` int(2) NOT NULL DEFAULT '0',
  `projecttitle` varchar(100) NOT NULL DEFAULT '',
  `hourfee` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`pid`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `timetrack` (
  `tid` int(10) NOT NULL,
  `jid` int(10) NOT NULL DEFAULT '0' COMMENT 'Job ticket ID',
  `userid` int(10) NOT NULL DEFAULT '0' COMMENT 'Employee ID',
  `started` int(10) NOT NULL DEFAULT '0' COMMENT 'Time started',
  `stopped` int(10) NOT NULL DEFAULT '0' COMMENT 'Time stopped',
  `notes` text,
  PRIMARY KEY (`tid`),
  KEY `jid` (`jid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `cid` int(11) NOT NULL DEFAULT '0',
  `usergroup` varchar(100) NOT NULL DEFAULT '',
  `username` varchar(20) NOT NULL DEFAULT '',
  `pwd` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `joined` int(10) NOT NULL DEFAULT '0',
  `avatar` varchar(100) DEFAULT NULL,
  `salt` varchar(5) NOT NULL DEFAULT '',
  `token` varchar(32) NOT NULL DEFAULT '',
  `lastactivity` int(10) NOT NULL DEFAULT '0',
  `lang` varchar(10) NOT NULL DEFAULT '',
  `timezone` varchar(100) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `address1` varchar(255) NOT NULL DEFAULT '',
  `address2` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `state` char(2) NOT NULL DEFAULT '',
  `zip` varchar(10) NOT NULL DEFAULT '',
  `homephone` varchar(15) NOT NULL DEFAULT '',
  `workphone` varchar(15) NOT NULL DEFAULT '',
  `cellphone` varchar(15) NOT NULL DEFAULT '',
  `lastupdated` int(11) NOT NULL DEFAULT '0',
  `comment` text,
  PRIMARY KEY (`userid`),
  UNIQUE KEY `users` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


# Triggers and Procedures
DELIMITER $$

-- TRIGGERS --------------------------------------------------------

-- Drop triggers before rebuilding
DROP TRIGGER IF EXISTS delSessions $$
DROP TRIGGER IF EXISTS delTickets $$
DROP TRIGGER IF EXISTS delProjects $$
DROP TRIGGER IF EXISTS delUserSessions $$

-- Delete timetrack sessions when job tickets are deleted
CREATE TRIGGER delSessions AFTER DELETE ON `jobtickets`
	FOR EACH ROW BEGIN
    DELETE FROM timetrack WHERE jid = OLD.jid ;
END$$

-- Delete job tickets when projects are deleted
CREATE TRIGGER delTickets AFTER DELETE ON `projects`
	FOR EACH ROW BEGIN
    DELETE FROM jobtickets WHERE pid = OLD.pid ;
END$$

-- Delete project when customers are deleted
CREATE TRIGGER delProjects AFTER DELETE ON `customers`
	FOR EACH ROW BEGIN
    DELETE FROM projects WHERE cid = OLD.cid ;
END$$

-- Delete timetrack sessions when users are deleted
CREATE TRIGGER delUserSessions AFTER DELETE ON `users`
	FOR EACH ROW BEGIN
    DELETE FROM timetrack WHERE userid = OLD.userid ;
END$$


-- PROCEDURES --------------------------------------------------------

CREATE PROCEDURE `estimateJobTime` (IN `jobid` INT)  BEGIN
	# Variable declarations
	DECLARE varComplexity INT(10) DEFAULT 0;
	DECLARE varJobtype INT(10) DEFAULT 0;
	DECLARE varTesthours INT(10) DEFAULT 0;
	DECLARE varResearchhours INT(10) DEFAULT 0;
	DECLARE varAvgHours DECIMAL(10,2);
	DECLARE varAvgDifference DECIMAL(10,2);
	DECLARE varTimeModifier DECIMAL(10,2);
	DECLARE varEstimatedHours DECIMAL(10,2);

	# The higher the timeModifier (1-10), the less weight the complexity approximation contributes
	SET varTimeModifier = 3.00;

	# Get task information
	SELECT jobtype, testhours, researchhours, complexity
	INTO varJobtype, varTesthours, varResearchhours, varComplexity
	FROM jobtickets
	WHERE jid = jobid ;

	# Calculate some historic averages that we will need later
	SELECT AVG(j.totalHours), AVG(j.totalDifference) INTO varAvgHours, varAvgDifference
	FROM (
		SELECT j.jid, (SUM(t.stopped) - SUM(t.started)) / 60 / 60 totalHours,
		((SUM(t.stopped) - SUM(t.started)) / 60 / 60) - j.estimatedhours totalDifference
		FROM jobtickets j, timetrack t
		WHERE j.jid = t.jid
			AND j.jobtype = varJobtype # Calculate based on the job type
			AND j.complexity != 0 # Exclude jobs where complexity was not entered
			AND t.stopped != 0 # Exclude jobs currently in session
		GROUP BY j.jid
	) j;

	# Here is the magic formula!
	SELECT (   ((varComplexity / varTimeModifier) * varAvgHours) + (varAvgDifference * .5) + (varTesthours + varResearchhours)   ) n INTO varEstimatedHours;
	UPDATE jobtickets SET estimatedhours = ROUND(varEstimatedHours, 2) WHERE jid = jobid;
END$$

DELIMITER ;

COMMIT;
