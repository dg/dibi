Successfully connected to DB 'mysql'

SELECT * FROM `nucleus_item` WHERE `inumber` =  38;
-- Result: object(DibiMySqlResult) rows: 1
-- Takes: 4.994 ms

SELECT * FROM `nucleus_item` WHERE `inumber` <  38;
-- Result: object(DibiMySqlResult) rows: 29
-- Takes: 135.842 ms

SELECT * FROM `*nucleus_item` WHERE `inumber` <  38;
-- Result: Query error: Can't find file: '.\dgx\*nucleus_item.frm' (errno: 22)
-- Takes: 121.454 ms

