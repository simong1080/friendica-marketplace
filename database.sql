CREATE TABLE IF NOT EXISTS `store` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `uid` int(11) NOT NULL,
       `name` char(64) NOT NULL,
       `descr` char(255) NOT NULL,
       `latlong` char(64) NOT NULL,
       PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `products` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `sid` int(11) unsigned,
       `uid` int(11) unsigned NOT NULL,
       `name` char(64) NOT NULL,
       `descr` char(255) NOT NULL,
       `cat` char(255) NOT NULL,
       `price` int(11) unsigned NOT NULL,
       `latlong` char(64),
       `buynow` int(1) NOT NULL,
       `status` int(1) NOT NULL,
       `hash` char(64) NOT NULL,
       PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `wallets` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `uid` int(11) unsigned NOT NULL,
       `lnbits-domain` char(64) NOT NULL,
       `lnbits-api-key` char(64) NOT NULL,
       `lnbits-inv-key` char(64) NOT NULL,
       `lnbits-user` char(64) NOT NULL,
       `lnbits-wallet` char(64) NOT NULL,
       PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `lnbitsadmin` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `lnbits-domain` char(64) NOT NULL,
       `lnbits-admin-key` char(64) NOT NULL,
       `lnbits-admin-user` char(64) NOT NULL,
       PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
