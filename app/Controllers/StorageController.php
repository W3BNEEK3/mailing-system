<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\StorageException;

/**
 * StorageController
 *
 * Serves files from storage/uploads/ over HTTP.
 *
 * Why this exists:
 *   Uploaded files (logos, templates) are stored in storage/uploads/, which
 *   is intentionally outside the public/ web root so they cannot be accessed
 *   directly by URL. This controller acts as a gated proxy — it authenticates
 *   the request (via AuthMiddleware on the route), validates the requested
 *   path, and then streams the file.
 *
 * Route:
 *   GET /storage/{type}/{filename}
 *   AuthMiddleware applied — unauthenticated requests are redirected to /login.
 *
 * Security:
 *   - $type is validated against an allow-list ('logos', 'templates').
 *   - The resolved absolute path is checked with str_starts_with() to
 *     confirm it stays within storage/uploads/ (prevents path traversal).
 *   - realpath() is called to resolve any '..' or symlink tricks.
 *   - Files that do not exist return a 404.
 */
class StorageController extends BaseController
{
    /**
     * Serve a file from storage/uploads/{type}/{filename}.
     *
     * @param Request $request
     * @param string  $type      One of: 'logos', 'templates'
     * @param string  $filename  The bare filename — e.g. 'a1b2c3.png'
     * @return Response
     *
     * @throws NotFoundException   If the file does not exist
     * @throws StorageException    If the path escapes the uploads root
     */
    public function serve(Request $request, string $type, string $filename): Response
    {
        // Validate type against allow-list
        $allowedTypes = ['logos', 'templates'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new NotFoundException("Unknown storage type: {$type}");
        }

        // Build the absolute path
        $uploadsRoot = storage_path('uploads');
        $absPath     = $uploadsRoot . '/' . $type . '/' . $filename;

        // Resolve real path (handles any .., symlinks, etc.)
        $realPath = realpath($absPath);

        if ($realPath === false) {
            throw new NotFoundException("File not found: {$type}/{$filename}");
        }

        // Path traversal check — real path must start with the uploads root
        $realRoot = realpath($uploadsRoot);
        if ($realRoot === false || !str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) {
            throw new StorageException("Path traversal attempt detected for: {$absPath}");
        }

        // Confirm file exists and is a regular file (not a directory)
        if (!is_file($realPath)) {
            throw new NotFoundException("Not a file: {$realPath}");
        }

        // Detect MIME type using fileinfo
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $realPath) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        // Stream the file to the browser
        return Response::stream($realPath, $mimeType);
    }
}
