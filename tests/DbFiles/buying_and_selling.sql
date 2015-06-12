DROP TABLE IF EXISTS "Customers";
CREATE TABLE Customers (
 CustomerID INTEGER PRIMARY KEY
,CompanyName VARCHAR(60)
,ContactName VARCHAR(40)
,ContactTitle VARCHAR(60)
,Address VARCHAR(60)
,City VARCHAR(60)
,State VARCHAR(2)
);
INSERT INTO "Customers" VALUES(1,'Deerfield Tile','Dick Terrcotta','Owner','450 Village Street','Deerfield','IL');
INSERT INTO "Customers" VALUES(2,'Sagebrush Carpet','Barbara Berber','Director of Installations','10 Industrial Drive','El Paso','TX');
INSERT INTO "Customers" VALUES(3,'Floor Co.','Jim Wood','Installer','34218 Private Lane','Monclair','NJ');
INSERT INTO "Customers" VALUES(4,'Main Tile and Bath','Toni Faucet','Owner','Suite 23, Henry Building','Orlando','FL');
INSERT INTO "Customers" VALUES(5,'Slots Carpet','Jack Diamond III','Purchaser','3024 Jackpot Drive','Las Vegas','NV');
INSERT INTO "Customers" VALUES(6,'Slots Carpet','Jack Diamond III','Purchaser','3024 Jackpot Drive','Las Vegas','NV');
INSERT INTO "Customers" VALUES(7,'Main Tile and Bath','Toni Faucet','Owner','Suite 23, Henry Building','Orlando','FL');
INSERT INTO "Customers" VALUES(8,'Sagebrush Carpet','Barbara Berber','Director of Installations','10 Industrial Drive','El Paso','TX');
INSERT INTO "Customers" VALUES(9,'Floor Co.','Jim Wood','Installer','34218 Private Lane','Monclair','NJ');
INSERT INTO "Customers" VALUES(10,'Deerfield Tile','Dick Terrcotta','Owner','450 Village Street','Deerfield','IL');
DROP TABLE IF EXISTS "Employees";
CREATE TABLE Employees (
 EmployeeID INTEGER PRIMARY KEY
,LastName VARCHAR(20)
,FirstName VARCHAR(20)
,Title VARCHAR(60)
,Address VARCHAR(40)
,HireDate VARCHAR(25)
);
INSERT INTO "Employees" VALUES(1,'White','James','Account Manager',NULL,'2011-04-03');
INSERT INTO "Employees" VALUES(2,'Lee','Patty','Account Manager',NULL,'2008-09-15');
INSERT INTO "Employees" VALUES(3,'Smith','Robert','Account Manager',NULL,'2004-06-28');
INSERT INTO "Employees" VALUES(4,'Baker','Lisa','Account Manager',NULL,'2010-11-20');
DROP TABLE IF EXISTS "OrderDetails";
CREATE TABLE OrderDetails (
 OrderDetailID INTEGER PRIMARY KEY
,OrderID INTEGER
,ProductID INTEGER
,UnitPrice REAL
,Quantity INTEGER
);
INSERT INTO "OrderDetails" VALUES(1,1,1,1.1,30);
INSERT INTO "OrderDetails" VALUES(2,1,2,0.25,60);
INSERT INTO "OrderDetails" VALUES(3,2,3,5,80);
INSERT INTO "OrderDetails" VALUES(4,2,4,1.39,110);
INSERT INTO "OrderDetails" VALUES(5,2,5,9.97,140);
INSERT INTO "OrderDetails" VALUES(6,3,6,14.69,160);
INSERT INTO "OrderDetails" VALUES(7,3,1,1.1,30);
INSERT INTO "OrderDetails" VALUES(8,3,2,0.25,50);
INSERT INTO "OrderDetails" VALUES(9,4,3,5,80);
INSERT INTO "OrderDetails" VALUES(10,5,4,1.39,100);
INSERT INTO "OrderDetails" VALUES(11,5,5,9.97,130);
INSERT INTO "OrderDetails" VALUES(12,5,6,14.69,150);
INSERT INTO "OrderDetails" VALUES(13,6,1,1.1,20);
INSERT INTO "OrderDetails" VALUES(14,6,2,0.25,50);
INSERT INTO "OrderDetails" VALUES(15,6,3,5,70);
INSERT INTO "OrderDetails" VALUES(16,7,4,1.39,90);
INSERT INTO "OrderDetails" VALUES(17,7,5,9.97,120);
INSERT INTO "OrderDetails" VALUES(18,8,6,14.69,130);
INSERT INTO "OrderDetails" VALUES(19,8,1,1.1,20);
INSERT INTO "OrderDetails" VALUES(20,8,2,0.25,40);
INSERT INTO "OrderDetails" VALUES(21,9,3,5,60);
INSERT INTO "OrderDetails" VALUES(22,10,4,1.39,80);
INSERT INTO "OrderDetails" VALUES(23,10,1,1.1,20);
INSERT INTO "OrderDetails" VALUES(24,11,2,0.25,40);
INSERT INTO "OrderDetails" VALUES(25,11,3,5,60);
INSERT INTO "OrderDetails" VALUES(26,11,4,1.39,80);
INSERT INTO "OrderDetails" VALUES(27,12,1,1.1,20);
INSERT INTO "OrderDetails" VALUES(28,12,2,0.25,40);
INSERT INTO "OrderDetails" VALUES(29,13,3,5,50);
INSERT INTO "OrderDetails" VALUES(30,14,4,1.39,60);
INSERT INTO "OrderDetails" VALUES(31,14,5,9.97,80);
INSERT INTO "OrderDetails" VALUES(32,15,6,14.69,90);
INSERT INTO "OrderDetails" VALUES(33,15,1,1.1,20);
INSERT INTO "OrderDetails" VALUES(34,16,2,0.25,30);
INSERT INTO "OrderDetails" VALUES(35,16,3,5,40);
INSERT INTO "OrderDetails" VALUES(36,17,4,1.39,50);
INSERT INTO "OrderDetails" VALUES(37,17,5,9.97,70);
INSERT INTO "OrderDetails" VALUES(38,17,6,14.69,80);
INSERT INTO "OrderDetails" VALUES(39,18,1,1.1,10);
INSERT INTO "OrderDetails" VALUES(40,18,2,0.25,20);
INSERT INTO "OrderDetails" VALUES(41,18,3,5,40);
INSERT INTO "OrderDetails" VALUES(42,18,4,1.39,50);
INSERT INTO "OrderDetails" VALUES(43,19,5,9.97,60);
INSERT INTO "OrderDetails" VALUES(44,19,6,14.69,70);
INSERT INTO "OrderDetails" VALUES(45,20,1,1.1,10);
INSERT INTO "OrderDetails" VALUES(46,20,2,0.25,20);
INSERT INTO "OrderDetails" VALUES(47,20,3,5,30);
DROP TABLE IF EXISTS "Orders";
CREATE TABLE Orders (
 OrderID INTEGER PRIMARY KEY
,CustomerID INTEGER
,EmployeeID INTEGER
,OrderDate VARCHAR(25)
,RequiredDate VARCHAR(25)
,ShippedDate VARCHAR(25)
,ShipVia INTEGER
,FreightCharge REAL
);
INSERT INTO "Orders" VALUES(1,1,1,'2012-01-04','2012-01-09','2012-01-05',1,3.75);
INSERT INTO "Orders" VALUES(2,2,2,'2012-01-27','2012-02-01','2012-01-28',1,7.25);
INSERT INTO "Orders" VALUES(3,4,1,'2012-02-19','2012-02-24','2012-02-23',2,5.5);
INSERT INTO "Orders" VALUES(4,2,4,'2012-03-13','2012-03-18','2012-03-14',2,13.5);
INSERT INTO "Orders" VALUES(5,4,2,'2012-04-05','2012-04-10','2012-04-06',3,8.75);
INSERT INTO "Orders" VALUES(6,3,3,'2012-04-28','2012-05-03','2012-04-29',2,11);
INSERT INTO "Orders" VALUES(7,4,3,'2012-05-21','2012-05-26','2012-05-22',1,11.25);
INSERT INTO "Orders" VALUES(8,1,4,'2012-06-13','2012-06-18','2012-06-14',4,13.5);
INSERT INTO "Orders" VALUES(9,2,1,'2012-07-06','2012-07-11','2012-07-07',3,4.75);
INSERT INTO "Orders" VALUES(10,3,2,'2012-07-29','2012-08-03','2012-08-04',1,7.75);
INSERT INTO "Orders" VALUES(11,3,3,'2012-08-21','2012-08-26','2012-08-22',4,11.5);
INSERT INTO "Orders" VALUES(12,1,4,'2012-09-13','2012-09-18','2012-09-14',2,13);
INSERT INTO "Orders" VALUES(13,5,3,'2012-10-06','2012-10-11','2012-10-07',3,12.25);
INSERT INTO "Orders" VALUES(14,2,2,'2012-10-29','2012-11-03','2012-10-30',2,7.5);
INSERT INTO "Orders" VALUES(15,4,2,'2012-11-21','2012-11-26','2012-11-22',1,8.25);
INSERT INTO "Orders" VALUES(16,3,4,'2012-12-14','2012-12-19','2012-12-15',2,14);
INSERT INTO "Orders" VALUES(17,5,1,'2013-01-06','2013-01-11','2013-01-07',3,6.25);
INSERT INTO "Orders" VALUES(18,3,3,'2013-01-29','2013-02-03','2013-01-30',1,10.75);
INSERT INTO "Orders" VALUES(19,2,4,'2013-02-21','2013-02-26','2013-03-01',4,14);
INSERT INTO "Orders" VALUES(20,3,1,'2013-03-16','2013-03-21','2013-03-17',4,5.5);
DROP TABLE IF EXISTS "Shippers";
CREATE TABLE Shippers (
 ShipperID INTEGER PRIMARY KEY
,CompanyName VARCHAR(60)
,Phone VARCHAR(20)
);
INSERT INTO "Shippers" VALUES(1,'USPS','1 (800) 275-8777');
INSERT INTO "Shippers" VALUES(2,'Federal Express','1-800-463-3339');
INSERT INTO "Shippers" VALUES(3,'UPS','1 (800) 742-5877');
INSERT INTO "Shippers" VALUES(4,'DHL','1-800-CALL-DHL');
