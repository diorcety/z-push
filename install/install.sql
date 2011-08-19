

-- -----------------------------------------------------
-- Table `sync_devices`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_devices`;
CREATE  TABLE IF NOT EXISTS `as_devices` (
  `user_id` INT(11) NOT NULL ,
  `device_id` VARCHAR(64) NOT NULL ,
  `policy_key` INT NOT NULL DEFAULT 0,
  `status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `device_id`) 
) ENGINE = MyISAM DEFAULT CHARSET=utf8;