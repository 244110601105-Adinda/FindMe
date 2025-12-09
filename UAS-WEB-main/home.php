<?php
// home.php - DENGAN UPLOAD FOTO
require_once 'config.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// 2. AMBIL DATA USER
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    session_destroy();
    header("Location: Login.php");
    exit();
}

// 3. AMBIL POSTINGAN
$filter = $_GET['filter'] ?? 'semua';
$where = "";
if ($filter == 'kehilangan') $where = "WHERE post_type = 'kehilangan'";
if ($filter == 'penemuan') $where = "WHERE post_type = 'penemuan'";

$posts_query = "SELECT posts.*, users.name, users.program_study, users.profile_photo as user_photo 
                FROM posts 
                JOIN users ON posts.user_id = users.id 
                $where 
                ORDER BY created_at DESC 
                LIMIT 10";
$posts_result = mysqli_query($conn, $posts_query);

// 4. PROSES POSTING BARU DENGAN UPLOAD FOTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    $content = mysqli_real_escape_string($conn, $_POST['post_text']);
    $post_type = mysqli_real_escape_string($conn, $_POST['post_type']);
    $post_date = date('Y-m-d'); // Tanggal hari ini
    
    // Handle upload foto
    $image_path = '';
    $upload_error = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Buat folder uploads jika belum ada
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $image_name;
        
        // Validasi tipe file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validasi ukuran file (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['image']['size'] > $max_size) {
            $upload_error = "Ukuran file terlalu besar. Maksimal 5MB.";
        } elseif (!in_array($file_extension, $allowed_types)) {
            $upload_error = "Hanya file JPG, JPEG, PNG, GIF yang diizinkan.";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            $upload_error = "Gagal mengupload gambar.";
        }
    }
    
    // Jika tidak ada error upload, simpan ke database
    if (empty($upload_error)) {
        $insert_query = "INSERT INTO posts (user_id, content, post_type, image_path, post_date) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "issss", $user_id, $content, $post_type, $image_path, $post_date);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $_SESSION['success'] = "Postingan berhasil dikirim!";
            header("Location: home.php");
            exit();
        } else {
            $error = "Gagal menyimpan postingan ke database.";
        }
    } else {
        $error = $upload_error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindMe | Home</title>
    
    <!-- CSS EXTERNAL -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS INTERNAL -->
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: #f7f7fa; }
    
    /* NAVBAR */
    .navbar { background: white; padding: 15px 25px; display: flex; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .logo { font-size: 24px; font-weight: bold; color: #ff4fa3; }
    .logo span { color: #f7cfe5; }
    .nav-icons { margin-left: auto; display: flex; gap: 20px; align-items: center; }
    .nav-icons i { font-size: 20px; cursor: pointer; transition: all 0.3s; }
    .nav-icons i:hover { transform: scale(1.1); }
    
    /* TOMBOL LOGOUT */
    .logout-btn-nav { 
        background: transparent; 
        border: none; 
        color: #ff4fa3; 
        font-size: 20px; 
        cursor: pointer; 
        transition: all 0.3s;
    }
    
    /* CONTAINER */
    .container { display: flex; padding: 20px; gap: 20px; max-width: 1400px; margin: 0 auto; }
    
    /* SIDEBAR */
    .sidebar { width: 25%; }
    .profile-box { background: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .profile-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #ff4fa3; }
    
    /* TOMBOL LOGOUT DI PROFILE BOX */
    .profile-box .logout-btn { 
        margin-top: 15px; 
        background: transparent; 
        border: 2px solid #ff4fa3; 
        color: #ff4fa3; 
        padding: 8px 16px; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 500;
        transition: all 0.3s;
    }
    .profile-box .logout-btn:hover { 
        background: #ff4fa3; 
        color: white;
    }
    
    /* MAIN */
    .main { width: 50%; }
    .post-form { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .post-form textarea { width: 100%; height: 100px; padding: 12px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; resize: none; font-family: 'Poppins', sans-serif; }
    .post-form textarea:focus { outline: none; border-color: #ff4fa3; }
    .post-actions { display: flex; justify-content: space-between; margin-top: 10px; }
    .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; font-weight: 500; transition: all 0.3s; }
    .btn-primary { background: #ff4fa3; color: white; }
    .btn-primary:hover { background: #e83e8c; transform: translateY(-2px); }
    
    /* PREVIEW AREA */
    .preview-area { margin-bottom: 10px; }
    .preview-image { max-width: 150px; max-height: 150px; border-radius: 8px; margin-top: 10px; border: 2px solid #ffe6f3; }
    
    /* POSTS */
    .post { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .post-header { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
    .post-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #ff4fa3; }
    
    /* POST IMAGE */
    .post-image { max-width: 100%; max-height: 300px; border-radius: 8px; margin-top: 10px; border: 2px solid #ffe6f3; }
    
    /* TABS */
    .tabs { display: flex; gap: 20px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .tabs div { cursor: pointer; padding-bottom: 5px; transition: color 0.3s; }
    .tabs div:hover { color: #ff4fa3; }
    .tabs .active { border-bottom: 3px solid #ff4fa3; color: #ff4fa3; }
    
    /* RESPONSIVE */
    @media (max-width: 900px) {
        .container { flex-direction: column; }
        .sidebar, .main { width: 100%; }
        .nav-icons { gap: 15px; }
    }
    
    /* MESSAGE STYLES */
    .message { 
        padding: 12px 20px; 
        border-radius: 8px; 
        margin: 10px auto; 
        max-width: 1400px; 
        text-align: center;
        font-weight: 500;
    }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">Find<span>Me</span></div>
    <div class="nav-icons">
        <i class="fa-solid fa-house" onclick="window.location.href='home.php'" title="Home"></i>
        <i class="fa-regular fa-comment" onclick="window.location.href='chat.php'" title="Chat"></i>
        <i class="fa-solid fa-bell" title="Notifikasi"></i>
        <i class="fa-solid fa-user" onclick="window.location.href='profil.php'" title="Profil"></i>
        
        <!-- TOMBOL LOGOUT DI NAVBAR -->
        <button class="logout-btn-nav" onclick="confirmLogout()" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</nav>

<!-- MESSAGES -->
<?php if (isset($_SESSION['success'])): ?>
<div class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="message error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="container">
    
    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-box">
            <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'assets/default-profile.png'); ?>" 
                 class="profile-img"
                 onerror="this.src='assets/default-profile.png'">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['program_study'] ?? ''); ?></p>
            
            <button class="btn" onclick="window.location.href='profil.php'" style="margin-top: 10px; background: #f0f0f0;">
                <i class="fa-solid fa-user-pen"></i> Edit Profil
            </button>
            
            <!-- TOMBOL LOGOUT -->
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <!-- POST FORM DENGAN UPLOAD FOTO -->
        <form method="POST" action="" enctype="multipart/form-data" class="post-form">
            <!-- Preview Area -->
            <div class="preview-area" id="previewArea"></div>
            
            <textarea name="post_text" placeholder="Apa yang hilang/temukan hari ini?" required></textarea>
            
            <div style="display: flex; gap: 20px; margin-bottom: 10px;">
                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="radio" name="post_type" value="kehilangan" checked> 
                    <span>Kehilangan</span>
                </label>
                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="radio" name="post_type" value="penemuan"> 
                    <span>Penemuan</span>
                </label>
            </div>
            
            <div class="post-actions">
                <div>
                    <!-- UPLOAD FOTO -->
                    <button type="button" class="btn" onclick="document.getElementById('imageInput').click()" style="background: #f0f0f0;">
                        <i class="fa-solid fa-image"></i> Pilih Foto
                    </button>
                    <input type="file" id="imageInput" name="image" accept="image/*" style="display:none" onchange="previewImage(this)">
                </div>
                <button type="submit" name="submit_post" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Kirim
                </button>
            </div>
        </form>
        
        <!-- TABS -->
        <div class="tabs">
            <div onclick="window.location.href='home.php'" class="<?php echo $filter == 'semua' ? 'active' : ''; ?>">
                Semua
            </div>
            <div onclick="window.location.href='home.php?filter=kehilangan'" class="<?php echo $filter == 'kehilangan' ? 'active' : ''; ?>">
                Kehilangan
            </div>
            <div onclick="window.location.href='home.php?filter=penemuan'" class="<?php echo $filter == 'penemuan' ? 'active' : ''; ?>">
                Penemuan
            </div>
        </div>
        
        <!-- POSTS -->
        <div class="posts">
            <?php if (mysqli_num_rows($posts_result) > 0): ?>
                <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['user_photo'] ?? 'assets/default-profile.png'); ?>" 
                             class="post-avatar"
                             onerror="this.src='assets/default-profile.png'">
                        <div>
                            <strong><?php echo htmlspecialchars($post['name']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($post['program_study']); ?> • 
                                   <?php echo ucfirst($post['post_type']); ?></small>
                        </div>
                    </div>
                    <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    
                    <!-- TAMPILKAN FOTO JIKA ADA -->
                    <?php if (!empty($post['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         class="post-image"
                         onerror="this.style.display='none'">
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; display: flex; gap: 15px;">
                        <button onclick="likePost(this)" class="btn" style="background: #f0f0f0;">
                            <i class="fa-regular fa-heart"></i> Suka
                        </button>
                        <button onclick="openChat(<?php echo $post['user_id']; ?>)" class="btn" style="background: #f0f0f0;">
                            <i class="fa-regular fa-comment"></i> Chat
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="post">
                    <div style="text-align: center; padding: 30px; color: #888;">
                        <i class="fa-regular fa-folder-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>Belum ada postingan</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- RIGHT SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-box">
            <h4><i class="fa-solid fa-flag"></i> Laporkan Konten</h4>
            <p style="margin: 10px 0; font-size: 14px; color: #666;">Laporkan postingan yang tidak pantas</p>
            <button class="btn btn-primary" onclick="window.location.href='ajukanLaporan.php'">
                <i class="fa-solid fa-triangle-exclamation"></i> Ajukan Laporan
            </button>
        </div>
        
        <div class="profile-box">
            <h4><i class="fa-solid fa-fire"></i> Pencarian Populer</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px;">
                <span style="background: #ffe6f3; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;" 
                      onclick="searchTag('STNK')">STNK</span>
                <span style="background: #ffe6f3; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;" 
                      onclick="searchTag('DOMPET')">DOMPET</span>
                <span style="background: #ffe6f3; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;" 
                      onclick="searchTag('HP')">HP</span>
            </div>
        </div>
    </aside>
</div>

<!-- JAVASCRIPT -->
<script>
function likePost(btn) {
    const icon = btn.querySelector('i');
    if (icon.classList.contains('fa-regular')) {
        icon.classList.remove('fa-regular');
        icon.classList.add('fa-solid');
        icon.style.color = '#ff4fa3';
        btn.innerHTML = '<i class="fa-solid fa-heart"></i> Disukai';
        btn.style.background = '#ffe6f3';
    } else {
        icon.classList.remove('fa-solid');
        icon.classList.add('fa-regular');
        icon.style.color = '';
        btn.innerHTML = '<i class="fa-regular fa-heart"></i> Suka';
        btn.style.background = '#f0f0f0';
    }
}

function openChat(userId) {
    window.location.href = 'chat.php?user_id=' + userId;
}

function searchTag(tag) {
    alert('Mencari: ' + tag);
}

// PREVIEW IMAGE SEBELUM UPLOAD
function previewImage(input) {
    const previewArea = document.getElementById('previewArea');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validasi ukuran (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar. Maksimal 5MB.');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Hanya file gambar (JPG, PNG, GIF) yang diizinkan.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewArea.innerHTML = '';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-image';
            
            // Tambah tombol hapus
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '× Hapus';
            removeBtn.style.cssText = `
                margin-left: 10px;
                background: #ff4fa3;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 12px;
            `;
            removeBtn.onclick = function() {
                previewArea.innerHTML = '';
                input.value = '';
            };
            
            const wrapper = document.createElement('div');
            wrapper.style.display = 'flex';
            wrapper.style.alignItems = 'center';
            wrapper.style.marginTop = '10px';
            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            
            previewArea.appendChild(wrapper);
        }
        
        reader.readAsDataURL(file);
    }
}

// LOGOUT
function confirmLogout() {
    if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = 'logout.php';
    }
}

// Auto-hide messages
setTimeout(() => {
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(() => {
            if (msg.parentNode) {
                msg.parentNode.removeChild(msg);
            }
        }, 500);
    });
}, 5000);

// Enter untuk submit form
document.querySelector('textarea').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault();
        this.form.submit();
    }
});
</script>

</body>
</html>