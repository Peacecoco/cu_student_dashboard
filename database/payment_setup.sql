CREATE TABLE IF NOT EXISTS `paymentoptions` (
  `optionid` INT(11) NOT NULL AUTO_INCREMENT,
  `optioncode` VARCHAR(50) NOT NULL,
  `optionname` VARCHAR(100) NOT NULL,
  `chargepercentage` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `fixedcharge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `isactive` TINYINT(1) NOT NULL DEFAULT 1,
  `createdat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedat` DATETIME DEFAULT NULL,
  PRIMARY KEY (`optionid`),
  UNIQUE KEY `optioncode` (`optioncode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `paymentoptions` (`optioncode`, `optionname`, `chargepercentage`, `fixedcharge`, `isactive`, `updatedat`)
VALUES
  ('paystack', 'Paystack', 0.0150, 0.00, 1, NOW()),
  ('flutterwave', 'Flutterwave', 0.0140, 0.00, 1, NOW()),
  ('remita', 'Remita', 0.0200, 0.00, 1, NOW())
ON DUPLICATE KEY UPDATE `optionname` = VALUES(`optionname`), `chargepercentage` = VALUES(`chargepercentage`), `fixedcharge` = VALUES(`fixedcharge`), `isactive` = VALUES(`isactive`), `updatedat` = NOW();

CREATE TABLE IF NOT EXISTS `paymenttransactions` (
  `paymentid` INT(11) NOT NULL AUTO_INCREMENT,
  `applicationid` INT(11) NOT NULL,
  `referencenumber` VARCHAR(50) NOT NULL,
  `matricnumber` VARCHAR(50) NOT NULL,
  `paymentoptioncode` VARCHAR(50) NOT NULL,
  `paymentoptionname` VARCHAR(100) NOT NULL,
  `baseamount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `chargeamount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `totalamount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paymentreference` VARCHAR(100) NOT NULL,
  `gatewayreference` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'successful',
  `paidat` DATETIME DEFAULT NULL,
  `createdat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`paymentid`),
  UNIQUE KEY `paymentreference` (`paymentreference`),
  KEY `applicationid` (`applicationid`),
  KEY `referencenumber` (`referencenumber`),
  KEY `matricnumber` (`matricnumber`),
  CONSTRAINT `fk_paymenttransactions_application` FOREIGN KEY (`applicationid`) REFERENCES `idcardapplications` (`applicationid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
