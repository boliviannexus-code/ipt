<?php

return [
    'disk' => env('IMAGE_DISK', 'public'),
    'max_width' => (int) env('IMAGE_MAX_WIDTH', 1920),
    'max_height' => (int) env('IMAGE_MAX_HEIGHT', 1920),
    'max_pixels' => (int) env('IMAGE_MAX_PIXELS', 40000000),
    'quality' => (int) env('IMAGE_WEBP_QUALITY', 82),
];
