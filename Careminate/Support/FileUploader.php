<?php declare(strict_types=1);
namespace Careminate\Support;

class FileUploader
{
    /**
     * Handles file uploads with validation and error handling.
     *
     * @param array  $file       The file from $_FILES.
     * @param string|null $uploadDir  The directory where the file will be uploaded.
     * @param string|null $fileName  The custom file name (optional).
     * @param array  $allowedTypes Allowed MIME types (default: common image types).
     * @param int    $maxSize    Maximum file size in bytes (default: 5MB).
     * @return string|null The file name or null if the upload failed.
     */
    public static function store(array $file, ?string $uploadDir = null, ?string $fileName = null, array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], int $maxSize = 5242880): ?string
    {
        // Validate upload error
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            return null;
        }
        
        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            return null;
        }
        
        // Determine file name
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $fileName ? preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName) : uniqid('', true) . '.' . $fileExtension;
        
        // Ensure upload directory is set
        if (!$uploadDir) {
            return null;
        }

        $filePath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        
        // Ensure the directory exists
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            return null;
        }
        
        // Move uploaded file
        return move_uploaded_file($file['tmp_name'], $filePath) ? $fileName : null;
    }
}
