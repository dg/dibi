OK: connected to DB 'sqlite'

OK: SELECT * FROM [customers] WHERE [customer_id] =  1;
-- result: object(DibiSqliteResult) rows: 1
-- takes: 0.331 ms
-- driver: sqlite
-- 2007-05-12 00:14:11 

OK: SELECT * FROM [customers] WHERE [customer_id] <  5;
-- result: object(DibiSqliteResult) rows: 4
-- takes: 0.324 ms
-- driver: sqlite
-- 2007-05-12 00:14:11 

ERROR: [1] SQL logic error or missing database
-- SQL: SELECT FROM [customers] WHERE [customer_id] <  5
-- driver: sqlite;
-- 2007-05-12 00:14:11 

ERROR: [1] SQL logic error or missing database
-- SQL: SELECT FROM [customers] WHERE [customer_id] <  38
-- driver: sqlite;
-- 2007-05-12 00:14:11 

