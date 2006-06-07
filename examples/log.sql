Successfully connected to DB 'mysql'

SELECT * FROM `nucleus_item` WHERE `inumber` =  38;
-- Result: Query error: Table 'test.nucleus_item' doesn't exist
-- Takes: 1.357 ms

SELECT * FROM `nucleus_item` WHERE `inumber` <  38;
-- Result: Query error: Table 'test.nucleus_item' doesn't exist
-- Takes: 2.013 ms

SELECT * FROM `*nucleus_item` WHERE `inumber` <  38;
-- Result: Query error: Can't find file: '.\test\*nucleus_item.frm' (errno: 22)
-- Takes: 75.413 ms

