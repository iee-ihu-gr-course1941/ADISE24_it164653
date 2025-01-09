<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cant_stop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

header('Content-Type: application/json');

// Επαλήθευση POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method. Use POST."]);
    exit;
}

// Ανάγνωση δεδομένων
$game_session_id = filter_input(INPUT_POST, 'game_session_id', FILTER_VALIDATE_INT);
$player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
$chosen_combinations = isset($_POST['chosen_combinations']) ? $_POST['chosen_combinations'] : [];

if (!$game_session_id || !$player_id || empty($chosen_combinations)) {
    echo json_encode(["error" => "Invalid or missing game_session_id, player_id, or chosen_combinations."]);
    exit;
}

// Δημιουργία εγγραφών στον πίνακα temporary_progress
foreach ($chosen_combinations as $column_number) {
    // Δημιουργία εγγραφής στον πίνακα temporary_progress με position = 1
    $insert_sql = "INSERT INTO temporary_progress (game_session_id, player_id, column_number, current_position) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iii", $game_session_id, $player_id, $column_number);
    $stmt->execute();
}

echo json_encode([
    "message" => "Combinations chosen and progress created successfully."
]);

$stmt->close();
$conn->close();
?>
