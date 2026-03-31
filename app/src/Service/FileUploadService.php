<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class FileUploadService
{
    private string $uploadDirectory;
    private Filesystem $filesystem;

    public function __construct(string $uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->filesystem = new Filesystem();
    }

    /**
     * Upload un fichier
     */
    public function upload(UploadedFile $file, ?string $subDirectory = null): string
    {
        $directory = $this->uploadDirectory;
        if ($subDirectory) {
            $directory .= '/' . $subDirectory;
            $this->filesystem->mkdir($directory);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($directory, $newFilename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        return $subDirectory ? $subDirectory . '/' . $newFilename : $newFilename;
    }

    /**
     * Supprime un fichier
     */
    public function delete(string $filename): void
    {
        $filePath = $this->uploadDirectory . '/' . $filename;
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    /**
     * Récupère le chemin complet d'un fichier
     */
    public function getFullPath(string $filename): string
    {
        return $this->uploadDirectory . '/' . $filename;
    }

    /**
     * Vérifie si un fichier existe
     */
    public function fileExists(string $filename): bool
    {
        return $this->filesystem->exists($this->uploadDirectory . '/' . $filename);
    }
}
