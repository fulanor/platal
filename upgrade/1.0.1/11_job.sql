DROP TABLE IF EXISTS profile_job_entreprise_term;
DROP TABLE IF EXISTS profile_mentor_term;
DROP TABLE IF EXISTS profile_job_term;
DROP TABLE IF EXISTS profile_job_term_search;
DROP TABLE IF EXISTS profile_job_term_relation;
DROP TABLE IF EXISTS profile_job_term_enum;

CREATE TABLE `profile_job_term_enum` (
  `jtid` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'term id',
  `name` varchar(255) NOT NULL COMMENT 'name used in hierarchical context',
  `full_name` varchar(255) NOT NULL COMMENT 'name to use whithout context',
  PRIMARY KEY (`jtid`)
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='job terms';

CREATE TABLE `profile_job_term_relation` (
  `jtid_1` int unsigned NOT NULL COMMENT 'first term id',
  `jtid_2` int unsigned NOT NULL COMMENT 'second term id',
  `rel` enum('narrower','related') NOT NULL DEFAULT 'narrower' COMMENT 'relation between the second to the first term (second is narrower than first)',
  `computed` enum('original','computed') NOT NULL DEFAULT 'original' COMMENT 'relations can be computed from two original relations',
  PRIMARY KEY (`jtid_1`, `computed`, `jtid_2`),
  FOREIGN KEY (`jtid_1`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `jtid_2` (`jtid_2`),
  FOREIGN KEY (`jtid_2`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='job terms relations';

CREATE TABLE `profile_job_term_search` (
  `search` varchar(50) NOT NULL COMMENT 'search token for a term',
  `jtid` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'term id',
  PRIMARY KEY (`search`, `jtid`),
  INDEX `jtid` (`jtid`),
  FOREIGN KEY (`jtid`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='search tokens of job terms';

CREATE TABLE `profile_job_term` (
  `pid` INT(11) UNSIGNED DEFAULT NULL COMMENT 'profile id',
  `jid` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT 'job id in profile',
  `jtid` INT UNSIGNED DEFAULT NULL COMMENT 'term id',
  `computed` enum('original','computed') NOT NULL DEFAULT 'original' COMMENT 'terms can be added by user or computed from entreprise',
  PRIMARY KEY (`pid`, `jid`, `jtid`),
  INDEX `jtid` (`jtid`),
  FOREIGN KEY (`pid`, `jid`) REFERENCES `profile_job` (`pid`, `id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`jtid`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='job terms for jobs in profiles';

CREATE TABLE `profile_mentor_term` (
  `pid` INT(11) UNSIGNED DEFAULT NULL COMMENT 'profile id',
  `jtid` int unsigned NOT NULL COMMENT 'term id',
  PRIMARY KEY (`pid`, `jtid`),
  INDEX `jtid` (`jtid`),
  FOREIGN KEY (pid) REFERENCES profiles (pid) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`jtid`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='job terms for mentorship in profiles';

CREATE TABLE `profile_job_entreprise_term` (
  `eid` int(6) unsigned default NULL COMMENT 'entreprise id',
  `jtid` int unsigned NOT NULL COMMENT 'term id',
  PRIMARY KEY (`eid`, `jtid`),
  INDEX `jtid` (`jtid`),
  FOREIGN KEY (`eid`) REFERENCES `profile_job_enum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`jtid`) REFERENCES `profile_job_term_enum` (`jtid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB, CHARSET=utf8, COMMENT='job terms associated to entreprises';

-- Adds the root term --
INSERT INTO `profile_job_term_enum` (`jtid`, `name`) VALUES (0, '');
UPDATE `profile_job_term_enum` SET `jtid` = 0 WHERE `name` = '';

-- vim:set syntax=mysql:
