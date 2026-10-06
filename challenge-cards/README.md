# 🎯 Challenge Cards — NIGHTFALL Security-Awareness Lab

Fun, player-facing cards for each lab: catchy title, story description, flag, tiered hints, and a vulnerable-line explanation. One file per challenge — paste straight into your platform.

| # | Card | Title | Vector | Endpoint | Flag |
|---|------|-------|--------|----------|------|
| 0 | [00-sql-injection.md](00-sql-injection.md) | 🚪 Knock Twice, Walk Right In | SQL Injection | `POST /` | `CTF{8ee4d84cbeef15123c24791da26006d9}` |
| 1 | [01-basic-traversal.md](01-basic-traversal.md) | 🗺️ Two Dots and a Slash Walk Into a Server | Path Traversal | `/dr-strange?filename=` | `CTF{75c4bcd59149b3004345e414c325f3b6}` |
| 2 | [02-dot-segment.md](02-dot-segment.md) | 🛡️ The Sanitizer That Blinks Once | Path Traversal | `/captain-america?filename=` | `CTF{234b5a3edf97aa319646be27d8a89db0}` |
| 3 | [03-double-decode.md](03-double-decode.md) | 🎭 Check the Mask, Not the Face | Path Traversal | `/deadpool?filename=` | `CTF{2c7c8d5ec9db726a120b290922222e47}` |
| 4 | [04-unicode-escape.md](04-unicode-escape.md) | 🐉 Speak in Spells, Slip Through Walls | Path Traversal | `/goku?filename=` | `CTF{fb4f3b1240b02c9c9efd10a01777d13f}` |

> Full technical setup (source files, remediation table, run commands) lives in [`../LAB_SETUP.md`](../LAB_SETUP.md).
> Flags live in `../flags/*.txt` and can be re-randomized any time (see LAB_SETUP.md).
