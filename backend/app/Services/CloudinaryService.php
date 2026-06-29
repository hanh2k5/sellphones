<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected static function getClient()
    {
        $url = env('CLOUDINARY_URL');
        if ($url) {
            $parsed = parse_url($url);
            $cloudName = $parsed['host'] ?? null;
            $apiKey = $parsed['user'] ?? null;
            $apiSecret = $parsed['pass'] ?? null;
        } else {
            $cloudName = env('CLOUDINARY_CLOUD_NAME');
            $apiKey = env('CLOUDINARY_API_KEY');
            $apiSecret = env('CLOUDINARY_API_SECRET');
        }

        return new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
        ]);
    }

    public static function upload(UploadedFile $file, $folder = 'products')
    {
        try {
            $cloudinary = self::getClient();
            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
            ]);
            return $result['secure_url'];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function delete($url)
    {
        if (!$url || !str_starts_with($url, 'http')) {
            return;
        }

        try {
            $cloudinary = self::getClient();
            $publicId = self::getPublicId($url);
            if ($publicId) {
                $cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
            }
        } catch (\Exception $e) {
            Log::error('Cloudinary delete error: ' . $e->getMessage());
        }
    }

    protected static function getPublicId($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', $path);
        $uploadIndex = array_search('upload', $segments);
        if ($uploadIndex === false) return null;

        $startSegment = $uploadIndex + 1;
        if (isset($segments[$startSegment]) && preg_match('/^v\d+$/', $segments[$startSegment])) {
            $startSegment++;
        }

        $publicIdSegments = array_slice($segments, $startSegment);
        $publicIdPath = implode('/', $publicIdSegments);

        // Strip file extension to get the raw public ID
        $filename = pathinfo($publicIdPath, PATHINFO_FILENAME);
        $dir = pathinfo($publicIdPath, PATHINFO_DIRNAME);
        
        return $dir !== '.' ? $dir . '/' . $filename : $filename;
    }
}
