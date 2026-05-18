<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'connect.php';

$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$name      = $_SESSION['name'];

// ── Summary counts ─────────────────────────────────────────
$total_trips = $pdo->query("SELECT COUNT(*) FROM trip")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
$total_buses = $pdo->query("SELECT COUNT(*) FROM bus")->fetchColumn();

// ── Recent bookings ─────────────────────────────────────────
$recent = $pdo->query("
    SELECT b.booking_id, b.timestamp, b.no_show_flag,
           CONCAT(u.first_name,' ',u.last_name) AS student_name,
           t.departure, bs.bus_number
    FROM booking b
    JOIN student s  ON b.student_id = s.student_id
    JOIN `user` u   ON s.student_id = u.user_id
    JOIN trip t     ON b.trip_id    = t.trip_id
    JOIN bus bs     ON t.bus_id     = bs.bus_id
    ORDER BY b.timestamp DESC
    LIMIT 8
")->fetchAll();

// ── Upcoming trips ──────────────────────────────────────────
$trips = $pdo->query("
    SELECT t.trip_id, t.departure, t.available_seats,
           bs.bus_number, bs.capacity,
           CONCAT(u.first_name,' ',u.last_name) AS driver_name
    FROM trip t
    JOIN bus bs     ON t.bus_id    = bs.bus_id
    JOIN driver d   ON t.driver_id = d.driver_id
    JOIN `user` u   ON d.driver_id = u.user_id
    WHERE t.departure >= NOW()
    ORDER BY t.departure ASC
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — BusSync</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
   *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --brand: #1D9E75; --brand-dk: #0F6E56; --brand-lt: #E1F5EE;
    --sidebar-bg: #0F2318; --sidebar-w: 230px;
    --bg: #F4F6F3; --card: #FFFFFF;
    --text: #1a1a18; --muted: #6b7280;
    --border: #e5e7eb; --danger: #E24B4A;
    --radius: 12px;
  }
  body { font-family: 'Sora', sans-serif; background: var(--bg); display: flex; min-height: 100vh; }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w); background: var(--sidebar-bg);
    display: flex; flex-direction: column;
    padding: 1.5rem 1rem; position: fixed;
    top: 0; left: 0; bottom: 0; z-index: 10;
  }
  .sidebar .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; padding: 0 .5rem; }
  .sidebar .brand-icon {
    width: 34px; height: 34px; background: var(--brand);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
  }
  .sidebar .brand-icon svg { width: 18px; height: 18px; fill: #fff; }
  .sidebar .brand-name { font-family: 'Space Mono', monospace; font-size: 1rem; color: #fff; font-weight: 700; }

  .nav-label { font-size: .68rem; font-weight: 600; letter-spacing: .08em;
               color: rgba(255,255,255,.35); padding: .5rem .75rem; margin-top: .5rem; text-transform: uppercase; }
  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: .6rem .75rem; border-radius: 8px; text-decoration: none;
    font-size: .875rem; color: rgba(255,255,255,.65);
    transition: background .2s, color .2s; margin-bottom: 2px;
  }
  .nav-item:hover, .nav-item.active { background: rgba(29,158,117,.25); color: #fff; }
  .nav-item svg { width: 17px; height: 17px; fill: currentColor; flex-shrink: 0; }

  .sidebar-footer { margin-top: auto; }
  .user-pill {
    display: flex; align-items: center; gap: 10px;
    padding: .65rem .75rem; border-radius: 8px;
    background: rgba(255,255,255,.07); margin-bottom: .75rem;
  }
  .avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--brand); display: flex; align-items: center;
    justify-content: center; font-size: .75rem; font-weight: 600; color: #fff; flex-shrink: 0;
  }
  .user-info p { font-size: .8rem; color: #fff; line-height: 1.3; }
  .user-info span { font-size: .72rem; color: rgba(255,255,255,.45); }
  .logout-btn {
    display: flex; align-items: center; gap: 8px;
    padding: .55rem .75rem; border-radius: 8px; text-decoration: none;
    font-size: .84rem; color: rgba(255,255,255,.5);
    transition: background .2s, color .2s;
  }
  .logout-btn:hover { background: rgba(226,75,74,.2); color: #FCA5A5; }
  .logout-btn svg { width: 16px; height: 16px; fill: currentColor; }

  /* ── Main ── */
  .main { margin-left: var(--sidebar-w); flex: 1; padding: 2rem 1.75rem; }
  .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
  .topbar h1 { font-size: 1.3rem; font-weight: 600; color: var(--text); }
  .topbar .date { font-size: .85rem; color: var(--muted); }

  /* ── Stat cards ── */
  .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 2rem; }
  .stat-card {
    background: var(--card); border-radius: var(--radius);
    border: 1px solid var(--border); padding: 1.1rem 1.25rem;
  }
  .stat-card .label { font-size: .78rem; color: var(--muted); margin-bottom: .5rem; }
  .stat-card .value { font-size: 1.75rem; font-weight: 600; color: var(--text); line-height: 1; }
  .stat-card .sub { font-size: .75rem; color: var(--brand); margin-top: .35rem; }
  .stat-card.accent { background: var(--brand); border-color: var(--brand); }
  .stat-card.accent .label, .stat-card.accent .sub { color: rgba(255,255,255,.7); }
  .stat-card.accent .value { color: #fff; }

  /* ── Grid layout ── */
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

  /* ── Section cards ── */
  .section-card {
    background: var(--card); border-radius: var(--radius);
    border: 1px solid var(--border); padding: 1.25rem;
  }
  .section-card h2 { font-size: .95rem; font-weight: 600; color: var(--text); margin-bottom: 1rem; }

  /* ── Table ── */
  table { width: 100%; border-collapse: collapse; font-size: .84rem; }
  th { text-align: left; font-size: .73rem; font-weight: 600; letter-spacing: .04em;
       color: var(--muted); padding: 0 .75rem .6rem; text-transform: uppercase; }
  td { padding: .6rem .75rem; color: var(--text); border-top: 1px solid var(--border); }
  tr:hover td { background: #f9fafb; }

  /* ── Badge ── */
  .badge {
    display: inline-block; padding: 2px 9px; border-radius: 20px;
    font-size: .73rem; font-weight: 600;
  }
  .badge-ok  { background: #F0FDF4; color: #166534; }
  .badge-warn { background: #FEF2F2; color: #991B1B; }

  /* ── Trip card ── */
  .trip-card {
    border: 1px solid var(--border); border-radius: 10px;
    padding: .9rem 1rem; margin-bottom: .75rem;
  }
  .trip-card:last-child { margin-bottom: 0; }
  .trip-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .4rem; }
  .trip-bus { font-weight: 600; font-size: .9rem; color: var(--text); }
  .trip-seats { font-size: .8rem; color: var(--brand); font-weight: 500; }
  .trip-meta { font-size: .8rem; color: var(--muted); }

  @media (max-width: 900px) {
    .sidebar { width: 60px; }
    .sidebar .brand-name, .nav-item span, .user-info, .logout-btn span, .nav-label { display: none; }
    .main { margin-left: 60px; }
    .stats { grid-template-columns: 1fr 1fr; }
    .grid2 { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<!-- ── Sidebar ── -->
<nav class="sidebar">
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M4 16l2-8h12l2 8H4zm2 0v3h2v-1h8v1h2v-3H6zm2-10h8v2H8V6zm0 4h8v1H8v-1z"/></svg>
    </div>
    <span class="brand-name">BusSync</span>
  </div>

  <span class="nav-label">Menu</span>

  <a href="dashboard.php" class="nav-item active">
    <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 4h2v-2h2v2h2v2h-2v2h-2v-2h-2z"/></svg>
    <span>Dashboard</span>
  </a>
  <a href="readrecords.php" class="nav-item">
    <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span>Records</span>
  </a>

  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="avatar"><?= strtoupper(substr($name, 0, 2)) ?></div>
      <div class="user-info">
        <p><?= htmlspecialchars($name) ?></p>
        <span><?= ucfirst($user_type) ?></span>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">
      <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      <span>Logout</span>
    </a>
  </div>
</nav>

<!-- ── Main ── -->
<main class="main">
  <div class="topbar">
    <h1>Dashboard</h1>
    <span class="date"><?= date('l, F j, Y') ?></span>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card accent">
      <div class="label">Total Trips</div>
      <div class="value"><?= $total_trips ?></div>
      <div class="sub">Scheduled trips</div>
    </div>
    <div class="stat-card">
      <div class="label">Total Bookings</div>
      <div class="value"><?= $total_bookings ?></div>
      <div class="sub">All time</div>
    </div>
    <div class="stat-card">
      <div class="label">Registered Users</div>
      <div class="value"><?= $total_users ?></div>
      <div class="sub">Students &amp; Drivers</div>
    </div>
    <div class="stat-card">
      <div class="label">Fleet Size</div>
      <div class="value"><?= $total_buses ?></div>
      <div class="sub">Active buses</div>
    </div>
  </div>

  <!-- Bottom grid -->
  <div class="grid2">

    <!-- Recent Bookings -->
    <div class="section-card">
      <h2>Recent Bookings</h2>
      <?php if ($recent): ?>
      <table>
        <thead>
          <tr>
            <th>Student</th>
            <th>Bus</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= htmlspecialchars($r['bus_number']) ?></td>
            <td>
              <?php if ($r['no_show_flag']): ?>
                <span class="badge badge-warn">No-show</span>
              <?php else: ?>
                <span class="badge badge-ok">Booked</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p style="color:var(--muted);font-size:.87rem">No bookings yet.</p>
      <?php endif; ?>
    </div>

    <!-- Upcoming Trips -->
    <div class="section-card">
      <h2>Upcoming Trips</h2>
      <?php if ($trips): ?>
        <?php foreach ($trips as $t): ?>
        <div class="trip-card">
          <div class="trip-header">
            <span class="trip-bus">Bus <?= htmlspecialchars($t['bus_number']) ?></span>
            <span class="trip-seats"><?= $t['available_seats'] ?> seats left</span>
          </div>
          <div class="trip-meta">
            <?= date('M j, Y — g:i A', strtotime($t['departure'])) ?><br>
            Driver: <?= htmlspecialchars($t['driver_name']) ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--muted);font-size:.87rem">No upcoming trips.</p>
      <?php endif; ?>
    </div>

  </div>
</main>

</body>
</html>
