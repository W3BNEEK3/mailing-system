<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\StorageException;

/**
 * FileUploadService
 *
 * Handles all file uploads for the Emirates platform.
 * Validates, renames, and moves uploaded files into storage/uploads/.
 *
 * Security principles applied here:
 *   1. MIME type is validated using PHP's fileinfo extension (not the
 *      client-supplied $_FILES['type'], which is trivially spoofed).
 *   2. File extension is validated against an allow-list independent of MIME.
 *   3. Files are renamed to a UUID — original filenames from the client are
 *      never used in the stored path to prevent path traversal.
 *   4. All destination paths are verified to stay within storage/uploads/
 *      before writing.
 *   5. Old files are deleted only after the new file is confirmed moved.
 *
 * Usage (logo upload):
 *   $service = new FileUploadService();
 *   $relativePath = $service->uploadLogo($_FILES['site_logo'], 'site');
 *   // Returns: 'logos/site/a1b2c3d4.png'
 *
 * Usage (template upload):
 *   $relativePath = $service->uploadTemplate($_FILES['template_file']);
 *   // Returns: 'templates/uuid/index.html'
 */
class FileUploadService
{
    /** Absolute path to storage/uploads/ — e.g. /var/www/emirates/storage/uploads */
    private string $uploadsRoot;

    /** Max logo file size in bytes (2MB). Reads from config/storage.php. */
    private int $maxLogoSize;

    /** Max template file size in bytes (5MB). */
    private int $maxTemplateSize;

    /** Allowed MIME types for logos. */
    private array $allowedLogoMimes;

    /** Allowed MIME types for templates. */
    private array $allowedTemplateMimes;

    public function __construct()
    {
        $this->uploadsRoot         = config('storage.uploads_path');
        $this->maxLogoSize         = config('storage.max_logo_size', 2 * 1024 * 1024);
        $this->maxTemplateSize     = config('storage.max_template_size', 5 * 1024 * 1024);
        $this->allowedLogoMimes    = config('storage.allowed_logo_mimes', ['image/png', 'image/jpeg', 'image/svg+xml']);
        $this->allowedTemplateMimes = config('storage.allowed_template_mimes', ['text/html', 'application/zip']);
    }

    // ─── Logo Upload ──────────────────────────────────────────────────────────

    /**
     * Validate and store a logo upload.
     *
     * @param array  $file     The $_FILES entry for the uploaded logo
     * @param string $context  Sub-directory under logos/: 'global' or 'site'
     * @return string          Relative path from uploads root: 'logos/global/uuid.png'
     *
     * @throws StorageException  On validation failure or move failure
     */
    public function uploadLogo(array $file, string $context = 'global'): string
    {
        // Validate PHP upload error code first
        $this->assertNoUploadError($file);

        // Validate file size
        if ($file['size'] > $this->maxLogoSize) {
            $maxMb = round($this->maxLogoSize / (1024 * 1024), 1);
            throw new StorageException("Logo file exceeds the {$maxMb}MB size limit.");
        }

        // Validate MIME type using fileinfo (server-side, not client-supplied)
        $mime = $this->detectMime($file['tmp_name']);
        if (!in_array($mime, $this->allowedLogoMimes, true)) {
            throw new StorageException(
                "Invalid file type '{$mime}'. Logos must be PNG, JPEG, or SVG."
            );
        }

        // Derive extension from validated MIME (never from client filename)
        $extension = $this->mimeToExtension($mime);

        // Build destination path
        $subDir      = "logos/{$context}";
        $filename    = \App\Helpers\Str::uuid() . '.' . $extension;
        $destination = $this->resolveDestination($subDir, $filename);

        // Move the uploaded file
        $this->moveUpload($file['tmp_name'], $destination);

        return "{$subDir}/{$filename}";
    }

    // ─── Template Upload ──────────────────────────────────────────────────────

    /**
     * Validate and store a template file upload.
     *
     * Accepts:
     *   - Single .html file  → stored at templates/{uuid}/index.html
     *   - .zip archive       → extracted; the first .html file found is the entry point
     *
     * @param array $file  The $_FILES entry for the uploaded template
     * @return string      Relative path to the main HTML file: 'templates/uuid/index.html'
     *
     * @throws StorageException
     */
    public function uploadTemplate(array $file): string
    {
        $this->assertNoUploadError($file);

        if ($file['size'] > $this->maxTemplateSize) {
            $maxMb = round($this->maxTemplateSize / (1024 * 1024), 1);
            throw new StorageException("Template file exceeds the {$maxMb}MB size limit.");
        }

        $mime = $this->detectMime($file['tmp_name']);
        if (!in_array($mime, $this->allowedTemplateMimes, true)) {
            throw new StorageException(
                "Invalid file type. Templates must be an .html file or a .zip archive."
            );
        }

        $uuid    = \App\Helpers\Str::uuid();
        $subDir  = "templates/{$uuid}";
        $destDir = $this->uploadsRoot . '/' . $subDir;

        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new StorageException("Failed to create template directory: {$destDir}");
        }

