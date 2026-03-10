<?php

namespace App\Service;

/**
 * Service pour chiffrer/déchiffrer les données sensibles (ex: mots de passe SMTP).
 * Utilise l'algorithme AES-256-CBC avec la clé APP_SECRET de Symfony.
 */
class EncryptionService
{
    private string $secretKey;
    private string $cipher = 'aes-256-cbc';

    public function __construct(string $appSecret)
    {
        // Dériver une clé de 32 octets à partir de APP_SECRET
        $this->secretKey = hash('sha256', $appSecret, true);
    }

    /**
     * Chiffre une chaîne de caractères.
     */
    public function encrypt(string $plainText): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($plainText, $this->cipher, $this->secretKey, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement des données.');
        }

        // Stocker IV + données chiffrées encodées en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre une chaîne de caractères.
     */
    public function decrypt(string $encryptedText): string
    {
        $data = base64_decode($encryptedText);

        if ($data === false) {
            throw new \RuntimeException('Erreur lors du décodage des données chiffrées.');
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->secretKey, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Erreur lors du déchiffrement des données.');
        }

        return $decrypted;
    }
}
