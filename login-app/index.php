<?php
// ==========================================================================
//  Sentinel // Secure Access Console  —  DELIBERATELY VULNERABLE
//  Challenge 0: SQL Injection authentication bypass.
//  Part of the File Inclusion CTF Lab. For local, authorized training only.
// ==========================================================================

session_start();

// --- Sign out: clear the session and return to the login form --------------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /');
    exit;
}

$dbfile = '/tmp/ctf_login.db';
$db = new PDO('sqlite:' . $dbfile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Seed the users table once ---------------------------------------------
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    password TEXT NOT NULL
)");
$count = $db->query("SELECT COUNT(*) AS c FROM users")->fetch(PDO::FETCH_ASSOC)['c'];
if ($count == 0) {
    // Strong, unguessable password — the ONLY intended way in is SQL injection.
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin',   'S3cr3t_' . bin2hex(random_bytes(6))]);
    $stmt->execute(['support', 'Supp0rt_' . bin2hex(random_bytes(6))]);
}

$loginFlag = @trim(file_get_contents('/flags/flag_login.txt')) ?: 'CTF{flag_file_missing}';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // *** "Hardening" (Variation A): strip SQL comment sequences so the classic
    // *** comment-out trick ( admin'-- ) no longer works. The input is STILL
    // *** concatenated into the query, so boolean-logic injection remains possible
    // *** (e.g.  admin' OR '1'='1 ) — the attacker just has to think a bit more.
    $strip = ['--', '#', '/*', '*/'];
    $username = str_replace($strip, '', $username);
    $password = str_replace($strip, '', $password);

    // !!! VULNERABLE: user input concatenated straight into the SQL string.
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    try {
        $result = $db->query($query);
        $user = $result ? $result->fetch(PDO::FETCH_ASSOC) : false;
        if ($user) {
            // Establish a session, then redirect (Post/Redirect/Get) so a
            // page reload keeps you on the dashboard instead of re-posting.
            $_SESSION['authed'] = true;
            $_SESSION['user']   = $user['username'];
            header('Location: /');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (Exception $e) {
        // Generic message — production apps never leak SQL errors to users.
        $error = 'Invalid username or password.';
    }
}

// Authentication state is driven by the session (survives reloads).
$authed = !empty($_SESSION['authed']);
$who    = $_SESSION['user'] ?? '';

// Level metadata for the post-login dashboard.
$levels = [
    ['n'=>1,'ep'=>'/dr-strange','cls'=>'l1','title'=>'Basic Path Traversal',
     'desc'=>'No filtering at all. The filename is read straight off disk — climb out of the app directory and read anything you like.'],
    ['n'=>2,'ep'=>'/captain-america','cls'=>'l2','title'=>'The Dot-Segment Dance',
     'desc'=>'A naïve filter strips traversal sequences — but only once, and without re-normalizing. Craft input that collapses back into a traversal.'],
    ['n'=>3,'ep'=>'/deadpool','cls'=>'l3','title'=>'Blacklist + Double Decode',
     'desc'=>'A blacklist inspects your input, then the app decodes it a second time before use. Get your payload past the check, let the decoder rebuild it.'],
    ['n'=>4,'ep'=>'/goku','cls'=>'l4','title'=>'Unicode Unlocked',
     'desc'=>'Standard encodings are blocked. The backend un-escapes Unicode sequences before reading — speak its language to slip through.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sentinel // Secure Access</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root{
        --bg:#eef1fb; --panel:rgba(255,255,255,.72); --line:rgba(99,102,241,.16);
        --txt:#1a1f36; --muted:#6b7396; --cyan:#0891b2; --violet:#7c3aed;
        --pink:#db2777; --green:#059669; --red:#e11d48; --accent:#6366f1;
        --l1:#e11d48; --l2:#2563eb; --l3:#f59e0b; --l4:#059669;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{height:100%}
    body{
        font-family:'Inter',system-ui,sans-serif;color:var(--txt);
        background:var(--bg);position:relative;min-height:100%;
        display:flex;align-items:center;justify-content:center;padding:24px;overflow-x:hidden;
    }
    body.dash{display:block;overflow-y:auto}
    /* soft animated aurora blobs (light) */
    .aurora{position:fixed;inset:-30%;z-index:0;filter:blur(80px);opacity:.6;pointer-events:none}
    .aurora span{position:absolute;border-radius:50%;mix-blend-mode:multiply;animation:drift 18s ease-in-out infinite}
    .aurora .b1{width:46vw;height:46vw;left:0%;top:2%;background:radial-gradient(circle,#c4b5fd,transparent 62%)}
    .aurora .b2{width:42vw;height:42vw;right:-4%;top:-6%;background:radial-gradient(circle,#a5f3fc,transparent 62%);animation-delay:-6s}
    .aurora .b3{width:44vw;height:44vw;left:28%;bottom:-14%;background:radial-gradient(circle,#fbcfe8,transparent 62%);animation-delay:-12s}
    @keyframes drift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(6%,-4%) scale(1.08)}66%{transform:translate(-5%,5%) scale(.94)}}
    .grid{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.5;
        background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);
        background-size:44px 44px;
        mask-image:radial-gradient(circle at 50% 30%,#000 0%,transparent 80%);
        -webkit-mask-image:radial-gradient(circle at 50% 30%,#000 0%,transparent 80%);}

    /* ===================== LOGIN CARD ===================== */
    .wrap{position:relative;z-index:2;width:100%;max-width:420px}
    .card{
        position:relative;background:var(--panel);border:1px solid rgba(255,255,255,.7);
        border-radius:20px;padding:34px 30px 30px;overflow:hidden;
        backdrop-filter:blur(20px) saturate(150%);-webkit-backdrop-filter:blur(20px) saturate(150%);
        box-shadow:0 30px 70px -24px rgba(79,70,229,.35),0 2px 10px -2px rgba(30,41,90,.08),
                   0 0 0 1px rgba(99,102,241,.06) inset;
        animation:rise .7s cubic-bezier(.2,.8,.2,1) both;
    }
    @keyframes rise{from{opacity:0;transform:translateY(18px) scale(.98)}to{opacity:1;transform:none}}
    .card::before{content:"";position:absolute;left:0;right:0;top:0;height:3px;
        background:linear-gradient(90deg,transparent,var(--cyan),var(--violet),var(--pink),transparent);
        background-size:200% 100%;animation:sweep 4s linear infinite}
    @keyframes sweep{to{background-position:200% 0}}

    .brand{display:flex;align-items:center;gap:12px;margin-bottom:6px}
    .logo{width:42px;height:42px;border-radius:12px;flex:none;display:grid;place-items:center;
        background:linear-gradient(135deg,#7c3aed,#0891b2);box-shadow:0 8px 20px -6px rgba(124,58,237,.5)}
    .logo svg{width:22px;height:22px;display:block}
    .brand h1{font-family:'Space Grotesk',sans-serif;font-size:20px;letter-spacing:.3px;line-height:1;color:#111527}
    .brand .sub{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);letter-spacing:2px;text-transform:uppercase}
    .tag{color:var(--muted);font-size:13px;margin:14px 0 22px;line-height:1.5}
    .tag b{color:var(--txt);font-weight:600}

    .field{position:relative;margin-bottom:16px}
    .field label{position:absolute;left:14px;top:15px;color:var(--muted);font-size:14px;
        pointer-events:none;transition:.18s ease;font-family:'JetBrains Mono',monospace}
    .field input{width:100%;padding:23px 14px 8px;font-size:15px;color:var(--txt);
        background:rgba(255,255,255,.85);border:1px solid var(--line);border-radius:12px;outline:none;
        transition:border-color .2s,box-shadow .2s;font-family:'Inter',sans-serif}
    .field input:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(99,102,241,.14)}
    .field input:focus+label,.field input:not(:placeholder-shown)+label{
        top:8px;font-size:10.5px;color:var(--accent);letter-spacing:2px;text-transform:uppercase}

    .btn{width:100%;margin-top:6px;padding:14px;border:none;border-radius:12px;cursor:pointer;
        font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:15px;letter-spacing:.4px;color:#fff;
        background:linear-gradient(135deg,#6366f1,#7c3aed);position:relative;overflow:hidden;
        transition:transform .12s,box-shadow .2s;box-shadow:0 12px 26px -8px rgba(99,102,241,.6)}
    .btn:hover{box-shadow:0 16px 34px -8px rgba(124,58,237,.65)}
    .btn:active{transform:translateY(1px) scale(.99)}
    .btn::after{content:"";position:absolute;top:0;left:-120%;width:60%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);
        transform:skewX(-20deg);animation:shine 3.2s ease-in-out infinite}
    @keyframes shine{0%,60%{left:-120%}80%,100%{left:140%}}

    .alert{margin-bottom:18px;padding:11px 14px;border-radius:10px;font-size:13px;
        font-family:'JetBrains Mono',monospace;border:1px solid;display:flex;align-items:center;gap:8px;animation:shake .4s}
    .alert.err{color:var(--red);background:rgba(225,29,72,.07);border-color:rgba(225,29,72,.3)}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}

    .row{display:flex;align-items:center;justify-content:space-between;margin:2px 2px 16px;font-size:13px}
    .remember{display:flex;align-items:center;gap:7px;color:var(--muted);cursor:pointer;user-select:none}
    .remember input{accent-color:var(--accent);width:15px;height:15px;cursor:pointer}
    .forgot{color:var(--accent);text-decoration:none;font-weight:500}
    .forgot:hover{text-decoration:underline}
    .signup{margin-top:20px;text-align:center;font-size:13px;color:var(--muted)}
    .signup a{color:var(--accent);text-decoration:none;font-weight:600}
    .signup a:hover{text-decoration:underline}
    .foot{text-align:center;margin-top:18px;font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--muted);letter-spacing:1px}
    .foot .dot{color:var(--green)}

    /* ===================== DASHBOARD ===================== */
    .dashwrap{position:relative;z-index:2;max-width:1060px;margin:0 auto;padding:40px 8px 60px;animation:rise .6s cubic-bezier(.2,.8,.2,1) both}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .topbar .brand h1{font-size:22px}
    .pill{display:inline-flex;align-items:center;gap:8px;font-family:'JetBrains Mono',monospace;font-size:12px;
        color:var(--green);background:rgba(5,150,105,.1);border:1px solid rgba(5,150,105,.3);
        padding:7px 13px;border-radius:999px}
    .pill .live{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 0 0 rgba(5,150,105,.5);animation:pulse 2s infinite}
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(5,150,105,.5)}70%{box-shadow:0 0 0 8px rgba(5,150,105,0)}100%{box-shadow:0 0 0 0 rgba(5,150,105,0)}}
    .logout{margin-left:12px;color:var(--muted);text-decoration:none;font-family:'JetBrains Mono',monospace;font-size:12px}
    .logout:hover{color:var(--accent)}

    .flagbanner{background:linear-gradient(120deg,rgba(5,150,105,.1),rgba(8,145,178,.08));
        border:1px solid rgba(5,150,105,.35);border-radius:16px;padding:16px 22px;margin-bottom:14px;
        display:flex;align-items:center;gap:16px;flex-wrap:wrap}
    .flagbanner .seal{width:44px;height:44px;border-radius:50%;flex:none;display:grid;place-items:center;
        background:radial-gradient(circle,rgba(5,150,105,.2),transparent 70%);border:1.5px solid rgba(5,150,105,.5)}
    .flagbanner .seal svg{width:22px;height:22px}
    .flagbanner .msg{flex:1;min-width:220px}
    .flagbanner h2{font-family:'Space Grotesk',sans-serif;font-size:17px;color:#065f46;margin-bottom:2px}
    .flagbanner p{color:var(--muted);font-size:13px;line-height:1.4}
    .flagbanner .flag{flex:none;font-family:'JetBrains Mono',monospace;font-size:14px;color:#047857;
        background:rgba(255,255,255,.7);border:1px dashed rgba(5,150,105,.6);border-radius:10px;padding:8px 14px;white-space:nowrap}
    .flagbanner .flag span{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:3px}

    .intro{color:var(--muted);font-size:14px;margin:20px 4px 16px;line-height:1.55}
    .intro b{color:var(--txt)}

    .cards{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .lvl{background:rgba(255,255,255,.82);border:1px solid var(--line);border-radius:16px;overflow:hidden;
        box-shadow:0 14px 40px -22px rgba(30,41,90,.4);transition:transform .18s,box-shadow .18s}
    .lvl:hover{transform:translateY(-3px);box-shadow:0 22px 50px -24px rgba(30,41,90,.45)}
    .lvl .head{padding:15px 20px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:16px;
        display:flex;align-items:center;gap:10px}
    .lvl .head .num{font-family:'JetBrains Mono',monospace;font-size:12px;background:rgba(255,255,255,.25);
        padding:3px 8px;border-radius:6px}
    .lvl.l1 .head{background:var(--l1)} .lvl.l2 .head{background:var(--l2)}
    .lvl.l3 .head{background:var(--l3)} .lvl.l4 .head{background:var(--l4)}
    .lvl .body{padding:18px 20px 20px}
    .lvl .desc{color:var(--muted);font-size:13.5px;line-height:1.55;margin-bottom:15px;min-height:60px}
    .lvl form{display:flex;gap:8px}
    .lvl input{flex:1;min-width:0;padding:11px 13px;font-size:14px;color:var(--txt);background:#fff;
        border:1px solid var(--line);border-radius:10px;outline:none;font-family:'JetBrains Mono',monospace;transition:.2s}
    .lvl input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.12)}
    .lvl button{border:none;border-radius:10px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;
        font-size:13px;padding:0 16px;cursor:pointer;white-space:nowrap;transition:filter .15s,transform .1s}
    .lvl button:hover{filter:brightness(1.08)} .lvl button:active{transform:scale(.97)}
    .lvl.l1 button{background:var(--l1)} .lvl.l2 button{background:var(--l2)}
    .lvl.l3 button{background:var(--l3)} .lvl.l4 button{background:var(--l4)}
    .lvl .path{margin-top:10px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--muted)}

    .objectives{margin-top:22px;background:rgba(255,255,255,.7);border:1px solid var(--line);border-radius:16px;padding:24px 26px}
    .objectives h3{font-family:'Space Grotesk',sans-serif;font-size:18px;color:var(--accent);margin-bottom:14px}
    .objectives ul{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:12px 24px}
    .objectives li{display:flex;gap:10px;align-items:flex-start;color:var(--muted);font-size:14px;line-height:1.5}
    .objectives li b{color:var(--txt)}
    .objectives .ico{font-size:17px;line-height:1.3}
    .toolbar{margin-top:22px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
    .toolbar a{text-decoration:none;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14px;
        padding:11px 20px;border-radius:11px;transition:.18s}
    .toolbar .hints{color:#fff;background:linear-gradient(135deg,#0891b2,#6366f1);box-shadow:0 12px 26px -10px rgba(8,145,178,.6)}
    .toolbar .hints:hover{box-shadow:0 16px 32px -10px rgba(99,102,241,.6);transform:translateY(-1px)}
    .toolbar .ghost{color:var(--muted);background:rgba(255,255,255,.7);border:1px solid var(--line)}
    .toolbar .ghost:hover{color:var(--accent);border-color:var(--accent)}

    @media(max-width:720px){
        .cards{grid-template-columns:1fr}
        .objectives ul{grid-template-columns:1fr}
        .flagbanner .flag{margin-left:0;width:100%}
    }
    /* ===== Challenge 0 solved — compact success (labs are delivered via the platform) ===== */
    .win{text-align:center}
    .win .seal2{width:64px;height:64px;margin:6px auto 14px;border-radius:50%;display:grid;place-items:center;
        background:radial-gradient(circle,rgba(5,150,105,.16),transparent 70%);border:1.5px solid rgba(5,150,105,.45);
        box-shadow:0 8px 26px -6px rgba(5,150,105,.4);animation:pop .55s cubic-bezier(.2,1.4,.4,1) both}
    @keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
    .win .seal2 svg{width:32px;height:32px}
    .win h2{font-family:'Space Grotesk',sans-serif;font-size:23px;color:var(--green);margin-bottom:4px;letter-spacing:.3px}
    .win .who{color:var(--muted);font-size:13px;line-height:1.5}
    .win .who b{color:var(--txt)}
    .win .flagbox{margin:20px 0 6px;font-family:'JetBrains Mono',monospace;font-size:15px;color:#047857;
        background:rgba(5,150,105,.07);border:1px dashed rgba(5,150,105,.6);border-radius:12px;padding:15px;word-break:break-all}
    .win .flagbox span{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:5px}
    .win .clue{margin:22px 0 2px;text-align:left;position:relative;padding:15px 18px;border-radius:14px;overflow:hidden;
        background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(8,145,178,.05));border:1px solid var(--line)}
    .win .clue::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(180deg,var(--violet),var(--cyan))}
    .win .clue .lbl{display:flex;align-items:center;gap:7px;font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:2px;
        text-transform:uppercase;color:var(--violet);margin-bottom:9px}
    .win .clue .lbl .blink{width:7px;height:7px;border-radius:50%;background:var(--violet);animation:bl 1.4s steps(1) infinite}
    @keyframes bl{50%{opacity:.25}}
    .win .clue p{font-family:'Inter',sans-serif;font-style:italic;font-size:14.5px;line-height:1.65;color:var(--txt);margin:0}
    .win .clue p b{color:var(--violet);font-style:normal;font-weight:600}
    .win .so{display:inline-block;margin-top:18px;color:var(--muted);font-size:12px;text-decoration:none;font-family:'JetBrains Mono',monospace}
    .win .so:hover{color:var(--accent)}
</style>
</head>
<body>
<div class="aurora"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
<div class="grid"></div>

<?php if ($authed): ?>
  <!-- ============ CHALLENGE 0 SOLVED (labs continue on the CTF platform) ============ -->
  <div class="wrap">
    <div class="card">
      <div class="brand">
        <div class="logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
        </div>
        <div><h1>Sentinel</h1><div class="sub">Secure Access</div></div>
      </div>
      <div class="win">
        <div class="seal2"><svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></div>
        <h2>Authentication bypassed 🎉</h2>
        <div class="who">signed in as <b><?= htmlspecialchars($who) ?></b> — and you never did have the password, did you?</div>
        <div class="flagbox"><span>Challenge 0 flag</span><?= htmlspecialchars($loginFlag) ?></div>
        <div class="clue">
          <div class="lbl"><span class="blink"></span> your next move</div>
          <p>One lock down — but the vault isn't empty. A file rests somewhere behind this door that was <b>never meant to leave its folder</b>, and the walls keeping it in are thinner than they look. Find the way out they forgot to seal.</p>
        </div>
        <a class="so" href="/?logout=1">sign out</a>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- ============ LOGIN ============ -->
  <div class="wrap">
    <div class="card">
      <div class="brand">
        <div class="logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><circle cx="12" cy="15" r="1.4" fill="#fff" stroke="none"></circle></svg>
        </div>
        <div><h1>Sentinel</h1><div class="sub">Secure Access</div></div>
      </div>

      <p class="tag">Welcome back. Sign in to your account to continue.</p>

      <?php if ($error): ?>
        <div class="alert err">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/">
        <div class="field">
          <input type="text" name="username" id="username" placeholder=" " autocomplete="off" autofocus>
          <label for="username">Username</label>
        </div>
        <div class="field">
          <input type="password" name="password" id="password" placeholder=" " autocomplete="off">
          <label for="password">Password</label>
        </div>
        <div class="row">
          <label class="remember"><input type="checkbox" name="remember"> Remember me</label>
          <a class="forgot" href="#">Forgot password?</a>
        </div>
        <button class="btn" type="submit">Sign in →</button>
      </form>

      <p class="signup">Don't have an account? <a href="#">Request access</a></p>
      <div class="foot"><span class="dot">●</span> Sentinel &nbsp;·&nbsp; © 2026 &nbsp;·&nbsp; <span class="dot">Secure connection</span></div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
