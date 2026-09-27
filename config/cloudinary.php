<?php

return [
    'url' => env('CLOUDINARY_URL'),
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', 'ml_default'),
    // Carpeta por defecto para productos — coincide con lo que ya existe en Cloudinary
    'folder' => env('CLOUDINARY_FOLDER', 'tag-q/producto'),
];
