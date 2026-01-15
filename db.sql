-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 15 Oca 2026, 19:46:53
-- Sunucu sürümü: 10.4.28-MariaDB
-- PHP Sürümü: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `language_platform`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `based_on_assessment_id` int(11) DEFAULT NULL,
  `cefr_level` enum('A1','A2','B1','B2','C1','C2') DEFAULT NULL,
  `ielts_estimate` varchar(30) DEFAULT NULL,
  `toefl_estimate` varchar(30) DEFAULT NULL,
  `summary` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_recommendation_items`
--

CREATE TABLE `ai_recommendation_items` (
  `id` int(11) NOT NULL,
  `recommendation_id` int(11) NOT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `category` enum('GRAMMAR','VOCAB','READING','LISTENING','SPEAKING','WRITING') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_results`
--

CREATE TABLE `ai_results` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `model` varchar(64) NOT NULL,
  `result_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`result_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` enum('GRAMMAR','VOCAB','READING','LISTENING','SPEAKING','WRITING') NOT NULL,
  `level` enum('A1','A2','B1','B2','C1','C2') DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assessment_answers`
--

CREATE TABLE `assessment_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_index` int(11) NOT NULL,
  `question_text` text DEFAULT NULL,
  `answer_text` longtext DEFAULT NULL,
  `is_correct` tinyint(4) DEFAULT NULL,
  `correct_answer` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assessment_attempts`
--

CREATE TABLE `assessment_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('listening','speaking','writing') NOT NULL,
  `part` tinyint(4) DEFAULT NULL,
  `status` enum('in_progress','submitted','evaluated') NOT NULL DEFAULT 'in_progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `assessment_attempts`
--

INSERT INTO `assessment_attempts` (`id`, `user_id`, `type`, `part`, `status`, `started_at`, `submitted_at`, `duration_seconds`, `meta_json`) VALUES
(1, 4, 'speaking', NULL, 'in_progress', '2026-01-15 19:05:48', NULL, 150, '{\"prep_seconds\":10,\"questions_count\":5}'),
(2, 4, 'speaking', NULL, 'in_progress', '2026-01-15 19:06:24', NULL, 150, '{\"prep_seconds\":10,\"questions_count\":5}'),
(3, 4, 'speaking', NULL, 'in_progress', '2026-01-15 19:06:49', NULL, 150, '{\"prep_seconds\":10,\"questions_count\":5}'),
(4, 4, 'speaking', NULL, 'in_progress', '2026-01-15 19:08:12', NULL, 150, '{\"prep_seconds\":10,\"questions_count\":5}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assessment_results`
--

CREATE TABLE `assessment_results` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `correct_count` int(11) NOT NULL DEFAULT 0,
  `wrong_count` int(11) NOT NULL DEFAULT 0,
  `score_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cefr_estimate` enum('A1','A2','B1','B2','C1','C2') DEFAULT NULL,
  `feedback_summary` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assessment_uploads`
--

CREATE TABLE `assessment_uploads` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `kind` enum('speaking_audio') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `instructor_invite_codes`
--

CREATE TABLE `instructor_invite_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_by_user_id` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by_admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `category` enum('GRAMMAR','VOCAB','READING','LISTENING','SPEAKING','WRITING') NOT NULL,
  `level` enum('A1','A2','B1','B2','C1','C2') DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `source` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `education_level` varchar(100) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','INSTRUCTOR','LEARNER') NOT NULL DEFAULT 'LEARNER',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_plain` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `type` enum('writing','speaking','listening','vocabulary','grammar','reading') NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `title` varchar(255) DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_status` (`student_id`,`status`),
  KEY `idx_instructor` (`instructor_id`),
  CONSTRAINT `fk_assign_instructor`
    FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assign_student`
    FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `name`, `education_level`, `email`, `password_hash`, `role`, `reset_token`, `reset_token_expiry`, `active`, `created_at`, `password_plain`) VALUES
