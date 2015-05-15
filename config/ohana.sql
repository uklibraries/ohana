-- MySQL Script generated by MySQL Workbench
-- 05/07/15 09:36:46
-- Model: New Model    Version: 1.0
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema ohana
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `ohana` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `ohana` ;

-- -----------------------------------------------------
-- Table `ohana`.`year`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`year` ;

CREATE TABLE IF NOT EXISTS `ohana`.`year` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `year` INT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`year` (`id` ASC);

CREATE UNIQUE INDEX `year_UNIQUE` ON `ohana`.`year` (`year` ASC);


-- -----------------------------------------------------
-- Table `ohana`.`collection`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`collection` ;

CREATE TABLE IF NOT EXISTS `ohana`.`collection` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `collection` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`collection` (`id` ASC);

CREATE UNIQUE INDEX `collection_UNIQUE` ON `ohana`.`collection` (`collection` ASC);


-- -----------------------------------------------------
-- Table `ohana`.`type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`type` ;

CREATE TABLE IF NOT EXISTS `ohana`.`type` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

CREATE UNIQUE INDEX `type_UNIQUE` ON `ohana`.`type` (`type` ASC);

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`type` (`id` ASC);


-- -----------------------------------------------------
-- Table `ohana`.`identifier`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`identifier` ;

CREATE TABLE IF NOT EXISTS `ohana`.`identifier` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `canonical` VARCHAR(45) NULL,
  `as_submitted` VARCHAR(45) NULL,
  `year_id` INT NOT NULL,
  `collection_id` INT NOT NULL,
  `type_id` INT NOT NULL,
  `circulating` TINYINT(1) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_identifier_year`
    FOREIGN KEY (`year_id`)
    REFERENCES `ohana`.`year` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_identifier_collection1`
    FOREIGN KEY (`collection_id`)
    REFERENCES `ohana`.`collection` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_identifier_type1`
    FOREIGN KEY (`type_id`)
    REFERENCES `ohana`.`type` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`identifier` (`id` ASC);

CREATE UNIQUE INDEX `canonical_UNIQUE` ON `ohana`.`identifier` (`canonical` ASC);

CREATE INDEX `fk_identifier_year_idx` ON `ohana`.`identifier` (`year_id` ASC);

CREATE INDEX `fk_identifier_collection1_idx` ON `ohana`.`identifier` (`collection_id` ASC);

CREATE INDEX `fk_identifier_type1_idx` ON `ohana`.`identifier` (`type_id` ASC);


-- -----------------------------------------------------
-- Table `ohana`.`year_counter`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`year_counter` ;

CREATE TABLE IF NOT EXISTS `ohana`.`year_counter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `value` INT NOT NULL,
  `year_id` INT NOT NULL,
  `type_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_year_counter_year1`
    FOREIGN KEY (`year_id`)
    REFERENCES `ohana`.`year` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_year_counter_type1`
    FOREIGN KEY (`type_id`)
    REFERENCES `ohana`.`type` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`year_counter` (`id` ASC);

CREATE INDEX `fk_year_counter_year1_idx` ON `ohana`.`year_counter` (`year_id` ASC);

CREATE INDEX `fk_year_counter_type1_idx` ON `ohana`.`year_counter` (`type_id` ASC);


-- -----------------------------------------------------
-- Table `ohana`.`collection_counter`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ohana`.`collection_counter` ;

CREATE TABLE IF NOT EXISTS `ohana`.`collection_counter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `value` INT NOT NULL,
  `type_id` INT NOT NULL,
  `collection_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_collection_counter_type1`
    FOREIGN KEY (`type_id`)
    REFERENCES `ohana`.`type` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_collection_counter_collection1`
    FOREIGN KEY (`collection_id`)
    REFERENCES `ohana`.`collection` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE UNIQUE INDEX `id_UNIQUE` ON `ohana`.`collection_counter` (`id` ASC);

CREATE INDEX `fk_collection_counter_type1_idx` ON `ohana`.`collection_counter` (`type_id` ASC);

CREATE INDEX `fk_collection_counter_collection1_idx` ON `ohana`.`collection_counter` (`collection_id` ASC);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
