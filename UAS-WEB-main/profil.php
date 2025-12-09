<?php
// profil.php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user dari database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: Login.php");
    exit();
}

// Ambil notifikasi (untuk navbar)
$notifications_query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
$notifications_stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
$notification_count = mysqli_num_rows($notifications_result);

// Ambil pesan chat yang belum dibaca
$chat_query = "SELECT COUNT(*) as unread FROM chats WHERE receiver_id = ? AND is_read = 0";
$chat_stmt = mysqli_prepare($conn, $chat_query);
mysqli_stmt_bind_param($chat_stmt, "i", $user_id);
mysqli_stmt_execute($chat_stmt);
$chat_result = mysqli_stmt_get_result($chat_stmt);
$chat_data = mysqli_fetch_assoc($chat_result);
$unread_chats = $chat_data['unread'];

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $program_study = mysqli_real_escape_string($conn, $_POST['program_study']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    // Handle password (jika diisi)
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $password_update = ", password = MD5('$password')";
    } else {
        $password_update = "";
    }

    // Handle upload foto profil
    $profile_photo = $user['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $target_dir = "uploads/profile/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Hapus foto lama jika bukan default
        if ($profile_photo && $profile_photo != 'profil.png' && file_exists($profile_photo)) {
            unlink($profile_photo);
        }

        $file_name = time() . '_' . basename($_FILES['profile_photo']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
            $profile_photo = $target_file;
        }
    }

    // Update data di database
    $update_query = "UPDATE users SET 
                    name = ?, 
                    email = ?, 
                    gender = ?, 
                    program_study = ?, 
                    nim = ?, 
                    contact = ?, 
                    profile_photo = ? 
                    $password_update 
                    WHERE id = ?";

    // Buat prepared statement berdasarkan apakah password diupdate atau tidak
    if (!empty($_POST['password'])) {
        $update_query = "UPDATE users SET 
                        name = ?, 
                        email = ?, 
                        gender = ?, 
                        program_study = ?, 
                        nim = ?, 
                        contact = ?, 
                        profile_photo = ?, 
                        password = MD5(?) 
                        WHERE id = ?";

        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param(
            $update_stmt,
            "ssssssssi",
            $name,
            $email,
            $gender,
            $program_study,
            $nim,
            $contact,
            $profile_photo,
            $_POST['password'],
            $user_id
        );
    } else {
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param(
            $update_stmt,
            "sssssssi",
            $name,
            $email,
            $gender,
            $program_study,
            $nim,
            $contact,
            $profile_photo,
            $user_id
        );
    }

    if (mysqli_stmt_execute($update_stmt)) {
        $success = "Profil berhasil diperbarui!";

        // Update session data
        $_SESSION['user_name'] = $name;

        // Refresh data user
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
    } else {
        $error = "Gagal memperbarui profil!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --pink-main: #ff4fa3;
            --pink-light: #ffe6f3;
            --gray-bg: #f7f7fa;
            --white: #fff;
            --text-dark: #333;
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #ffe6f3;
        }

        .navbar {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            background: var(--white);
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            color: var(--pink-main);
        }

        .logo span {
            color: #f7cfe5;
        }

        .nav-icons {
            margin-left: auto;
            display: flex;
            gap: 25px;
            font-size: 21px;
            cursor: pointer;
        }

        .nav-icons span.active {
            color: var(--pink-main);
            font-weight: bold;
        }

        .navbar input {
            margin-left: 25px;
            width: 360px;
            padding: 10px 18px;
            border-radius: 20px;
            border: 1px solid #ddd;
        }

        /* CARD BESAR */
        .profile-wrapper {
            width: 90%;
            margin: 80px auto;
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.07);
        }

        /* JUDUL */
        .profile-wrapper h2 {
            margin: 0 0 25px 0;
            font-weight: 600;
            font-size: 24px;
            color: #ff4fa3;
        }

        /* GRID 2 KOLOM */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px 40px;
            align-items: start;
        }

        /* FOTO PROFIL */
        .profile-photo {
            grid-column: 2;
            grid-row: 1 / span 3;
            justify-self: center;
            text-align: center;
        }

        .profile-photo img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .profile-photo button {
            margin-top: 10px;
            padding: 6px 14px;
            border-radius: 10px;
            background: #eaeaea;
            border: none;
            cursor: pointer;
        }

        /* FORM GROUP */
        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border-radius: 12px;
            border: 2px solid #eee;
            background: #fafafa;
            font-size: 14px;
            transition: .2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #ff4fa3;
            outline: none;
        }

        /* BUTTON SIMPAN */
        .btn-save {
            margin-top: 35px;
            padding: 12px 28px;
            background: #ff4fa3;
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-save:hover {
            background: #f771b2;
        }

        .notif-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ff4fa3;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 50%;
        }

        .notif-dropdown {
            position: absolute;
            top: 35px;
            right: 0;
            width: 220px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            z-index: 100;
        }

        .notif-item {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            cursor: pointer;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item:hover {
            background-color: #ffe7f1;
        }

        .chat-icon,
        .notif-icon {
            position: relative;
            cursor: pointer;
        }

        .chat-badge,
        .notif-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ff4fa3;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 50%;
        }

        /* Pesan sukses/error */
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

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="logo">Find<span>Me</span></div>
        <input type="text" placeholder="Cari informasi...">
        <div class="nav-icons" id="navMenu">
            <span id="homeBtn"><i class="fa-solid fa-house"></i></span>
            <span id="chatBtn" class="chat-icon">
                <i class="fa-regular fa-comment"></i>
                <?php if ($unread_chats > 0): ?>
                    <span class="chat-badge" id="chatBadge"><?php echo $unread_chats; ?></span>
                <?php endif; ?>
            </span>

            <span id="notifBtn" class="notif-icon">
                <i class="fa-solid fa-bell"></i>
                <?php if ($notification_count > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </span>

            <!-- Dropdown Notifikasi -->
            <div class="notif-dropdown" id="notifDropdown">
                <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                    <?php mysqli_data_seek($notifications_result, 0); ?>
                    <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                        <div class="notif-item"><?php echo htmlspecialchars($notification['message']); ?></div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notif-item">Tidak ada notifikasi</div>
                <?php endif; ?>
            </div>
            <span id="profileBtn"><i class="fa-solid fa-user"></i></span>
        </div>
    </nav>

    <div class="profile-wrapper">
        <h2>Edit Profil</h2>

        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-grid">

                <!-- FOTO PROFIL -->
                <div class="profile-photo">
                    <img id="photoPreview" src="<?php echo htmlspecialchars($user['profile_photo'] ?: 'profil.png'); ?>">
                    <br>
                    <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none" onchange="previewImage(this)">
                    <button type="button" onclick="document.getElementById('photoInput').click()">Ganti Foto</button>
                </div>

                <!-- FORM KIRI -->
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="name" id="nama" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="gender" id="gender" required>
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="Laki-laki" <?php echo ($user['gender'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo ($user['gender'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password (kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" name="password" id="password" placeholder="Password baru">
                </div>

                <!-- FORM KANAN -->
                <div class="form-group">
                    <label>Prodi</label>
                    <input type="text" name="program_study" id="prodi" value="<?php echo htmlspecialchars($user['program_study'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim" id="nim" value="<?php echo htmlspecialchars($user['nim'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Kontak</label>
                    <input type="text" name="contact" id="kontak" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>">
                </div>

            </div>
            
            <button type="submit" name="update_profile" class="btn-save">SIMPAN</button>
        </form>
    </div>

    <script>
        // NAVBAR FUNCTIONS
        document.getElementById("homeBtn")?.addEventListener("click", () => {
            window.location.href = "home.php";
        });

        document.getElementById("chatBtn")?.addEventListener("click", () => {
            window.location.href = "chat.php";
        });

        const notifBtn = document.getElementById("notifBtn");
        const notifDropdown = document.getElementById("notifDropdown");

        notifBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            if (notifDropdown.style.display === "flex") {
                notifDropdown.style.display = "none";
            } else {
                notifDropdown.style.display = "flex";
                notifDropdown.style.flexDirection = "column";
            }
        });

        document.getElementById("profileBtn").addEventListener("click", function() {
            window.location.href = "profil.php";
        });

        // Tutup dropdown notifikasi saat klik di luar
        document.addEventListener("click", (e) => {
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.style.display = "none";
            }
        });

        // Preview image sebelum upload
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>

</html>