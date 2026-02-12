<?php

return [
    'enable_attachments' => filter_var(env('ENABLE_ATTACHMENTS', false), FILTER_VALIDATE_BOOLEAN),
];
