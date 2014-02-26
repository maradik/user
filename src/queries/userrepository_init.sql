-- Инициализация таблиц БД для класса UserRepository 

CREATE TABLE `%usertablename%` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `session` varchar(32) NOT NULL,
  `role` int(11) NOT NULL DEFAULT '0',
  `createdate` int(11) NOT NULL,
  `logindate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `session` (`session`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32;