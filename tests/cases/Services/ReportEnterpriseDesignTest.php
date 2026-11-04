<?php

use App\Services\ReportExportService;

class ReportEnterpriseDesignTest extends TestCase
{
    private object $snapshot;

    public function setUp(): void
    {
        parent::setUp();
        $this->snapshot = (object)[
            'report_number' => 'ER-TEST-0001',
            'created_at' => '2026-11-04 12:00:00',
            'snapshot_data' => [
                'employee' => [
                    'full_name' => 'أحمد محمد العمري',
                    'emp_code' => 'EMP-001',
                    'department_name' => 'المبيعات',
                    'branch_name' => 'الرياض',
                    'position_title' => 'أخصائي مبيعات',
                    'manager_name' => 'سلمان',
                    'email' => 'ahmed@example.com',
                    'phone' => '0501234567',
                    'hire_date' => '2023-01-15',
                    'status' => 'نشط',
                ],
                'report' => [
                    'averages' => [
                        'overall' => ['average' => 85.0, 'grade_label' => 'جيد جداً'],
                        'annual' => ['average' => 90.0, 'grade_label' => 'ممتاز'],
                        'periodic' => ['average' => null, 'grade_label' => null],
                        'course' => ['average' => 80.0, 'grade_label' => 'جيد جداً'],
                        'workshop' => ['average' => 75.0, 'grade_label' => 'جيد'],
                    ],
                    'records' => [
                        [
                            'evaluation_date' => '2026-01-15',
                            'evaluation_type_label' => 'سنوي',
                            'template_name' => 'أداء سنوي',
                            'final_score' => 90.0,
                            'grade_label' => 'ممتاز',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testPdfContainsEnterpriseCoverBrandingAndFooter(): void
    {
        $service = new ReportExportService();
        $method = new \ReflectionMethod($service, 'buildHtml');
        $html = $method->invoke($service, $this->snapshot);

        $this->assertStringContainsString('class="cover"', $html, 'Must have cover page.');
        $this->assertStringContainsString('سرّي', $html, 'Must have confidential marking.');
        $this->assertStringContainsString('صفحة', $html, 'Must reference page numbering.');
        $this->assertStringContainsString('مسند', $html, 'Must use official document term.');
        $this->assertStringContainsString('#0c2823', $html, 'Must use primary brand color.');
        $this->assertStringContainsString('#c8943f', $html, 'Must use accent brand color.');
        $this->assertStringContainsString('dejavusans', $html, 'Must use the Arabic-shaping font.');

        $pdf = $service->render('pdf', $this->snapshot);
        $this->assertStringStartsWith('%PDF-', $pdf['content']);
        $this->assertGreaterThan(1000, strlen($pdf['content']));
    }

    public function testDocxHasCoverPageAndSignatures(): void
    {
        $result = (new ReportExportService())->render('docx', $this->snapshot);
        $this->assertStringStartsWith('PK', $result['content']);
        $xml = $this->extractZipEntry($result['content'], 'word/document.xml');
        $this->assertStringContainsString('مسند', $xml);
        $this->assertStringContainsString('التوقيعات', $xml);
    }

    public function testXlsxHasReportDataAndSignatures(): void
    {
        $result = (new ReportExportService())->render('xlsx', $this->snapshot);
        $this->assertStringStartsWith('PK', $result['content']);
        $xml = $this->extractZipEntry($result['content'], 'xl/sharedStrings.xml');
        $this->assertStringContainsString('التوقيعات', $xml);
        $this->assertStringContainsString('تقرير', $xml);
    }

    private function extractZipEntry(string $zipBinary, string $entryName): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'cd020_s36_');
        if ($temporaryFile === false) {
            $this->fail('Unable to allocate a temporary archive file.');
        }

        try {
            if (file_put_contents($temporaryFile, $zipBinary) === false) {
                $this->fail('Unable to write the temporary archive file.');
            }
            $zip = new \ZipArchive();
            if ($zip->open($temporaryFile) !== true) {
                $this->fail('Unable to open the generated archive.');
            }
            try {
                $content = $zip->getFromName($entryName);
            } finally {
                $zip->close();
            }
            $this->assertNotFalse($content);
            return $content;
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }
}
