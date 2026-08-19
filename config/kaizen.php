<?php

return [
    'attachments' => [
        'disk' => env('KAIZEN_ATTACHMENT_DISK', 'local'), // Private safe disk
        'max_images_per_context' => 8,
        'max_image_kb' => 8192,
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],
];
