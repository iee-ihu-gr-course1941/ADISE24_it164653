-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 09 Ιαν 2025 στις 22:04:03
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `cant_stop_db`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game_sessions`
--

CREATE TABLE `game_sessions` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_player` int(11) DEFAULT 1,
  `game_session_id` int(11) DEFAULT NULL,
  `active_player_id` int(11) DEFAULT NULL,
  `status` enum('active','completed','waiting') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `moves`
--

CREATE TABLE `moves` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `game_session_id` int(11) NOT NULL,
  `move_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `column_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `game_id` int(11) DEFAULT NULL,
  `player_name` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  `chosen_dice` varchar(255) DEFAULT NULL,
  `chosen_combinations` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `game_session_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `column_number` int(11) NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `chosen_combinations` text DEFAULT NULL,
  `current_position` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `temporary_progress`
--

CREATE TABLE `temporary_progress` (
  `id` int(11) NOT NULL,
  `game_session_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `column_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_position` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active_player_id` (`active_player_id`);

--
-- Ευρετήρια για πίνακα `moves`
--
ALTER TABLE `moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `game_session_id` (`game_session_id`);

--
-- Ευρετήρια για πίνακα `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`);

--
-- Ευρετήρια για πίνακα `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_session_id` (`game_session_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Ευρετήρια για πίνακα `temporary_progress`
--
ALTER TABLE `temporary_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_session_id`),
  ADD KEY `player_id` (`player_id`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `game_sessions`
--
ALTER TABLE `game_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT για πίνακα `moves`
--
ALTER TABLE `moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT για πίνακα `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT για πίνακα `temporary_progress`
--
ALTER TABLE `temporary_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD CONSTRAINT `game_sessions_ibfk_1` FOREIGN KEY (`active_player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL;

--
-- Περιορισμοί για πίνακα `moves`
--
ALTER TABLE `moves`
  ADD CONSTRAINT `moves_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `moves_ibfk_2` FOREIGN KEY (`game_session_id`) REFERENCES `game_sessions` (`id`),
  ADD CONSTRAINT `moves_ibfk_3` FOREIGN KEY (`game_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`game_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `temporary_progress`
--
ALTER TABLE `temporary_progress`
  ADD CONSTRAINT `temporary_progress_ibfk_1` FOREIGN KEY (`game_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `temporary_progress_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
