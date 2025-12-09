<?php
// update_status.php
require_once 'config.php';

// Cek apakah admin
if (!isset($_SESSION['user_id'])) {
    header("Location: LoginAdmin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_id']) && isset($_POST['status'])) {
    $report_id = intval($_POST['report_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE reports SET status = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $status, $report_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Tambahkan notifikasi ke pelapor
        $notif_query = "INSERT INTO notifications (user_id, message) 
                       SELECT reporter_id, 'Laporan Anda telah diupdate statusnya' 
                       FROM reports WHERE id = ?";
        $notif_stmt = mysqli_prepare($conn, $notif_query);
        mysqli_stmt_bind_param($notif_stmt, "i", $report_id);
        mysqli_stmt_execute($notif_stmt);
    }
}

header("Location: daftar.php");
exit();
?>