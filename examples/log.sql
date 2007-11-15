OK: SELECT * FROM [customers] WHERE [customer_id] =  1;
-- rows: 1
-- takes: 0.395 ms
-- driver: sqlite
-- 2007-11-15 01:19:00

OK: SELECT * FROM [customers] WHERE [customer_id] <  5;
-- rows: 4
-- takes: 0.437 ms
-- driver: sqlite
-- 2007-11-15 01:19:00

ERROR: [1] near "FROM": syntax error
-- SQL: SELECT FROM [customers] WHERE [customer_id] <  38
-- driver: ;
-- 2007-11-15 01:19:00

