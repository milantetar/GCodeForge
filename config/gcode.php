<?php

return [
    'converter_version' => env('GCODE_CONVERTER_VERSION', '1'),
    'disk' => env('GCODE_DISK', 'local'),
    'max_input_bytes' => (int) env('GCODE_MAX_INPUT_BYTES', 2 * 1024 * 1024),
    'max_repetitions' => (int) env('GCODE_MAX_REPETITIONS', 1000),
    'queue_threshold_bytes' => (int) env('GCODE_QUEUE_THRESHOLD_BYTES', 256 * 1024),
    'result_cache_seconds' => (int) env('GCODE_RESULT_CACHE_SECONDS', 900),
];
