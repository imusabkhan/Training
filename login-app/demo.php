<?php
// ==========================================================================
//  SENTINEL // Demo  —  guided SQL-Injection auth-bypass warm-up.
//  Teaches ONE technique: the comment trick ( admin'-- ).
//  For local, authorized training only.
// ==========================================================================

$BRAND = 'Sentinel';

$dbfile = '/tmp/ctf_demo.db';
$db = new PDO('sqlite:' . $dbfile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)");
if ($db->query("SELECT COUNT(*) c FROM users")->fetch()['c'] == 0) {
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', 'S3cr3t_' . bin2hex(random_bytes(6))]);   // unguessable — only SQLi gets in
}

$demoFlag = @trim(@file_get_contents('/flags/flag_demo.txt')) ?: 'CTF{demo_flag_missing}';

$error = '';
$authed = false;
$who = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULNERABLE ON PURPOSE — classic concatenation, no filtering (the teaching case)
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    try {
        $row = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        if ($row) { $authed = true; $who = $row['username']; }
        else { $error = 'Invalid username or password.'; }
    } catch (Exception $e) { $error = 'Invalid username or password.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $BRAND ?> — Sign in</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root{--bg:#eef1fb;--panel:rgba(255,255,255,.72);--line:rgba(99,102,241,.16);
        --txt:#1a1f36;--muted:#6b7396;--cyan:#0891b2;--violet:#7c3aed;--pink:#db2777;--green:#059669;--red:#e11d48;--accent:#6366f1;}
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{font-family:'Inter',system-ui,sans-serif;color:var(--txt);background:var(--bg);
        display:flex;align-items:center;justify-content:center;padding:24px;overflow:hidden;position:relative}
    .aurora{position:fixed;inset:-30%;z-index:0;filter:blur(80px);opacity:.6;pointer-events:none}
    .aurora span{position:absolute;border-radius:50%;mix-blend-mode:multiply;animation:drift 18s ease-in-out infinite}
    .aurora .b1{width:46vw;height:46vw;left:0;top:2%;background:radial-gradient(circle,#c4b5fd,transparent 62%)}
    .aurora .b2{width:42vw;height:42vw;right:-4%;top:-6%;background:radial-gradient(circle,#a5f3fc,transparent 62%);animation-delay:-6s}
    .aurora .b3{width:44vw;height:44vw;left:28%;bottom:-14%;background:radial-gradient(circle,#fbcfe8,transparent 62%);animation-delay:-12s}
    @keyframes drift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(6%,-4%) scale(1.08)}66%{transform:translate(-5%,5%) scale(.94)}}
    .wrap{position:relative;z-index:2;width:100%;max-width:410px}
    .card{position:relative;background:var(--panel);border:1px solid rgba(255,255,255,.7);border-radius:20px;padding:34px 30px 28px;overflow:hidden;
        backdrop-filter:blur(20px) saturate(150%);-webkit-backdrop-filter:blur(20px) saturate(150%);
        box-shadow:0 30px 70px -24px rgba(79,70,229,.35),0 2px 10px -2px rgba(30,41,90,.08),0 0 0 1px rgba(99,102,241,.06) inset;
        animation:rise .6s cubic-bezier(.2,.8,.2,1) both}
    @keyframes rise{from{opacity:0;transform:translateY(16px) scale(.98)}to{opacity:1;transform:none}}
    .card::before{content:"";position:absolute;left:0;right:0;top:0;height:3px;
        background:linear-gradient(90deg,transparent,var(--cyan),var(--violet),var(--pink),transparent);background-size:200% 100%;animation:sweep 4s linear infinite}
    @keyframes sweep{to{background-position:200% 0}}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:4px}
    .logo{width:42px;height:42px;border-radius:12px;flex:none;display:grid;place-items:center;background:linear-gradient(135deg,#7c3aed,#0891b2);box-shadow:0 8px 20px -6px rgba(124,58,237,.5)}
    .logo svg{width:22px;height:22px}
    .brand h1{font-family:'Space Grotesk',sans-serif;font-size:20px;color:#111527}
    .brand .sub{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);letter-spacing:2px;text-transform:uppercase}
    .tag{color:var(--muted);font-size:13px;margin:16px 0 22px;line-height:1.5}
    .field{position:relative;margin-bottom:15px}
    .field label{position:absolute;left:14px;top:15px;color:var(--muted);font-size:14px;pointer-events:none;transition:.18s;font-family:'JetBrains Mono',monospace}
    .field input{width:100%;padding:23px 14px 8px;font-size:15px;color:var(--txt);background:rgba(255,255,255,.85);border:1px solid var(--line);border-radius:12px;outline:none;transition:.2s;font-family:'Inter',sans-serif}
    .field input:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(99,102,241,.14)}
    .field input:focus+label,.field input:not(:placeholder-shown)+label{top:8px;font-size:10.5px;color:var(--accent);letter-spacing:2px;text-transform:uppercase}
    .btn{width:100%;margin-top:4px;padding:14px;border:none;border-radius:12px;cursor:pointer;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:15px;color:#fff;
        background:linear-gradient(135deg,#6366f1,#7c3aed);position:relative;overflow:hidden;transition:transform .12s,box-shadow .2s;box-shadow:0 12px 26px -8px rgba(99,102,241,.6)}
    .btn:hover{box-shadow:0 16px 34px -8px rgba(124,58,237,.65)} .btn:active{transform:translateY(1px) scale(.99)}
    .alert{margin-bottom:16px;padding:11px 14px;border-radius:10px;font-size:13px;font-family:'JetBrains Mono',monospace;border:1px solid;animation:shake .4s;color:var(--red);background:rgba(225,29,72,.07);border-color:rgba(225,29,72,.3)}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
    .row{display:flex;align-items:center;justify-content:space-between;margin:2px 2px 16px;font-size:13px}
    .remember{display:flex;align-items:center;gap:7px;color:var(--muted);cursor:pointer;user-select:none}
    .remember input{accent-color:var(--accent);width:15px;height:15px;cursor:pointer}
    .forgot{color:var(--accent);text-decoration:none;font-weight:500}
    .forgot:hover{text-decoration:underline}
    .signup{margin-top:20px;text-align:center;font-size:13px;color:var(--muted)}
    .signup a{color:var(--accent);text-decoration:none;font-weight:600}
    .signup a:hover{text-decoration:underline}
    .foot{text-align:center;margin-top:16px;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--muted);letter-spacing:1px}
    /* success */
    .win{text-align:center}
    .win .burst{font-size:46px;line-height:1;animation:pop .6s cubic-bezier(.2,1.5,.4,1) both}
    @keyframes pop{from{transform:scale(0) rotate(-20deg)}to{transform:scale(1) rotate(0)}}
    .win h2{font-family:'Space Grotesk',sans-serif;font-size:23px;color:var(--green);margin:14px 0 4px;letter-spacing:.3px}
    .win .ex{color:var(--txt);font-size:14px;font-weight:500;margin-bottom:4px}
    .win .who{color:var(--muted);font-size:13px}
    .flagbox{margin:20px 0 6px;font-family:'JetBrains Mono',monospace;font-size:15px;color:#047857;background:rgba(5,150,105,.07);
        border:1px dashed rgba(5,150,105,.6);border-radius:12px;padding:15px;word-break:break-all}
    .flagbox span{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:5px}
    .next{color:var(--muted);font-size:13px;margin:16px 0 14px;line-height:1.5}
    .next b{color:var(--txt)}
    .cta{display:inline-block;text-decoration:none;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14px;color:#fff;
        background:linear-gradient(135deg,#0891b2,#6366f1);padding:12px 24px;border-radius:11px;box-shadow:0 12px 26px -10px rgba(8,145,178,.6);transition:transform .12s}
    .cta:hover{transform:translateY(-1px)}
    .retry{display:block;margin-top:14px;color:var(--muted);font-size:12px;text-decoration:none;font-family:'JetBrains Mono',monospace}
    .retry:hover{color:var(--accent)}
</style>
</head>
<body>
<div class="aurora"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
<div class="wrap">
  <div class="card">
    <div class="brand">
      <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><circle cx="12" cy="15" r="1.4" fill="#fff" stroke="none"></circle></svg></div>
      <div><h1><?= $BRAND ?></h1><div class="sub">Secure Access</div></div>
    </div>

    <?php if ($authed): ?>
      <div class="win">
        <div class="burst">🎉</div>
        <h2>You're in!</h2>
        <div class="ex">No password. No problem. You just walked past the login with a single quote. 🫡</div>
        <div class="who">authenticated as <b><?= htmlspecialchars($who) ?></b></div>
        <div class="flagbox"><span>Your flag</span><?= htmlspecialchars($demoFlag) ?></div>
        <p class="next">That was the warm-up. The real portal at <b>/</b> has the same weakness — but it's been patched just enough that this exact trick won't work. Can you still get in? 😏</p>
        <a class="cta" href="/">Enter the real portal →</a>
        <a class="retry" href="/demo">‹ replay the demo</a>
      </div>
    <?php else: ?>
      <p class="tag">Welcome back. Sign in to your account to continue.</p>

      <?php if ($error): ?><div class="alert">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST" action="/demo">
        <div class="field"><input type="text" name="username" id="u" placeholder=" " autocomplete="off" autofocus><label for="u">Username</label></div>
        <div class="field"><input type="password" name="password" id="p" placeholder=" " autocomplete="off"><label for="p">Password</label></div>
        <div class="row">
          <label class="remember"><input type="checkbox" name="remember"> Remember me</label>
        </div>
        <button class="btn" type="submit">Sign in →</button>
      </form>

      <div class="foot"><?= $BRAND ?> &nbsp;·&nbsp; © 2026 &nbsp;·&nbsp; Secure connection</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
