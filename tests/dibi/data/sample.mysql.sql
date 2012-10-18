-- phpMyAdmin SQL Dump
-- version 2.11.1.2
-- http://www.phpmyadmin.net
--
-- Poèítaè: localhost
-- Vygenerováno: Nedìle 02. prosince 2007, 19:49
-- Verze MySQL: 5.0.45
-- Verze PHP: 5.2.1

SET FOREIGN_KEY_CHECKS=0;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Databáze: `dibi`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`customer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Vypisuji data pro tabulku `customers`
--

INSERT INTO `customers` (`customer_id`, `name`) VALUES
(1, 'Dave Lister'),
(2, 'Arnold Rimmer'),
(3, 'The Cat'),
(4, 'Holly'),
(5, 'Kryten'),
(6, 'Kristine Kochanski');

-- --------------------------------------------------------

--
-- Struktura tabulky `enumtest`
--

DROP TABLE IF EXISTS `enumtest`;
CREATE TABLE IF NOT EXISTS `enumtest` (
  `id` int(11) NOT NULL auto_increment,
  `test` enum('a','b','c') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Vypisuji data pro tabulku `enumtest`
--


-- --------------------------------------------------------

--
-- Struktura tabulky `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Vypisuji data pro tabulku `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES
(1, 2, 1, 7),
(2, 2, 3, 2),
(3, 1, 2, 3),
(4, 6, 3, 5);

-- --------------------------------------------------------

--
-- Struktura tabulky `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL auto_increment,
  `title` varchar(100) default NULL,
  PRIMARY KEY  (`product_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Vypisuji data pro tabulku `products`
--

INSERT INTO `products` (`product_id`, `title`) VALUES
(1, 'Chair'),
(2, 'Table'),
(3, 'Computer');

-- --------------------------------------------------------

--
-- Struktura tabulky `settest`
--

DROP TABLE IF EXISTS `settest`;
CREATE TABLE IF NOT EXISTS `settest` (
  `id` int(11) NOT NULL auto_increment,
  `test` set('a','b','c') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Vypisuji data pro tabulku `settest`
--


-- --------------------------------------------------------

--
-- Struktura tabulky `where`
--

DROP TABLE IF EXISTS `where`;
CREATE TABLE IF NOT EXISTS `where` (
  `select` int(11) NOT NULL,
  `dot.dot` int(11) NOT NULL,
  `is` int(11) NOT NULL,
  `quot'n' space` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Vypisuji data pro tabulku `where`
--

INSERT INTO `where` (`select`, `dot.dot`, `is`, `quot'n' space`) VALUES
(1, 2, 3, 4);

SET FOREIGN_KEY_CHECKS=1;

SET SQL_MODE="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";

