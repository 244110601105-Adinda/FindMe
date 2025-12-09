<?php
// homeAdmin.php
require_once 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: LoginAdmin.php");
    exit();
}

// Cek role admin
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM users WHERE id = ?";
$role_stmt = mysqli_prepare($conn, $role_query);
mysqli_stmt_bind_param($role_stmt, "i", $user_id);
mysqli_stmt_execute($role_stmt);
$role_result = mysqli_stmt_get_result($role_stmt);
$role_data = mysqli_fetch_assoc($role_result);

if ($role_data['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

// Ambil data admin
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Ambil statistik
$pelapor_query = "SELECT COUNT(DISTINCT reporter_id) as count FROM reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$pelapor_result = mysqli_query($conn, $pelapor_query);
$pelapor_data = mysqli_fetch_assoc($pelapor_result);

$selesai_query = "SELECT COUNT(*) as count FROM reports WHERE status = 'selesai' AND MONTH(created_at) = MONTH(CURRENT_DATE())";
$selesai_result = mysqli_query($conn, $selesai_query);
$selesai_data = mysqli_fetch_assoc($selesai_result);

$laporan_query = "SELECT COUNT(*) as count FROM reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$laporan_result = mysqli_query($conn, $laporan_query);
$laporan_data = mysqli_fetch_assoc($laporan_result);

// Ambil laporan terpopuler
$populer_query = "
    SELECT reason, COUNT(*) as jumlah 
    FROM reports 
    GROUP BY reason 
    ORDER BY jumlah DESC 
    LIMIT 3
";
$populer_result = mysqli_query($conn, $populer_query);

// Ambil semua postingan
$posts_query = "SELECT p.*, u.name, u.program_study, u.profile_photo 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC";
$posts_result = mysqli_query($conn, $posts_query);

// Filter postingan berdasarkan tab
$filter = $_GET['filter'] ?? 'semua';
$where_clause = "";
if ($filter == 'kehilangan') {
    $where_clause = "WHERE p.post_type = 'kehilangan'";
} elseif ($filter == 'penemuan') {
    $where_clause = "WHERE p.post_type = 'penemuan'";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindMe | Lost & Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --pink-main: #ff4fa3;
            --pink-light: #ffe6f3;
            --gray-bg: #f7f7fa;
            --white: #fff;
            --text-dark: #333;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            background: var(--gray-bg);
            color: var(--text-dark);
        }

        /* NAVBAR */
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

        .navbar input {
            margin-left: 25px;
            width: 360px;
            padding: 10px 18px;
            border-radius: 20px;
            border: 1px solid #ddd;
        }

        /* CONTAINER */
        .container {
            display: flex;
            height: calc(100vh - 56px);
        }

        /* LEFT & RIGHT FIXED */
        .left,
        .right {
            width: 22%;
            height: 100%;
            overflow: hidden;
        }

        /* MAIN SCROLLABLE */
        main {
            width: 56%;
            height: 100%;
            overflow-y: auto;
            padding: 20px;
        }

        /* LEFT BOXES */
        .profile-box,
        .menu-card {
            background: var(--white);
            padding: 15px;
            border-radius: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .profile-img {
            width: 70px;
            border-radius: 50%;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid #ff4fa3;
            color: #ff4fa3;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #ff4fa3;
            color: white;
        }

        /* MENU CARD */
        .menu-card h4 {
            margin-bottom: 10px;
            font-size: 16px;
            color: var(--pink-main);
        }

        .menu-card ul {
            list-style: none;
            padding: 0;
        }

        .menu-card li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #fdf0f7;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .menu-card li:hover {
            background: var(--pink-light);
        }

        .menu-card li span.count {
            background: var(--pink-main);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 12px;
        }

        /* POST CARD */
        .post-card {
            background: var(--white);
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .post-header {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .mini-profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-img {
            max-width: 150px;
            height: auto;
            border-radius: 10px;
            margin-top: 10px;
            display: block;
        }

        /* TABS FILTER */
        .tabs {
            display: flex;
            gap: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .tabs div {
            padding-bottom: 5px;
            cursor: pointer;
        }

        .tabs .active {
            border-bottom: 3px solid var(--pink-main);
            color: var(--pink-main);
        }

        /* STATS CARD (Kanan) */
        .stats-card {
            background: var(--white);
            border-radius: 12px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin: 20px auto;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .stat-item .number {
            font-size: 32px;
            font-weight: bold;
            color: #ff4fa3;
        }

        .stat-item .label {
            font-size: 14px;
            color: #555;
        }

        /* RESPONSIVE */
        @media(max-width: 900px) {
            .container {
                flex-direction: column;
            }

            .left,
            .right,
            main {
                width: 100%;
                height: auto;
                overflow: visible;
            }
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
            <span id="daftarBtn"><i class="fa-regular fa-folder"></i></span>
        </div>
    </nav>

    <div class="container">
        <!-- LEFT -->
        <aside class="left">
            <div class="profile-box">
                <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'profil.png'); ?>" class="profile-img">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <button class="logout-btn" id="logoutBtn">Logout</button>
            </div>

            <div class="menu-card">
                <h4>Laporan Terpopuler</h4>
                <ul>
                    <?php
                    $reasons_text = [
                        'ujaran-kebencian' => 'Ujaran Kebencian',
                        'hoaks' => 'Informasi palsu',
                        'kata-tidak-pantas' => 'Tidak sesuai konteks',
                        'sara' => 'Mengandung SARA'
                    ];

                    if (mysqli_num_rows($populer_result) > 0):
                        mysqli_data_seek($populer_result, 0);
                        while ($row = mysqli_fetch_assoc($populer_result)):
                    ?>
                            <li>
                                <?php echo $reasons_text[$row['reason']] ?? $row['reason']; ?>
                                <span class="count"><?php echo $row['jumlah']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>Belum ada laporan</li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <!-- MAIN -->
        <main>
            <!-- TABS FILTER -->
            <div class="tabs" id="tabsMenu">
                <div data-tab="semua" onclick="window.location.href='homeAdmin.php?filter=semua'">Semua</div>
                <div data-tab="kehilangan" onclick="window.location.href='homeAdmin.php?filter=kehilangan'">Kehilangan</div>
                <div data-tab="penemuan" onclick="window.location.href='homeAdmin.php?filter=penemuan'">Penemuan</div>
            </div>

            <!-- POSTS FROM DATABASE -->
            <?php
            $filtered_query = "SELECT p.*, u.name, u.program_study, u.profile_photo 
                          FROM posts p 
                          JOIN users u ON p.user_id = u.id 
                          $where_clause 
                          ORDER BY p.created_at DESC";
            $filtered_result = mysqli_query($conn, $filtered_query);

            if (mysqli_num_rows($filtered_result) > 0):
                while ($post = mysqli_fetch_assoc($filtered_result)):
            ?>
                    <div class="post-card" data-type="<?php echo $post['post_type']; ?>">
                        <div class="post-header">
                            <img src="<?php echo htmlspecialchars($post['profile_photo'] ?? 'profil.png'); ?>" class="mini-profile">
                            <div>
                                <b><?php echo htmlspecialchars($post['name']); ?></b><br>
                                <small><?php echo htmlspecialchars($post['program_study']); ?> •
                                    <?php echo ucfirst($post['post_type']); ?></small>
                            </div>
                        </div>

                        <p><b><?php echo htmlspecialchars($post['content']); ?></b><br>
                            <?php if (!empty($post['location'])): ?>
                                Tempat: <?php echo htmlspecialchars($post['location']); ?><br>
                            <?php endif; ?>
                            Tanggal: <?php
                                        if (!empty($post['post_date']) && $post['post_date'] != '0000-00-00') {
                                            echo date('d-m-Y', strtotime($post['post_date']));
                                        } else {
                                            echo date('d-m-Y'); 
                                        }
                                        ?>
                        </p>

                        <?php if (!empty($post['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="post-img">
                        <?php endif; ?>

                        <div style="margin-top: 10px;">
                            <i class="fa-regular fa-heart" onclick="likePost(this)"></i>
                            <i class="fa-regular fa-comment"></i>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="post-card">
                    <p style="text-align: center; color: #888;">Tidak ada postingan ditemukan</p>
                </div>
            <?php endif; ?>
        </main>

        <!-- RIGHT -->
        <aside class="right">
            <div class="stats-card">
                <div class="stat-item">
                    <div class="number"><?php echo $pelapor_data['count']; ?></div>
                    <div class="label">Pelapor bulan ini</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $selesai_data['count']; ?></div>
                    <div class="label">Ditandai selesai</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $laporan_data['count']; ?></div>
                    <div class="label">Mengajukan laporan</div>
                </div>
            </div>
        </aside>
    </div>

    <script>
        // NAVBAR
        document.getElementById("homeBtn")?.addEventListener("click", () => {
            window.location.href = "homeAdmin.php";
        });

        document.getElementById("daftarBtn")?.addEventListener("click", () => {
            window.location.href = "daftar.php";
        });

        // TABS - Highlight active tab
        const currentFilter = "<?php echo $filter; ?>";
        const tabs = document.querySelectorAll("#tabsMenu div");
        tabs.forEach(tab => {
            if (tab.dataset.tab === currentFilter) {
                tab.classList.add("active");
            }
        });

        // LIKE FUNCTION
        function likePost(icon) {
            icon.classList.toggle("fa-solid");
            icon.classList.toggle("fa-regular");
            icon.style.color = icon.classList.contains("fa-solid") ? "#ff4fa3" : "#000";
        }

        // LOGOUT
        document.getElementById("logoutBtn").addEventListener("click", () => {
            if (confirm("Apakah Anda yakin ingin keluar?")) {
                window.location.href = "logout.php";
            }
        });
    </script>

</body>

</html>