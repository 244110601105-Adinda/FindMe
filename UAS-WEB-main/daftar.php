<?php
// daftar.php
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

// Ambil statistik laporan
$total_query = "SELECT COUNT(*) as total FROM reports";
$total_result = mysqli_query($conn, $total_query);
$total_data = mysqli_fetch_assoc($total_result);

$proses_query = "SELECT COUNT(*) as proses FROM reports WHERE status = 'diproses'";
$proses_result = mysqli_query($conn, $proses_query);
$proses_data = mysqli_fetch_assoc($proses_result);

$selesai_query = "SELECT COUNT(*) as selesai FROM reports WHERE status = 'selesai'";
$selesai_result = mysqli_query($conn, $selesai_query);
$selesai_data = mysqli_fetch_assoc($selesai_result);

$tolak_query = "SELECT COUNT(*) as tolak FROM reports WHERE status = 'ditolak'";
$tolak_result = mysqli_query($conn, $tolak_query);
$tolak_data = mysqli_fetch_assoc($tolak_result);

// Ambil statistik berdasarkan jenis pelanggaran
$jenis_query = "SELECT reason, COUNT(*) as jumlah FROM reports GROUP BY reason";
$jenis_result = mysqli_query($conn, $jenis_query);
$jenis_stats = [];
while ($row = mysqli_fetch_assoc($jenis_result)) {
    $jenis_stats[$row['reason']] = $row['jumlah'];
}

// Ambil data laporan untuk tabel
$reports_query = "SELECT r.*, u.name as reporter_name 
                  FROM reports r 
                  LEFT JOIN users u ON r.reporter_id = u.id 
                  ORDER BY r.created_at DESC";
$reports_result = mysqli_query($conn, $reports_query);

// Filter laporan
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];
$types = '';

