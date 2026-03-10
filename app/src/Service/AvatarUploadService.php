<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class AvatarUploadService
{
    public function __construct(
        private string $avatarDirectory,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Upload un avatar et retourne le nom du fichier
     */
    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->avatarDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload de l\'avatar: ' . $e->getMessage());
        }

        return $newFilename;
    }

    /**
     * Supprime un avatar du serveur
     */
    public function delete(string $filename): void
    {
        $filePath = $this->avatarDirectory . '/' . $filename;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Vérifie si le fichier est une image valide
     */
    public function isValidImage(UploadedFile $file): bool
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $allowedMimeTypes, true);
    }

    /**
     * Vérifie la taille du fichier (max 2MB)
     */
    public function isValidSize(UploadedFile $file, int $maxSizeInMb = 2): bool
    {
        return $file->getSize() <= ($maxSizeInMb * 1024 * 1024);
    }

    /**
     * Redimensionne l'image (optionnel, nécessite l'extension GD)
     */
    public function resize(string $filename, int $width = 200, int $height = 200): void
    {
        $filePath = $this->avatarDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return;
        }

        // Vérifier si l'extension GD est disponible
        if (!extension_loaded('gd')) {
            return;
        }

        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return;
        }

        $mimeType = $imageInfo['mime'];

        // Créer l'image source selon le type
        $sourceImage = match($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filePath),
            'image/png' => imagecreatefrompng($filePath),
            'image/gif' => imagecreatefromgif($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$sourceImage) {
            return;
        }

        // Créer l'image redimensionnée
        $resizedImage = imagecreatetruecolor($width, $height);

        // Préserver la transparence pour PNG et GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        }

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $width,
            $height,
            imagesx($sourceImage),
            imagesy($sourceImage)
        );

        // Sauvegarder l'image redimensionnée
        match($mimeType) {
            'image/jpeg' => imagejpeg($resizedImage, $filePath, 90),
            'image/png' => imagepng($resizedImage, $filePath, 9),
            'image/gif' => imagegif($resizedImage, $filePath),
            'image/webp' => imagewebp($resizedImage, $filePath, 90),
            default => null,
        };

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    }

    /**
     * Retourne le chemin du répertoire des avatars
     */
    public function getAvatarDirectory(): string
    {
        return $this->avatarDirectory;
    }
}
