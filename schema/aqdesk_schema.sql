-- =====================================================================
--  AQDesk — Answer Question Community, Learning and Knowledge Platform
--  CS619 Final Year Project — Prototype Phase
--
--  MySQL / MariaDB schema (matches the Laravel migrations in /src)
--
--  This file is provided as the "Database schema (.sql)" deliverable.
--  You do NOT need to run it by hand: `php artisan migrate --seed`
--  builds exactly this structure and fills it with demo data.
--  Import this file only if you want the empty structure directly.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `aqdesk`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `aqdesk`;

-- ---------------------------------------------------------------------
-- FRAMEWORK TABLES (Laravel: sessions, cache and queue support)
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;

-- ---------------------------------------------------------------------
-- APPLICATION TABLES (dropped children first)
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `job_applications`;
DROP TABLE IF EXISTS `job_posts`;
DROP TABLE IF EXISTS `votes`;
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `question_tag`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `profiles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

-- =====================================================================
--  MODULE 2 — ROLE-BASED ACCESS
-- =====================================================================

CREATE TABLE `roles` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255) NOT NULL,
    `slug`        VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  MODULE 1 — AUTHENTICATION   /   MODULE 4 — USER PROFILE
-- =====================================================================

CREATE TABLE `users` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(255) NOT NULL,
    `email`             VARCHAR(255) NOT NULL,
    `role_id`           BIGINT UNSIGNED NULL,
    `email_verified_at` TIMESTAMP NULL,
    `password`          VARCHAR(255) NOT NULL,
    `remember_token`    VARCHAR(100) NULL,
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_role_id_foreign` (`role_id`),
    CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `profiles` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `avatar_path`  VARCHAR(255) NULL,
    `headline`     VARCHAR(255) NULL,
    `bio`          TEXT NULL,
    `skills`       VARCHAR(255) NULL,
    `organization` VARCHAR(255) NULL,
    `location`     VARCHAR(255) NULL,
    `website`      VARCHAR(255) NULL,
    `linkedin`     VARCHAR(255) NULL,
    `github`       VARCHAR(255) NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `profiles_user_id_unique` (`user_id`),
    CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  MODULE 5 — COMMUNITY Q&A
-- =====================================================================

CREATE TABLE `categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255) NOT NULL,
    `slug`        VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `icon`        VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `slug`       VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `questions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NULL,
    `title`       VARCHAR(255) NOT NULL,
    `slug`        VARCHAR(255) NOT NULL,
    `body`        TEXT NOT NULL,
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_closed`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `questions_slug_unique` (`slug`),
    KEY `questions_created_at_index` (`created_at`),
    KEY `questions_user_id_foreign` (`user_id`),
    KEY `questions_category_id_foreign` (`category_id`),
    CONSTRAINT `questions_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `questions_category_id_foreign` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot: many-to-many between questions and tags.
CREATE TABLE `question_tag` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`      BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `question_tag_question_id_tag_id_unique` (`question_id`, `tag_id`),
    KEY `question_tag_tag_id_foreign` (`tag_id`),
    CONSTRAINT `question_tag_question_id_foreign` FOREIGN KEY (`question_id`)
        REFERENCES `questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `question_tag_tag_id_foreign` FOREIGN KEY (`tag_id`)
        REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `answers` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `body`        TEXT NOT NULL,
    `is_accepted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `answers_question_id_foreign` (`question_id`),
    KEY `answers_user_id_foreign` (`user_id`),
    CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`)
        REFERENCES `questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `answers_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Polymorphic votes: one table serves BOTH questions and answers.
