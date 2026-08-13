# AI Configuration Folder

This folder holds the Google Gemini API credentials for the ClassSense AI
Academic Insight feature. **It is blocked from direct web access** by the
`.htaccess` deny rule — the key can only be read server-side via `require()`.

## Files

| File         | Purpose                                                              |
| ------------ | -------------------------------------------------------------------- |
| `config.php` | Returns the Gemini API key, model, endpoint, and API revision.      |
| `.htaccess`  | Denies all direct web access to this folder.                        |
| `README.md`  | This file.                                                           |

## Getting / rotating the API key

1. Go to <https://aistudio.google.com/apikey> and sign in with your Google account.
2. Click **Create API key** and copy it (starts with `AIza...`).
3. Open `config.php` and replace the value of `gemini_api_key`.
4. Save — no restart needed; the key is read on every request.

## Notes

- `config.php` is git-ignored (see root `.gitignore`) so the key is never
  committed to the repository.
- The project uses the **Interactions API** (`POST /v1beta/interactions`,
  `Api-Revision: 2026-05-20`) because Google retires the legacy
  `generateContent` endpoint for new keys.
- Free tier of `gemini-3.6-flash` is sufficient for this feature; insights are
  cached per student/class for 6 hours and re-analyzed only when the underlying
  grades/attendance data changes.
