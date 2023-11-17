<?php

namespace App\Services;

class ImageHelper
{
    public static function convertImageToBase64($imagePath) {
        // Vérifiez si le fichier image existe
        if (file_exists($imagePath)) {
            // Lire le contenu du fichier en binaire
            $imageData = file_get_contents($imagePath);

            // Convertir les données binaires en données Base64
            $base64Data = 'data:image/png;base64,' . base64_encode($imageData);

            return $base64Data;
        } else {
            return false; // Gérer le cas où le fichier image n'existe pas
        }
    }
}