-- votable_type holds the model class name, votable_id its primary key.
CREATE TABLE `votes` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `votable_type` VARCHAR(255) NOT NULL,
    `votable_id`   BIGINT UNSIGNED NOT NULL,
    `value`        TINYINT NOT NULL COMMENT '1 = upvote, -1 = downvote',
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `votes_unique_per_user` (`user_id`, `votable_id`, `votable_type`),
    KEY `votes_votable_type_votable_id_index` (`votable_type`, `votable_id`),
    CONSTRAINT `votes_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  MODULE 6 — JOBS AND OPPORTUNITIES
--  NOTE: the table is `job_posts`, not `jobs`, because Laravel reserves
--        `jobs` for its queue worker.
-- =====================================================================

CREATE TABLE `job_posts` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL COMMENT 'the employer / expert who posted it',
    `title`        VARCHAR(255) NOT NULL,
    `slug`         VARCHAR(255) NOT NULL,
    `company`      VARCHAR(255) NOT NULL,
    `type`         ENUM('job','internship') NOT NULL DEFAULT 'job',
    `work_mode`    ENUM('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
    `location`     VARCHAR(255) NULL,
    `description`  TEXT NOT NULL,
    `requirements` TEXT NULL,
    `salary_min`   DECIMAL(10,2) NULL,
    `salary_max`   DECIMAL(10,2) NULL,
    `deadline`     DATE NULL,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `job_posts_slug_unique` (`slug`),
    KEY `job_posts_type_is_active_index` (`type`, `is_active`),
    KEY `job_posts_user_id_foreign` (`user_id`),
    CONSTRAINT `job_posts_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_applications` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_post_id`  BIGINT UNSIGNED NOT NULL,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `cover_letter` TEXT NULL,
    `resume_path`  VARCHAR(255) NULL,
    `status`       ENUM('pending','reviewed','shortlisted','rejected','accepted')
                   NOT NULL DEFAULT 'pending',
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `job_applications_job_post_id_user_id_unique` (`job_post_id`, `user_id`),
    KEY `job_applications_user_id_foreign` (`user_id`),
    CONSTRAINT `job_applications_job_post_id_foreign` FOREIGN KEY (`job_post_id`)
        REFERENCES `job_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `job_applications_user_id_foreign` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  LARAVEL FRAMEWORK TABLES
-- =====================================================================

CREATE TABLE `password_reset_tokens` (
    `email`      VARCHAR(255) NOT NULL,
    `token`      VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id`            VARCHAR(255) NOT NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    `ip_address`    VARCHAR(45) NULL,
    `user_agent`    TEXT NULL,
    `payload`       LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key`        VARCHAR(255) NOT NULL,
    `value`      MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key`        VARCHAR(255) NOT NULL,
    `owner`      VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue`        VARCHAR(255) NOT NULL,
    `payload`      LONGTEXT NOT NULL,
    `attempts`     TINYINT UNSIGNED NOT NULL,
    `reserved_at`  INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at`   INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id`             VARCHAR(255) NOT NULL,
    `name`           VARCHAR(255) NOT NULL,
    `total_jobs`     INT NOT NULL,
    `pending_jobs`   INT NOT NULL,
    `failed_jobs`    INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options`        MEDIUMTEXT NULL,
    `cancelled_at`   INT NULL,
    `created_at`     INT NOT NULL,
    `finished_at`    INT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`       VARCHAR(255) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue`      TEXT NOT NULL,
    `payload`    LONGTEXT NOT NULL,
    `exception`  LONGTEXT NOT NULL,
    `failed_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  REFERENCE DATA
--  The three roles and the four categories named in FR2 of the
--  Main Requirement File. `php artisan db:seed` inserts these plus
--  demo users, questions, answers, votes, jobs and applications.
-- =====================================================================

INSERT INTO `roles` (`name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
('Administrator',         'admin',  'Manages users, roles and all community content.',        NOW(), NOW()),
('Expert / Professional', 'expert', 'Answers questions and publishes jobs and internships.',  NOW(), NOW()),
('Member',                'member', 'Student or community member. Asks, answers and applies.', NOW(), NOW());

INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `created_at`, `updated_at`) VALUES
('EdTech',                  'edtech',                  'Learning technology, online education and digital pedagogy.', 'mortarboard', NOW(), NOW()),
('Artificial Intelligence', 'artificial-intelligence', 'Machine learning, LLMs, data science and applied AI.',        'cpu',         NOW(), NOW()),
('Web Development',         'web-development',         'Frontend, backend, frameworks, databases and deployment.',    'code-slash',  NOW(), NOW()),
('Internet Governance',     'internet-governance',     'Digital policy, data protection and online rights.',          'globe2',      NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
