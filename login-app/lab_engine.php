<?php
// ==========================================================================
//  SENTINEL // Interactive LFI Lab engine  (Levels 1–4)
//  Rich, guided, developer-friendly front-ends for the path-traversal labs.
//  A thin wrapper (lab1.php ... lab4.php) sets $LEVEL then includes this.
//  Each level replicates its real filter so the winning payload matches the
//  actual endpoint, and adds a visual file tree, smart step-by-step feedback,
//  a hint ladder, and a win celebration.
//  Audience = engineers with NO security background: never leave them lost.
// ==========================================================================
session_start();
$BRAND = 'Sentinel';

// ---- Per-level configuration --------------------------------------------
$files_common = [
  'welcome.txt'=>"Welcome to the Sentinel Document Viewer.\nType a file name to open it.\n",
  'notes.txt'  =>"TODO: move secret files out of the web root.\n(we never did)\n",
  'server.log' =>"[INFO] viewer started\n[INFO] serving ./public\n",
];
$LEVELS = [
  1 => [
    'num'=>1,'name'=>'Basic Path Traversal','flagfile'=>'/flags/flag1.txt','prize'=>'flag.txt','filter'=>'none',
    'scenario'=>"You've found an internal <b>Document Viewer</b>. It opens any file you name from its <code>public/</code> folder — and it does <b>zero</b> checking.",
    'objective'=>"Read the file <code>flag.txt</code> — but it lives <b>outside</b> the <code>public/</code> folder.",
    'files'=>$files_common,
    'hints'=>[
      "The viewer just glues your filename onto the end of <code>public/</code>. Think about <b>where the flag sits</b> compared to that folder.",
      "In a file path, <code>..</code> means <i>“go up one folder”</i> (the parent directory). Nothing here is stopping you from using it.",
      "Type <code>../flag.txt</code> and hit Open — that climbs out of <code>public/</code> and reads the flag."
    ],
    'clue'=>"That was the easy door. The next viewer actually <i>tries</i> to block <code>../</code> — but its lock is flimsy. Can you still slip through?",
  ],
  2 => [
    'num'=>2,'name'=>'The Filter That Blinks Once','flagfile'=>'/flags/flag2.txt','prize'=>'flag.txt','filter'=>'strip',
    'scenario'=>"This viewer got a security upgrade — it now <b>strips <code>../</code></b> out of your input before opening the file. Looks safe… but it only cleans up <b>once</b>, and never re-checks its own output.",
    'objective'=>"Read <code>flag.txt</code> outside <code>public/</code> — but every <code>../</code> you type gets deleted.",
    'files'=>$files_common,
    'hints'=>[
      "The filter deletes <code>../</code> from your input — but only in a <b>single pass</b>. It doesn't look again at the result.",
      "What could you type so that, <i>after</i> the filter removes a <code>../</code> from the middle, the leftovers fall back together into <code>../</code>?",
      "Type <code>....//flag.txt</code> — the filter removes the inner <code>../</code>, and the remaining <code>..</code> + <code>/</code> snap back into <code>../</code>."
    ],
    'clue'=>"Two down. The next viewer has a real <b>blacklist</b>… and it sneakily decodes your input a second time. Time to get clever with encoding.",
  ],
  3 => [
    'num'=>3,'name'=>'Check the Mask, Not the Face','flagfile'=>'/flags/flag3.txt','prize'=>'flag3.txt','filter'=>'blacklist2x',
    'scenario'=>"This viewer has a <b>blacklist</b>: it rejects anything containing <code>../</code>, <code>..</code>, <code>passwd</code> and friends. Then — only <i>after</i> that check — it <b>URL-decodes your input one more time</b> before opening the file.",
    'objective'=>"Read the flag file (it's called <code>flag3.txt</code>) — but the blacklist blocks <code>../</code> on sight.",
    'files'=>$files_common,
    'hints'=>[
      "The blacklist checks your input <b>as-is</b>, but the viewer <b>decodes it again</b> before using it. Anything you encode gets rebuilt <i>after</i> the check has already passed.",
      "URL-encoding: <code>.</code> is <code>%2e</code> and <code>/</code> is <code>%2f</code>. But a single layer is undone by the web server before the check even runs — so encode it <b>twice</b>.",
      "Type <code>%252e%252e%252fflag3.txt</code>. The check sees harmless <code>%2e%2e%2f…</code>; the viewer's second decode turns it back into <code>../</code>."
    ],
    'clue'=>"Last one. This viewer blocks the usual tricks outright — but it speaks a secret language: <b>Unicode</b>.",
  ],
  4 => [
    'num'=>4,'name'=>'Speak in Spells','flagfile'=>'/flags/flag4.txt','prize'=>'flag.txt','filter'=>'unicode',
    'scenario'=>"The final viewer <b>blocks plain <code>../</code></b> outright. But to be “helpful”, it first <b>un-escapes Unicode sequences</b> (like <code>\\u0041</code> → <code>A</code>) before opening your file.",
    'objective'=>"Read <code>flag.txt</code> outside <code>public/</code> — but any literal <code>..</code> is rejected.",
    'files'=>$files_common,
    'hints'=>[
      "Plain <code>../</code> is rejected. But the viewer turns Unicode escapes into real characters <i>before</i> reading — so write the forbidden characters as Unicode.",
      "<code>.</code> is <code>\\u002e</code> and <code>/</code> is <code>\\u002f</code>. So <code>../</code> becomes <code>\\u002e\\u002e\\u002f</code>.",
      "Type <code>\\u002e\\u002e\\u002fflag.txt</code> — it slips past the <code>..</code> check, then decodes to <code>../flag.txt</code>."
    ],
    'clue'=>"That's all four — you've out-thought every filter. 🏆 Now you know first-hand why blacklists and clever “sanitizers” are no substitute for proper path validation.",
  ],
];

