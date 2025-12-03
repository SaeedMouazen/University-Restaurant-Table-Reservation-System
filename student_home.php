<?php
require 'config.php';
if (!is_logged_in() || !is_student()) {
    header("Location: login.php");
    exit;
}

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$message = '';

function generate_slots($start, $end, $intervalMinutes = 30) {
    $slots = [];
    $cur = strtotime($start);
    $endT = strtotime($end);
    while ($cur < $endT) {
        $slots[] = date('H:i', $cur);
        $cur = strtotime("+$intervalMinutes minutes", $cur);
    }
    return $slots;
}

// Read filters
$meal   = $_GET['meal']   ?? 'breakfast';
$date   = $_GET['date']   ?? date('Y-m-d');
$action = $_GET['action'] ?? 'slots'; // default: show slots

$today       = date('Y-m-d');
$tomorrow    = date('Y-m-d', strtotime('+1 day'));
$currentTime = date('H:i:s');

// Domain rule: only today or tomorrow
if ($date > $tomorrow || $date < $today) {
    $message = "You can only book for today or tomorrow.";
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['time_slot']) && empty($message)) {
    $time_slot = $_POST['time_slot'];

    if ($date === $tomorrow && $currentTime > $settings['booking_limit_hour']) {
        $message = "Booking for tomorrow is closed (after 10pm).";
    } else {
        // Prevent more than one active reservation per student per date+meal
        $checkStudent = $pdo->prepare(
            "SELECT COUNT(*) FROM reservations 
             WHERE student_id = ? AND date = ? AND meal = ? AND status = 'active'"
        );
        $checkStudent->execute([$_SESSION['user_id'], $date, $meal]);

        if ($checkStudent->fetchColumn() > 0) {
            $message = "You already have a reservation for this meal on this date.";
        } else {
            // Find a free table for that slot
            $tablesStmt = $pdo->prepare(
                "SELECT id FROM tables_restaurant WHERE status = 'active'"
            );
            $tablesStmt->execute();
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            if ($tables) {
                $table_id = null;
                foreach ($tables as $t) {
                    $check = $pdo->prepare(
                        "SELECT COUNT(*) FROM reservations 
                         WHERE table_id = ? AND date = ? AND meal = ? AND time_slot = ? AND status='active'"
                    );
                    $check->execute([$t, $date, $meal, $time_slot]);
                    if ($check->fetchColumn() == 0) {
                        $table_id = $t;
                        break;
                    }
                }

                if ($table_id) {
                    $ins = $pdo->prepare(
                        "INSERT INTO reservations (student_id, table_id, date, meal, time_slot) 
                         VALUES (?,?,?,?,?)"
                    );
                    $ins->execute([$_SESSION['user_id'], $table_id, $date, $meal, $time_slot]);
                    $message = "Reservation confirmed at $time_slot ($meal).";
                } else {
                    $message = "No tables available for this time slot.";
                }
            }
        }
    }
}

// Generate slots only for Show Slots
$slots = [];
$bookedCounts = [];
$totalActiveTables = 0;

if ($action === 'slots') {
    switch ($meal) {
        case 'lunch':
            $slots = generate_slots($settings['lunch_start'], $settings['lunch_end']);
            break;
        case 'dinner':
            $slots = generate_slots($settings['dinner_start'], $settings['dinner_end']);
            break;
        default:
            $slots = generate_slots($settings['breakfast_start'], $settings['breakfast_end']);
            $meal  = 'breakfast';
    }

    if ($slots) {
        $totalActiveTables = $pdo->query(
            "SELECT COUNT(*) FROM tables_restaurant WHERE status='active'"
        )->fetchColumn();

        foreach ($slots as $s) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM reservations 
                 WHERE date = ? AND meal = ? AND time_slot = ? AND status='active'"
            );
            $stmt->execute([$date, $meal, $s]);
            $bookedCounts[$s] = $stmt->fetchColumn();
        }
    }
}

// Reservations for selected date (all meals, so student can see what they booked)
$my = $pdo->prepare(
    "SELECT r.*, t.table_number 
     FROM reservations r 
     JOIN tables_restaurant t ON r.table_id = t.id
     WHERE r.student_id = ? AND r.date = ?
     ORDER BY r.meal, r.time_slot"
);
$my->execute([$_SESSION['user_id'], $date]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Home - University Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<p><a href="logout.php" class="logout-btn">Logout</a></p>
<div class="page-wrapper">
    <div class="page-box">

        <div class="student-main-box">

            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Student)</h2>
            

            <h3>Book a Table / View Reservations</h3>
            <form method="get" action="" class="filter-form">
                <label>Date:</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>
                <label>Meal:</label>
                <select name="meal">
                    <option value="breakfast" <?php if($meal=='breakfast') echo 'selected'; ?>>Breakfast</option>
                    <option value="lunch" <?php if($meal=='lunch') echo 'selected'; ?>>Lunch</option>
                    <option value="dinner" <?php if($meal=='dinner') echo 'selected'; ?>>Dinner</option>
                </select>

                <button type="submit" name="action" value="slots">Show Slots</button>
                <button type="submit" name="action" value="reservations">Show Reservations</button>
            </form>

            <p style="color:blue;"><?php echo htmlspecialchars($message); ?></p>

            <?php if ($action === 'slots'): ?>
                <form method="post">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                    <table border="1" cellpadding="5">
                        <tr><th>Time Slot</th><th>Availability</th><th>Action</th></tr>
                        <?php foreach ($slots as $s): 
                            $booked    = $bookedCounts[$s] ?? 0;
                            $available = $totalActiveTables - $booked;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s); ?></td>
                            <td><?php echo $available > 0 ? "$available tables available" : "Full"; ?></td>
                            <td>
                                <?php if ($available > 0 && empty($message)): ?>
                                    <button type="submit" name="time_slot" value="<?php echo htmlspecialchars($s); ?>">Book</button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </form>
            <?php endif; ?>

            <?php if ($action === 'reservations'): ?>
                <h3>My Reservations on <?php echo htmlspecialchars($date); ?></h3>
                <table border="1" cellpadding="5">
                    <tr><th>Meal</th><th>Time</th><th>Table</th><th>Status</th><th>Action</th></tr>
                    <?php foreach ($my as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['meal']); ?></td>
                        <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
                        <td><?php echo htmlspecialchars($row['table_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <a href="student_cancel.php?id=<?php echo $row['id']; ?>">Cancel</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

        </div><!-- /student-main-box -->

    </div>
</div>
<p><a href="" class="empty"></a></p>
</body>
</html>
