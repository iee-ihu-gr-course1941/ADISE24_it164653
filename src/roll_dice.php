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
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if (!$game_session_id || !$player_id || !$action) {
    echo json_encode(["error" => "Invalid or missing game_session_id, player_id, or action."]);
    exit;
}

// Ρίψη ζαριών (4 ζάρια)
$dice_rolls = [];
for ($i = 0; $i < 4; $i++) {
    $dice_rolls[] = rand(1, 6);
}

// Υπολογισμός δυνατών συνδυασμών (όλοι οι συνδυασμοί 2-2 από τα 4 ζάρια)
$valid_combinations = [];
for ($i = 0; $i < 4; $i++) {
    for ($j = $i + 1; $j < 4; $j++) {
        $valid_combinations[] = $dice_rolls[$i] + $dice_rolls[$j];
    }
}

// Αφαίρεση διπλών συνδυασμών και επιστροφή των πρώτων 3
$valid_combinations = array_unique($valid_combinations);
$valid_combinations = array_slice($valid_combinations, 0, 3);  // Περιορισμός σε 3 συνδυασμούς

// Εάν η ενέργεια είναι "roll"
if ($action === 'roll') {
    echo json_encode([
        "message" => "You rolled the dice: " . implode(", ", $dice_rolls),
        "dice_rolls" => $dice_rolls,
        "valid_combinations" => $valid_combinations
    ]);
    exit;
}

// Εάν η ενέργεια είναι "continue", συνεχίζουμε
if ($action === 'continue') {
    // Ανάκτηση των επιλεγμένων συνδυασμών του παίκτη από τον πίνακα temporary_progress
    $sql_player_columns = "SELECT column_number, current_position FROM temporary_progress WHERE game_session_id = $game_session_id AND player_id = $player_id";
    $result = mysqli_query($conn, $sql_player_columns);

    if ($result && mysqli_num_rows($result) > 0) {
        // Αν υπάρχουν στήλες για τον παίκτη
        $player_columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $player_columns[] = $row;
        }

        // Εύρεση των στήλων που ταιριάζουν με τους συνδυασμούς
        $matching_columns = [];
        foreach ($player_columns as $column) {
            if (in_array($column['column_number'], $valid_combinations)) {
                $matching_columns[] = $column['column_number'];
            }
        }

        // Αν υπάρχουν matching columns, ενημερώνουμε τη θέση μόνο για αυτές τις στήλες
        foreach ($matching_columns as $column_number) {
            $update_position_sql = "UPDATE temporary_progress SET current_position = current_position + 1 WHERE game_session_id = $game_session_id AND player_id = $player_id AND column_number = $column_number";
            mysqli_query($conn, $update_position_sql);
        }

        // Επιστρέφουμε τα αποτελέσματα του παιχνιδιού
        if (!empty($matching_columns)) {
            echo json_encode([
                "message" => "You moved forward in columns: " . implode(", ", $matching_columns),
                "dice_rolls" => $dice_rolls,
                "valid_combinations" => $valid_combinations
            ]);
            exit;
        } else {
            // Αν δεν ταιριάζει κανένας συνδυασμός, διαγραφή της πρόοδου του παίκτη και αλλαγή σειράς
            mysqli_query($conn, "DELETE FROM temporary_progress WHERE game_session_id = $game_session_id AND player_id = $player_id");

            // Αλλαγή σειράς στον επόμενο παίκτη
            $next_player_result = mysqli_query($conn, "SELECT id FROM players WHERE game_id = (SELECT game_id FROM game_sessions WHERE id = $game_session_id) AND id > $player_id ORDER BY id LIMIT 1");
            $next_player = mysqli_fetch_assoc($next_player_result) ?: mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM players WHERE game_id = (SELECT game_id FROM game_sessions WHERE id = $game_session_id) ORDER BY id LIMIT 1"));

            // Ενημέρωση του active_player_id στον πίνακα game_sessions
            mysqli_query($conn, "UPDATE game_sessions SET active_player_id = {$next_player['id']} WHERE id = $game_session_id");

            echo json_encode([
                "message" => "No matching combinations. You lose all progress. It's now the next player's turn.",
                "dice_rolls" => $dice_rolls,
                "valid_combinations" => $valid_combinations
            ]);
            exit;
        }
    } else {
        // Αν δεν υπάρχουν εγγραφές στον πίνακα temporary_progress, δημιουργούμε την εγγραφή για τον παίκτη
        mysqli_query($conn, "INSERT INTO temporary_progress (game_session_id, player_id, column_number, current_position)
                             SELECT $game_session_id, $player_id, column_number, 1 
                             FROM game_columns WHERE game_session_id = $game_session_id");
        echo json_encode(["message" => "Progress records created successfully."]);
        exit;
    }
}

// Εάν η ενέργεια είναι "stop", κλείνουμε τις στήλες του παίκτη
if ($action === 'stop') {
    // Μεταφορά των στήλων από temporary_progress στο progress
    $sql_move_to_progress = "INSERT INTO progress (game_session_id, player_id, column_number, current_position)
                             SELECT game_session_id, player_id, column_number, current_position 
                             FROM temporary_progress WHERE game_session_id = $game_session_id AND player_id = $player_id";
    mysqli_query($conn, $sql_move_to_progress);

    //Διαγραφή των δεδομένων από το temporary_progress
   $sql_delete_temp_progress = "DELETE FROM temporary_progress WHERE game_session_id = $game_session_id AND player_id = $player_id";
   mysqli_query($conn, $sql_delete_temp_progress);

    // Αλλαγή σειράς στον επόμενο παίκτη
    $next_player_sql = "SELECT id FROM players WHERE game_id = (SELECT id FROM game_sessions WHERE id = $game_session_id) AND id > $player_id ORDER BY id LIMIT 1";
    $next_result = mysqli_query($conn, $next_player_sql);

    if ($next_result) {
        $next_player = mysqli_fetch_assoc($next_result);
    } else {
        // Αν ο τρέχων παίκτης είναι ο τελευταίος, επανεκκινεί τη σειρά με τον πρώτο παίκτη
        $next_player_sql = "SELECT id FROM players WHERE game_id = (SELECT game_id FROM game_sessions WHERE id = $game_session_id) ORDER BY id LIMIT 1";
        $next_result = mysqli_query($conn, $next_player_sql);
        $next_player = mysqli_fetch_assoc($next_result);
    }

    // Ενημέρωση του active_player_id στον πίνακα game_sessions
    mysqli_query($conn, "UPDATE game_sessions SET active_player_id = " . $next_player['id'] . " WHERE id = $game_session_id");

    echo json_encode([
        "message" => "Your progress has been locked. It's now the next player's turn."
    ]);
    exit;
}


$conn->close();
?>
