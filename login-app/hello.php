<?php
// ==========================================================================
//  SENTINEL // Hello World  —  onboarding lab.
//  No exploitation. The flag is shown openly so players learn the
//  find-the-flag -> submit-it flow on the platform before real challenges.
// ==========================================================================
$BRAND = 'Sentinel';
$flag  = 'CTF{hello_world}';   // dummy flag — intentionally plain
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $BRAND ?> — Hello World</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root{--bg:#eef1fb;--panel:rgba(255,255,255,.72);--line:rgba(99,102,241,.16);
        --txt:#1a1f36;--muted:#6b7396;--cyan:#0891b2;--violet:#7c3aed;--pink:#db2777;--green:#059669;--accent:#6366f1;}
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
    .wrap{position:relative;z-index:2;width:100%;max-width:440px}
    .card{position:relative;background:var(--panel);border:1px solid rgba(255,255,255,.7);border-radius:20px;padding:34px 30px 28px;overflow:hidden;text-align:center;
        backdrop-filter:blur(20px) saturate(150%);-webkit-backdrop-filter:blur(20px) saturate(150%);
        box-shadow:0 30px 70px -24px rgba(79,70,229,.35),0 2px 10px -2px rgba(30,41,90,.08),0 0 0 1px rgba(99,102,241,.06) inset;
        animation:rise .6s cubic-bezier(.2,.8,.2,1) both}
    @keyframes rise{from{opacity:0;transform:translateY(16px) scale(.98)}to{opacity:1;transform:none}}
    .card::before{content:"";position:absolute;left:0;right:0;top:0;height:3px;background:linear-gradient(90deg,transparent,var(--cyan),var(--violet),var(--pink),transparent);background-size:200% 100%;animation:sweep 4s linear infinite}
    @keyframes sweep{to{background-position:200% 0}}
    .brand{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:6px}
    .logo{width:42px;height:42px;border-radius:12px;flex:none;display:grid;place-items:center;background:linear-gradient(135deg,#7c3aed,#0891b2);box-shadow:0 8px 20px -6px rgba(124,58,237,.5)}
    .logo svg{width:22px;height:22px}
    .brand h1{font-family:'Space Grotesk',sans-serif;font-size:20px;color:#111527}
    .brand .sub{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);letter-spacing:2px;text-transform:uppercase}
    .wave{font-size:46px;line-height:1;margin:14px 0 2px;animation:wv 1.8s ease-in-out infinite;transform-origin:70% 70%;display:inline-block}
    @keyframes wv{0%,60%,100%{transform:rotate(0)}10%{transform:rotate(16deg)}20%{transform:rotate(-8deg)}30%{transform:rotate(16deg)}40%{transform:rotate(-4deg)}50%{transform:rotate(10deg)}}
    h2{font-family:'Space Grotesk',sans-serif;font-size:23px;color:#111527;margin-bottom:8px}
    .tag{color:var(--muted);font-size:14px;line-height:1.6;margin:0 2px 20px}
    .tag b{color:var(--txt)}
    .flagbox{position:relative;font-family:'JetBrains Mono',monospace;font-size:17px;color:#047857;background:rgba(5,150,105,.07);
        border:1px dashed rgba(5,150,105,.6);border-radius:12px;padding:16px 14px;word-break:break-all;letter-spacing:.5px}
    .flagbox .lbl{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:7px}
    .copy{margin-top:14px;border:none;border-radius:11px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14px;
        padding:12px 22px;cursor:pointer;background:linear-gradient(135deg,#6366f1,#7c3aed);box-shadow:0 12px 26px -8px rgba(99,102,241,.6);transition:transform .12s}
    .copy:hover{transform:translateY(-1px)} .copy:active{transform:translateY(1px) scale(.99)}
    .steps{text-align:left;margin:22px 2px 4px;display:grid;gap:10px}
    .step{display:flex;gap:11px;align-items:flex-start;font-size:13px;color:var(--muted);line-height:1.5}
    .step b{color:var(--txt)}
    .step .n{flex:none;width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#7c3aed);color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:12px;display:grid;place-items:center}
    .foot{margin-top:18px;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--muted);letter-spacing:1px}
</style>
</head>
<body>
<div class="aurora"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
<div class="wrap">
  <div class="card">
    <div class="brand">
      <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></div>
      <div><h1><?= $BRAND ?></h1><div class="sub">Hello World</div></div>
    </div>

    <div class="wave">👋</div>
    <h2>Welcome — this is a flag!</h2>
    <p class="tag">No hacking needed for this one. Every challenge hides a <b>flag</b> like the one below. Your job is always to find it and <b>submit it</b> to score. Here's a free one so you can practice the flow.</p>

    <div class="flagbox">
      <span class="lbl">Your flag</span>
      <span id="flag"><?= htmlspecialchars($flag) ?></span>
    </div>
    <button class="copy" id="copyBtn" type="button">📋 Copy flag</button>

    <div class="steps">
      <div class="step"><span class="n">1</span><div><b>Copy</b> the flag above (or tap the button).</div></div>
      <div class="step"><span class="n">2</span><div><b>Paste it</b> into the <b>Submit Flag</b> box on the challenge platform.</div></div>
      <div class="step"><span class="n">3</span><div>See the ✅ and your score go up. That's it — now the real challenges work exactly the same way.</div></div>
    </div>

    <div class="foot"><?= $BRAND ?> &nbsp;·&nbsp; HELLO WORLD &nbsp;·&nbsp; ONBOARDING LAB</div>
  </div>
</div>
<script>
  document.getElementById('copyBtn').addEventListener('click', function () {
    var f = document.getElementById('flag').textContent;
    navigator.clipboard.writeText(f).then(function () {
      var b = document.getElementById('copyBtn');
      b.textContent = '✅ Copied!';
      setTimeout(function () { b.textContent = '📋 Copy flag'; }, 1600);
    }).catch(function () {});
  });
</script>
</body>
</html>
