/*
SQLyog Community v12.12 (64 bit)
MySQL - 5.6.14 : Database - blog
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`blog` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `blog`;

/*Table structure for table `authors` */

DROP TABLE IF EXISTS `authors`;

CREATE TABLE `authors` (
  `author_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `authors` */

LOCK TABLES `authors` WRITE;

UNLOCK TABLES;

/*Table structure for table `comments` */

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `body` text,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `fk_comments_belong_to_post` (`post_id`),
  CONSTRAINT `fk_comments_belong_to_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `comments` */

LOCK TABLES `comments` WRITE;

UNLOCK TABLES;

/*Table structure for table `posts` */

DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `post_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) unsigned NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`post_id`),
  KEY `fk_posts_belong_to_an_author` (`author_id`),
  CONSTRAINT `fk_posts_belong_to_an_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `posts` */

LOCK TABLES `posts` WRITE;

UNLOCK TABLES;

/*Table structure for table `posts_tags` */

DROP TABLE IF EXISTS `posts_tags`;

CREATE TABLE `posts_tags` (
  `posts_tags_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`posts_tags_id`),
  KEY `fk_post_tags_belong_to_a_post` (`post_id`),
  KEY `fk_post_tags_belongs_to_a_tag` (`tag_id`),
  CONSTRAINT `fk_post_tags_belong_to_a_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_tags_belongs_to_a_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `posts_tags` */

LOCK TABLES `posts_tags` WRITE;

UNLOCK TABLES;

/*Table structure for table `summaries` */

DROP TABLE IF EXISTS `summaries`;

CREATE TABLE `summaries` (
  `summary_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `view_count` int(11) DEFAULT NULL,
  `comment_count` int(11) DEFAULT NULL,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `post_id` (`post_id`),
  CONSTRAINT `fk_a_post_has_one_summary` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `summaries` */

LOCK TABLES `summaries` WRITE;

UNLOCK TABLES;

/*Table structure for table `tags` */

DROP TABLE IF EXISTS `tags`;

CREATE TABLE `tags` (
  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `m_timestamp` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

/*Data for the table `tags` */

LOCK TABLES `tags` WRITE;

UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
