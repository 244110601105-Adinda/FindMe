<?php
// ajukanLaporan.php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Proses form laporan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reported_name = mysqli_real_escape_string($conn, $_POST['reported_name']);
    $program_study = mysqli_real_escape_string($conn, $_POST['program_study']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Insert laporan ke database
    $query = "INSERT INTO reports (reporter_id, reported_user, reported_program_study, reason, description) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $reported_name, $program_study, $reason, $description);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Laporan berhasil dikirim!";
        
        // Tambahkan notifikasi untuk admin
        $admin_query = "SELECT id FROM users WHERE role = 'admin'";
        $admin_result = mysqli_query($conn, $admin_query);
        
        while ($admin = mysqli_fetch_assoc($admin_result)) {
            $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, 'Ada laporan baru dari pengguna')";
            $notif_stmt = mysqli_prepare($conn, $notif_query);
            mysqli_stmt_bind_param($notif_stmt, "i", $admin['id']);
            mysqli_stmt_execute($notif_stmt);
        }
    } else {
        $error = "Gagal mengirim laporan!";
    }
}

// Ambil data user untuk navbar
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Ambil notifikasi
$notifications_query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
$notifications_stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
$notification_count = mysqli_num_rows($notifications_result);

// Ambil chat yang belum dibaca
$chat_query = "SELECT COUNT(*) as unread FROM chats WHERE receiver_id = ? AND is_read = 0";
$chat_stmt = mysqli_prepare($conn, $chat_query);
mysqli_stmt_bind_param($chat_stmt, "i", $user_id);
mysqli_stmt_execute($chat_stmt);
$chat_result = mysqli_stmt_get_result($chat_stmt);
$chat_data = mysqli_fetch_assoc($chat_result);
$unread_chats = $chat_data['unread'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajukan Laporan</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    /* CSS dari ajukanLaporan.html tetap sama */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f2f2f2;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 500px;
        margin: 50px auto;
        background-color: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    input[type="text"],
    select,
    textarea {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #ff4fa3;
        outline: none;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    .btn-group {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .btn {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .back-btn {
        background-color: #ccc;
        color: #333;
    }

    .back-btn:hover {
        background-color: #aaa;
    }

    .submit-btn {
        background-color: #ff4fa3;
        color: white;
    }

    .submit-btn:hover {
        background-color: #f96eb1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .error {
        border-color: #ff4fa3 !important;
    }
    
    .message {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .error-msg {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
</head>
<body>

<div class="container">
    <h2>Formulir Pengajuan Laporan Tidak Pantas</h2>
    
    <?php if (isset($success)): ?>
    <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="message error-msg"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <label for="reported-name">Nama Akun yang Dilaporkan</label>
        <input type="text" id="reported-name" name="reported_name" placeholder="Nama akun" required>

        <label for="prodi">Prodi</label>
        <input type="text" id="prodi" name="program_study" placeholder="Program studi akun" required>

        <label for="reason">Alasan Pelaporan</label>
        <select id="reason" name="reason" required>
            <option value="" disabled selected>Pilih alasan</option>
            <option value="sara">Mengandung SARA</option>
            <option value="kata-tidak-pantas">Kata-kata Tidak Pantas</option>
            <option value="ujaran-kebencian">Ujaran Kebencian</option>
            <option value="hoaks">Informasi Hoaks</option>
        </select>

        <label for="description">Tambahkan Deskripsi</label>
        <textarea id="description" name="description" placeholder="Jelaskan secara detail..." required></textarea>

        <div class="btn-group">
            <button type="button" class="btn back-btn" onclick="goBack()">← Kembali</button>
            <button type="submit" class="btn submit-btn">Kirim Laporan</button>
        </div>
    </form>
</div>

<script>
    function goBack() {
        history.back();
    }

    // Validasi form
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const reportedName = document.getElementById('reported-name');
        const prodi = document.getElementById('prodi');
        const reason = document.getElementById('reason');
        const description = document.getElementById('description');

        [reportedName, prodi, reason, description].forEach(field => {
            field.classList.remove('error');
        });

        let valid = true;
        if(reportedName.value.trim() === '') { reportedName.classList.add('error'); valid=false; }
        if(prodi.value.trim() === '') { prodi.classList.add('error'); valid=false; }
        if(reason.value === '') { reason.classList.add('error'); valid=false; }
        if(description.value.trim() === '') { description.classList.add('error'); valid=false; }

        if(!valid) {
            e.preventDefault();
            alert('Mohon lengkapi semua field sebelum mengirim laporan.');
        }
    });
</script>

</body>
</html>