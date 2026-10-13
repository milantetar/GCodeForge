<?php

return [
    'converter_version' => env('GCODE_CONVERTER_VERSION', '2'),
    'disk' => env('GCODE_DISK', 'local'),
    'max_input_bytes' => (int) env('GCODE_MAX_INPUT_BYTES', 2 * 1024 * 1024),
    'max_repetitions' => (int) env('GCODE_MAX_REPETITIONS', 1000),
    'max_output_bytes' => (int) env('GCODE_MAX_OUTPUT_BYTES', 16 * 1024 * 1024),
    'max_output_lines' => (int) env('GCODE_MAX_OUTPUT_LINES', 100000),
    'queue_threshold_bytes' => (int) env('GCODE_QUEUE_THRESHOLD_BYTES', 256 * 1024),
    'result_cache_seconds' => (int) env('GCODE_RESULT_CACHE_SECONDS', 900),
];
