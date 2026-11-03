<?php

use App\Services\AveragesEngine;

class AveragesEngineTest extends TestCase
{
    private AveragesEngine $engine;

    public function setUp(): void
    {
        parent::setUp();
        $this->engine = new AveragesEngine();
    }

    public function testNormalAverageCalculation(): void
    {
        $scale = $this->mockScale();
        $records = [
            $this->mockRecord('2025-01-15', 5.0),
            $this->mockRecord('2025-06-20', 4.0),
            $this->mockRecord('2025-09-10', 3.0),
        ];
        $summary = $this->engine->summarize($records, $scale);
        $this->assertSame(3, $summary['evaluation_count']);
        $this->assertSame(3, $summary['scored_count']);
        $this->assertEquals(4.0, $summary['average']);
        $this->assertSame('جيد جداً', $summary['grade_label']);
    }

    public function testEmptyRecordsProduceNullAverage(): void
    {
        $scale = $this->mockScale();
        $summaryA = $this->engine->summarize([], $scale);
        $this->assertSame(0, $summaryA['evaluation_count']);
        $this->assertNull($summaryA['average']);
        $this->assertNull($summaryA['grade_label']);

        $records = [
            $this->mockRecord('2025-01-15', null),
            $this->mockRecord('2025-06-20', null),
        ];
        $summaryB = $this->engine->summarize($records, $scale);
        $this->assertSame(2, $summaryB['evaluation_count']);
        $this->assertSame(0, $summaryB['scored_count']);
        $this->assertNull($summaryB['average']);
    }

    public function testSummarizeByYearWithEmptyYears(): void
    {
        $scale = $this->mockScale();
        $records = [
            $this->mockRecord('2024-04-10', 5.0),
            $this->mockRecord('2026-02-20', 3.0),
        ];
        $yearly = $this->engine->summarizeByYear($records, $scale);
        $this->assertSame(3, count($yearly));
        $this->assertSame(2024, $yearly[0]['year']);
        $this->assertEquals(5.0, $yearly[0]['average']);
        $this->assertSame('ممتاز', $yearly[0]['grade_label']);
        $this->assertSame(2025, $yearly[1]['year']);
        $this->assertSame(0, $yearly[1]['evaluation_count']);
        $this->assertNull($yearly[1]['average']);
        $this->assertSame(2026, $yearly[2]['year']);
        $this->assertEquals(3.0, $yearly[2]['average']);
        $this->assertSame('جيد', $yearly[2]['grade_label']);
    }

    private function mockScale(): array
    {
        return [
            (object)['grade_label' => 'ممتاز', 'min_value' => 90.0, 'max_value' => 100.0, 'numeric_value' => 5.0, 'sort_order' => 1],
            (object)['grade_label' => 'جيد جداً', 'min_value' => 80.0, 'max_value' => 89.99, 'numeric_value' => 4.0, 'sort_order' => 2],
            (object)['grade_label' => 'جيد', 'min_value' => 70.0, 'max_value' => 79.99, 'numeric_value' => 3.0, 'sort_order' => 3],
            (object)['grade_label' => 'مقبول', 'min_value' => 60.0, 'max_value' => 69.99, 'numeric_value' => 2.0, 'sort_order' => 4],
            (object)['grade_label' => 'ضعيف', 'min_value' => 0.0, 'max_value' => 59.99, 'numeric_value' => 1.0, 'sort_order' => 5],
        ];
    }

    private function mockRecord(string $date, ?float $gradeValue): object
    {
        return (object)[
            'evaluation_date' => $date,
            'grade_value'     => $gradeValue,
        ];
    }
}
