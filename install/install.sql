

-- -----------------------------------------------------
-- Table `as_devices`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_devices`;
CREATE TABLE IF NOT EXISTS `as_devices` (
  `user_id` INT(11) NOT NULL ,
  `device_id` VARCHAR(64) NOT NULL ,
  `policy_key` INT NOT NULL DEFAULT 0,
  `status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `device_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_default_calendar`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_default_calendar`;
CREATE TABLE IF NOT EXISTS `as_default_calendar` (
  `user_id` INT(11) NOT NULL ,
  `calendar_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`calendar_id`) REFERENCES  `cal_calendars`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_calendars`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_calendars`;
CREATE TABLE IF NOT EXISTS `as_calendars` (
  `user_id` INT(11) NOT NULL ,
  `calendar_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`, `calendar_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`calendar_id`) REFERENCES  `cal_calendars`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_default_addressbook`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_default_addressbook`;
CREATE TABLE IF NOT EXISTS `as_default_addressbook` (
  `user_id` INT(11) NOT NULL ,
  `addressbook_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`addressbook_id`) REFERENCES  `ab_addressbooks`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_addressbooks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_addressbooks`;
CREATE TABLE IF NOT EXISTS `as_addressbooks` (
  `user_id` INT(11) NOT NULL ,
  `addressbook_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`, `addressbook_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`addressbook_id`) REFERENCES  `ab_addressbooks`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_default_tasklist`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_default_tasklist`;
CREATE TABLE IF NOT EXISTS `as_default_tasklist` (
  `user_id` INT(11) NOT NULL ,
  `tasklist_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`tasklist_id`) REFERENCES  `ta_lists`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

-- -----------------------------------------------------
-- Table `as_tasklists`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `as_tasklists`;
CREATE TABLE IF NOT EXISTS `as_tasklists` (
  `user_id` INT(11) NOT NULL ,
  `tasklist_id` INT(11) NOT NULL ,
  PRIMARY KEY (`user_id`, `tasklist_id`),
  FOREIGN KEY (`user_id`) REFERENCES  `go_users`(`ìd`) ON DELETE CASCADE,
  FOREIGN KEY (`tasklist_id`) REFERENCES  `ta_lists`(`ìd`) ON DELETE CASCADE
) ENGINE = MyISAM DEFAULT CHARSET = utf8;