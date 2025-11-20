SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;


/* ---------------- ADMIN TABLE ---------------- */
CREATE TABLE IF NOT EXISTS `admin` (
  `Aid` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(60) DEFAULT NULL,
  `password` text NOT NULL,
  `location` text NOT NULL,
  `address` text NOT NULL,
  PRIMARY KEY (`Aid`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- DELIVERY PERSON TABLE ---------------- */
CREATE TABLE IF NOT EXISTS `delivery_persons` (
  `Did` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `city` varchar(50) DEFAULT NULL,
  `live_lat` varchar(50) NULL,
  `live_lng` varchar(50) NULL,
  `last_updated` timestamp NULL,
  PRIMARY KEY (`Did`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- FOOD DONATIONS ---------------- */
CREATE TABLE IF NOT EXISTS `food_donations` (
  `Fid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(60) NOT NULL,
  `food` varchar(50) NOT NULL,
  `type` text NOT NULL,
  `category` text NOT NULL,
  `quantity` text NOT NULL,
  `prepared_at` datetime NULL,
  `best_before` datetime NULL,
  `perishability` ENUM('hot','cold','dry') DEFAULT 'dry',
  `storage_condition` varchar(255) NULL,
  `quality_status` ENUM('pending','pass','fail','needs_review') NOT NULL DEFAULT 'pending',
  `quality_verified_by` int(11) NULL,
  `date` datetime DEFAULT current_timestamp(),
  `address` text NOT NULL,
  `location` varchar(50) NOT NULL,
  `lat` decimal(10,7) NULL,
  `lng` decimal(10,7) NULL,
  `phoneno` varchar(25) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `delivery_by` int(11) DEFAULT NULL,
  `receiver_request` ENUM('none','requested') NOT NULL DEFAULT 'none',
  `status` ENUM('pending','assigned','picked_up','delivered') NOT NULL DEFAULT 'pending',
  `picked_at` datetime NULL,
  `delivered_at` datetime NULL,
  PRIMARY KEY (`Fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- LOGIN TABLE ---------------- */
CREATE TABLE IF NOT EXISTS `login` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(60) NOT NULL,
  `password` text NOT NULL,
  `gender` text NOT NULL,
  `security_question` varchar(255) NULL,
  `security_answer` varchar(255) NULL,
  `role` ENUM('super_admin','receiver','donor','delivery') NOT NULL DEFAULT 'donor',
  `city` varchar(60) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- USER FEEDBACK ---------------- */
CREATE TABLE IF NOT EXISTS `user_feedback` (
  `feedback_id` int(11) NOT NULL,
  `name` varchar(255),
  `email` varchar(255),
  `message` text,
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- RECEIVERS TABLE SAFE ---------------- */
CREATE TABLE IF NOT EXISTS `receivers` (
  `Rid` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` varchar(60) DEFAULT NULL,
  `password` text NOT NULL,
  `location` text NOT NULL,
  `address` text NOT NULL,
  PRIMARY KEY (`Rid`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ---------------- FOREIGN KEY FIX ---------------- */
ALTER TABLE `login`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `food_donations`
  MODIFY `quality_verified_by` INT(11) NULL;

ALTER TABLE `food_donations`
  DROP FOREIGN KEY IF EXISTS `fk_quality_superadmin`;

ALTER TABLE `food_donations`
  ADD CONSTRAINT `fk_quality_superadmin`
    FOREIGN KEY (`quality_verified_by`) REFERENCES `login`(`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;



/* ---------------- VERIFICATION TABLE ---------------- */
CREATE TABLE IF NOT EXISTS `food_verification` (
  `vid` INT AUTO_INCREMENT PRIMARY KEY,
  `Fid` INT NOT NULL,
  `quality_verified` TINYINT(1) DEFAULT 0,
  `quality_score` INT DEFAULT NULL,
  `quality_proof` VARCHAR(255) DEFAULT NULL,
  `quality_reason` TEXT DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `verification_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`Fid`) REFERENCES `food_donations`(`Fid`)
);


CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id INT NOT NULL,
    donor_id INT NOT NULL,
    donation_id INT NOT NULL,
    feedback_text TEXT,
    sentiment VARCHAR(10),
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE food_donations 
ADD COLUMN quality_proof VARCHAR(255) NULL,
ADD COLUMN quality_score INT NULL,
ADD COLUMN quality_reason TEXT NULL;


CREATE TABLE IF NOT EXISTS leaderboard (
    donor_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    badge VARCHAR(50) DEFAULT 'New Contributor',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


/* ---------------- AUTO-INCREMENT FIXES ---------------- */
ALTER TABLE `admin` MODIFY `Aid` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `delivery_persons` MODIFY `Did` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `login` MODIFY `id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `food_donations` MODIFY `Fid` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_feedback` MODIFY `feedback_id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE food_donations
ADD COLUMN expiry_date DATETIME NULL AFTER quantity;

ALTER TABLE food_donations
ADD COLUMN agreement_accepted TINYINT(1) DEFAULT 0;

-- 1. Add column to delivery_persons (if not present)
ALTER TABLE delivery_persons
ADD COLUMN consent_signed TINYINT(1) DEFAULT 0;

-- 2. Create the consent log table
CREATE TABLE IF NOT EXISTS delivery_consent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    consent_text LONGTEXT NOT NULL,
    consent_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES delivery_persons(Did)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Add consent flag to receivers table
ALTER TABLE receivers
ADD COLUMN consent_signed TINYINT(1) DEFAULT 0;

-- 2. Create NGO consent logs table
CREATE TABLE IF NOT EXISTS ngo_consent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id INT NOT NULL,
    consent_text LONGTEXT NOT NULL,
    consent_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES receivers(Rid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
