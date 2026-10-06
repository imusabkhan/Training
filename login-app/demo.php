<?php
// ==========================================================================
//  NIGHTFALL // Demo / Warm-up  —  guided example shown BEFORE the real labs.
//  A trivial path-traversal demo with the answer given openly, plus a
//  flag-submission box so players learn the find -> submit -> score flow.
// ==========================================================================

$demoFlag = @trim(@file_get_contents('/var/www/html/demo_flag.txt')) ?: 'CTF{demo_flag_missing}';

// --- Trivial (intentional) path traversal: reads from demo_files/ ----------
$readOutput = null;
$readError  = null;
$filename   = $_GET['filename'] ?? '';
if ($filename !== '') {
    // VULNERABLE ON PURPOSE — this is the demonstration of the technique.
    $target = 'demo_files/' . $filename;
    $content = @file_get_contents($target);
    if ($content === false) {
        $readError = "Couldn't read '" . $target . "'";
    } else {
        $readOutput = $content;
    }
}

// --- Flag submission demo --------------------------------------------------
$submitResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = trim($_POST['flag'] ?? '');
    $submitResult = (strcasecmp($submitted, $demoFlag) === 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NIGHTFALL // Demo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root{--bg:#eef1fb;--panel:rgba(255,255,255,.78);--line:rgba(99,102,241,.16);
        --txt:#1a1f36;--muted:#6b7396;--cyan:#0891b2;--violet:#7c3aed;--green:#059669;--red:#e11d48;--accent:#6366f1;}
    *{box-sizing:border-box;margin:0;padding:0}
    html{height:100%}
    body{font-family:'Inter',system-ui,sans-serif;color:var(--txt);background:var(--bg);min-height:100%;padding:24px;overflow-x:hidden;position:relative}
    .aurora{position:fixed;inset:-30%;z-index:0;filter:blur(80px);opacity:.55;pointer-events:none}
    .aurora span{position:absolute;border-radius:50%;mix-blend-mode:multiply;animation:drift 18s ease-in-out infinite}
    .aurora .b1{width:46vw;height:46vw;left:0;top:2%;background:radial-gradient(circle,#c4b5fd,transparent 62%)}
    .aurora .b2{width:42vw;height:42vw;right:-4%;top:-6%;background:radial-gradient(circle,#a5f3fc,transparent 62%);animation-delay:-6s}
    .aurora .b3{width:44vw;height:44vw;left:28%;bottom:-14%;background:radial-gradient(circle,#fbcfe8,transparent 62%);animation-delay:-12s}
    @keyframes drift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(6%,-4%) scale(1.08)}66%{transform:translate(-5%,5%) scale(.94)}}
    .wrap{position:relative;z-index:2;max-width:760px;margin:0 auto;padding:20px 4px 60px}
    .brand{display:flex;align-items:center;gap:12px;margin-bottom:8px}
    .logo{width:44px;height:44px;border-radius:12px;flex:none;display:grid;place-items:center;
        background:linear-gradient(135deg,#7c3aed,#0891b2);box-shadow:0 8px 20px -6px rgba(124,58,237,.5)}
    .logo svg{width:22px;height:22px}
    .brand h1{font-family:'Space Grotesk',sans-serif;font-size:22px;color:#111527}
    .brand .sub{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);letter-spacing:2px;text-transform:uppercase}
    .badge{display:inline-block;font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:1px;text-transform:uppercase;
        color:var(--violet);background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.25);padding:4px 10px;border-radius:999px;margin:6px 0 18px}
    .card{background:var(--panel);border:1px solid rgba(255,255,255,.7);border-radius:18px;padding:24px 26px;margin-bottom:18px;
        backdrop-filter:blur(18px) saturate(150%);-webkit-backdrop-filter:blur(18px) saturate(150%);
        box-shadow:0 20px 55px -26px rgba(79,70,229,.35),0 0 0 1px rgba(99,102,241,.05) inset}
    .card h2{font-family:'Space Grotesk',sans-serif;font-size:17px;margin-bottom:10px;display:flex;align-items:center;gap:9px}
    .card p{color:var(--muted);font-size:14px;line-height:1.6;margin-bottom:10px}
    .card p b{color:var(--txt)} code{font-family:'JetBrains Mono',monospace;color:var(--violet);background:rgba(124,58,237,.08);padding:1px 6px;border-radius:5px;font-size:.92em}
    .step{counter-reset:none;display:flex;gap:12px;margin:14px 0}
    .step .n{flex:none;width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#7c3aed);color:#fff;
        font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;display:grid;place-items:center}
    .step .t{font-size:14px;color:var(--muted);line-height:1.55;padding-top:2px} .step .t b{color:var(--txt)}
    form.read{display:flex;gap:8px;margin:14px 0 6px}
    form.read input{flex:1;min-width:0;padding:11px 13px;font-size:14px;font-family:'JetBrains Mono',monospace;color:var(--txt);
        background:#fff;border:1px solid var(--line);border-radius:10px;outline:none;transition:.2s}
    form.read input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.12)}
    form.read button{border:none;border-radius:10px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:13px;
        padding:0 18px;cursor:pointer;background:linear-gradient(135deg,#6366f1,#7c3aed)}
    .try{display:inline-block;margin:2px 6px 2px 0;font-family:'JetBrains Mono',monospace;font-size:12px;text-decoration:none;
        color:var(--accent);background:rgba(99,102,241,.08);border:1px solid var(--line);border-radius:8px;padding:6px 10px}
    .try:hover{background:rgba(99,102,241,.16)}
    .out{margin-top:12px;font-family:'JetBrains Mono',monospace;font-size:13px;white-space:pre-wrap;word-break:break-word;
        background:#0f1424;color:#c9d4f5;border-radius:10px;padding:14px 16px;line-height:1.5}
    .out .hit{color:#5ff0b0;font-weight:600}
    .out.err{background:rgba(225,29,72,.07);color:var(--red);border:1px solid rgba(225,29,72,.25)}
    .flagbox{font-family:'JetBrains Mono',monospace;font-size:15px;color:#047857;background:rgba(5,150,105,.07);
        border:1px dashed rgba(5,150,105,.6);border-radius:12px;padding:14px 16px;word-break:break-all}
    .flagbox span{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:5px}
    form.submit{display:flex;gap:8px;margin-top:4px}
    form.submit input{flex:1;min-width:0;padding:12px 14px;font-size:14px;font-family:'JetBrains Mono',monospace;color:var(--txt);
        background:#fff;border:1px solid var(--line);border-radius:10px;outline:none}
    form.submit input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(5,150,105,.14)}
    form.submit button{border:none;border-radius:10px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14px;
        padding:0 20px;cursor:pointer;background:linear-gradient(135deg,#059669,#0891b2)}
    .result{margin-top:12px;padding:12px 14px;border-radius:10px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:9px}
    .result.ok{color:#065f46;background:rgba(5,150,105,.1);border:1px solid rgba(5,150,105,.35)}
    .result.no{color:var(--red);background:rgba(225,29,72,.07);border:1px solid rgba(225,29,72,.3)}
    .cta{display:inline-block;margin-top:8px;text-decoration:none;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14px;
        color:#fff;background:linear-gradient(135deg,#0891b2,#6366f1);padding:12px 22px;border-radius:11px;box-shadow:0 12px 26px -10px rgba(8,145,178,.6)}
    .cta:hover{transform:translateY(-1px)}
    .foot{text-align:center;margin-top:10px;font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--muted);letter-spacing:1px}
</style>
</head>
<body>
<div class="aurora"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
<div class="wrap">

  <div class="brand">
    <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></div>
    <div><h1>NIGHTFALL</h1><div class="sub">Demo · Warm-up</div></div>
  </div>
  <div class="badge">🎓 Practice round — not scored</div>

  <div class="card">
    <h2>👋 How this CTF works</h2>
    <p>Every challenge hides a secret <b>flag</b> that looks like <code>CTF{...}</code>. Your job is to <b>exploit a bug</b> in the app to reveal it, then <b>submit the flag</b> to score. This page walks you through one easy example end-to-end so the real labs feel familiar.</p>
    <div class="step"><div class="n">1</div><div class="t"><b>Find the bug.</b> Here it's a file viewer that reads any name you give it from the <code>demo_files/</code> folder — with no safety checks.</div></div>
    <div class="step"><div class="n">2</div><div class="t"><b>Exploit it.</b> Use <code>../</code> to climb out of that folder and read a file you're not supposed to.</div></div>
    <div class="step"><div class="n">3</div><div class="t"><b>Submit the flag</b> you recover in the box below and watch for the ✅.</div></div>
  </div>

  <div class="card">
    <h2>🗂️ Try the file viewer</h2>
    <p>First read the normal file, then escape the folder to grab the flag:</p>
    <form class="read" method="get" action="/demo">
      <input type="text" name="filename" value="<?= htmlspecialchars($filename) ?>" placeholder="Enter a filename…" autocomplete="off">
      <button type="submit">Read File →</button>
    </form>
    <div>
      <a class="try" href="/demo?filename=welcome.txt">try: welcome.txt</a>
      <a class="try" href="/demo?filename=../demo_flag.txt">try the exploit: ../demo_flag.txt</a>
    </div>
    <?php if ($readOutput !== null): ?>
      <div class="out"><?php
        $safe = htmlspecialchars($readOutput);
        // highlight the flag if present
        echo preg_replace('/(CTF\{[^}]*\})/', '<span class="hit">$1</span>', $safe);
      ?></div>
    <?php elseif ($readError !== null): ?>
      <div class="out err"><?= htmlspecialchars($readError) ?></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>🚩 Submit the flag</h2>
    <p>Recovered the flag above? Paste it here to see what a correct submission looks like. (For this demo it's also shown below — in the real labs you'll have to find it yourself.)</p>
    <form class="submit" method="post" action="/demo">
      <input type="text" name="flag" placeholder="CTF{...}" autocomplete="off">
      <button type="submit">Submit Flag</button>
    </form>
    <?php if ($submitResult === true): ?>
      <div class="result ok">✅ Correct! That's exactly how you'll submit flags in the real challenges.</div>
    <?php elseif ($submitResult === false): ?>
      <div class="result no">❌ Not quite — read the flag from the viewer above and paste it exactly.</div>
    <?php endif; ?>
    <div style="margin-top:14px" class="flagbox"><span>Demo flag (shown for the walkthrough)</span><?= htmlspecialchars($demoFlag) ?></div>
  </div>

  <div class="card" style="text-align:center">
    <h2 style="justify-content:center">Ready for the real thing?</h2>
    <p>The labs use the same idea with real defenses to bypass — starting with a login you have to break into.</p>
    <a class="cta" href="/">Start the labs →</a>
  </div>

  <div class="foot">NIGHTFALL · demo · authorized training use only</div>
</div>
</body>
</html>
