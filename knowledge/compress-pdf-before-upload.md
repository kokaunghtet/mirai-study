# Compressing exam PDFs before upload

Exam paper uploads are capped at **20 MB** (`ExamPaperController::store` →
`'file' => [... 'max:20480']`). Some JLPT papers are large scanned PDFs that
exceed this. Rather than raising the limit, **compress the PDF locally** before
uploading — scanned papers shrink 60–90% with no visible quality loss.

This is a manual pre-upload step. No app code is involved.

## One-time install

**macOS:**
```bash
brew install ghostscript
```

**Linux (Debian/Ubuntu):**
```bash
sudo apt update && sudo apt install ghostscript
```
(Fedora: `sudo dnf install ghostscript` · Arch: `sudo pacman -S ghostscript`)

**Windows:**
- Installer: download from https://ghostscript.com/releases/gsdnld.html, or
- `choco install ghostscript` / `winget install ArtifexSoftware.GhostScript`

> **Windows binary name is different.** The command is `gswin64c` (64-bit) or
> `gswin32c` (32-bit), **not** `gs`. Swap `gs` → `gswin64c` in every command
> below. macOS/Linux use `gs`.

## Compress a single file

macOS/Linux:
```bash
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 \
   -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH \
   -sOutputFile=out.pdf in.pdf
```

Windows (PowerShell or cmd):
```bat
gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=out.pdf in.pdf
```

Then check the size and upload `out.pdf` if it fits:

```bash
ls -lh out.pdf                 # macOS / Linux
```
```powershell
(Get-Item out.pdf).Length/1MB  # Windows PowerShell — value is in MB
```

Under 20 MB → upload it. Still over → rerun with a smaller `-dPDFSETTINGS`.

## Quality dial (`-dPDFSETTINGS`)

| Setting    | DPI  | Use when                                   |
|------------|------|--------------------------------------------|
| `/screen`  | 72   | Smallest, softest — last resort            |
| `/ebook`   | 150  | **Start here** — best size/quality balance |
| `/printer` | 300  | Sharp, larger — if `/ebook` looks too soft |

## Batch-compress a folder

macOS / Linux (bash):
```bash
for f in *.pdf; do
  gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook \
     -dNOPAUSE -dQUIET -dBATCH -sOutputFile="compressed_$f" "$f"
done
```

Windows (PowerShell):
```powershell
Get-ChildItem *.pdf | ForEach-Object {
  gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook `
     -dNOPAUSE -dQUIET -dBATCH -sOutputFile="compressed_$($_.Name)" $_.Name
}
```

## Notes

- Compression is **lossy on images**. Text-only scans → invisible. Color
  diagrams/photos → eyeball one output before bulk-processing.
- Fallback for a file that won't fit even at `/ebook`: host it on Google Drive
  and store an external link instead (not yet built — would add a `file_type`
  = link branch to `store`/`view`/`download`).
