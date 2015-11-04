SET client_encoding = 'UTF8';
DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public;

CREATE TABLE products (
	product_id serial NOT NULL,
	title varchar(100) NOT NULL,
	PRIMARY KEY (product_id)
);

INSERT INTO products (product_id, title) VALUES
(1, 'Chair'),
(2, 'Table'),
(3, 'Computer');
SELECT setval('products_product_id_seq', 3, TRUE);

CREATE INDEX title ON products USING btree (title);

CREATE TABLE customers (
	customer_id serial NOT NULL,
	name varchar(100) NOT NULL,
	PRIMARY KEY (customer_id)
);

INSERT INTO customers (customer_id, name) VALUES
(1, 'Dave Lister'),
(2, 'Arnold Rimmer'),
(3, 'The Cat'),
(4, 'Holly'),
(5, 'Kryten'),
(6, 'Kristine Kochanski');
SELECT setval('customers_customer_id_seq', 6, TRUE);

CREATE TABLE orders (
	order_id serial NOT NULL,
	customer_id integer NOT NULL,
	product_id integer NOT NULL,
	amount real NOT NULL
);

INSERT INTO orders (order_id, customer_id, product_id, amount) VALUES
(1, 2, 1, 7),
(2, 2, 3, 2),
(3, 1, 2, 3),
(4, 6, 3, 5);
SELECT setval('orders_order_id_seq', 4, TRUE);

ALTER TABLE ONLY orders
    ADD CONSTRAINT orders_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY orders
    ADD CONSTRAINT orders_product_id_fkey FOREIGN KEY (product_id) REFERENCES products(product_id) ON UPDATE CASCADE ON DELETE RESTRICT;
