DROP DATABASE IF EXISTS dibi_test;
CREATE DATABASE dibi_test;
USE dibi_test;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
	`product_id` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(100) NOT NULL,
	PRIMARY KEY (`product_id`),
	KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `products` (`product_id`, `title`) VALUES
(1, 'Chair'),
(3, 'Computer'),
(2, 'Table');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
	`customer_id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL,
	PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `customers` (`customer_id`, `name`) VALUES
(1, 'Dave Lister'),
(2, 'Arnold Rimmer'),
(3, 'The Cat'),
(4, 'Holly'),
(5, 'Kryten'),
(6, 'Kristine Kochanski');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
	`order_id` int(11) NOT NULL AUTO_INCREMENT,
	`customer_id` int(11) NOT NULL,
	`product_id` int(11) NOT NULL,
	`amount` float NOT NULL,
	PRIMARY KEY (`order_id`),
	KEY `customer_id` (`customer_id`),
	KEY `product_id` (`product_id`),
	CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
	CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES
(1, 2, 1, 7),
(2, 2, 3, 2),
(3, 1, 2, 3),
(4, 6, 3, 5);
