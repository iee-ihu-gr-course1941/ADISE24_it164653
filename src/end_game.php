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

if (!$game_session_id) {
    echo json_encode(["error" => "Invalid or missing game_session_id."]);
    exit;
}

// Διαγραφή των δεδομένων από τη βάση για το συγκεκριμένο παιχνίδι
$sql_delete_progress = "DELETE FROM progress WHERE game_session_id = $game_session_id";
mysqli_query($conn, $sql_delete_progress);

$sql_delete_temp_progress = "DELETE FROM temporary_progress WHERE game_session_id = $game_session_id";
mysqli_query($conn, $sql_delete_temp_progress);

$sql_delete_players = "DELETE FROM players WHERE game_id = (SELECT game_id FROM game_sessions WHERE id = $game_session_id)";
mysqli_query($conn, $sql_delete_players);

$sql_delete_game_session = "DELETE FROM game_sessions WHERE id = $game_session_id";
mysqli_query($conn, $sql_delete_game_session);

// Επιστροφή μηνύματος
echo json_encode([
    "message" => "Game is over, close or start again."
]);

$conn->close();
?>
