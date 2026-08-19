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

        /**
         * The managed storage path prefix under which all Kaizen attachment
         * files reside. Audit and cleanup operations are strictly confined to
         * paths that begin with this prefix. An empty or root-level prefix
         * will cause cleanup operations to refuse and abort for safety.
         */
        'managed_prefix' => env('KAIZEN_ATTACHMENT_PREFIX', 'kaizens'),

        /**
         * Allowed storage disks for KaizenAttachments. A DB row whose
         * storage_disk is not in this list will be flagged as INVALID_DISK.
         */
        'allowed_disks' => [
            env('KAIZEN_ATTACHMENT_DISK', 'local'),
        ],

        /**
         * Grace period in minutes before a physical file without a DB record
         * is considered a cleanup candidate. Protects in-flight uploads whose
         * DB transaction has not yet committed.
         */
        'orphan_grace_minutes' => (int) env('KAIZEN_ORPHAN_GRACE_MINUTES', 10),
    ],
];
