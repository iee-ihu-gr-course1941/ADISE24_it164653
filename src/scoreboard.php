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

// Επαλήθευση GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["error" => "Invalid request method. Use GET."]);
    exit;
}

// Ανάγνωση του game_session_id από το αίτημα
$game_session_id = filter_input(INPUT_GET, 'game_session_id', FILTER_VALIDATE_INT);

if (!$game_session_id) {
    echo json_encode(["error" => "Invalid or missing game_session_id."]);
    exit;
}

// Ανάκτηση των δεδομένων των παικτών
$sql = "
    SELECT 
        p.player_id, 
        p.column_number, 
        p.current_position 
    FROM progress p
    WHERE p.game_session_id = ? 
    ORDER BY p.player_id, p.column_number
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $game_session_id);
$stmt->execute();
$result = $stmt->get_result();

$scoreboard = [];

while ($row = $result->fetch_assoc()) {
    $scoreboard[] = $row;
}

$stmt->close();

if (empty($scoreboard)) {
    echo json_encode(["message" => "No data found for the given game session."]);
    exit;
}

echo json_encode([
    "game_session_id" => $game_session_id,
    "scoreboard" => $scoreboard
]);

$conn->close();
?>
