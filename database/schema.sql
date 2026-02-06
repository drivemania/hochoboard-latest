
CREATE TABLE `__PREFIX__boards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `notice` longtext DEFAULT NULL,
  `board_skin` varchar(50) DEFAULT 'basic',
  `list_count` int(11) DEFAULT 20,
  `read_level` int(11) DEFAULT 1,
  `write_level` int(11) DEFAULT 2,
  `comment_level` int(11) DEFAULT 2,
  `use_secret` tinyint(1) DEFAULT 0,
  `use_editor` tinyint(1) DEFAULT 0,
  `type` varchar(20) DEFAULT 'document',
  `custom_fields` longtext DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__characters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_path2` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `profile_data` longtext DEFAULT NULL CHECK (json_valid(`profile_data`)),
  `relationship` longtext DEFAULT NULL CHECK (json_valid(`relationship`)),
  `is_main` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_group_user` (`group_id`,`user_id`),
  KEY `idx_board_id` (`board_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `board_id` int(11) NOT NULL,
  `doc_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `nickname` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_id` (`doc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `doc_num` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `nickname` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `custom_data` longtext DEFAULT NULL CHECK (json_valid(`custom_data`)),
  `is_notice` tinyint(1) DEFAULT 0,
  `is_secret` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `hit` int(11) DEFAULT 0,
  `comment_count` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_board_id` (`board_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `theme` varchar(50) DEFAULT 'basic',
  `use_notification` tinyint(1) DEFAULT 1,
  `use_fixed_char_fields` tinyint(1) DEFAULT 0,
  `char_fixed_fields` longtext DEFAULT NULL CHECK (json_valid(`char_fixed_fields`)),
  `custom_main_id` int(11) DEFAULT 0,
  `favicon` varchar(255) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `point_name` varchar(10) DEFAULT '포인트' NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_secret` tinyint(1) DEFAULT 0,
  `is_memo_use` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__menus` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `type` varchar(20) DEFAULT 'board',
  `target_id` int(11) NOT NULL,
  `target_url` varchar(2048) NULL DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `order_num` int(11) DEFAULT 0,
  `is_show` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_group_slug` (`group_id`,`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_nickname` varchar(50) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted_sender` tinyint(1) DEFAULT 0,
  `is_deleted_receiver` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`,`is_deleted_sender`),
  KEY `idx_receiver` (`receiver_id`,`is_deleted_receiver`,`read_at`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `message` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_viewed` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_viewed` (`user_id`,`is_viewed`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `level` tinyint(3) unsigned DEFAULT 1,
  `user_point` int(11) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `directory` varchar(100) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_directory` (`directory`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__emoticons` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__board_sequences` (
  `board_id` INT UNSIGNED NOT NULL,
  `last_num` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__user_autologin` (
  `key_id` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `last_ip` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`key_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `__PREFIX__plugin_meta` (
  `id` int(11) unsigned AUTO_INCREMENT PRIMARY KEY,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `plugin_name` VARCHAR(50) NOT NULL,
  `key_name` VARCHAR(50) NOT NULL,
  `value` LONGTEXT,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_target` (`target_type`, `target_id`),
  INDEX `idx_plugin` (`plugin_name`, `key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__items` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `icon_path` VARCHAR(2048) NULL,
    `effect_type` ENUM('none', 'lottery', 'create_item', 'random_box') NOT NULL DEFAULT 'none',
    `effect_data` longtext NULL,
    `is_sellable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_binding` TINYINT(1) NOT NULL DEFAULT 0,
    `is_permanent` TINYINT(1) NOT NULL DEFAULT 0,
    `sell_price` INT NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` timestamp NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__character_items` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `character_id` int(11) UNSIGNED NOT NULL,
    `item_id` int(11) UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `options` longtext NULL,
    `comment` VARCHAR(255) NULL, 
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` timestamp NULL,
    INDEX `idx_char_item` (`character_id`, `item_id`),
    FOREIGN KEY (`item_id`) REFERENCES `__PREFIX__items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__settlement_logs` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `group_id` int(11) UNSIGNED NOT NULL,
    `admin_id` int(11) UNSIGNED NOT NULL,
    `target_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `target_list` longtext NULL,
    `point_amount` INT NOT NULL DEFAULT 0,
    `items_json` longtext NULL,
    `reason` TEXT NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_group_admin` (`group_id`, `admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__shops` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `npc_image_path` VARCHAR(2048) NULL,
    `npc_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_open` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` timestamp NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__shop_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT(11) UNSIGNED NOT NULL,
    `item_id` INT(11) UNSIGNED NOT NULL,
    `price` INT NOT NULL DEFAULT 0,
    `purchase_limit` INT NOT NULL DEFAULT 0,
    `display_order` INT NOT NULL DEFAULT 0,
    
    FOREIGN KEY (`shop_id`) REFERENCES `__PREFIX__shops` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `__PREFIX__items` (`id`) ON DELETE CASCADE,
    INDEX `idx_shop_display` (`shop_id`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `__PREFIX__shop_purchase_logs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shop_item_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `character_id` INT(11) UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price_at_purchase` INT NOT NULL,
    `purchased_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_item` (`user_id`, `shop_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;