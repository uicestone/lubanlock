SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `lubanlock` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `lubanlock`;

CREATE TABLE IF NOT EXISTS `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `syscode` varchar(255) NOT NULL,
  `sysname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `company_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company-key` (`company`,`key`),
  KEY `name` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `leader` int(11) DEFAULT NULL,
  `open` tinyint(1) NOT NULL,
  `extra_course` int(11) DEFAULT NULL,
  `company` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `leader` (`leader`),
  KEY `company` (`company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `client` varchar(255) NOT NULL,
  `duration` float NOT NULL,
  `ip` char(15) DEFAULT NULL,
  `company` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `nav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `name` varchar(16) NOT NULL,
  `params` text,
  `href` varchar(255) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `href` (`href`),
  KEY `order` (`order`),
  KEY `user` (`user`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `num` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `company` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company` (`company`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `num` (`num`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `comment` text,
  `uid` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `object-key-value` (`object`,`key`,`value`(255)),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `value` (`value`(255)),
  KEY `key-value` (`key`,`value`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object_relationship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` int(11) NOT NULL,
  `relative` int(11) NOT NULL,
  `relation` varchar(255) NOT NULL,
  `mod` int(11) NOT NULL DEFAULT '0',
  `is_on` tinyint(1) DEFAULT '1',
  `num` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `people-relative-relation-is_on` (`object`,`relative`,`relation`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `relative` (`relative`),
  KEY `relation` (`relation`),
  KEY `num` (`num`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object_relationship_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relationship` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `uid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `relationship` (`relationship`,`key`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `key-value` (`key`,`value`(255)),
  KEY `value` (`value`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `date` datetime NOT NULL,
  `content` text,
  `comment` text,
  `uid` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student` (`object`),
  KEY `date` (`date`),
  KEY `uid` (`uid`),
  KEY `time` (`time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `object_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` int(11) NOT NULL,
  `tag_taxonomy` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object-tag_taxonomy` (`object`,`tag_taxonomy`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `tag_taxonomy` (`tag_taxonomy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` mediumtext NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT '0' COMMENT '标签组合在一起时的顺序',
  `color` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `order` (`order`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tag_taxonomy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` int(11) NOT NULL,
  `taxonomy` varchar(255) NOT NULL,
  `discription` text,
  `parent` int(11) DEFAULT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT '',
  `lastip` varchar(255) DEFAULT NULL,
  `lastlogin` int(11) DEFAULT NULL,
  `company` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`company`),
  KEY `company` (`company`),
  KEY `password` (`password`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user-key` (`user`,`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


ALTER TABLE `company_config`
  ADD CONSTRAINT `company_config_ibfk_1` FOREIGN KEY (`company`) REFERENCES `company` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `group`
  ADD CONSTRAINT `group_ibfk_2` FOREIGN KEY (`company`) REFERENCES `company` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `group_ibfk_4` FOREIGN KEY (`id`) REFERENCES `object` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `group_ibfk_5` FOREIGN KEY (`leader`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `nav`
  ADD CONSTRAINT `nav_ibfk_3` FOREIGN KEY (`parent`) REFERENCES `nav` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object`
  ADD CONSTRAINT `object_ibfk_2` FOREIGN KEY (`company`) REFERENCES `company` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `object_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object_meta`
  ADD CONSTRAINT `object_meta_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `object_meta_ibfk_4` FOREIGN KEY (`object`) REFERENCES `object` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `object_meta_ibfk_5` FOREIGN KEY (`object`) REFERENCES `object` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object_relationship`
  ADD CONSTRAINT `object_relationship_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `object_relationship_ibfk_5` FOREIGN KEY (`object`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `object_relationship_ibfk_6` FOREIGN KEY (`relative`) REFERENCES `object` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object_relationship_meta`
  ADD CONSTRAINT `object_relationship_meta_ibfk_1` FOREIGN KEY (`relationship`) REFERENCES `object_relationship` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `object_relationship_meta_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object_status`
  ADD CONSTRAINT `object_status_ibfk_4` FOREIGN KEY (`object`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `object_status_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `object_tag`
  ADD CONSTRAINT `object_tag_ibfk_3` FOREIGN KEY (`object`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `object_tag_ibfk_5` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `tag_taxonomy`
  ADD CONSTRAINT `tag_taxonomy_ibfk_1` FOREIGN KEY (`tag`) REFERENCES `tag` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`company`) REFERENCES `company` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `user_ibfk_3` FOREIGN KEY (`id`) REFERENCES `object` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `user_config`
  ADD CONSTRAINT `user_config_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;