$LEVEL = $LEVEL ?? 1;
$cfg = $LEVELS[$LEVEL];
$prize = $cfg['prize'];
$sess =& $_SESSION['lab'.$LEVEL];
if (!isset($sess)) $sess = ['steps'=>['read'=>false,'escape'=>false,'flag'=>false], 'tries'=>0];

// ---- Sandbox (real files, so traversal is authentic) --------------------
$root = "/tmp/lab$LEVEL";
$base = "$root/public/";
if (!is_dir($base)) { @mkdir($base, 0777, true); }
foreach ($cfg['files'] as $fn=>$body) { if (!file_exists($base.$fn)) @file_put_contents($base.$fn, $body); }
$flag = trim(@file_get_contents($cfg['flagfile'])) ?: 'CTF{flag_file_missing}';
@file_put_contents("$root/$prize", $flag."\n");   // the prize, one level above public/

// ---- The vulnerability, per level ---------------------------------------
// Treats the typed value as the raw request payload. Returns:
//   ['blocked'=>bool, 'path'=>?string, 'reason'=>?string]
function lab_process($level, $input) {
  switch ($level) {
    case 1: // no protection
      return ['blocked'=>false,'path'=>$input];
    case 2: // single, non-recursive strip of "../"
      return ['blocked'=>false,'path'=>str_replace('../','',$input)];
    case 3: // blacklist BEFORE a second URL-decode
      $v1 = urldecode($input);                       // framework/first decode
      $tokens = ['../','..\\','..','passwd','shadow','hosts','config','flag.txt'];
      foreach ($tokens as $t) if (stripos($v1,$t)!==false) return ['blocked'=>true,'path'=>null,'reason'=>$t];
      return ['blocked'=>false,'path'=>urldecode($v1)]; // code/second decode
    case 4: // block literal ".." , then decode \uXXXX
      if (strpos($input,'..')!==false) return ['blocked'=>true,'path'=>null,'reason'=>'..'];
      $dec = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($m)=>mb_chr(hexdec($m[1]),'UTF-8'), $input);
      return ['blocked'=>false,'path'=>$dec];
  }
  return ['blocked'=>false,'path'=>$input];
}

