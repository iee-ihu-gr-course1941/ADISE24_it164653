<?php
namespace App;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $db_name = 'cant_stop_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }

    public function initialize() {
        $conn = $this->connect();
        if ($conn) {
            $queries = [
                "CREATE TABLE IF NOT EXISTS players (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS game_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    status VARCHAR(20) DEFAULT 'ongoing',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS moves (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    player_id INT NOT NULL,
                    game_session_id INT NOT NULL,
                    move_data TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (player_id) REFERENCES players(id),
                    FOREIGN KEY (game_session_id) REFERENCES game_sessions(id)
                )"
            ];
            foreach ($queries as $query) {
                $conn->exec($query);
            }
            echo "Tables created successfully!";
        }
    }
}
