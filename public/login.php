<?php
require_once __DIR__ . '/../includes/config.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $st->execute([$username]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: /index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects. Vérifiez votre nom d\'utilisateur et mot de passe.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — Omega Tech Auto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --black:#0A0A0A;--gold:#C9A84C;--gold-light:#E8C86A;--gold-pale:#FBF5E6;
  --white:#FFFFFF;--gray-50:#F8F8FA;--gray-100:#F0F0F4;--gray-200:#E2E2EA;
  --gray-400:#9898A8;--gray-700:#3A3A48;--gray-900:#1A1A26;
  --danger:#DC2626;
}
html,body{height:100%;font-family:'Inter',sans-serif}
body{
  display:grid;
  grid-template-columns:1fr 480px;
  min-height:100vh;
  background:var(--gray-50);
}
/* LEFT PANEL — Visual */
.login-visual{
  background:var(--black);
  display:flex;flex-direction:column;
  justify-content:center;align-items:flex-start;
  padding:60px;
  position:relative;overflow:hidden;
}
.login-visual::before{
  content:'';
  position:absolute;top:-100px;right:-100px;
  width:500px;height:500px;
  background:radial-gradient(circle,rgba(201,168,76,.12) 0%,transparent 70%);
  pointer-events:none;
}
.login-visual::after{
  content:'AUTO';
  position:absolute;bottom:-30px;left:-10px;
  font-family:'Space Grotesk',sans-serif;
  font-size:180px;font-weight:900;
  color:rgba(255,255,255,.025);
  pointer-events:none;
  letter-spacing:-.03em;
}

.visual-logo{
  display:flex;align-items:center;gap:14px;
  margin-bottom:60px;
}
.visual-logo-icon{
  width:52px;height:52px;
  background:var(--gold);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:26px;color:var(--black);
}
.visual-logo-text{}
.visual-logo-name{
  font-family:'Space Grotesk',sans-serif;
  font-size:16px;font-weight:700;
  color:var(--white);letter-spacing:.14em;
}
.visual-logo-sub{
  font-size:10px;font-weight:500;
  color:var(--gold);letter-spacing:.2em;
  text-transform:uppercase;
}
.visual-headline{
  font-family:'Space Grotesk',sans-serif;
  font-size:44px;font-weight:700;
  color:var(--white);
  line-height:1.1;
  max-width:400px;
}
.visual-headline span{color:var(--gold);}
.visual-desc{
  font-size:15px;color:var(--gray-400);
  max-width:340px;margin-top:16px;
  line-height:1.7;
}
.visual-stats{
  display:flex;gap:32px;margin-top:48px;
}
.visual-stat-value{
  font-family:'Space Grotesk',sans-serif;
  font-size:28px;font-weight:700;color:var(--gold);
}
.visual-stat-label{font-size:12px;color:var(--gray-400);margin-top:2px;}

/* RIGHT PANEL — Form */
.login-form-panel{
  background:var(--white);
  display:flex;flex-direction:column;
  justify-content:center;
  padding:60px 52px;
  border-left:1px solid var(--gray-200);
}
.form-panel-title{
  font-family:'Space Grotesk',sans-serif;
  font-size:28px;font-weight:700;
  color:var(--gray-900);
}
.form-panel-sub{
  font-size:14px;color:var(--gray-400);
  margin-top:6px;margin-bottom:36px;
}

.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:18px;}
.form-label{font-size:13px;font-weight:500;color:var(--gray-700);}
.form-input-wrap{position:relative;}
.form-input-wrap i{
  position:absolute;left:14px;top:50%;
  transform:translateY(-50%);
  color:var(--gray-400);font-size:15px;
}
.form-input{
  width:100%;
  padding:12px 14px 12px 42px;
  border:1px solid var(--gray-200);
  border-radius:8px;
  font-size:14px;font-family:'Inter',sans-serif;
  color:var(--gray-900);
  outline:none;transition:all .2s ease;
}
.form-input:focus{
  border-color:var(--gold);
  box-shadow:0 0 0 3px rgba(201,168,76,.12);
}
.form-input::placeholder{color:var(--gray-400);}

.error-box{
  background:#FEF2F2;
  border:1px solid #FCA5A5;
  border-radius:8px;
  padding:12px 16px;
  font-size:13px;color:var(--danger);
  display:flex;align-items:center;gap:10px;
  margin-bottom:20px;
}

.btn-login{
  width:100%;
  padding:14px;
  background:var(--gold);
  color:var(--black);
  border:none;
  border-radius:8px;
  font-size:15px;font-weight:600;
  font-family:'Space Grotesk',sans-serif;
  cursor:pointer;
  letter-spacing:.04em;
  transition:all .2s ease;
  display:flex;align-items:center;justify-content:center;gap:10px;
  margin-top:8px;
}
.btn-login:hover{
  background:var(--gold-light);
  box-shadow:0 4px 20px rgba(201,168,76,.35);
  transform:translateY(-1px);
}

.login-hint{
  font-size:12px;color:var(--gray-400);
  margin-top:24px;text-align:center;
  background:var(--gray-50);
  border-radius:8px;padding:12px;
  border:1px solid var(--gray-100);
}
.login-hint code{
  background:var(--gray-200);
  padding:1px 6px;border-radius:4px;
  font-size:12px;color:var(--gray-700);
}

@media(max-width:900px){
  body{grid-template-columns:1fr;}
  .login-visual{display:none;}
  .login-form-panel{padding:40px 28px;}
}
</style>
</head>
<body>

<div class="login-visual">
  <div class="visual-logo">
    <div class="visual-logo-icon">⟁</div>
    <div class="visual-logo-text">
      <div class="visual-logo-name">OMEGA</div>
      <div class="visual-logo-sub">TECH AUTO</div>
    </div>
  </div>

  <h1 class="visual-headline">
    Gestion Auto<br><span>Nouvelle ère.</span>
  </h1>
  <p class="visual-desc">
    Plateforme intégrée de vente, location et suivi de flotte automobile. 
    Efficacité, élégance, précision.
  </p>

  <div class="visual-stats">
    <div>
      <div class="visual-stat-value">100%</div>
      <div class="visual-stat-label">Suivi en temps réel</div>
    </div>
    <div>
      <div class="visual-stat-value">FCFA</div>
      <div class="visual-stat-label">Facturation locale</div>
    </div>
    <div>
      <div class="visual-stat-value">∞</div>
      <div class="visual-stat-label">Véhicules gérés</div>
    </div>
  </div>
</div>

<div class="login-form-panel">
  <h2 class="form-panel-title">Connexion</h2>
  <p class="form-panel-sub">Accédez à votre tableau de bord</p>

  <?php if ($error): ?>
  <div class="error-box">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="form-group">
      <label class="form-label">Nom d'utilisateur</label>
      <div class="form-input-wrap">
        <i class="fa-regular fa-user"></i>
        <input type="text" name="username" class="form-input" placeholder="admin" 
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Mot de passe</label>
      <div class="form-input-wrap">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
      </div>
    </div>

    <button type="submit" class="btn-login">
      <i class="fa-solid fa-right-to-bracket"></i>
      Se connecter
    </button>
  </form>

  <div class="login-hint">
    <strong>Démo :</strong> Utilisateur <code>admin</code> · Mot de passe <code>password</code>
  </div>
</div>

</body>
</html>
