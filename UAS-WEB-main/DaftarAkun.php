<?php
// DaftarAkunAdmin.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama!";
    } else {
        // Cek apakah email sudah terdaftar
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Register sebagai admin
            $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, MD5(?), 'user')";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sss", $name, $email, $password);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = "Akun admin berhasil dibuat!";
                
                // Kosongkan form
                $_POST = array();
            } else {
                $error = "Terjadi kesalahan saat mendaftar!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FindMe | Daftar Akun</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }

    body {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #ffe6f3, #f0fff0);
    }

    .register-container {
        background: #fff;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 15px 25px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 400px;
        text-align: center;
        position: relative;
    }

    .register-container h2 {
        color: #ff4fa3;
        margin-bottom: 25px;
        font-weight: 700;
    }

    .register-container input {
        width: 100%;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 16px;
    }

    .register-container button {
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

    .register-container button:hover {
        background: #ff7fc2;
    }

    .register-container .links {
        margin-top: 15px;
        font-size: 14px;
    }

    .register-container .links a {
        color: #ff4fa3;
        text-decoration: none;
        transition: 0.3s;
    }

    .register-container .links a:hover {
        color: #ff7fc2;
    }

    .register-container .show-password {
        margin-top: 10px;
        font-size: 14px;
        color: #ff4fa3;
        cursor: pointer;
        user-select: none;
    }
    
    .message {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
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

<div class="register-container">
    <h2>Daftar Akun FindMe</h2>
    
    <?php if (isset($success)): ?>
    <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="name" placeholder="Nama Lengkap" 
               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        <input type="email" name="email" placeholder="Email" 
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required>
        <div class="show-password" onclick="togglePassword()">Tampilkan Password</div>
        <button type="submit">Daftar</button>
    </form>

    <div class="links">
        Sudah punya akun? <a href="Login.php">Login di sini</a>
    </div>
</div>

<script>
function togglePassword(){
    const passInput = document.querySelector('input[name="password"]');
    const confirmPassInput = document.querySelector('input[name="confirm_password"]');
    if(passInput.type === "password"){
        passInput.type = "text";
        confirmPassInput.type = "text";
    } else {
        passInput.type = "password";
        confirmPassInput.type = "password";
    }
}
</script>

</body>
</html>