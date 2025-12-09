<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role_query = "SELECT role FROM users WHERE id = ?";
    $role_stmt = mysqli_prepare($conn, $role_query);
    mysqli_stmt_bind_param($role_stmt, "i", $user_id);
    mysqli_stmt_execute($role_stmt);
    $role_result = mysqli_stmt_get_result($role_stmt);
    $role_data = mysqli_fetch_assoc($role_result);
    
    if ($role_data['role'] == 'admin') {
        header("Location: homeAdmin.php");
    } else {
        header("Location: home.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FindMe | Lost and Found</title>

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: #ffffff;
        color: #222;
        line-height: 1.6;
    }

    /* NAVBAR */
    nav {
        padding: 20px 50px;
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

    /* MAIN SECTION */
    .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 80px 50px;
        flex-wrap: wrap;
        gap: 40px;
    }

    .text-area {
        flex: 1 1 400px;
    }

    .text-area h1 {
        font-size: 60px;
        color: #ff4fa3;
        margin-bottom: 10px;
    }

    .text-area h2 {
        font-size: 36px;
        margin-bottom: 20px;
    }

    .text-area p {
        max-width: 500px;
        color: #555;
        margin-bottom: 30px;
    }

    .buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: 500;
        border: 2px solid #ff4fa3;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-primary {
        background: #ff4fa3;
        color: #fff;
    }

    .btn-primary:hover {
        background: #ff7fc2;
    }

    .btn-outline {
        background: transparent;
        color: #ff4fa3;
    }

    .btn-outline:hover {
        background: #ffe6f3;
    }

    /* IMAGE AREA */
    .image-area {
        flex: 1 1 300px;
        text-align: center;
    }

    .image-area img {
        width: 100%;
        max-width: 450px;
        border-radius: 50%;
        box-shadow: 0 10px 25px rgba(255, 121, 184, 0.3);
    }

    /* FOOTER */
    footer {
        text-align: center;
        padding: 20px 0;
        color: #777;
        font-size: 14px;
        border-top: 1px solid #eee;
        margin-top: 50px;
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .container {
            flex-direction: column-reverse;
            text-align: center;
        }
        .text-area h1 {
            font-size: 48px;
        }
        .text-area h2 {
            font-size: 28px;
        }
        .text-area p {
            margin: 0 auto 20px;
        }
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
    </ul>
</nav>


<!-- MAIN CONTENT -->
<div class="container">
    <div class="text-area">
        <h1>Lost & Found</h1>
        <h2>Mudah dan Cepat</h2>
        <p>
            FindMe adalah layanan pelaporan barang hilang dan temuan. Mudah, cepat, dan terorganisir untuk membantu menemukan kembali barang pentingmu.
        </p>

        <div class="buttons">
            <button class="btn btn-primary" onclick="loginUser()">Login Pengguna</button>
            <button class="btn btn-outline" onclick="loginAdmin()">Login Admin</button>
        </div>
    </div>

    <div class="image-area">
        <img src="assets/gambar3.png" alt="Illustrasi Lost and Found">
    </div>
</div>

<footer>
    © 2025 FindMe — All rights reserved
</footer>

<!-- JAVASCRIPT -->
<script>
function loginUser() {
    window.location.href = "Login.php";
}

function loginAdmin() {
    window.location.href = "LoginAdmin.php";
}
</script>

</body>
</html>