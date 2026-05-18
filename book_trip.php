<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit;
}

require 'connect.php';

$student_id = $_SESSION['user_id'];
$trip_id    = (int)($_POST['trip_id'] ?? 0);

if (!$trip_id) {
    header('Location: readrecords.php?tab=trips');
    exit;
}


$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("SELECT available_seats FROM trip WHERE trip_id = ? FOR UPDATE");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (!$trip || $trip['available_seats'] <= 0) {
        throw new Exception('No seats available for this trip.');
    }


    $stmt = $pdo->prepare("
        INSERT INTO booking (student_id, trip_id, timestamp, no_show_flag)
        VALUES (?, ?, NOW(), 0)
    ");
    $stmt->execute([$student_id, $trip_id]);


    $stmt = $pdo->prepare("
        UPDATE trip SET available_seats = available_seats - 1
        WHERE trip_id = ?
    ");
    $stmt->execute([$trip_id]);

    $pdo->commit();

    $_SESSION['booking_success'] = 'Trip booked successfully!';
    header('Location: readrecords.php?tab=trips');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['booking_error'] = $e->getMessage();
    header('Location: readrecords.php?tab=trips');
    exit;
}
?>