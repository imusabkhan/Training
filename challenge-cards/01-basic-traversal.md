# 🗺️ Level 1 — Two Dots and a Slash Walk Into a Server

> **Vector:** Path Traversal (no protection) · **Endpoint:** `GET /dr-strange?filename=`

*(alt titles: "The Open Sesame" · "Wrong Turn at uploads/" · "Dr. Strange's Portal Has No Wards")*

---

## 📝 Description
Dr. Strange conjured a file viewer that reads anything you name from the `uploads/` folder. Trouble is, the portal has no wards — point it *upward* and it happily wanders out of the folder and into the rest of the filesystem.

Your mission: escape `uploads/` and read the flag sitting one level up.

## 🧩 Vulnerable Code
**`ChallengesController.java` → `readFile()`**
```java
private static final String UPLOAD_DIR = "uploads/";

// Challenge 1: Basic Path Traversal
@GetMapping("/dr-strange")
public String readFile(@RequestParam String filename, Model model) {
    try {
        // Vulnerable: direct file read without any validation
        Path filePath = Paths.get(UPLOAD_DIR + filename);   // ◀ VULNERABLE — no validation
        String content = Files.readString(filePath);
        model.addAttribute("content", content);
        model.addAttribute("filename", filename);
    } catch (Exception e) {
        model.addAttribute("error", "File not found or cannot be read: " + e.getMessage());
    }
    return "file-content";
}
```

## 🚩 Flag
```
CTF{75c4bcd59149b3004345e414c325f3b6}
```
*(from `flags/flag1.txt`, read as `../flag1.txt`)*

## 💡 Hints
- **Nudge:** The viewer just sticks your filename onto the end of `uploads/`. Where does the file live compared to that folder?
- **Warmer:** `..` means "go up one directory." Nothing here is stopping you from using it.
- **The key:**
  ```bash
  curl "http://<host>/dr-strange?filename=../flag1.txt"
  # bonus: ../../../../../../etc/passwd
  ```

## 🔍 Vulnerable Line Explanation
**`ChallengesController.java` → `readFile()`**
```java
Path filePath = Paths.get(UPLOAD_DIR + filename);   // ◀ VULNERABLE — no validation
```
The raw `filename` is glued onto the base directory with **zero filtering**. A `../` sequence walks straight out of `uploads/`, so the "file viewer" becomes a "read anything on disk" tool.

**Fix:** canonicalize the path (`toRealPath()` / `normalize()`) and assert the result still lives under `UPLOAD_DIR` before reading.
