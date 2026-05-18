<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'connect.php';

$name      = $_SESSION['name'];
$user_type = $_SESSION['user_type'];

// ── Delete booking ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking_id'])) {
    $delete_id = (int)$_POST['delete_booking_id'];
    $stmt = $pdo->prepare("DELETE FROM booking WHERE booking_id = ?");
    $stmt->execute([$delete_id]);
    header('Location: readrecords.php?tab=bookings');
    exit;
}

// Tab & Pagination
$tab      = $_GET['tab'] ?? 'bookings';
$per_page = 5;
$page     = max(1, (int)($_GET['page'] ?? 1));

// Count totals
switch ($tab) {
    case 'users':
        $total = $pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn(); break;
    case 'trips':
        $total = $pdo->query("SELECT COUNT(*) FROM trip")->fetchColumn(); break;
    case 'noshows':
        $total = $pdo->query("SELECT COUNT(*) FROM noshow_record")->fetchColumn(); break;
    default:
        $total = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn(); break;
}

$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Fetch paginated data
$data = [];
switch ($tab) {
    case 'users':
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, user_type, phone_number, is_active, created_at FROM `user` ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); $data = $stmt->fetchAll(); break;

    case 'trips':
        $stmt = $pdo->prepare("SELECT t.trip_id, t.departure, t.available_seats, bs.bus_number, bs.capacity, CONCAT(u.first_name,' ',u.last_name) AS driver_name FROM trip t JOIN bus bs ON t.bus_id=bs.bus_id JOIN driver d ON t.driver_id=d.driver_id JOIN `user` u ON d.driver_id=u.user_id ORDER BY t.departure DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); $data = $stmt->fetchAll(); break;

    case 'noshows':
        $stmt = $pdo->prepare("SELECT nr.record_id, nr.recorded_date, CONCAT(u.first_name,' ',u.last_name) AS student_name, nr.booking_id FROM noshow_record nr JOIN student s ON nr.student_id=s.student_id JOIN `user` u ON s.student_id=u.user_id ORDER BY nr.recorded_date DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); $data = $stmt->fetchAll(); break;

    default:
        $stmt = $pdo->prepare("SELECT b.booking_id, b.timestamp, b.no_show_flag, CONCAT(u.first_name,' ',u.last_name) AS student_name, bs.bus_number, t.departure FROM booking b JOIN student s ON b.student_id=s.student_id JOIN `user` u ON s.student_id=u.user_id JOIN trip t ON b.trip_id=t.trip_id JOIN bus bs ON t.bus_id=bs.bus_id ORDER BY b.timestamp DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); $data = $stmt->fetchAll(); break;
}

function page_url($tab, $p) {
    return '?tab=' . urlencode($tab) . '&page=' . (int)$p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Records — BusSync</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --brand: #1D9E75; --brand-dk: #0F6E56; --sidebar-bg: #0F2318; --sidebar-w: 230px;
    --bg: #F4F6F3; --card: #FFFFFF; --text: #1a1a18; --muted: #6b7280;
    --border: #e5e7eb; --danger: #E24B4A; --radius: 12px;
  }
  body { font-family: 'Sora', sans-serif; background: var(--bg); display: flex; min-height: 100vh; }
  .sidebar { width: var(--sidebar-w); background: var(--sidebar-bg); display: flex; flex-direction: column; padding: 1.5rem 1rem; position: fixed; top: 0; left: 0; bottom: 0; }
  .sidebar .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; padding: 0 .5rem; }
  .sidebar .brand-icon { width: 34px; height: 34px; background: var(--brand); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
  .sidebar .brand-icon svg { width: 18px; height: 18px; fill: #fff; }
  .sidebar .brand-name { font-family: 'Space Mono', monospace; font-size: 1rem; color: #fff; font-weight: 700; }
  .nav-label { font-size: .68rem; font-weight: 600; letter-spacing: .08em; color: rgba(255,255,255,.35); padding: .5rem .75rem; margin-top: .5rem; text-transform: uppercase; }
  .nav-item { display: flex; align-items: center; gap: 10px; padding: .6rem .75rem; border-radius: 8px; text-decoration: none; font-size: .875rem; color: rgba(255,255,255,.65); transition: background .2s, color .2s; margin-bottom: 2px; }
  .nav-item:hover, .nav-item.active { background: rgba(29,158,117,.25); color: #fff; }
  .nav-item svg { width: 17px; height: 17px; fill: currentColor; flex-shrink: 0; }
  .sidebar-footer { margin-top: auto; }
  .user-pill { display: flex; align-items: center; gap: 10px; padding: .65rem .75rem; border-radius: 8px; background: rgba(255,255,255,.07); margin-bottom: .75rem; }
  .avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--brand); display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 600; color: #fff; }
  .user-info p { font-size: .8rem; color: #fff; } .user-info span { font-size: .72rem; color: rgba(255,255,255,.45); }
  .logout-btn { display: flex; align-items: center; gap: 8px; padding: .55rem .75rem; border-radius: 8px; text-decoration: none; font-size: .84rem; color: rgba(255,255,255,.5); transition: background .2s, color .2s; }
  .logout-btn:hover { background: rgba(226,75,74,.2); color: #FCA5A5; }
  .logout-btn svg { width: 16px; height: 16px; fill: currentColor; }
  .main { margin-left: var(--sidebar-w); flex: 1; padding: 2rem 1.75rem; }
  .tabs { display: flex; gap: 4px; margin-bottom: 1.5rem; background: #e9ede8; border-radius: 10px; padding: 4px; width: fit-content; }
  .tab { padding: .45rem 1rem; border-radius: 7px; text-decoration: none; font-size: .85rem; font-weight: 500; color: var(--muted); transition: background .2s, color .2s; }
  .tab.active { background: #fff; color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,.08); }
  .tab:hover:not(.active) { color: var(--text); }
  .table-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; }
  .table-card table { width: 100%; border-collapse: collapse; font-size: .84rem; }
  .table-card th { text-align: left; font-size: .73rem; font-weight: 600; letter-spacing: .04em; color: var(--muted); padding: .75rem 1rem; text-transform: uppercase; background: #f9fafb; border-bottom: 1px solid var(--border); }
  .table-card td { padding: .7rem 1rem; color: var(--text); border-top: 1px solid var(--border); }
  .table-card tr:hover td { background: #f9fafb; }
  .empty { padding: 2.5rem; text-align: center; color: var(--muted); font-size: .9rem; }
  .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: .73rem; font-weight: 600; }
  .badge-ok { background: #F0FDF4; color: #166534; }
  .badge-warn { background: #FEF2F2; color: #991B1B; }
  .badge-blue { background: #EFF6FF; color: #1E40AF; }
  .btn { padding: .55rem 1.1rem; background: var(--brand); color: #fff; border: none; border-radius: 9px; font-family: 'Sora', sans-serif; font-size: .875rem; font-weight: 600; cursor: pointer; transition: background .2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .btn:hover { background: var(--brand-dk); }

  /* ── Pagination ── */
  .pagination-wrap { display: flex; align-items: center; justify-content: space-between; padding: .85rem 1rem; border-top: 1px solid var(--border); background: #f9fafb; }
  .pagination-info { font-size: .78rem; color: var(--muted); }
  .pagination { display: flex; align-items: center; gap: 4px; }
  .pg-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 10px; border-radius: 8px; text-decoration: none; font-size: .8rem; font-weight: 500; color: var(--text); background: #fff; border: 1px solid var(--border); transition: background .15s, color .15s, border-color .15s; font-family: 'Sora', sans-serif; cursor: pointer; white-space: nowrap; }
  .pg-btn:hover:not(.pg-disabled):not(.pg-active) { background: var(--brand); color: #fff; border-color: var(--brand); }
  .pg-btn.pg-active { background: var(--brand); color: #fff; border-color: var(--brand); cursor: default; font-weight: 700; }
  .pg-btn.pg-disabled { color: #c5c9cc; pointer-events: none; cursor: default; border-color: #e9ede8; background: #f9fafb; }
  .pg-ellipsis { font-size: .85rem; color: var(--muted); padding: 0 4px; line-height: 34px; }

  @media (max-width: 900px) {
    .sidebar { width: 60px; }
    .sidebar .brand-name, .nav-item span, .user-info, .logout-btn span, .nav-label { display: none; }
    .main { margin-left: 60px; padding: 1.25rem; }
    .pagination-wrap { flex-direction: column; gap: .6rem; align-items: flex-start; }
  }
</style>
</head>
<body>

<nav class="sidebar">
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M4 16l2-8h12l2 8H4zm2 0v3h2v-1h8v1h2v-3H6zm2-10h8v2H8V6zm0 4h8v1H8v-1z"/></svg>
    </div>
    <span class="brand-name">BusSync</span>
  </div>
  <span class="nav-label">Menu</span>
  <a href="dashboard.php" class="nav-item">
    <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 4h2v-2h2v2h2v2h-2v2h-2v-2h-2z"/></svg>
    <span>Dashboard</span>
  </a>
  <a href="readrecords.php" class="nav-item active">
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

<main class="main">

<?php if (isset($_SESSION['booking_success'])): ?>
  <div style="margin-bottom:1rem; padding:.75rem 1rem; background:#F0FDF4; border-radius:8px; color:#166534; font-size:.85rem;"><?= htmlspecialchars($_SESSION['booking_success']) ?></div>
  <?php unset($_SESSION['booking_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['booking_error'])): ?>
  <div style="margin-bottom:1rem; padding:.75rem 1rem; background:#FEF2F2; border-radius:8px; color:#991B1B; font-size:.85rem;"><?= htmlspecialchars($_SESSION['booking_error']) ?></div>
  <?php unset($_SESSION['booking_error']); ?>
<?php endif; ?>

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
    <h1 style="font-size:1.3rem; font-weight:600; color:var(--text);">Records</h1>
    <?php if ($tab === 'trips' && $user_type !== 'student'): ?>
      <a href="create_trip.php" class="btn">+ New Trip</a>
    <?php endif; ?>
  </div>

  <div class="tabs">
    <a class="tab <?= $tab==='bookings' ? 'active' : '' ?>" href="?tab=bookings&page=1">Bookings</a>
    <a class="tab <?= $tab==='trips'    ? 'active' : '' ?>" href="?tab=trips&page=1">Trips</a>
    <a class="tab <?= $tab==='users'    ? 'active' : '' ?>" href="?tab=users&page=1">Users</a>
    <a class="tab <?= $tab==='noshows'  ? 'active' : '' ?>" href="?tab=noshows&page=1">No-shows</a>
  </div>

  <div class="table-card">

    <?php if (empty($data)): ?>
      <div class="empty">No records found.</div>

    <?php elseif ($tab === 'bookings'): ?>
      <table>
        <thead><tr><th>#</th><th>Student</th><th>Bus</th><th>Departure</th><th>Booked At</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($data as $r): ?>
          <tr>
            <td><?= $r['booking_id'] ?></td>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= htmlspecialchars($r['bus_number']) ?></td>
            <td><?= date('M j, Y g:i A', strtotime($r['departure'])) ?></td>
            <td><?= date('M j, Y g:i A', strtotime($r['timestamp'])) ?></td>
            <td><?= $r['no_show_flag'] ? '<span class="badge badge-warn">No-show</span>' : '<span class="badge badge-ok">Booked</span>' ?></td>
            <td>
              <form method="POST" action="readrecords.php?tab=bookings&page=<?= $page ?>" style="margin:0;">
                <input type="hidden" name="delete_booking_id" value="<?= $r['booking_id'] ?>">
                <button type="submit" onclick="return confirm('Delete booking #<?= $r['booking_id'] ?> for <?= htmlspecialchars(addslashes($r['student_name'])) ?>? This cannot be undone.')"
                        style="background:none;border:none;cursor:pointer;font-size:.8rem;color:var(--danger);font-weight:500;font-family:'Sora',sans-serif;padding:0;">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'trips'): ?>
      <table>
        <thead><tr><th>#</th><th>Bus</th><th>Driver</th><th>Departure</th><th>Seats Left</th><th>Capacity</th><?php if ($user_type==='student'): ?><th>Book</th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($data as $r): ?>
          <tr>
            <td><?= $r['trip_id'] ?></td>
            <td><?= htmlspecialchars($r['bus_number']) ?></td>
            <td><?= htmlspecialchars($r['driver_name']) ?></td>
            <td><?= date('M j, Y g:i A', strtotime($r['departure'])) ?></td>
            <td><?= $r['available_seats'] ?></td>
            <td><?= $r['capacity'] ?></td>
            <td><?php if ($user_type==='student' && $r['available_seats']>0): ?>
              <form method="POST" action="book_trip.php" style="margin:0;">
                <input type="hidden" name="trip_id" value="<?= $r['trip_id'] ?>">
                <button type="submit" class="btn" style="padding:0.3rem 0.7rem;font-size:0.75rem;">Book</button>
              </form>
            <?php elseif ($user_type==='student'): ?><span style="color:var(--danger);">Full</span><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'users'): ?>
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Type</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($data as $r): ?>
          <tr>
            <td><?= $r['user_id'] ?></td>
            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><span class="badge badge-blue"><?= ucfirst($r['user_type']) ?></span></td>
            <td><?= htmlspecialchars($r['phone_number'] ?: '—') ?></td>
            <td><?= $r['is_active'] ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-warn">Inactive</span>' ?></td>
            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            <td><a href="edit_user.php?id=<?= $r['user_id'] ?>" style="font-size:.8rem;color:var(--brand);text-decoration:none;font-weight:500;">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'noshows'): ?>
      <table>
        <thead><tr><th>#</th><th>Student</th><th>Booking ID</th><th>Recorded Date</th></tr></thead>
        <tbody>
          <?php foreach ($data as $r): ?>
          <tr>
            <td><?= $r['record_id'] ?></td>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= $r['booking_id'] ?></td>
            <td><?= date('M j, Y', strtotime($r['recorded_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php if (!empty($data)): ?>
    <!-- Pagination Footer -->
    <div class="pagination-wrap">
      <span class="pagination-info">
        <?php
          $from = $offset + 1;
          $to   = min($offset + $per_page, $total);
          echo "Showing {$from}–{$to} of {$total} record" . ($total !== 1 ? 's' : '');
        ?>
      </span>

      <nav class="pagination" aria-label="Pagination">
        <!-- Prev -->
        <?php if ($page <= 1): ?>
          <span class="pg-btn pg-disabled">&#8592; Prev</span>
        <?php else: ?>
          <a class="pg-btn" href="<?= page_url($tab, $page - 1) ?>">&#8592; Prev</a>
        <?php endif; ?>

        <?php
          // Window of page numbers with ellipsis
          $window = 1;
          $pages_to_show = [];
          for ($i = 1; $i <= $total_pages; $i++) {
              if ($i === 1 || $i === $total_pages || ($i >= $page - $window && $i <= $page + $window)) {
                  $pages_to_show[] = $i;
              }
          }
          $prev_p = null;
          foreach ($pages_to_show as $p):
              if ($prev_p !== null && $p - $prev_p > 1): ?>
                <span class="pg-ellipsis">&hellip;</span>
          <?php endif;
              if ($p === $page): ?>
                <span class="pg-btn pg-active"><?= $p ?></span>
          <?php else: ?>
                <a class="pg-btn" href="<?= page_url($tab, $p) ?>"><?= $p ?></a>
          <?php endif;
              $prev_p = $p;
          endforeach;
        ?>

        <!-- Next -->
        <?php if ($page >= $total_pages): ?>
          <span class="pg-btn pg-disabled">Next &#8594;</span>
        <?php else: ?>
          <a class="pg-btn" href="<?= page_url($tab, $page + 1) ?>">Next &#8594;</a>
        <?php endif; ?>
      </nav>
    </div>
    <?php endif; ?>

  </div><!-- /.table-card -->
</main>
</body>
</html>
