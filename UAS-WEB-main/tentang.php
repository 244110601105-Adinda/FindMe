<?php
// tentang.php
require_once 'config.php';

// Cek jika sudah login
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role_query = "SELECT role FROM users WHERE id = ?";
    $role_stmt = mysqli_prepare($conn, $role_query);
    mysqli_stmt_bind_param($role_stmt, "i", $user_id);
    mysqli_stmt_execute($role_stmt);
    $role_result = mysqli_stmt_get_result($role_stmt);
    $role_data = mysqli_fetch_assoc($role_result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FindMe | Tentang</title>

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        background: #f5f5f5;
        color: #222;
        line-height: 1.6;
    }

    /* NAVBAR */
    nav {
        padding: 10px 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .logo {
        font-size: 24px;
        font-weight: 700;
        color: #ff4fa3;
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 30px;
    }

    nav ul li a {
        text-decoration: none;
        color: #444;
        font-weight: 500;
        transition: 0.3s;
    }

    nav ul li a:hover {
        color: #ff4fa3;
    }

    /* MAIN CONTAINER */
    .container {
        max-width: 900px;
        margin: 50px auto;
        padding: 0 20px;
    }
    /* CARD STYLES */
    .card, .team-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        padding: 30px 40px; /* lebih luas kiri-kanan */
        margin-bottom: 40px;
        width: 100%;       /* memanjang full container */
        box-sizing: border-box;
    }

    /* Judul card rata kiri */
    .card h1, .card h2, .team-card h3 {
        color: #ff4fa3;
        margin-bottom: 20px;
        text-align: left; /* rata kiri */
    }

    .card p, .team-card p {
        color: #555;
        font-size: 16px;
        margin-bottom: 15px;
        text-align: justify;
    }

    .team-card ul {
        list-style: none;
        padding-left: 0;
    }

    .team-card ul li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 16px;
    }

    .team-card ul li:last-child {
        border-bottom: none;
    }
    

    /* FOOTER */
    footer {
        text-align: center;
        padding: 30px 0;
        color: #777;
        font-size: 14px;
        border-top: 1px solid #eee;
        margin-top: 50px;
    }

</style>
</head>

<body>

<!-- NAVBAR -->
<nav>
    <div class="logo">FindMe</div>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="tentang.php">Tentang</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo ($role_data['role'] == 'admin') ? 'homeAdmin.php' : 'home.php'; ?>">Dashboard</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="container">

    <!-- Deskripsi Layanan -->
    <div class="card">
        <h1>Tentang FindMe</h1>
        <p>FindMe adalah platform layanan pelaporan barang hilang dan temuan yang mudah, cepat, dan aman. 
        Dengan sistem terorganisir, pengguna dapat melaporkan barang yang hilang atau ditemukan secara real-time, 
        memudahkan proses pencarian dan pengembalian barang penting Anda.</p>
    </div>

    <!-- Visi & Misi -->
    <div class="card">
        <h2>Visi & Misi</h2>
        <p><b>Visi:</b> Menjadi platform Lost and Found digital terpercaya yang memudahkan setiap orang menemukan kembali barang penting mereka.</p>
        <p><b>Misi:</b></p>
        <ul style="text-align: left; padding-left: 20px;">
            <li>Menyediakan sistem pelaporan barang hilang dan temuan yang mudah digunakan.</li>
            <li>Meningkatkan efektivitas pengembalian barang dengan data yang terorganisir.</li>
            <li>Memberikan pengalaman pengguna yang aman dan transparan.</li>
        </ul>
    </div>

    <!-- Tim Pengembang Card -->
<div class="team-card">
    <h3>Tentang Pengembang</h3>
    <p>
        Aplikasi <b>FindMe</b> merupakan platform layanan pelaporan barang hilang dan temuan, dikembangkan oleh mahasiswa <b>UIN Saizu</b> yang fokus pada inovasi digital di lingkungan kampus. 
        Aplikasi ini dibuat untuk memudahkan mahasiswa dan civitas kampus dalam melaporkan serta menemukan kembali barang-barang penting yang hilang.
    </p>
    <p><b>Dikembangkan oleh:</b></p>
    <ul>
        <li>Adinda Wakhidatul Mar'ah</li>
        <li>Nihayatuzzain Abdillah</li>
        <li>Muhammad Mahrus</li>
        <li>Sasi Amalia Salsabila</li>
    </ul>
</div>

</div>

<footer>
    © 2025 FindMe — All rights reserved
</footer>

</body>
</html>