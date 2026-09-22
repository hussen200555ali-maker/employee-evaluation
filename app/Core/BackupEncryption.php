<?php
namespace App\Core;

class BackupEncryption {
    private $encryptionMethod = 'AES-256-GCM';
    
    /**
     * تشفير ملف النسخ الاحتياطي
     */
    public function encryptBackup($filePath, $key) {
        $encryptedPath = $filePath . '.enc';
        
        // توليد IV عشوائي
        $iv = random_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        
        // تشفير الملف
        $encryptedData = openssl_encrypt(
            file_get_contents($filePath),
            $this->encryptionMethod,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        // حفظ الملف المشفر مع IV و Tag
        file_put_contents($encryptedPath, $iv . $tag . $encryptedData);
        
        // حذف الملف الأصلي غير المشفر
        unlink($filePath);
        
        return $encryptedPath;
    }
    
    /**
     * فك تشفير ملف النسخ الاحتياطي
     */
    public function decryptBackup($encryptedPath, $key) {
        $data = file_get_contents($encryptedPath);
        
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encryptedData = substr($data, $ivLength + 16);
        
        $decryptedData = openssl_decrypt(
            $encryptedData,
            $this->encryptionMethod,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decryptedData === false) {
            throw new \Exception("فشل فك التشفير: الملف تالف أو مفتاح غير صحيح");
        }
        
        return $decryptedData;
    }
    
    /**
     * توليد مفتاح تشفير آمن
     */
    public function generateKey() {
        return base64_encode(random_bytes(32)); // 256-bit key
    }
}