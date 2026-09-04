<?php
/**
 * Admin Login Page
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['full_name'];

                setFlash('success', 'Welcome back, ' . $admin['full_name'] . '!');
                header("Location: index.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'System error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Anjiana Store</title>
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    body {
      background: var(--bg-color);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .login-box {
      width: 100%;
      max-width: 420px;
      background: var(--card-bg, #fff);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>

  <div class="login-box">
    <div style="text-align: center; margin-bottom: 2rem;">
      <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--primary-color);">ANJIANA ADMIN</h1>
      <p class="text-muted" style="font-size: 0.9rem; margin-top: 0.3rem;">Sign in to manage your clothing store</p>
    </div>

    <?php if ($error): ?>
      <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;">
        ✕ <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div style="margin-bottom: 1.25rem;">
        <label for="username" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Username or Email</label>
        <input type="text" name="username" id="username" class="form-control" required autofocus placeholder="admin" value="<?= e($_POST['username'] ?? '') ?>">
      </div>

      <div style="margin-bottom: 1.75rem;">
        <label for="password" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Password</label>
        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; border-radius: 6px;">
        Sign In to Dashboard →
      </button>
    </form>

    <div style="margin-top: 2rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
      <a href="../index.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">← Return to Storefront</a>
    </div>

    <div style="margin-top: 1rem; background: var(--bg-alt, #f8f9fa); padding: 0.75rem; border-radius: 6px; font-size: 0.78rem; color: var(--text-muted); text-align: center;">
      Default Credentials: <code>admin</code> / <code>admin123</code>
    </div>
  </div>

</body>
</html>