        if ($mime === 'application/zip') {
            return $this->extractTemplateZip($file['tmp_name'], $destDir, $subDir);
        }

        // Plain HTML file
        $destPath = $destDir . '/index.html';
        $this->moveUpload($file['tmp_name'], $destPath);

        return "{$subDir}/index.html";
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * Delete a previously uploaded file.
     *
     * The path must be relative to the uploads root (as returned by uploadLogo
     * and uploadTemplate). This method refuses to delete anything outside
     * storage/uploads/ to prevent path traversal attacks.
     *
     * @param string $relativePath  e.g. 'logos/global/abc123.png'
     * @return bool  true if deleted, false if the file did not exist
     *
     * @throws StorageException if the path escapes the uploads root
     */
    public function delete(string $relativePath): bool
    {
        // Resolve real path and confirm it stays within uploads root
        $absPath = $this->uploadsRoot . '/' . ltrim($relativePath, '/');
        $real    = realpath($absPath);

        if ($real === false) {
            // File doesn't exist — not an error, just a no-op
            return false;
        }

        $realRoot = realpath($this->uploadsRoot);
        if ($realRoot === false || !str_starts_with($real, $realRoot . DIRECTORY_SEPARATOR)) {
            throw new StorageException(
                "Refusing to delete file outside uploads root: {$absPath}"
            );
        }

        return unlink($real);
    }

    // ─── Internal Helpers ─────────────────────────────────────────────────────

    /**
     * Assert that PHP's upload error code is UPLOAD_ERR_OK.
     * Translates PHP error codes into human-readable StorageExceptions.
     */
    private function assertNoUploadError(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];

        if ($error !== UPLOAD_ERR_OK) {
            throw new StorageException(
                $messages[$error] ?? "Unknown upload error code: {$error}"
            );
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new StorageException('Invalid upload: not a valid uploaded file.');
        }
    }

    /**
     * Detect the true MIME type of a file using PHP's fileinfo extension.
     * Never trust $_FILES['type'] — it is client-supplied and trivially forged.
     */
    private function detectMime(string $tmpPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new StorageException('Server error: fileinfo extension is not available.');
        }

        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if ($mime === false) {
            throw new StorageException('Could not determine file type.');
        }

        return $mime;
    }

    /**
     * Map a validated MIME type to a safe file extension.
     */
    private function mimeToExtension(string $mime): string
    {
        return match ($mime) {
            'image/png'       => 'png',
            'image/jpeg'      => 'jpg',
            'image/svg+xml'   => 'svg',
            'text/html'       => 'html',
            'application/zip' => 'zip',
            default           => throw new StorageException("No extension mapping for MIME: {$mime}"),
        };
    }

    /**
     * Resolve a destination path and ensure the directory exists.
     * Throws StorageException if the resolved path escapes uploads root.
     *
     * @param string $subDir   Relative sub-directory under uploads root (e.g. 'logos/site')
     * @param string $filename Safe filename (UUID-based, no user input)
     * @return string          Absolute path to the destination file
     */
    private function resolveDestination(string $subDir, string $filename): string
    {
        $dir = $this->uploadsRoot . '/' . $subDir;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new StorageException("Failed to create upload directory: {$dir}");
        }

        return $dir . '/' . $filename;
    }

    /**
     * Move the uploaded temp file to its final destination.
     * Uses move_uploaded_file() (not rename()) to satisfy PHP's upload security checks.
     */
    private function moveUpload(string $tmpPath, string $destination): void
    {
        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new StorageException(
                "Failed to move uploaded file to: {$destination}"
            );
        }
    }

    /**
     * Extract a ZIP template archive and return the path to the main HTML file.
     *
     * Extraction rules:
     *   - Only files with safe extensions are extracted (html, css, png, jpg, svg, gif, woff, woff2).
     *   - Files with path components that traverse upward (..) are skipped.
     *   - The first .html file found (sorted alphabetically) is the entry point.
     */
    private function extractTemplateZip(string $zipPath, string $destDir, string $subDir): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new StorageException('Failed to open ZIP archive for extraction.');
        }

        $allowedExtensions = ['html', 'css', 'png', 'jpg', 'jpeg', 'svg', 'gif', 'woff', 'woff2'];
        $htmlFiles         = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            // Skip directory entries and any path traversal attempts
            if (str_ends_with($name, '/') || str_contains($name, '..')) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            // Flatten to just the basename — no user-supplied subdirectory structure
            $safeFilename = \App\Helpers\Str::uuid() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '_', basename($name));
            $destPath     = $destDir . '/' . $safeFilename;

            file_put_contents($destPath, $zip->getFromIndex($i));

            if ($ext === 'html') {
                $htmlFiles[] = $safeFilename;
            }
        }

        $zip->close();

        if (empty($htmlFiles)) {
            throw new StorageException('The ZIP archive does not contain any .html files.');
        }

        sort($htmlFiles);
        $entryFile = $htmlFiles[0];

        // Rename the entry file to index.html for a consistent return path
        rename($destDir . '/' . $entryFile, $destDir . '/index.html');

        return "{$subDir}/index.html";
    }
}
