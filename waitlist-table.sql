CREATE TABLE `waitlist_samples` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(4) NOT NULL,
  `term` varchar(4) NOT NULL,
  `dept` varchar(8) NOT NULL,
  `course_number` varchar(8) NOT NULL,
  `section` varchar(8) NOT NULL,
  `status` varchar(64) NOT NULL,
  `seats` int(11) NOT NULL,
  `open` int(11) NOT NULL,
  `waitlist` int(11) NOT NULL,
  `remote_addr` varchar(24) NOT NULL,
  `datetime` datetime NOT NULL,
  `last_checked` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8