<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use phpseclib3\Net\SFTP;

class AdminUploadController extends AdminController
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');

        if (!$this->validateFileSecurity($file)) {
            return $this->error('文件类型不合法', 400);
        }

        $fileType = $this->getFileType($file);
        $path     = $this->storeFile($file, $fileType);

        return $this->success([
            'file_name' => $this->sanitizeFileName($file->getClientOriginalName()),
            'file_size' => $file->getSize(),
            'file_type' => $fileType,
            'mime_type' => $file->getMimeType(),
            'path'      => $path,
            'url'       => Storage::disk('resource_sftp')->url($path),
        ], '上传成功');
    }

    public function batchUpload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240',
        ]);

        $files         = $request->file('files');
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (!$this->validateFileSecurity($file)) {
                continue;
            }

            $fileType = $this->getFileType($file);
            $path     = $this->storeFile($file, $fileType);

            $uploadedFiles[] = [
                'file_name' => $this->sanitizeFileName($file->getClientOriginalName()),
                'file_size' => $file->getSize(),
                'file_type' => $fileType,
                'mime_type' => $file->getMimeType(),
                'path'      => $path,
                'url'       => Storage::disk('resource_sftp')->url($path),
            ];
        }

        return $this->success($uploadedFiles, '批量上传成功');
    }

    protected function validateFileSecurity(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType  = $file->getMimeType();

        $allowedExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',
            'mp3', 'wav', 'ogg',
            'mp4', 'avi', 'mov', 'wmv',
            'zip', 'rar', '7z', 'tar', 'gz'
        ];

        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'application/rtf', 'application/vnd.oasis.opendocument.text',
            'audio/mpeg', 'audio/wav', 'audio/ogg',
            'video/mp4', 'video/avi', 'video/quicktime', 'video/x-ms-wmv',
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            'application/x-tar', 'application/gzip'
        ];

        return in_array($extension, $allowedExtensions) && in_array($mimeType, $allowedMimes);
    }

    protected function sanitizeFileName(string $fileName): string
    {
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        return $fileName;
    }

    protected function getFileType($file)
    {
        $mime      = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        if (str_starts_with($mime, 'image/')) {
            return 'images';
        }

        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        $documentMimes      = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        if (in_array(strtolower($extension), $documentExtensions) || in_array($mime, $documentMimes)) {
            return 'documents';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'videos';
        }

        $archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
        $archiveMimes      = ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/x-tar', 'application/gzip'];
        if (in_array(strtolower($extension), $archiveExtensions) || in_array($mime, $archiveMimes)) {
            return 'archives';
        }

        return 'others';
    }

    protected function storeFile(UploadedFile $file, string $fileType)
    {
        $convertToWebp = Config::get('permission.upload.convert_to_webp', true);
        $webpQuality   = Config::get('permission.upload.webp_quality', 80);
        $disk          = Storage::disk('resource_sftp');

        $extension    = $file->getClientOriginalExtension();
        $mimeType     = $file->getMimeType();
        $originalPath = $file->getRealPath();

        $directory  = 'resources/' . $fileType . '/' . date('Y/m/d');
        $randomName = self::generateRandomString(32, false);

        $this->ensureDirectoryExists($disk, $directory);

        $isImage       = str_starts_with($mimeType, 'image/');
        $isAlreadyWebp = $mimeType === 'image/webp';

        if ($isImage) {
            if ($isAlreadyWebp) {
                $fileName = $randomName . '.webp';
                $fullPath = $directory . '/' . $fileName;
                $disk->put($fullPath, file_get_contents($originalPath));
            } else if ($convertToWebp) {
                $tempPath = tempnam(sys_get_temp_dir(), 'webp_') . '.webp';
                $image    = null;

                switch ($mimeType) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($originalPath);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($originalPath);
                        if ($image !== false) {
                            imagealphablending($image, true);
                            imagesavealpha($image, true);
                        }
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($originalPath);
                        break;
                }

                if ($image !== false && function_exists('imagewebp')) {
                    $webpConverted = imagewebp($image, $tempPath, $webpQuality);
                    if ($image !== false) {
                        imagedestroy($image);
                    }

                    if ($webpConverted && file_exists($tempPath)) {
                        $fileName = $randomName . '.webp';
                        $fullPath = $directory . '/' . $fileName;
                        $disk->put($fullPath, file_get_contents($tempPath));
                        unlink($tempPath);
                    } else {
                        $fileName = $randomName . '.' . $extension;
                        $fullPath = $directory . '/' . $fileName;
                        $disk->put($fullPath, file_get_contents($originalPath));
                    }
                } else {
                    if ($image !== false) {
                        imagedestroy($image);
                    }
                    $fileName = $randomName . '.' . $extension;
                    $fullPath = $directory . '/' . $fileName;
                    $disk->put($fullPath, file_get_contents($originalPath));
                }
            } else {
                $fileName = $randomName . '.' . $extension;
                $fullPath = $directory . '/' . $fileName;
                $disk->put($fullPath, file_get_contents($originalPath));
            }
        } else {
            $fileName = $randomName . '.' . $extension;
            $fullPath = $directory . '/' . $fileName;
            $disk->put($fullPath, file_get_contents($originalPath));
        }

        return $fullPath;
    }

    protected function ensureDirectoryExists($disk, string $directory)
    {
        $sftpConfig    = Config::get('filesystems.disks.resource_sftp', []);
        $host          = $sftpConfig['host'];
        $username      = $sftpConfig['username'];
        $password      = $sftpConfig['password'];
        $port          = $sftpConfig['port'] ?? 22;
        $root          = $sftpConfig['root'] ?? '/';
        $directoryPerm = $sftpConfig['directoryPerm'] ?? 0755;

        $permission = is_string($directoryPerm) ? octdec($directoryPerm) : (int)$directoryPerm;

        try {
            $sftp = new SFTP($host, $port);
            if (!$sftp->login($username, $password)) {
                throw new \Exception("SFTP login failed");
            }

            $fullPath = rtrim($root, '/') . '/' . $directory;
            if (!$sftp->file_exists($fullPath)) {
                if (!$sftp->mkdir($fullPath, $permission, true)) {
                    throw new \Exception("Failed to create directory via SFTP: $fullPath");
                }
            } else {
                $sftp->chmod($permission, $fullPath, true);
            }

            $sftp->disconnect();
        } catch (\Exception $e) {
            $disk->makeDirectory($directory, $permission, true);
        }
    }

    public static function generateRandomString(int $len = 32, bool $special = false): string
    {
        $chars = array_merge(
            range('a', 'z'), range('A', 'Z'), range('0', '9')
        );

        if ($special) {
            $chars = array_merge($chars, str_split('!@#$?|{/:;%^&*()-_[]}<~+=,.'));
        }

        $randomString = '';
        $max          = count($chars) - 1;

        for ($i = 0; $i < $len; $i ++) {
            $randomString .= $chars[random_int(0, $max)];
        }

        return $randomString;
    }
}
