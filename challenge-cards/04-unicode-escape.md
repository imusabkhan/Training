# 🐉 Level 4 — Speak in Spells, Slip Through Walls

> **Vector:** Path Traversal (Unicode escape decoding) · **Endpoint:** `GET /goku?filename=`

*(alt titles: "Goku Reads Your \uXXXX" · "The Wall That Understands Runes" · "Properties, Please")*

---

## 📝 Description
Goku's viewer is clever: before opening a file it *un-escapes* your input, turning `\uXXXX` runes into real characters. It looks locked down — until you realize you can write `.` and `/` as spells. Speak the traversal in Unicode and the wall translates it back into an escape route.

Your mission: encode your `../` as Unicode escapes and read the flag.

## 🧩 Vulnerable Code
**`ChallengesController.java` → `secureRead4()` + `decodeUnicode()`**
```java
// Challenge 4: Unicode escape decoding
@GetMapping("/goku")
public String secureRead4(@RequestParam String filename, Model model) {
    try {
        String decodedFilename = decodeUnicode(filename);                          // ◀ un-escapes \uXXXX
        Path filePath = Paths.get(UPLOAD_DIR).resolve(decodedFilename).normalize(); // ◀ VULNERABLE
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
        model.addAttribute("filename", decodedFilename);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}

private String decodeUnicode(String input) throws Exception {
    Properties props = new Properties();
    props.load(new StringReader("key=" + input));   // ◀ Java Properties decodes \uXXXX escapes
    return props.getProperty("key");
}
```

## 🚩 Flag
```
CTF{fb4f3b1240b02c9c9efd10a01777d13f}
```
*(from `flags/flag4.txt`, read as `../flag4.txt`)*

## 💡 Hints
- **Nudge:** The app feeds your input through Java `Properties`, which decodes `\uXXXX` escapes into real characters before the file is read.
- **Warmer:** `.` is `.` and `/` is `/`. So `../` becomes `../` *after* decoding.
- **The key:** send the backslash URL-encoded as `%5c`.
  ```bash
  curl "http://<host>/goku?filename=%5cu002e%5cu002e%5cu002fflag4.txt"
  # in a browser you can also just use ../flag4.txt
  ```

## 🔍 Vulnerable Line Explanation
**`ChallengesController.java` → `secureRead4()` + `decodeUnicode()`**
```java
String decodedFilename = decodeUnicode(filename);                          // ◀ un-escapes \uXXXX
Path filePath = Paths.get(UPLOAD_DIR).resolve(decodedFilename).normalize(); // ◀ VULNERABLE
...
props.load(new StringReader("key=" + input));   // ◀ Java Properties decodes \uXXXX escapes
```
Input is Unicode-unescaped **before** the read, so `../` is rebuilt into `../` and used to resolve the path. The `normalize()` then happily collapses it into a real traversal — the decoding step handed the attacker exactly the characters a filter would have blocked.

**Fix:** decode all layers first, then canonicalize and confirm the final path stays under the base directory (whitelist known-good names).
