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

- **Payload**:
