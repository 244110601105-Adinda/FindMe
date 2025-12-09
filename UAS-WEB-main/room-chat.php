<?php
// room-chat.php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil parameter dari URL
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($receiver_id == 0) {
    header("Location: chat.php");
    exit();
}

// Ambil data user yang login
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Ambil data user penerima
$receiver_query = "SELECT * FROM users WHERE id = ?";
$receiver_stmt = mysqli_prepare($conn, $receiver_query);
mysqli_stmt_bind_param($receiver_stmt, "i", $receiver_id);
mysqli_stmt_execute($receiver_stmt);
$receiver_result = mysqli_stmt_get_result($receiver_stmt);
$receiver = mysqli_fetch_assoc($receiver_result);

if (!$receiver) {
    header("Location: chat.php");
    exit();
}

// Ambil pesan antara kedua user
$messages_query = "
    SELECT c.*, u.name as sender_name, u.profile_photo as sender_photo
    FROM chats c
    JOIN users u ON c.sender_id = u.id
    WHERE (c.sender_id = ? AND c.receiver_id = ?) 
       OR (c.sender_id = ? AND c.receiver_id = ?)
    ORDER BY c.created_at ASC
";
$messages_stmt = mysqli_prepare($conn, $messages_query);
mysqli_stmt_bind_param($messages_stmt, "iiii", $user_id, $receiver_id, $receiver_id, $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);

// Tandai pesan sebagai sudah dibaca
$mark_read_query = "UPDATE chats SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
$mark_read_stmt = mysqli_prepare($conn, $mark_read_query);
mysqli_stmt_bind_param($mark_read_stmt, "ii", $user_id, $receiver_id);
mysqli_stmt_execute($mark_read_stmt);

// Proses kirim pesan baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    if (!empty($message)) {
        $insert_query = "INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "iis", $user_id, $receiver_id, $message);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Tambahkan notifikasi untuk penerima
            $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, 'Pesan baru dari " . $user['name'] . "')";
            $notif_stmt = mysqli_prepare($conn, $notif_query);
            mysqli_stmt_bind_param($notif_stmt, "i", $receiver_id);
            mysqli_stmt_execute($notif_stmt);
            
            // Refresh halaman
            header("Location: room-chat.php?user_id=" . $receiver_id);
            exit();
        }
    }
}

// Ambil notifikasi untuk navbar
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
<title>Room Chat - <?php echo htmlspecialchars($receiver['name']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root {
    --pink-main: #ff4fa3;
    --pink-light: #ffe7f1;
    --gray-bg: #fafafa;
    --white: #fff;
    --text: #333;
}

    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
        background: #f8f8f8;
    }
    .navbar {
    display: flex;
    align-items: center;
    padding: 12px 25px;
    background: var(--white);
    box-shadow: 0 2px 5px rgba(0,0,0,.1);
    position: sticky;
    top: 0;
    z-index: 10;
}
.logo { font-size: 25px; font-weight: bold; color: var(--pink-main); }
.logo span { color: #f7cfe5; }
.nav-icons { margin-left: auto; display: flex; gap: 25px; font-size: 21px; cursor: pointer; }
.nav-icons span.active { color: var(--pink-main); font-weight: bold; }
.navbar input { margin-left: 25px; width: 360px; padding: 10px 18px; border-radius: 20px; border: 1px solid #ddd; }

    /* ===== HEADER ===== */
    .chat-header {
        padding: 12px 18px;
        background: #ffffff;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #ececec;
    }

    .chat-header img {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ff4fa3;
    }

    .chat-header .name {
        font-size: 18px;
        font-weight: 600;
    }

    /* ===== AREA CHAT ===== */
    .chat-area {
        height: calc(100vh - 120px);
        overflow-y: auto;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .bubble-left {
        max-width: 60%;
        background: #ffffff;
        padding: 12px 18px;
        border-radius: 14px;
        color: #333;
        border: 1px solid #e5e5e5;
        align-self: flex-start;
    }

    .bubble-right {
        max-width: 60%;
        background: #ff4fa3;
        padding: 12px 18px;
        border-radius: 14px;
        color: white;
        margin-left: auto;
        align-self: flex-end;
    }

    /* ===== INPUT AREA ===== */
    .input-area {
        padding: 15px 20px;
        background: #ffffff;
        border-top: 1px solid #ececec;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .input-area input {
        flex: 1;
        padding: 15px 18px;
        border-radius: 40px;
        border: 1px solid #ddd;
        outline: none;
        font-size: 14px;
    }

    .send-btn {
        background: #ff4fa3;
        padding: 12px 25px;
        border-radius: 14px;
        color: white;
        cursor: pointer;
        font-weight: 600;
        border: none;
        font-size: 14px;
    }

    .send-btn:hover {
        opacity: 0.9;
    }
    
    .time-stamp {
        font-size: 11px;
        margin-top: 5px;
        opacity: 0.7;
        text-align: right;
    }
    
    .empty-chat {
        text-align: center;
        color: #888;
        margin-top: 50px;
        font-size: 16px;
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
        <span id="profileBtn" class="profile-icon">
            <i class="fa-solid fa-user"></i>
        </span>
    </div>
</nav>

<!-- HEADER -->
<div class="chat-header">
    <img id="chatPhoto" src="<?php echo htmlspecialchars($receiver['profile_photo'] ?: 'profil.png'); ?>" alt="<?php echo htmlspecialchars($receiver['name']); ?>">
    <div class="name" id="chatName"><?php echo htmlspecialchars($receiver['name']); ?></div>
</div>

<!-- AREA CHAT -->
<div class="chat-area" id="chatBox">
    <?php if (mysqli_num_rows($messages_result) > 0): ?>
        <?php while ($message = mysqli_fetch_assoc($messages_result)): ?>
        <div class="bubble-<?php echo ($message['sender_id'] == $user_id) ? 'right' : 'left'; ?>">
            <?php echo htmlspecialchars($message['message']); ?>
            <div class="time-stamp">
                <?php echo date('H:i', strtotime($message['created_at'])); ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-chat">
            Mulai percakapan dengan <?php echo htmlspecialchars($receiver['name']); ?>
        </div>
    <?php endif; ?>
</div>

<!-- INPUT AREA -->
<form method="POST" action="" class="input-area">
    <input type="text" name="message" id="chatInput" placeholder="Tulis pesan..." required>
    <button type="submit" name="send_message" class="send-btn">Kirim</button>
</form>

<script>
// NAVBAR FUNCTIONS
document.getElementById("homeBtn")?.addEventListener("click", () => {
    window.location.href = "home.php";
});

document.getElementById("chatBtn")?.addEventListener("click", () => {
    window.location.href = "chat.php";
});

document.getElementById("profileBtn").addEventListener("click", function () {
    window.location.href = "profil.php";
});

// Auto scroll ke bawah pesan
const chatBox = document.getElementById("chatBox");
if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Kirim pesan dengan Enter
const chatInput = document.getElementById("chatInput");
if (chatInput) {
    chatInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            this.form.submit();
        }
    });
}
</script>

</body>
</html>