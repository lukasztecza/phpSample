CREATE TABLE IF NOT EXISTS `file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
    `type` TINYINT(4) NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`name`),
    KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
