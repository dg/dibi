CREATE TABLE products (
	product_id COUNTER,
	title TEXT(50)
);

INSERT INTO products (product_id, title) VALUES (1, 'Chair');
INSERT INTO products (product_id, title) VALUES (2, 'Table');
INSERT INTO products (product_id, title) VALUES (3, 'Computer');

CREATE TABLE [customers] (
	[customer_id] COUNTER,
	[name] TEXT(50)
);

INSERT INTO `customers` (`customer_id`, `name`) VALUES (1, 'Dave Lister');
INSERT INTO `customers` (`customer_id`, `name`) VALUES (2, 'Arnold Rimmer');
INSERT INTO `customers` (`customer_id`, `name`) VALUES (3, 'The Cat');
INSERT INTO `customers` (`customer_id`, `name`) VALUES (4, 'Holly');
INSERT INTO `customers` (`customer_id`, `name`) VALUES (5, 'Kryten');
INSERT INTO `customers` (`customer_id`, `name`) VALUES (6, 'Kristine Kochanski');

CREATE TABLE [orders] (
	[order_id] COUNTER,
	[customer_id] INTEGER,
	[product_id] INTEGER,
	[amount] FLOAT
);

INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES (1, 2, 1, 7);
INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES (2, 2, 3, 2);
INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES (3, 1, 2, 3);
INSERT INTO `orders` (`order_id`, `customer_id`, `product_id`, `amount`) VALUES (4, 6, 3, 5);