// ---- Handle an attempt ---------------------------------------------------
$filename = $_GET['filename'] ?? '';
$result = null;
if (isset($_GET['filename'])) {
  $sess['tries']++;
  if ($filename === '') {
    $result = ['type'=>'empty','title'=>'Type a file name','msg'=>'Start by opening a normal file — try <code>welcome.txt</code>.'];
  } else {
    $r = lab_process($LEVEL, $filename);
    if ($r['blocked']) {
      $guide = $LEVEL==3
        ? "It checks your input <b>before</b> decoding it a second time — hide the forbidden characters so the check sees something harmless that decodes back to <code>../</code>."
        : ($LEVEL==4
          ? "Plain <code>../</code> is blocked. But the viewer decodes Unicode escapes first — write the dots and slash as <code>\\uXXXX</code>."
          : "The filter rejected that. Look at <i>how</i> it checks — there may be a gap.");
      $tok = isset($r['reason']) ? " (it caught <code>".htmlspecialchars($r['reason'])."</code>)" : "";
      $result = ['type'=>'blocked','title'=>'🛑 Blocked by the filter'.$tok,'msg'=>$guide];
    } else {
      $processed = $r['path'];
      $content = @file_get_contents($base.$processed);
      $proc_escaped = (strpos($processed,'..')!==false);
      $raw_tried    = (strpos($filename,'..')!==false || strpos($filename,'%2')!==false || strpos($filename,'\\u')!==false);
      if ($content === false) {
        if ($LEVEL==2 && $raw_tried && !$proc_escaped) {
          $result = ['type'=>'notfound','title'=>'🧹 The filter wiped your <code>../</code>','msg'=>"See? It stripped the traversal — but only <b>once</b>. Sneak one past a single cleanup: what collapses back into <code>../</code>?"];
        } elseif ($proc_escaped) {
          $result = ['type'=>'notfound','title'=>'🔥 Warmer — you climbed out!','msg'=>"You escaped <code>public/</code>, but there's no file there by that name. The prize is called <code>$prize</code> and sits <b>one</b> level up."];
        } else {
          $result = ['type'=>'notfound','title'=>'Not found','msg'=>"No file named that inside <code>public/</code>. Nothing secret lives in here — you'll need to <b>climb out</b> of the folder."];
        }
      } elseif (strpos($content,'CTF{')!==false) {
        $sess['steps']['read']=$sess['steps']['escape']=$sess['steps']['flag']=true;
        $result = ['type'=>'flag','title'=>'🎉 Flag captured!','body'=>$content];
      } else {
        $sess['steps']['read']=true;
        if ($proc_escaped) $sess['steps']['escape']=true;
        $result = ['type'=>'file','title'=>'📄 '.htmlspecialchars($filename),
          'msg'=>$proc_escaped ? "Nice — that was outside <code>public/</code>. Keep going toward <code>$prize</code>."
                               : "That's a normal file inside <code>public/</code>. The flag isn't in here — try climbing <b>out</b>.",
          'body'=>$content];
      }
    }
  }
}
$steps = $sess['steps'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $BRAND ?> — Lab <?= $cfg['num'] ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{--bg:#eef1fb;--panel:rgba(255,255,255,.78);--line:rgba(99,102,241,.16);--txt:#1a1f36;--muted:#6b7396;
    --cyan:#0891b2;--violet:#7c3aed;--pink:#db2777;--green:#059669;--amber:#d97706;--red:#e11d48;--accent:#6366f1;}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',system-ui,sans-serif;color:var(--txt);background:var(--bg);min-height:100vh;padding:26px 20px 60px;position:relative;overflow-x:hidden}
  .aurora{position:fixed;inset:-30%;z-index:0;filter:blur(85px);opacity:.5;pointer-events:none}
  .aurora span{position:absolute;border-radius:50%;mix-blend-mode:multiply}
  .aurora .b1{width:46vw;height:46vw;left:0;top:0;background:radial-gradient(circle,#c4b5fd,transparent 62%)}
  .aurora .b2{width:42vw;height:42vw;right:-4%;top:-6%;background:radial-gradient(circle,#a5f3fc,transparent 62%)}
  .aurora .b3{width:40vw;height:40vw;left:30%;bottom:-12%;background:radial-gradient(circle,#fbcfe8,transparent 62%)}
  .wrap{position:relative;z-index:2;max-width:860px;margin:0 auto}
  code{font-family:'JetBrains Mono',monospace;font-size:.9em;color:var(--violet);background:rgba(124,58,237,.08);padding:1px 6px;border-radius:5px}
  .top{display:flex;align-items:center;gap:13px;margin-bottom:16px}
  .logo{width:44px;height:44px;border-radius:12px;flex:none;display:grid;place-items:center;background:linear-gradient(135deg,#7c3aed,#0891b2);box-shadow:0 8px 20px -6px rgba(124,58,237,.5)}
  .logo svg{width:22px;height:22px}
  .top h1{font-family:'Space Grotesk',sans-serif;font-size:19px;color:#111527;line-height:1.1}
  .top .sub{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);letter-spacing:2px;text-transform:uppercase}
  .lvlbadge{margin-left:auto;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent);background:rgba(99,102,241,.1);border:1px solid var(--line);padding:6px 12px;border-radius:999px}
  .card{background:var(--panel);border:1px solid rgba(255,255,255,.7);border-radius:18px;padding:20px 22px;margin-bottom:16px;
    backdrop-filter:blur(16px) saturate(150%);-webkit-backdrop-filter:blur(16px) saturate(150%);box-shadow:0 18px 50px -26px rgba(79,70,229,.33)}
  .scen{font-size:14.5px;line-height:1.6;color:var(--txt)} .scen b{font-weight:600}
  .obj{margin-top:12px;padding:12px 15px;border-radius:11px;background:rgba(217,119,6,.08);border:1px solid rgba(217,119,6,.25);font-size:14px;color:#92400e}
  .obj b{color:#7c3a06}
  .steps{display:flex;gap:8px;margin:16px 0 2px;flex-wrap:wrap}
  .pstep{flex:1;min-width:150px;display:flex;align-items:center;gap:9px;padding:10px 13px;border-radius:11px;border:1px solid var(--line);background:rgba(255,255,255,.6);font-size:13px;color:var(--muted);transition:.2s}
  .pstep.done{border-color:rgba(5,150,105,.4);background:rgba(5,150,105,.08);color:#065f46}
  .pstep .dot{width:22px;height:22px;border-radius:50%;flex:none;display:grid;place-items:center;font-size:12px;font-weight:700;font-family:'Space Grotesk',sans-serif;background:#e5e9f5;color:var(--muted)}
  .pstep.done .dot{background:var(--green);color:#fff}
  .grid{display:grid;grid-template-columns:230px 1fr;gap:16px}
  @media(max-width:680px){.grid{grid-template-columns:1fr}}
  .tree{font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.9}
  .tree .h{font-family:'Space Grotesk',sans-serif;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
  .tree .up{color:var(--violet)} .tree .here{color:var(--txt);font-weight:600}
  .tree .f{color:var(--muted);cursor:pointer;padding-left:16px;display:block;border-radius:6px}
  .tree .f:hover{color:var(--accent);background:rgba(99,102,241,.08)}
  .tree .locked{color:var(--amber)} .tree .arrow{color:var(--pink)}
  .console h3{font-family:'Space Grotesk',sans-serif;font-size:13px;color:var(--muted);margin-bottom:9px;text-transform:uppercase;letter-spacing:1px}
  form.open{display:flex;gap:8px}
  form.open .pre{font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--muted);display:flex;align-items:center;padding:0 2px 0 4px}
  form.open input{flex:1;min-width:0;padding:12px 13px;font-size:14px;font-family:'JetBrains Mono',monospace;color:var(--txt);background:#fff;border:1px solid var(--line);border-radius:10px;outline:none;transition:.2s}
  form.open input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.12)}
  form.open button{border:none;border-radius:10px;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:13px;padding:0 18px;cursor:pointer;background:linear-gradient(135deg,#6366f1,#7c3aed)}
  .fb{margin-top:14px;border-radius:12px;padding:13px 15px;font-size:13.5px;line-height:1.55}
  .fb .t{font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:14.5px;margin-bottom:3px}
  .fb.file{background:rgba(99,102,241,.06);border:1px solid var(--line)} .fb.file .t{color:var(--accent)}
  .fb.notfound{background:rgba(217,119,6,.07);border:1px solid rgba(217,119,6,.3)} .fb.notfound .t{color:#b45309}
  .fb.blocked{background:rgba(225,29,72,.07);border:1px solid rgba(225,29,72,.3)} .fb.blocked .t{color:var(--red)}
  .fb.empty{background:rgba(99,102,241,.06);border:1px solid var(--line)}
  .viewer{margin-top:11px;font-family:'JetBrains Mono',monospace;font-size:12.5px;white-space:pre-wrap;word-break:break-word;background:#0f1424;color:#c9d4f5;border-radius:10px;padding:13px 15px;line-height:1.5;max-height:240px;overflow:auto}
  .win{text-align:center;padding:10px 6px 4px}
  .win .burst{font-size:46px;animation:pop .6s cubic-bezier(.2,1.5,.4,1) both}
  @keyframes pop{from{transform:scale(0) rotate(-15deg)}to{transform:scale(1)}}
  .win h2{font-family:'Space Grotesk',sans-serif;font-size:23px;color:var(--green);margin:12px 0 4px}
  .win .flagbox{margin:16px auto 6px;max-width:460px;font-family:'JetBrains Mono',monospace;font-size:15px;color:#047857;background:rgba(5,150,105,.07);border:1px dashed rgba(5,150,105,.6);border-radius:12px;padding:14px;word-break:break-all}
  .win .flagbox span{display:block;font-size:9px;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:5px}
  .win .sub2{color:var(--muted);font-size:13px;margin-top:8px}
  .win .clue{max-width:520px;margin:18px auto 0;text-align:left;position:relative;padding:14px 16px;border-radius:13px;background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(8,145,178,.05));border:1px solid var(--line)}
  .win .clue::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(180deg,var(--violet),var(--cyan))}
  .win .clue .l{font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--violet);margin-bottom:7px}
  .win .clue p{font-style:italic;font-size:14px;line-height:1.6;color:var(--txt)}
  .win .again{display:inline-block;margin-top:16px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted);text-decoration:none}
  .win .again:hover{color:var(--accent)}
  .hintbtn{border:1px dashed var(--line);background:rgba(99,102,241,.05);color:var(--accent);font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:13px;padding:10px 16px;border-radius:11px;cursor:pointer;transition:.18s}
  .hintbtn:hover{background:rgba(99,102,241,.12)}
  .hintlist{margin-top:12px;display:grid;gap:9px}
  .hintitem{padding:11px 14px;border-radius:11px;background:rgba(124,58,237,.06);border:1px solid var(--line);font-size:13.5px;line-height:1.55;color:var(--txt)}
  .hintitem .tag{display:block;font-family:'JetBrains Mono',monospace;font-size:9.5px;letter-spacing:2px;text-transform:uppercase;color:var(--violet);margin-bottom:4px}
  .tries{text-align:center;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--muted);margin-top:14px}
</style>
</head>
<body>
<div class="aurora"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
<div class="wrap">

  <div class="top">
    <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></div>
    <div><h1><?= $BRAND ?> Labs</h1><div class="sub">Lab <?= $cfg['num'] ?> · <?= htmlspecialchars($cfg['name']) ?></div></div>
    <span class="lvlbadge">LVL <?= $cfg['num'] ?> / 4</span>
  </div>

  <?php if ($result && $result['type']==='flag'): ?>
    <div class="card">
      <div class="win">
        <div class="burst">🎉</div>
        <h2>Flag captured!</h2>
        <div class="flagbox"><span>Your flag</span><?= htmlspecialchars(preg_match('/CTF\{[^}]*\}/',$result['body'],$m)?$m[0]:trim($result['body'])) ?></div>
        <div class="sub2">Copy it and submit it on the platform to score. 🏆</div>
        <div class="clue"><div class="l">⟐ your next move</div><p><?= $cfg['clue'] ?></p></div>
        <a class="again" href="?">‹ try again from scratch</a>
      </div>
    </div>
  <?php else: ?>

    <div class="card">
      <p class="scen"><?= $cfg['scenario'] ?></p>
      <div class="obj">🎯 <b>Your goal:</b> <?= $cfg['objective'] ?></div>
      <div class="steps">
        <div class="pstep <?= $steps['read']?'done':'' ?>"><span class="dot"><?= $steps['read']?'✓':'1' ?></span> Open a file</div>
        <div class="pstep <?= $steps['escape']?'done':'' ?>"><span class="dot"><?= $steps['escape']?'✓':'2' ?></span> Climb out of <code>public/</code></div>
        <div class="pstep <?= $steps['flag']?'done':'' ?>"><span class="dot"><?= $steps['flag']?'✓':'3' ?></span> Read the flag</div>
      </div>
    </div>

    <div class="grid">
      <div class="card tree">
        <div class="h">📁 File system</div>
        <div><span class="up">📂 ..</span> <span class="arrow">← the flag is up here</span></div>
        <div style="padding-left:14px"><span class="locked">🔒 <?= htmlspecialchars($prize) ?></span></div>
        <div style="margin-top:4px"><span class="here">📂 public/</span> <span style="color:var(--muted);font-size:11px">(you are here)</span></div>
        <?php foreach (array_keys($cfg['files']) as $fn): ?>
          <span class="f" onclick="fill('<?= htmlspecialchars($fn) ?>')">📄 <?= htmlspecialchars($fn) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="card console">
        <h3>📂 Document viewer</h3>
        <form class="open" method="get" action="">
          <span class="pre">public/</span>
          <input type="text" id="fn" name="filename" value="<?= htmlspecialchars($filename) ?>" placeholder="e.g. welcome.txt" autocomplete="off" autofocus>
          <button type="submit">Open →</button>
        </form>
        <?php if ($result && $result['type']!=='flag'): ?>
          <div class="fb <?= $result['type'] ?>">
            <div class="t"><?= $result['title'] ?></div>
            <?php if (!empty($result['msg'])): ?><div><?= $result['msg'] ?></div><?php endif; ?>
            <?php if (!empty($result['body'])): ?><div class="viewer"><?= htmlspecialchars($result['body']) ?></div><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <button class="hintbtn" id="hintbtn" type="button" onclick="nextHint()">💡 I'm stuck — give me a nudge</button>
      <div class="hintlist" id="hintlist"></div>
    </div>

    <div class="tries">attempts: <?= (int)$sess['tries'] ?></div>
  <?php endif; ?>

</div>
<script>
  function fill(n){ var i=document.getElementById('fn'); i.value=n; i.focus(); }
  var HINTS = <?= json_encode($cfg['hints']) ?>;
  var TAGS = ['nudge','warmer','the key'];
  var shown = 0;
  function nextHint(){
    if (shown >= HINTS.length) return;
    var d=document.createElement('div'); d.className='hintitem';
    d.innerHTML='<span class="tag">hint '+(shown+1)+' · '+(TAGS[shown]||'')+'</span>'+HINTS[shown];
    document.getElementById('hintlist').appendChild(d); shown++;
    var b=document.getElementById('hintbtn');
    if (shown>=HINTS.length){ b.textContent='😅 that was the last hint'; b.disabled=true; }
    else b.textContent='💡 still stuck — another hint ('+shown+'/'+HINTS.length+')';
  }
</script>
</body>
</html>
