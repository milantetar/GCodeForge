<?php

namespace Tests\Unit\Domain;

use App\Domain\GCode\Converters\HexagonalFlatConverter;
use App\Domain\GCode\Converters\PatternRepeaterConverter;
use App\Domain\GCode\Converters\TwoAxisConverter;
use App\Domain\GCode\Exceptions\InvalidGCode;
use App\Domain\GCode\Parsing\GCodeTokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GCodeConvertersTest extends TestCase
{
    public function test_pattern_repeater_transforms_coordinates_and_applies_decimal_safe_offsets(): void
    {
        $converter = new PatternRepeaterConverter(new GCodeTokenizer);

        $result = $converter->convert(implode("\r\n", [
            '; header',
            'M3',
            'Z4 X2 Y1.5 ; first point',
            'Z4 X-1 Y2',
            'M5',
        ]), ['repetitions' => 2, 'y_distance' => '5']);

        $this->assertStringContainsString('Z1.500000 X-2.000000 Y2.000000 ; first point', $result->output);
        $this->assertStringContainsString('Z2.000000 X1.000000 Y-1.000000', $result->output);
        $this->assertStringContainsString('Z1.500000 X-2.000000 Y7.000000', $result->output);
        $this->assertStringContainsString('Z2.000000 X1.000000 Y4.000000', $result->output);
        $this->assertSame(2, $result->metadata['repetitions']);
    }

    public function test_hexagonal_flat_runs_transformations_in_the_required_order(): void
    {
        $converter = new HexagonalFlatConverter(new GCodeTokenizer);

        $result = $converter->convert(implode("\n", [
            'G90',
            'M3',
            'X13.738900 Y0.016700',
            'Z0',
            'Z4.000000',
            'M5',
        ]), ['repetitions' => 2, 'y_distance' => '2']);

        $this->assertStringNotContainsString("\nZ0\n", $result->output);
        $this->assertStringContainsString('X0 Y13.738900', $result->output);
        $this->assertStringContainsString('Z4.016700', $result->output);
        $this->assertStringContainsString('X0 Y15.738900', $result->output);
        $this->assertSame(2, substr_count($result->output, 'Z4.016700'));
    }

    public function test_two_axis_repeats_the_body_with_b_axis_and_normalized_feed_rates(): void
    {
        $converter = new TwoAxisConverter(new GCodeTokenizer);

        $result = $converter->convert(implode("\n", [
            'G90',
            'M3',
            'G1 F999 X1 Y2',
            'G1 X2 Y3 F888',
            'Z4',
            'M5',
        ]), [
            'repetitions' => 3,
            'b_increment' => '8.16',
            'rapid_feed_rate' => 10000,
            'engraving_feed_rate' => 1200,
        ]);

        $this->assertStringContainsString('G1 F10000 X1 Y2 B0.000000', $result->output);
        $this->assertStringContainsString('G1 X2 Y3 F1200 B8.160000', $result->output);
        $this->assertStringContainsString('G1 X2 Y3 F1200 B16.320000', $result->output);
        $this->assertSame(1, substr_count($result->output, "M3\n"));
        $this->assertSame(1, substr_count($result->output, "M5\n"));
    }

    /** @param array<string, mixed> $options */
    #[DataProvider('invalidPrograms')]
    public function test_converters_reject_invalid_programs(string $converterClass, string $gcode, array $options): void
    {
        $this->expectException(InvalidGCode::class);

        $converter = new $converterClass(new GCodeTokenizer);
        $converter->convert($gcode, $options);
    }

    /** @return iterable<string, array{class-string, string, array<string, mixed>}> */
    public static function invalidPrograms(): iterable
    {
        yield 'pattern missing coordinates' => [
            PatternRepeaterConverter::class,
            'M3\nM5',
            ['repetitions' => 1, 'y_distance' => 2],
        ];

        yield 'hexagonal missing preceding y' => [
            HexagonalFlatConverter::class,
            'M3\nZ4\nM5',
            ['repetitions' => 1, 'y_distance' => 2],
        ];

        yield 'two axis missing m3' => [
            TwoAxisConverter::class,
            'G1 X1 Y1\nZ4',
            [
                'repetitions' => 1,
                'b_increment' => 1,
                'rapid_feed_rate' => 100,
                'engraving_feed_rate' => 50,
            ],
        ];
    }
}
