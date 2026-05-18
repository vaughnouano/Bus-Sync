<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require 'connect.php';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name']  ?? '');
    $email        = trim($_POST['email']       ?? '');
    $phone        = trim($_POST['phone']       ?? '');
    $user_type    = $_POST['user_type']        ?? 'student';
    $password     = $_POST['password']         ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!$first_name || !$last_name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
       // Check duplicate email
$stmt = $pdo->prepare("SELECT user_id FROM `user` WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {

    $error = 'This email is already registered.';

} else {

    try {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO `user`
            (
                first_name,
                last_name,
                email,
                phone_number,
                user_type,
                password,
                is_active,
                created_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, 1, NOW()
            )
        ");

        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone,
            $user_type,
            $hashed
        ]);

        $user_id = $pdo->lastInsertId();

        // Create subtype record
        if ($user_type === 'student') {

            $stmt = $pdo->prepare("
                INSERT INTO student (student_id)
                VALUES (?)
            ");

            $stmt->execute([$user_id]);

        } elseif ($user_type === 'driver') {

            $stmt = $pdo->prepare("
                INSERT INTO driver
                (
                    driver_id,
                    license_number,
                    hire_date
                )
                VALUES
                (
                    ?, ?, CURDATE()
                )
            ");

            $stmt->execute([
                $user_id,
                'PENDING-' . $user_id
            ]);
        }

        $success = 'Account created successfully! You can now log in.';

    } catch (PDOException $e) {

        $error = $e->getMessage();

    }
            $success = 'Account created successfully! You can now log in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — BusSync</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --brand: #1D9E75; --brand-dk: #0F6E56; --bg: #F4F6F3;
    --card: #FFFFFF; --text: #1a1a18; --muted: #6b7280;
    --border: #d1d5db; --danger: #E24B4A; --success: #1D9E75;
    --radius: 14px;
  }
  body {
    font-family: 'Sora', sans-serif; background: var(--bg);
    min-height: 100vh; display: flex; align-items: center;
    justify-content: center; padding: 1.5rem;
  }
  .card {
    background: var(--card); border-radius: var(--radius);
    border: 1px solid var(--border); padding: 2.5rem 2rem;
    width: 100%; max-width: 460px;
  }
  .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
  .brand-icon {
    width: 38px; height: 38px; background: var(--brand);
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
  }
  .brand-icon svg { width: 20px; height: 20px; fill: #fff; }
  .brand-name { font-family: 'Space Mono', monospace; font-size: 1.1rem; font-weight: 700; color: var(--text); }
  h1 { font-size: 1.35rem; font-weight: 600; color: var(--text); margin-bottom: .35rem; }
  .subtitle { font-size: .875rem; color: var(--muted); margin-bottom: 1.75rem; }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  label { display: block; font-size: .8rem; font-weight: 500; color: var(--text); margin-bottom: .3rem; }
  input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select {
    width: 100%; padding: .65rem .9rem; border: 1px solid var(--border);
    border-radius: 9px; font-family: 'Sora', sans-serif; font-size: .9rem;
    color: var(--text); background: #fafafa; outline: none;
    transition: border-color .2s, box-shadow .2s; margin-bottom: 1rem;
  }
  input:focus, select:focus {
    border-color: var(--brand); box-shadow: 0 0 0 3px rgba(29,158,117,.15); background: #fff;
  }
  select { appearance: none; cursor: pointer; }
  .btn {
    width: 100%; padding: .75rem; background: var(--brand); color: #fff;
    border: none; border-radius: 9px; font-family: 'Sora', sans-serif;
    font-size: .95rem; font-weight: 600; cursor: pointer;
    transition: background .2s, transform .1s; margin-top: .25rem;
  }
  .btn:hover { background: var(--brand-dk); }
  .btn:active { transform: scale(.98); }
  .alert {
    border-radius: 8px; padding: .6rem .9rem;
    font-size: .84rem; margin-bottom: 1.1rem;
  }
  .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: var(--danger); }
  .alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
  .divider { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }
  .footer-link { text-align: center; font-size: .84rem; color: var(--muted); margin-top: 1.25rem; }
  .footer-link a { color: var(--brand); text-decoration: none; font-weight: 500; }
  .footer-link a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M4 16l2-8h12l2 8H4zm2 0v3h2v-1h8v1h2v-3H6zm2-10h8v2H8V6zm0 4h8v1H8v-1z"/></svg>
    </div>
    <span class="brand-name">BusSync</span>
  </div>

  <h1>Create an account</h1>
  <p class="subtitle">Join BusSync to book and manage your trips.</p>

  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" action="register.php">
    <div class="row">
      <div>
        <label for="first_name">First name <span style="color:var(--danger)">*</span></label>
        <input type="text" id="first_name" name="first_name"
               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
      </div>
      <div>
        <label for="last_name">Last name <span style="color:var(--danger)">*</span></label>
        <input type="text" id="last_name" name="last_name"
               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
      </div>
    </div>

    <label for="email">Email address <span style="color:var(--danger)">*</span></label>
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

    <label for="phone">Phone number</label>
    <input type="tel" id="phone" name="phone"
           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

    <label for="user_type">Account type <span style="color:var(--danger)">*</span></label>
    <select id="user_type" name="user_type">
      <option value="student" <?= ($_POST['user_type'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
      <option value="driver"  <?= ($_POST['user_type'] ?? '') === 'driver'  ? 'selected' : '' ?>>Driver</option>
    </select>

    <div class="row">
      <div>
        <label for="password">Password <span style="color:var(--danger)">*</span></label>
        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
      </div>
      <div>
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
      </div>
    </div>

    <button type="submit" class="btn">Create Account</button>
  </form>
  <?php endif; ?>

  <hr class="divider">
  <p class="footer-link">
    Already have an account? <a href="login.php">Sign in</a>
  </p>
</div>
</body>
</html>
