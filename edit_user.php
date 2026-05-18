<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'connect.php';

$name      = $_SESSION['name'];
$user_type = $_SESSION['user_type'];
$error     = '';
$success   = '';

$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    header('Location: readrecords.php?tab=users');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `user` WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: readrecords.php?tab=users');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']   ?? '');
    $last_name  = trim($_POST['last_name']    ?? '');
    $email      = trim($_POST['email']        ?? '');
    $phone      = trim($_POST['phone_number'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (!$first_name || !$last_name || !$email) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM `user` WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            $error = 'This email is already registered to another account.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `user`
                    SET
                        first_name   = ?,
                        last_name    = ?,
                        email        = ?,
                        phone_number = ?,
                        is_active    = ?
                    WHERE user_id = ?
                ");

                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $phone,
                    $is_active,
                    $user_id
                ]);

                $success = 'User updated successfully!';

                $stmt = $pdo->prepare("SELECT * FROM `user` WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

            } catch (PDOException $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User — BusSync</title>
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

  .card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 2.5rem 2rem; width: 100%; max-width: 460px; }
  h1 { font-size: 1.35rem; font-weight: 600; color: var(--text); margin-bottom: .35rem; }
  .subtitle { font-size: .875rem; color: var(--muted); margin-bottom: 1.75rem; }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  label { display: block; font-size: .8rem; font-weight: 500; color: var(--text); margin-bottom: .3rem; }
  input[type="text"], input[type="email"], input[type="tel"] {
    width: 100%; padding: .65rem .9rem; border: 1px solid var(--border);
    border-radius: 9px; font-family: 'Sora', sans-serif; font-size: .9rem;
    color: var(--text); background: #fafafa; outline: none;
    transition: border-color .2s, box-shadow .2s; margin-bottom: 1rem;
  }
  input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(29,158,117,.15); background: #fff; }
  .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1rem; }
  .checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--brand); margin-bottom: 0; }
  .checkbox-row label { margin-bottom: 0; font-size: .875rem; }
  .btn { width: 100%; padding: .75rem; background: var(--brand); color: #fff; border: none; border-radius: 9px; font-family: 'Sora', sans-serif; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s, transform .1s; margin-top: .25rem; }
  .btn:hover { background: var(--brand-dk); }
  .btn:active { transform: scale(.98); }
  .alert { border-radius: 8px; padding: .6rem .9rem; font-size: .84rem; margin-bottom: 1.1rem; }
  .alert-error   { background: #FEF2F2; border: 1px solid #FECACA; color: var(--danger); }
  .alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
  .divider { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }
  .footer-link { text-align: center; font-size: .84rem; color: var(--muted); margin-top: 1.25rem; }
  .footer-link a { color: var(--brand); text-decoration: none; font-weight: 500; }
  .footer-link a:hover { text-decoration: underline; }

  @media (max-width: 900px) {
    .sidebar { width: 60px; }
    .sidebar .brand-name, .nav-item span, .user-info, .logout-btn span, .nav-label { display: none; }
    .main { margin-left: 60px; }
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
  <div class="card">
    <h1>Edit User</h1>
    <p class="subtitle">Update details for <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>.</p>

    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" action="edit_user.php?id=<?= $user_id ?>">

      <div class="row">
        <div>
          <label for="first_name">First name <span style="color:var(--danger)">*</span></label>
          <input type="text" id="first_name" name="first_name"
                 value="<?= htmlspecialchars($_POST['first_name'] ?? $user['first_name']) ?>" required>
        </div>
        <div>
          <label for="last_name">Last name <span style="color:var(--danger)">*</span></label>
          <input type="text" id="last_name" name="last_name"
                 value="<?= htmlspecialchars($_POST['last_name'] ?? $user['last_name']) ?>" required>
        </div>
      </div>

      <label for="email">Email address <span style="color:var(--danger)">*</span></label>
      <input type="email" id="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>

      <label for="phone_number">Phone number</label>
      <input type="tel" id="phone_number" name="phone_number"
             value="<?= htmlspecialchars($_POST['phone_number'] ?? $user['phone_number']) ?>">

      <div class="checkbox-row">
        <input type="checkbox" id="is_active" name="is_active"
               <?= ($user['is_active'] ? 'checked' : '') ?>>
        <label for="is_active">Account is active</label>
      </div>

      <button type="submit" class="btn">Save Changes</button>
    </form>

    <hr class="divider">
    <p class="footer-link"><a href="readrecords.php?tab=users">← Back to Records</a></p>
  </div>
</main>

</body>
</html>
