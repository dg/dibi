OK: SELECT * FROM [customers] WHERE [customer_id] =  1;
-- rows: 1
-- takes: 0.294 ms
-- driver: sqlite
-- 2007-11-15 00:01:13

OK: SELECT * FROM [customers] WHERE [customer_id] <  5;
-- rows: 4
-- takes: 0.366 ms
-- driver: sqlite
-- 2007-11-15 00:01:13

ERROR: [1] near "FROM": syntax error
-- SQL: SELECT FROM [customers] WHERE [customer_id] <  38
-- driver: ;
-- 2007-11-15 00:01:13

OK: SELECT * FROM [customers] WHERE [customer_id] =  1;
-- rows: 1
-- takes: 0.299 ms
-- driver: sqlite
-- 2007-11-15 00:04:41

OK: SELECT * FROM [customers] WHERE [customer_id] <  5;
-- rows: 4
-- takes: 0.274 ms
-- driver: sqlite
-- 2007-11-15 00:04:41

ERROR: [1] near "FROM": syntax error
-- SQL: SELECT FROM [customers] WHERE [customer_id] <  38
-- driver: ;
-- 2007-11-15 00:04:41