if ($filter_date) {
    $where_conditions[] = "DATE(r.created_at) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

if ($filter_status != 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where_conditions[] = "(r.reported_user LIKE ? OR u.name LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$query = "SELECT r.*, u.name as reporter_name 
          FROM reports r 
          LEFT JOIN users u ON r.reporter_id = u.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY r.created_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $reports_result = mysqli_stmt_get_result($stmt);
} else {
    $reports_result = mysqli_query($conn, $query);
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard Laporan Konten Negatif</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* CSS dari daftar.html tetap sama */
:root{
  --pink-50:#fff3f8;
  --pink-100:#ffd6e7;
  --pink-200:#ffb8d1;
  --pink-500:#ff5fa2;
  --pink-main: #ff4fa3;
  --pink-light: #ffe6f3;
  --gray-bg: #f7f7fa;
  --white: #fff;
  --text-dark: #333;
  --accent:#ff6fa8;
  --muted:#777;
  --bg:#ffffff;
  --radius:12px;
  --shadow:0 6px 15px rgba(0,0,0,0.08);
  font-family:Inter,system-ui;
}

body{
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
  box-shadow: 0 2px 5px rgba(0,0,0,.1);
  position: sticky;
  top: 0;
  z-index: 10;
}
.logo { font-size: 25px; font-weight: bold; color: var(--pink-main); }
.logo span { color: #f7cfe5; }
.nav-icons { margin-left: auto; display: flex; gap: 25px; font-size: 21px; cursor: pointer; }
.navbar input { margin-left: 25px; width: 360px; padding: 10px 18px; border-radius: 20px; border: 1px solid #ddd; }

.container{
  max-width:1200px;
  margin:auto;
  display:flex;
  gap:20px;
}

/* MAIN */
main{
  flex:1;
  display:flex;
  flex-direction:column;
  gap:20px;
}

header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-top: 25px;
}

header h1{
  margin:0;
  font-size:28px;
  color: #ff4fa3;
}
header p{
  margin:4px 0 0;
  color:var(--muted);
}

/* FILTER BAR */
.filters{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.filters input,
.filters select{
  padding:8px 12px;
  border-radius:8px;
  border:1px solid #ddd;
  box-shadow:var(--shadow);
  background:white;
  font-size:14px;
}
.search-input{
  flex:1;
  min-width:200px;
}
.btn{
  background:var(--accent);
  border:0;
  padding:8px 16px;
  border-radius:8px;
  color:white;
  cursor:pointer;
  font-weight:600;
  box-shadow:0 6px 14px rgba(255,100,150,0.25);
}

/* CARD RINGKASAN */
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  gap:14px;
}
.card{
  background:white;
  padding:16px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.card p{
  margin:0;
  color:var(--muted);
  font-size:13px;
}
.card h2{
  margin:4px 0 0;
  font-size:22px;
}
.avatar{
  width:40px;
  height:40px;
  border-radius:12px;
  background:linear-gradient(135deg,var(--pink-200),var(--pink-500));
  display:flex;
  justify-content:center;
  align-items:center;
  color:white;
  font-weight:bold;
}

/* TABLE */
.table-wrapper{
  background:white;
  padding:16px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:820px;
}

th{
  background:white;
  position:sticky;
  top:0;
  text-align:left;
  padding:12px;
  color:var(--muted);
  border-bottom:2px solid #f4f4f4;
  font-size:16px;
  color: #ff4fa3;
}

td{
  padding:10px;
  border-bottom:1px solid #f7f7f7;
  font-size:14px;
}

/* STATUS */
.status {
  display: inline-flex;         
  align-items: center;
  justify-content: center;     
  width: 100px;
  font-weight: 600;
  font-size: 13px;
  padding: 6px 0px;
  border-radius: 12px;          
  margin: 6px 0;
}

.status.diproses {
  color: #a07900;
  background: #fff4d6;
}

.status.selesai {
  color: #0f7a32;
  background: #d6ffd8;
}

.status.tolak {
  color: #a70000;
  background: #ffd6d6;
}

/* SIDEBAR */
aside{
  width:260px;
  display:flex;
  flex-direction:column;
  gap:18px;
}
.panel{
  padding:14px;
  background:white;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-top: 25px;
}
.panel h4{
  margin:0 0 10px;
  color: #ff4fa3;
  font-size: 24px;
}
.stat-item{
  padding:8px;
  background:var(--pink-50);
  border-radius:10px;
  margin-bottom:8px;
  display:flex;
  justify-content:space-between;
}

@media(max-width:900px){
  aside{display:none}
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
  <main>
    <!-- HEADER -->
    <header>
      <div>
        <h1>Dashboard Laporan Konten Negatif</h1>
        <p>Monitoring laporan ujaran kebencian, kata tidak pantas, dan hoaks</p>
      </div>
    </header>

    <!-- FILTER BAR -->
    <form method="GET" action="" class="filters">
        <input type="date" id="dateFilter" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
        
        <select id="statusFilter" name="status">
            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
            <option value="selesai" <?php echo $filter_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
            <option value="diproses" <?php echo $filter_status == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
            <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
        </select>
        
        <input type="text" id="searchInput" name="search" placeholder="Cari laporan..." 
               value="<?php echo htmlspecialchars($search); ?>">
        
        <button type="submit" class="btn">Filter</button>
        <button type="button" class="btn" onclick="window.location.href='daftar.php'">Reset</button>
    </form>

    <!-- RINGKASAN -->
    <div class="cards">
      <div class="card">
        <div>
          <p>Total Laporan</p>
          <h2 id="totalCount"><?php echo $total_data['total']; ?></h2>
        </div>
        <div class="avatar">!</div>
      </div>

      <div class="card">
        <div>
          <p>Diproses</p>
          <h2 id="prosesCount"><?php echo $proses_data['proses']; ?></h2>
        </div>
        <div class="avatar">⟳</div>
      </div>

      <div class="card">
        <div>
          <p>Selesai</p>
          <h2 id="selesaiCount"><?php echo $selesai_data['selesai']; ?></h2>
        </div>
        <div class="avatar">✓</div>
      </div>

      <div class="card">
        <div>
          <p>Ditolak</p>
          <h2 id="tolakCount"><?php echo $tolak_data['tolak']; ?></h2>
        </div>
        <div class="avatar">✕</div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
      <table id="reportTable">
        <thead>
          <tr>
            <th>Nama Pelapor</th>
            <th>Akun Dilaporkan</th>
            <th>Alasan</th>
            <th>Status</th>
            <th>Tanggal Diterima</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <?php if (mysqli_num_rows($reports_result) > 0): ?>
            <?php while ($report = mysqli_fetch_assoc($reports_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($report['reporter_name'] ?? 'Tidak diketahui'); ?></td>
                <td><?php echo htmlspecialchars($report['reported_user']); ?></td>
                <td>
                    <?php 
                    $reasons = [
                        'sara' => 'Mengandung SARA',
                        'kata-tidak-pantas' => 'Kata-kata tidak pantas',
                        'ujaran-kebencian' => 'Ujaran kebencian',
                        'hoaks' => 'Hoaks / Informasi palsu'
                    ];
                    echo $reasons[$report['reason']] ?? $report['reason'];
                    ?>
                </td>
                <td>
                    <span class="status <?php echo $report['status']; ?>">
                        <?php 
                        $status_text = [
                            'diproses' => 'Diproses',
                            'selesai' => 'Selesai',
                            'ditolak' => 'Ditolak'
                        ];
                        echo $status_text[$report['status']] ?? $report['status'];
                        ?>
                    </span>
                </td>
                <td data-date="<?php echo date('Y-m-d', strtotime($report['created_at'])); ?>">
                    <?php echo date('d M Y', strtotime($report['created_at'])); ?>
                </td>
                <td>
                    <button onclick="updateStatus(<?php echo $report['id']; ?>, 'selesai')" 
                            style="background: #0f7a32; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                        ✓ Selesai
                    </button>
                    <button onclick="updateStatus(<?php echo $report['id']; ?>, 'ditolak')"
                            style="background: #a70000; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                        ✕ Tolak
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">
                    Tidak ada laporan ditemukan
                </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- SIDEBAR -->
  <aside>
    <div class="panel">
      <h4>Jenis Pelanggaran</h4>
      <div class="stat-item">
          <span>Ujaran Kebencian</span>
          <strong><?php echo $jenis_stats['ujaran-kebencian'] ?? 0; ?></strong>
      </div>
      <div class="stat-item">
          <span>Informasi Hoaks</span>
          <strong><?php echo $jenis_stats['hoaks'] ?? 0; ?></strong>
      </div>
      <div class="stat-item">
          <span>Kata Kata Tidak Pantas</span>
          <strong><?php echo $jenis_stats['kata-tidak-pantas'] ?? 0; ?></strong>
      </div>
      <div class="stat-item">
          <span>Mengandung SARA</span>
          <strong><?php echo $jenis_stats['sara'] ?? 0; ?></strong>
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

// Update status laporan
function updateStatus(reportId, status) {
    if (confirm(`Apakah Anda yakin ingin mengubah status laporan?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_status.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'report_id';
        idInput.value = reportId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        form.appendChild(idInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>