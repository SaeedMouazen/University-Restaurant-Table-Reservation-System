<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard - University Restaurant</title>
    <link rel="stylesheet" href="style.css">
    <p><a href="logout.php" class="logout-btn">Logout</a></p>

</head>
<body>
<div class="page-wrapper">
<div class="page-box"> 

<?php
require 'config.php';
if (!is_logged_in() || !is_manager()) {
    header("Location: login.php");
    exit;
}

$message = '';

if (isset($_GET['cancel_id'])) {
    $cid = $_GET['cancel_id'];
    $stmt = $pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id = ?");
    $stmt->execute([$cid]);
    $message = "Reservation cancelled.";
}

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $stmt = $pdo->prepare("UPDATE settings SET 
        breakfast_start=?, breakfast_end=?, 
        lunch_start=?, lunch_end=?, 
        dinner_start=?, dinner_end=?, 
        total_tables=?, booking_limit_hour=?
        WHERE id=1");
    $stmt->execute([
        $_POST['breakfast_start'], $_POST['breakfast_end'],
        $_POST['lunch_start'], $_POST['lunch_end'],
        $_POST['dinner_start'], $_POST['dinner_end'],
        $_POST['total_tables'], $_POST['booking_limit_hour']
    ]);
    $message = "Settings updated.";
}

$today = date('Y-m-d');
$date = $_GET['date'] ?? $today;

$resStmt = $pdo->prepare("SELECT r.*, u.name, u.university_id, t.table_number 
                          FROM reservations r
                          JOIN users u ON r.student_id = u.id
                          JOIN tables_restaurant t ON r.table_id = t.id
                          WHERE r.date = ?
                          ORDER BY r.time_slot");
$resStmt->execute([$date]);
$reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard - University Restaurant</title>
</head>
<body>
<div class="page-wrapper">
<div class="page-box">
<div class="admin-main-box">
<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Manager)</h2>


<p style="color:blue;"><?php echo htmlspecialchars($message); ?></p>

<h3>View Bookings</h3>
<form method="get">
    <label>Date:</label>
    <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
    <button type="submit">Show</button>
</form>

<table border="1" cellpadding="5">
<tr>
    <th>Student</th>
    <th>Univ ID</th>
    <th>Table</th>
    <th>Date</th>
    <th>Meal</th>
    <th>Time</th>
    <th>Status</th>
    <th>Action</th>
</tr>
<?php foreach ($reservations as $r): ?>
<tr>
    <td><?php echo htmlspecialchars($r['name']); ?></td>
    <td><?php echo htmlspecialchars($r['university_id']); ?></td>
    <td><?php echo htmlspecialchars($r['table_number']); ?></td>
    <td><?php echo htmlspecialchars($r['date']); ?></td>
    <td><?php echo htmlspecialchars($r['meal']); ?></td>
    <td><?php echo htmlspecialchars($r['time_slot']); ?></td>
    <td><?php echo htmlspecialchars($r['status']); ?></td>
    <td>
        <?php if ($r['status'] === 'active'): ?>
            <a href="?date=<?php echo htmlspecialchars($date); ?>&cancel_id=<?php echo $r['id']; ?>">Cancel</a>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<h3>Restaurant Settings</h3>
<form method="post">
    <input type="hidden" name="update_settings" value="1">
    <p>Breakfast: 
        <input type="time" name="breakfast_start" value="<?php echo $settings['breakfast_start']; ?>"> -
        <input type="time" name="breakfast_end" value="<?php echo $settings['breakfast_end']; ?>">
    </p>
    <p>Lunch: 
        <input type="time" name="lunch_start" value="<?php echo $settings['lunch_start']; ?>"> -
        <input type="time" name="lunch_end" value="<?php echo $settings['lunch_end']; ?>">
    </p>
    <p>Dinner: 
        <input type="time" name="dinner_start" value="<?php echo $settings['dinner_start']; ?>"> -
        <input type="time" name="dinner_end" value="<?php echo $settings['dinner_end']; ?>">
    </p>
    <p>Total tables: <input type="number" name="total_tables" value="<?php echo $settings['total_tables']; ?>"></p>
    <p>Booking limit hour (for tomorrow): 
        <input type="time" name="booking_limit_hour" value="<?php echo $settings['booking_limit_hour']; ?>">
    </p>
    <button type="submit">Save</button>
</form>
</div>
</div>
</div>
</div>
</div>
<p><a href="" class="empty"></a></p>
</body>
</html>
