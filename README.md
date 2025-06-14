# 🛡️ File Inclusion CTF Lab

Welcome to the **File Inclusion CTF Lab** – a deliberately vulnerable Java Spring Boot application designed to help developers and security enthusiasts understand, exploit, and defend against common **Local File Inclusion (LFI)** and **Path Traversal** vulnerabilities, including bypassing **blacklist-based filtering** and **internal microservices routing** through LFI.

---

## 🚀 What’s Inside?

This lab simulates real-world exploitation scenarios across 4 increasing levels of complexity.

### 🔥 Vulnerability Scenarios:

| Level | Endpoint              | Description                                                                 |
|-------|-----------------------|-----------------------------------------------------------------------------|
| 1     | `/dr-strange`         | Basic Path Traversal (`../`) with no protection.                            |
| 2     | `/deadpool`           | Basic blacklist filtering – bypassable with URL encoding.                  |
| 3     | `/captain-america`    | Blacklist + encoded slash bypass (`%2f`, mixed slashes).                   |
| 4     | `/goku`               | Double decoding vulnerability (`%c0%af`, `%252e%252e`) to bypass blacklist.|

---

## 🧪 Purpose

This intentionally vulnerable code is designed to:

- Teach developers the dangers of insecure file handling.
- Demonstrate how blacklist filters can be bypassed.
- Showcase how path traversal can expose internal files and microservices data.
- Highlight encoding/decoding tricks used by attackers.

---

## 🧩 Setup Instructions

### Prerequisites

- Java 11+
- Maven
- Git

### Clone & Run

```bash
git clone https://github.com/your-username/file-inclusion-ctf.git
cd file-inclusion-ctf
mvn spring-boot:run
