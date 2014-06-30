-- MySQL: 5.0.45

SET FOREIGN_KEY_CHECKS=0;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`customer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;


INSERT INTO `customers` (`customer_id`, `name`) VALUES
(1, 'Dave Lister'),
(2, 'Arnold Rimmer'),
(3, 'The Cat'),
(4, 'Holly'),
(5, 'Kryten'),
(6, 'Kristine Kochanski');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `enumtest`;
CREATE TABLE IF NOT EXISTS `enumtest` (
  `id` int(11) NOT NULL auto_increment,
  `test` enum('a','b','c') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES
(1, 2, 1, 7),
(2, 2, 3, 2),
(3, 1, 2, 3),
(4, 6, 3, 5);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL auto_increment,
  `title` varchar(100) default NULL,
  PRIMARY KEY  (`product_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `products` (`product_id`, `title`) VALUES
(1, 'Chair'),
(2, 'Table'),
(3, 'Computer');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `settest`;
CREATE TABLE IF NOT EXISTS `settest` (
  `id` int(11) NOT NULL auto_increment,
  `test` set('a','b','c') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `where`;
CREATE TABLE IF NOT EXISTS `where` (
  `select` int(11) NOT NULL,
  `dot.dot` int(11) NOT NULL,
  `is` int(11) NOT NULL,
  `quot'n' space` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `where` (`select`, `dot.dot`, `is`, `quot'n' space`) VALUES
(1, 2, 3, 4);

SET FOREIGN_KEY_CHECKS=1;

SET SQL_MODE="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
