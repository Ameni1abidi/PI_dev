<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryStorageService
{
    public function __construct(
        private readonly string $cloudUrl,
        private readonly string $cloudName,
        private readonly string $apiKey,
        private readonly string $apiSecret
    ) {
    }

    /**
     * @return array{secureUrl: string, publicId: string, resourceType: string}
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        $this->assertConfigured();
        [$cloudName, $apiKey, $apiSecret] = $this->resolveCredentials();

        if (!class_exists('Cloudinary\\Cloudinary')) {
            throw new \RuntimeException('Le SDK Cloudinary est manquant. Installez cloudinary/cloudinary_php.');
        }

        $cloudinaryClass = 'Cloudinary\\Cloudinary';
        $cloudinary = new $cloudinaryClass([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => ['secure' => true],
        ]);

        $resourceType = $this->resolveResourceType($file);
        $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => trim($folder, '/'),
            'resource_type' => $resourceType,
            'use_filename' => true,
            'unique_filename' => true,
            'overwrite' => false,
        ]);

        $secureUrl = (string) ($result['secure_url'] ?? '');
        $publicId = (string) ($result['public_id'] ?? '');

        if ($secureUrl === '' || $publicId === '') {
            throw new \RuntimeException('Upload Cloudinary invalide: URL securisee ou public_id manquant.');
        }

        return [
            'secureUrl' => $secureUrl,
            'publicId' => $publicId,
            'resourceType' => $resourceType,
        ];
    }

    public function delete(?string $publicId, ?string $resourceType): void
    {
        if ($publicId === null || $publicId === '') {
            return;
        }

        $this->assertConfigured();
        [$cloudName, $apiKey, $apiSecret] = $this->resolveCredentials();

        if (!class_exists('Cloudinary\\Cloudinary')) {
            return;
        }

        $cloudinaryClass = 'Cloudinary\\Cloudinary';
        $cloudinary = new $cloudinaryClass([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => ['secure' => true],
        ]);

        $cloudinary->uploadApi()->destroy($publicId, [
            'resource_type' => $resourceType ?: 'raw',
            'invalidate' => true,
        ]);
    }

    private function assertConfigured(): void
    {
        [$cloudName, $apiKey, $apiSecret] = $this->resolveCredentials();
        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            throw new \RuntimeException('Configuration Cloudinary incomplete. Verifiez CLOUDINARY_CLOUD_NAME/API_KEY/API_SECRET.');
        }
    }

    private function resolveResourceType(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true)) {
            return 'video';
        }

        // Fallback to client MIME type when server-side MIME guessers are unavailable.
        $mimeType = strtolower((string) $file->getClientMimeType());
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'raw';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolveCredentials(): array
    {
        $envCloudName = $this->readEnv('CLOUDINARY_CLOUD_NAME');
        $envApiKey = $this->readEnv('CLOUDINARY_API_KEY');
        $envApiSecret = $this->readEnv('CLOUDINARY_API_SECRET');
        $envCloudUrl = $this->readEnv('CLOUDINARY_URL');

        $cloudName = $this->cloudName !== '' ? $this->cloudName : $envCloudName;
        $apiKey = $this->apiKey !== '' ? $this->apiKey : $envApiKey;
        $apiSecret = $this->apiSecret !== '' ? $this->apiSecret : $envApiSecret;
        $cloudUrl = $this->cloudUrl !== '' ? $this->cloudUrl : $envCloudUrl;

        if ($cloudName !== '' && $apiKey !== '' && $apiSecret !== '') {
            return [$cloudName, $apiKey, $apiSecret];
        }

        if ($cloudUrl !== '') {
            $parsed = parse_url($cloudUrl);
            $parsedCloudName = (string) ($parsed['host'] ?? '');
            $parsedApiKey = urldecode((string) ($parsed['user'] ?? ''));
            $parsedApiSecret = urldecode((string) ($parsed['pass'] ?? ''));
            if ($parsedCloudName !== '' && $parsedApiKey !== '' && $parsedApiSecret !== '') {
                return [$parsedCloudName, $parsedApiKey, $parsedApiSecret];
            }
        }

        return [$cloudName, $apiKey, $apiSecret];
    }

    private function readEnv(string $name): string
    {
        $fromEnv = $_ENV[$name] ?? null;
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $fromServer = $_SERVER[$name] ?? null;
        if (is_string($fromServer) && $fromServer !== '') {
            return $fromServer;
        }

        $fromGetEnv = getenv($name);

        return is_string($fromGetEnv) ? $fromGetEnv : '';
    }
}