(1, 'Admin', NULL, 'admin@test.com', '$2y$10$ddqojy.OdgZfyWh8ki2G8OIXi3YIr2oPd/T5vtQGWrN83EJ9X7xOG', 'ADMIN', NULL, NULL, 1, '2025-12-30 23:45:04', NULL),
(4, 'zeynep', NULL, 'zeynep@seray.com', '$2y$10$FPKg.Zm2nLAtvXeSGBBC0eKXeUP.mkpdb3XyYlHJehR59uHcXJOu6', 'LEARNER', NULL, NULL, 1, '2026-01-15 14:07:24', '123456'),
(6, 'sinem', NULL, 'sinem@sinem.com', '$2y$10$gDteXXVWnB23Q1e.zrR6B.7qBlX79osHt5Qke53KNbENNcj9wdzP.', 'ADMIN', NULL, NULL, 1, '2026-01-15 14:20:02', '123456'),
(8, 'admin', NULL, 'admin@testadmin.com', '$2y$10$zqCHi0vVvbETSGGYTrT6u.dpKTlYkS2.as4IW0fapMdd5yDC8MjC.', 'ADMIN', NULL, NULL, 1, '2026-01-15 14:21:01', '123abc'),
(10, 'user', NULL, 'test@test.com', '$2y$10$tuiCTC2.kN0HTPrkY1/4IeWAreuY71Y6WyxNs/1QKUJVRfFR6hYUW', 'LEARNER', NULL, NULL, 1, '2026-01-15 14:34:46', '123456'),
(13, 'Seray', NULL, 'kakaolu@puding.com', '$2y$10$VUGYutxXTXuYcy6w.wAhb.6apTdN8dvooDXdYxVCBSXMRlFuAV8Hm', 'LEARNER', NULL, NULL, 1, '2026-01-15 18:44:25', 'Pass12345');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `based_on_assessment_id` (`based_on_assessment_id`);

--
-- Tablo için indeksler `ai_recommendation_items`
--
ALTER TABLE `ai_recommendation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recommendation_id` (`recommendation_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Tablo için indeksler `ai_results`
--
ALTER TABLE `ai_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`);

--
-- Tablo için indeksler `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_attempt_q` (`attempt_id`,`question_index`);

--
-- Tablo için indeksler `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`,`part`);

--
-- Tablo için indeksler `assessment_results`
--
ALTER TABLE `assessment_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`);

--
-- Tablo için indeksler `assessment_uploads`
--
ALTER TABLE `assessment_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`);

--
-- Tablo için indeksler `instructor_invite_codes`
--
ALTER TABLE `instructor_invite_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_invite_used_by` (`used_by_user_id`),
  ADD KEY `fk_invite_created_by` (`created_by_admin_id`);

--
-- Tablo için indeksler `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_reset_token` (`reset_token`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ai_recommendation_items`
--
ALTER TABLE `ai_recommendation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ai_results`
--
ALTER TABLE `ai_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `assessment_answers`
--
ALTER TABLE `assessment_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `assessment_results`
--
ALTER TABLE `assessment_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `assessment_uploads`
--
ALTER TABLE `assessment_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `instructor_invite_codes`
--
ALTER TABLE `instructor_invite_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`based_on_assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `ai_recommendation_items`
--
ALTER TABLE `ai_recommendation_items`
  ADD CONSTRAINT `ai_recommendation_items_ibfk_1` FOREIGN KEY (`recommendation_id`) REFERENCES `ai_recommendations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_recommendation_items_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `ai_results`
--
ALTER TABLE `ai_results`
  ADD CONSTRAINT `ai_results_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD CONSTRAINT `assessment_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `assessment_results`
--
ALTER TABLE `assessment_results`
  ADD CONSTRAINT `assessment_results_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `assessment_uploads`
--
ALTER TABLE `assessment_uploads`
  ADD CONSTRAINT `assessment_uploads_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `instructor_invite_codes`
--
ALTER TABLE `instructor_invite_codes`
  ADD CONSTRAINT `fk_invite_created_by` FOREIGN KEY (`created_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invite_used_by` FOREIGN KEY (`used_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
