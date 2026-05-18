<?php

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require 'connect.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM `user` WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name']      = $user['first_name'] . ' ' . $user['last_name'];

            $pdo->prepare("UPDATE `user` SET last_login = NOW() WHERE user_id = ?")
                ->execute([$user['user_id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — BusSync</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  *,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

:root {
  --brand: #1d9e75;
  --brand-dk: #0f6e56;
  --brand-lt: #e1f5ee;
  --bg: #f4f6f3;
  --card: #ffffff;
  --text: #1a1a18;
  --muted: #6b7280;
  --border: #d1d5db;
  --danger: #e24b4a;
  --radius: 14px;
}

body {
  font-family: "Sora", sans-serif;
  background: var(--bg);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2rem;
}

.brand-icon {
  width: 38px;
  height: 38px;
  background: var(--brand);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-icon svg {
  width: 20px;
  height: 20px;
  fill: #fff;
}

.brand-name {
  font-family: "Space Mono", monospace;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.5px;
}

h1 {
  font-size: 1.35rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 0.35rem;
}
.subtitle {
  font-size: 0.875rem;
  color: var(--muted);
  margin-bottom: 1.75rem;
}

label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 0.35rem;
}

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 0.65rem 0.9rem;
  border: 1px solid var(--border);
  border-radius: 9px;
  font-family: "Sora", sans-serif;
  font-size: 0.9rem;
  color: var(--text);
  background: #fafafa;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  outline: none;
  margin-bottom: 1.1rem;
}

input:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(29, 158, 117, 0.15);
  background: #fff;
}

.btn {
  width: 100%;
  padding: 0.75rem;
  background: var(--brand);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-family: "Sora", sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    background 0.2s,
    transform 0.1s;
  margin-top: 0.25rem;
}

.btn:hover {
  background: var(--brand-dk);
}
.btn:active {
  transform: scale(0.98);
}

.error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: var(--danger);
  border-radius: 8px;
  padding: 0.6rem 0.9rem;
  font-size: 0.84rem;
  margin-bottom: 1.1rem;
}

.footer-link {
  text-align: center;
  font-size: 0.84rem;
  color: var(--muted);
  margin-top: 1.25rem;
}

.footer-link a {
  color: var(--brand);
  text-decoration: none;
  font-weight: 500;
}
.footer-link a:hover {
  text-decoration: underline;
}

.divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 1.5rem 0;
}

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

  <h1>Welcome back</h1>
  <p class="subtitle">Sign in to your account to continue.</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <label for="email">Email address</label>
    <input type="email" id="email" name="email" placeholder="you@email.com"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="••••••••" required>

    <button type="submit" class="btn">Sign In</button>
  </form>

  <hr class="divider">
  <p class="footer-link">
    Don't have an account? <a href="register.php">Register here</a>
  </p>
</div>
</body>
</html>
