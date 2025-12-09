<?php
// LoginAdmin.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM users WHERE email = ? AND password = MD5(?) AND role = 'admin'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        header("Location: homeAdmin.php");
        exit();
    } else {
        $error = "Email, password salah, atau Anda bukan admin!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FindMe | Login Admin</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
    }

    body {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #ffe6f3, #f0fff0);
    }

    .login-container {
        background: #fff;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 15px 25px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 400px;
        text-align: center;
        position: relative;
    }

    .login-container img {
        width: 80px;
        margin-bottom: 20px;
    }

    .login-container h2 {
        color: #ff4fa3;
        margin-bottom: 25px;
        font-weight: 700;
    }

    .login-container input {
        width: 100%;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 16px;
    }

    .login-container button {
        width: 100%;
        padding: 12px;
        margin-top: 15px;
        border-radius: 10px;
        border: none;
        background: #ff4fa3;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .login-container button:hover {
        background: #ff7fc2;
    }

    .login-container .show-password {
        margin-top: 10px;
        font-size: 14px;
        color: #ff4fa3;
        cursor: pointer;
        user-select: none;
    }

    .login-container .links {
        margin-top: 15px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
    }

    .login-container .links a {
        text-decoration: none;
        color: #ff4fa3;
        transition: 0.3s;
    }

    .login-container .links a:hover {
        color: #ff7fc2;
    }

    @media(max-width: 500px){
        .login-container {
            padding: 30px 20px;
        }
    }
    
    .error-message {
        color: #ff4fa3;
        margin-bottom: 15px;
        font-size: 14px;
    }
</style>
</head>
<body>

<div class="login-container">
    <img src="assets/gambar3.png" alt="Illustration">
    <h2>Login Admin FindMe</h2>
    
    <?php if (isset($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="show-password" onclick="togglePassword()">Tampilkan Password</div>
        <button type="submit">Login</button>
    </form>

    <div class="links">
        <a href="#">Lupa Password?</a>
        <a href="DaftarAkunAdmin.php">Daftar Akun Admin</a>
    </div>
</div>

<script>
function togglePassword(){
    const passInput = document.querySelector('input[name="password"]');
    passInput.type = passInput.type === "password" ? "text" : "password";
}
</script>

</body>
</html>