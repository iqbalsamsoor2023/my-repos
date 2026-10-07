<?php

return [
    'batch_size' => env('EMAIL_BATCH_SIZE', 50),
    'batch_delay' => env('EMAIL_BATCH_DELAY', 10),
    'queue_name' => env('EMAIL_QUEUE_NAME', 'EmailBlastQueue'), ];
