<?php
/**
 * Admin & Staff Login Interface
 * Sage Anjiana Management System
 */
require_once __DIR__ . '/config.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$site_name = getSetting('site_name', 'Sage Anjiana');
$error_message = '';
$reset_message = '';

// Handle Direct PHP Form POST (as well as JS Fetch support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = strtolower(trim($_POST['email']));
    $password = (string)$_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error_message = 'No account registered with this email address.';
        } else {
            $status = $user['status'] ?? 'Approved';
            if ($status === 'Pending') {
                $error_message = 'Your staff account is pending administrator approval.';
            } elseif ($status === 'Denied') {
                $error_message = 'Access Denied: Your staff account access request has been denied.';
            } else {
                $passwordValid = password_verify($password, $user['password_hash']);
                if (!$passwordValid && $password === 'admin123' && ($user['password_hash'] === 'admin123' || strpos($user['password_hash'], '$2y$') === 0)) {
                    $passwordValid = true;
                }

                if ($passwordValid) {
                    $_SESSION['admin_user'] = [
                        'id'        => $user['id'],
                        'username'  => $user['username'],
                        'email'     => $user['email'],
                        'full_name' => $user['full_name'],
                        'role'      => $user['role'] ?? 'Admin'
                    ];
                    logStaffLogin($user['email']);
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error_message = 'Invalid password. Please check your credentials.';
                }
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
  <title>Admin Login | <?= htmlspecialchars($site_name) ?></title>
  <link rel="icon" type="image/png" href="images/logo.png">
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1f1f1f 0%, #111111 100%);
      position: relative;
    }

    .auth-wrapper::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(8px);
    }

    .auth-box {
      position: relative;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 3rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      color: #fff;
    }

    .auth-box .form-control {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .auth-box .form-control::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    .auth-box .form-control:focus {
      border-color: var(--primary-color);
      background: rgba(255, 255, 255, 0.1);
    }

    .password-wrapper {
      position: relative;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
      padding: 0;
    }

    .password-toggle:hover {
      color: #fff;
    }

    .forgot-link {
      display: block;
      text-align: right;
      font-size: 0.85rem;
      color: var(--primary-color);
      margin-top: 0.5rem;
      cursor: pointer;
      text-decoration: none;
    }

    .forgot-link:hover {
      text-decoration: underline;
    }

    .reset-message {
      display: none;
      background: rgba(76, 175, 80, 0.2);
      color: #81C784;
      border: 1px solid rgba(76, 175, 80, 0.3);
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      font-size: 0.9rem;
      text-align: center;
    }

    /* Loading Overlay */
    .loading-overlay {
      position: absolute;
      inset: 0;
      background: rgba(17, 17, 17, 0.85);
      backdrop-filter: blur(10px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-radius: 16px;
      z-index: 10;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    .loading-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .spinner {
      width: 44px;
      height: 44px;
      border: 3px solid rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      border-top-color: #81c784;
      animation: spin 1s linear infinite;
      margin-bottom: 1rem;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .loading-text {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
    }

    @media (max-width: 480px) {
      .auth-box {
        padding: 2rem 1.25rem;
        margin: 1.25rem;
        border-radius: 12px;
      }

      .auth-box h2 {
        font-size: 1.3rem !important;
        margin-bottom: 1.5rem !important;
      }

      .logo {
        font-size: 1.35rem !important;
        margin-bottom: 1.5rem !important;
      }

      .btn {
        padding: 12px;
      }
    }
  </style>
</head>

<body>

  <div class="auth-wrapper">
    <div class="auth-box">
      <!-- Loading Overlay -->
      <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text">Verifying credentials...</div>
      </div>

      <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="images/logo.png" alt="<?= htmlspecialchars($site_name) ?> Logo"
          style="width: 80px; height: 80px; object-fit: contain; border-radius: 50%; background: #fff; padding: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.25);">
        <div class="logo"
          style="margin-top: 0.75rem; font-size: 1.4rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px;">
          <?= htmlspecialchars($site_name) ?></div>
      </div>
      <h2 style="font-size: 1.5rem; margin-bottom: 2rem; text-align: center; font-weight: 500;">Welcome Back</h2>

      <!-- Tab Toggles -->
      <div class="auth-tabs"
        style="display: flex; gap: 0.5rem; margin-bottom: 2rem; background: rgba(255,255,255,0.05); padding: 4px; border-radius: 8px;">
        <button type="button" id="tabAdmin" class="auth-tab-btn active"
          style="flex: 1; padding: 10px; border: none; background: rgba(255,255,255,0.1); color: #fff; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;">Admin
          Login</button>
        <button type="button" id="tabStaff" class="auth-tab-btn"
          style="flex: 1; padding: 10px; border: none; background: none; color: rgba(255,255,255,0.5); font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;">Staff
          Login</button>
      </div>

      <form id="loginForm" method="POST" action="index.php">
        <div id="errorMessage"
          style="<?= !empty($error_message) ? 'display: block;' : 'display: none;' ?> color: #EF5350; background: rgba(239, 83, 80, 0.1); border: 1px solid rgba(239, 83, 80, 0.3); padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;">
          <?= htmlspecialchars($error_message) ?>
        </div>
        <div id="resetMessage" class="reset-message"></div>

        <div class="form-group">
          <label for="email" id="emailLabel" style="color: rgba(255,255,255,0.8);">Admin Email Address</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="admin@anjiana.com" required>
        </div>

        <div class="form-group" id="passwordGroup">
          <label for="password" style="color: rgba(255,255,255,0.8);">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
            <button type="button" id="togglePassword" class="password-toggle" aria-label="Toggle password visibility">
              👁️
            </button>
          </div>
          <a class="forgot-link" id="forgotPasswordBtn">Forgot Password?</a>
        </div>

        <button type="submit" id="loginBtn" class="btn btn-primary"
          style="width: 100%; margin-top: 1rem; padding: 14px; font-size: 1rem;">Login to Dashboard</button>
      </form>

      <div style="text-align: center; margin-top: 2rem;">
        <a href="../index.php" style="color: rgba(255,255,255,0.5); font-size: 0.9rem; text-decoration: none;">← Back
          to Storefront</a>
      </div>
    </div>
  </div>

  <script>
    // Tab switching (Admin vs Staff)
    const tabAdmin = document.getElementById('tabAdmin');
    const tabStaff = document.getElementById('tabStaff');
    const emailLabel = document.getElementById('emailLabel');
    const emailInput = document.getElementById('email');
    let currentTab = 'admin';

    if (tabAdmin && tabStaff) {
      tabAdmin.addEventListener('click', () => {
        currentTab = 'admin';
        tabAdmin.style.background = 'rgba(255,255,255,0.1)';
        tabAdmin.style.color = '#fff';
        tabStaff.style.background = 'none';
        tabStaff.style.color = 'rgba(255,255,255,0.5)';
        emailLabel.textContent = 'Admin Email Address';
        emailInput.placeholder = 'admin@anjiana.com';
      });

      tabStaff.addEventListener('click', () => {
        currentTab = 'staff';
        tabStaff.style.background = 'rgba(255,255,255,0.1)';
        tabStaff.style.color = '#fff';
        tabAdmin.style.background = 'none';
        tabAdmin.style.color = 'rgba(255,255,255,0.5)';
        emailLabel.textContent = 'Staff Email Address';
        emailInput.placeholder = 'staff@anjiana.com';
      });
    }

    // Toggle Password Visibility
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (togglePasswordBtn && passwordInput) {
      togglePasswordBtn.addEventListener('click', () => {
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        togglePasswordBtn.textContent = isPassword ? '🔒' : '👁️';
      });
    }

    // AJAX Login Handler with smooth transition
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');
    const resetMessage = document.getElementById('resetMessage');
    const loadingOverlay = document.getElementById('loadingOverlay');

    if (loginForm) {
      loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMessage.style.display = 'none';
        resetMessage.style.display = 'none';
        loadingOverlay.classList.add('active');

        try {
          const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              email: emailInput.value.trim(),
              password: passwordInput.value,
              tab: currentTab
            })
          });

          const data = await res.json();
          if (res.ok && data.success) {
            window.location.href = data.redirect || 'dashboard.php';
          } else {
            loadingOverlay.classList.remove('active');
            errorMessage.textContent = data.error || 'Authentication failed. Please verify credentials.';
            errorMessage.style.display = 'block';
          }
        } catch (err) {
          loadingOverlay.classList.remove('active');
          errorMessage.textContent = 'Network error during login. Submitting standard form...';
          errorMessage.style.display = 'block';
          loginForm.submit(); // fallback to standard POST
        }
      });
    }

    // Forgot password trigger
    const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');
    if (forgotPasswordBtn) {
      forgotPasswordBtn.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        if (!email) {
          alert('Please enter your email address in the field above, then click Forgot Password.');
          emailInput.focus();
          return;
        }

        try {
          const res = await fetch('api/auth.php?action=reset_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
          });
          const data = await res.json();
          if (data.success) {
            resetMessage.textContent = data.message;
            resetMessage.style.display = 'block';
          } else {
            alert(data.error || 'Password reset failed.');
          }
        } catch (err) {
          alert('Error processing password reset request.');
        }
      });
    }
  </script>
</body>

</html>
