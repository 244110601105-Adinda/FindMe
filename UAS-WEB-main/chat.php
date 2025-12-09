<?php
// chat.php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Ambil daftar chat
$chats_query = "
    SELECT DISTINCT 
        CASE 
            WHEN c.sender_id = ? THEN c.receiver_id 
            ELSE c.sender_id 
        END as other_user_id,
        u.name,
        u.profile_photo,
        MAX(c.created_at) as last_message_time,
        (SELECT message FROM chats WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
        COUNT(CASE WHEN c.receiver_id = ? AND c.is_read = 0 THEN 1 END) as unread_count
    FROM chats c
    JOIN users u ON (u.id = CASE WHEN c.sender_id = ? THEN c.receiver_id ELSE c.sender_id END)
    WHERE c.sender_id = ? OR c.receiver_id = ?
    GROUP BY other_user_id, u.name, u.profile_photo
    ORDER BY last_message_time DESC
";

$chats_stmt = mysqli_prepare($conn, $chats_query);
mysqli_stmt_bind_param($chats_stmt, "iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($chats_stmt);
$chats_result = mysqli_stmt_get_result($chats_stmt);

// Ambil notifikasi
$notifications_query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
$notifications_stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
$notification_count = mysqli_num_rows($notifications_result);

// Ambil total chat yang belum dibaca untuk badge
$unread_chats_query = "SELECT COUNT(*) as total FROM chats WHERE receiver_id = ? AND is_read = 0";
$unread_chats_stmt = mysqli_prepare($conn, $unread_chats_query);
mysqli_stmt_bind_param($unread_chats_stmt, "i", $user_id);
mysqli_stmt_execute($unread_chats_stmt);
$unread_chats_result = mysqli_stmt_get_result($unread_chats_stmt);
$unread_chats_data = mysqli_fetch_assoc($unread_chats_result);
$total_unread_chats = $unread_chats_data['total'];

// Jika ada parameter user, ambil pesan dengan user tersebut
$selected_user_id = $_GET['user_id'] ?? null;
$selected_user = null;
$messages = [];

if ($selected_user_id) {
    // Ambil data user yang dipilih
    $selected_user_query = "SELECT * FROM users WHERE id = ?";
    $selected_user_stmt = mysqli_prepare($conn, $selected_user_query);
    mysqli_stmt_bind_param($selected_user_stmt, "i", $selected_user_id);
    mysqli_stmt_execute($selected_user_stmt);
    $selected_user_result = mysqli_stmt_get_result($selected_user_stmt);
    $selected_user = mysqli_fetch_assoc($selected_user_result);
    
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
    mysqli_stmt_bind_param($messages_stmt, "iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
    mysqli_stmt_execute($messages_stmt);
    $messages_result = mysqli_stmt_get_result($messages_stmt);
    
    // Tandai pesan sebagai sudah dibaca
    $mark_read_query = "UPDATE chats SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
    $mark_read_stmt = mysqli_prepare($conn, $mark_read_query);
    mysqli_stmt_bind_param($mark_read_stmt, "ii", $user_id, $selected_user_id);
    mysqli_stmt_execute($mark_read_stmt);
}

// Proses kirim pesan baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && $selected_user_id) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $insert_query = "INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "iis", $user_id, $selected_user_id, $message);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Refresh halaman untuk menampilkan pesan baru
        header("Location: chat.php?user_id=" . $selected_user_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FindMe Chat</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* CSS dari chat.html tetap sama */
:root {
    --pink-main: #ff4fa3;
    --pink-light: #ffe7f1;
    --gray-bg: #fafafa;
    --white: #fff;
    --text: #333;
}

body {
    margin: 0;
    font-family: Poppins, sans-serif;
    background: var(--white);
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


.chat-wrapper {
    display: flex;
    height: calc(100vh - 56px);
}

/* LEFT PANEL */
.left-panel {
    width: 28%;
    border-right: 1px solid #ededed;
    background: var(--white);
    display: flex;
    flex-direction: column;
}

.left-header {
    padding: 18px;
    font-size: 20px;
    font-weight: 600;
    color: var(--pink-main);
    border-bottom: 1px solid #ececec;
}

.chat-list {
    overflow-y: auto;
    flex: 1;
}

.chat-item {
    display: flex;
    padding: 14px;
    gap: 12px;
    cursor: pointer;
    align-items: center;
    border-bottom: 1px solid #f2f2f2;
    transition: .15s;
}

.chat-item:hover,
.chat-item.active {
    background: var(--pink-light);
}

.chat-item img {
    width: 55px;
    height: 55px;
    border-radius: 50%;
}

.chat-info {
    display: flex;
    flex-direction: column
};
    .chat-name {
        font-weight: 600;
        color: var(--text);
        font-size: 15px;
    }

    .chat-last {
        font-size: 13px;
        color: #777;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .unread-badge {
        background: #ff4fa3;
        color: white;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: auto;
    }

    /* RIGHT PANEL */
    .right-panel {
        width: 72%;
        display: flex;
        flex-direction: column;
        background: var(--gray-bg);
    }

    .chat-top {
        padding: 12px 20px;
        background: var(--white);
        font-size: 17px;
        font-weight: 600;
        border-bottom: 1px solid #ececec;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    #chatPhoto {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
    }

    .messages-box {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .bubble {
        max-width: 60%;
        padding: 12px 16px;
        border-radius: 14px;
        font-size: 14px;
        word-wrap: break-word;
    }

    .them {
        background: var(--white);
        align-self: flex-start;
        border: 1px solid #ececec;
    }

    .me {
        background: var(--pink-main);
        color: white;
        align-self: flex-end;
    }

    .input-area {
        background: var(--white);
        padding: 12px 20px;
        border-top: 1px solid #ececec;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .input-area input {
        flex: 1;
        padding: 12px 16px;
        border-radius: 25px;
        border: 1px solid #dedede;
        outline: none;
    }

    .send-btn {
        background: var(--pink-main);
        color: white;
        padding: 10px 18px;
        border-radius: 25px;
        border: none;
        cursor: pointer;
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
                <?php if ($total_unread_chats > 0): ?>
                <span class="chat-badge" id="chatBadge"><?php echo $total_unread_chats; ?></span>
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

    <div class="chat-wrapper">
        <!-- LEFT PANEL - Daftar Chat -->
        <div class="left-panel">
            <div class="left-header">Chat</div>
            <div class="chat-list" id="chatList">
                <?php if (mysqli_num_rows($chats_result) > 0): ?>
                    <?php while ($chat = mysqli_fetch_assoc($chats_result)): ?>
                    <div class="chat-item <?php echo ($selected_user_id == $chat['other_user_id']) ? 'active' : ''; ?>" 
                         onclick="window.location.href='chat.php?user_id=<?php echo $chat['other_user_id']; ?>'">
                        <img src="<?php echo htmlspecialchars($chat['profile_photo'] ?: 'profil.png'); ?>">
                        <div class="chat-info">
                            <span class="chat-name"><?php echo htmlspecialchars($chat['name']); ?></span>
                            <span class="chat-last"><?php echo htmlspecialchars(substr($chat['last_message'] ?? 'No messages yet', 0, 30)); ?></span>
                        </div>
                        <?php if ($chat['unread_count'] > 0): ?>
                        <span class="unread-badge"><?php echo $chat['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #888;">
                        Belum ada percakapan
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANEL - Percakapan -->
        <div class="right-panel">
            <?php if ($selected_user): ?>
            <div class="chat-top">
                <img id="chatPhoto" src="<?php echo htmlspecialchars($selected_user['profile_photo'] ?: 'profil.png'); ?>">
                <span id="chatName"><?php echo htmlspecialchars($selected_user['name']); ?></span>
            </div>

            <div class="messages-box" id="messagesBox">
                <?php if (mysqli_num_rows($messages_result) > 0): ?>
                    <?php while ($message = mysqli_fetch_assoc($messages_result)): ?>
                    <div class="bubble <?php echo ($message['sender_id'] == $user_id) ? 'me' : 'them'; ?>">
                        <?php echo htmlspecialchars($message['message']); ?>
                        <div style="font-size: 11px; margin-top: 5px; opacity: 0.7;">
                            <?php echo date('H:i', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-chat">
                        Mulai percakapan dengan <?php echo htmlspecialchars($selected_user['name']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="" class="input-area">
                <input type="text" name="message" placeholder="Tulis pesan..." id="msgInput" required>
                <button type="submit" name="send_message" class="send-btn">Kirim</button>
            </form>
            <?php else: ?>
            <div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #888;">
                Pilih percakapan untuk memulai chat
            </div>
            <?php endif; ?>
        </div>
    </div>

<script>
// NAVBAR FUNCTIONS
document.getElementById("homeBtn")?.addEventListener("click", () => {
    window.location.href = "home.php";
});

document.getElementById("chatBtn")?.addEventListener("click", () => {
    window.location.href = "chat.php";
});

document.getElementById("profileBtn")?.addEventListener("click", function () {
    window.location.href = "profil.php";
});

// Auto scroll ke bawah pesan
const messagesBox = document.getElementById("messagesBox");
if (messagesBox) {
    messagesBox.scrollTop = messagesBox.scrollHeight;
}

// Kirim pesan dengan Enter
const msgInput = document.getElementById("msgInput");
if (msgInput) {
    msgInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            this.form.submit();
        }
    });
}
</script>

</body>
</html>