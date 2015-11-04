CREATE TABLE [products] (
	[product_id] INTEGER NOT NULL PRIMARY KEY,
	[title] VARCHAR(100) NOT NULL
);

CREATE INDEX "title" ON "products" ("title");

INSERT INTO "products" ("product_id", "title") VALUES (1, 'Chair');
INSERT INTO "products" ("product_id", "title") VALUES (2, 'Table');
INSERT INTO "products" ("product_id", "title") VALUES (3, 'Computer');

CREATE TABLE [customers] (
	[customer_id] INTEGER PRIMARY KEY NOT NULL,
	[name] VARCHAR(100) NOT NULL
);

INSERT INTO "customers" ("customer_id", "name") VALUES (1, 'Dave Lister');
INSERT INTO "customers" ("customer_id", "name") VALUES (2, 'Arnold Rimmer');
INSERT INTO "customers" ("customer_id", "name") VALUES (3, 'The Cat');
INSERT INTO "customers" ("customer_id", "name") VALUES (4, 'Holly');
INSERT INTO "customers" ("customer_id", "name") VALUES (5, 'Kryten');
INSERT INTO "customers" ("customer_id", "name") VALUES (6, 'Kristine Kochanski');

CREATE TABLE [orders] (
	[order_id] INTEGER NOT NULL PRIMARY KEY,
	[customer_id] INTEGER NOT NULL,
	[product_id] INTEGER NOT NULL,
	[amount] FLOAT NOT NULL,
	CONSTRAINT orders_product FOREIGN KEY (product_id) REFERENCES products (product_id),
	CONSTRAINT orders_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id)
);

INSERT INTO "orders" ("order_id", "customer_id", "product_id", "amount") VALUES (1, 2, 1, '7.0');
INSERT INTO "orders" ("order_id", "customer_id", "product_id", "amount") VALUES (2, 2, 3, '2.0');
INSERT INTO "orders" ("order_id", "customer_id", "product_id", "amount") VALUES (3, 1, 2, '3.0');
INSERT INTO "orders" ("order_id", "customer_id", "product_id", "amount") VALUES (4, 6, 3, '5.0');
