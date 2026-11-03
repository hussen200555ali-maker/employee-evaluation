<?php

use App\Services\ReportExportService;

class ReportExportHeadersTest extends TestCase
{
    private object $snapshot;

    public function setUp(): void
    {
        parent::setUp();
        $this->snapshot = (object)[
            'report_number' => 'ER-TEST-0001',
            'created_at'    => '2026-11-03 19:00:00',
            'snapshot_data' => [
                'employee' => [
                    'full_name' => 'اختبار الموظف',
                    'emp_code' => 'TEST-001',
                    'department_name' => 'تقنية المعلومات',
                    'branch_name' => 'الرياض',
                ],
                'report' => [
                    'averages' => [
                        'overall' => ['average' => 4.5, 'grade_label' => 'جيد جداً'],
                        'annual' => ['average' => 5.0, 'grade_label' => 'ممتاز'],
                        'periodic' => ['average' => null, 'grade_label' => null],
                        'course' => ['average' => 4.0, 'grade_label' => 'جيد جداً'],
                        'workshop' => ['average' => 3.0, 'grade_label' => 'جيد'],
                    ],
                    'records' => [
                        ['evaluation_date' => '2026-01-15', 'evaluation_type_label' => 'سنوي', 'template_name' => 'أداء سنوي', 'grade_value' => 5.0, 'grade_label' => 'ممتاز'],
                    ],
                ],
            ],
        ];
    }

    public function testPdfContainsLogoAndSignatures(): void
    {
        $service = new ReportExportService();
        $ref = new \ReflectionMethod($service, 'buildHtml');
        $html = $ref->invoke($service, $this->snapshot);
        $hasLogo = strpos($html, '<img') !== false || strpos($html, 'شعار الشركة') !== false;
        $this->assertTrue($hasLogo, 'PDF HTML must contain logo.');
        $this->assertStringContainsString('الموظف', $html);
        $this->assertStringContainsString('المدير المباشر', $html);
        $this->assertStringContainsString('مدير الموارد البشرية', $html);
        $this->assertStringContainsString('الاسم:', $html);
        $this->assertStringContainsString('التوقيع:', $html);
        $this->assertStringContainsString('التاريخ:', $html);
    }

    public function testXlsxContainsLogoAndSignatures(): void
    {
        $service = new ReportExportService();
        $result = $service->render('xlsx', $this->snapshot);
        $this->assertStringStartsWith('PK', $result['content']);
        $xml = $this->extractZipEntry($result['content'], 'xl/sharedStrings.xml');
        $this->assertStringContainsString('الموظف', $xml);
        $this->assertStringContainsString('المدير المباشر', $xml);
        $this->assertStringContainsString('مدير الموارد البشرية', $xml);
        $this->assertStringContainsString('التوقيعات', $xml);
    }

    public function testDocxContainsLogoAndSignatures(): void
    {
        $service = new ReportExportService();
        $result = $service->render('docx', $this->snapshot);
        $this->assertStringStartsWith('PK', $result['content']);
        $xml = $this->extractZipEntry($result['content'], 'word/document.xml');
        $this->assertStringContainsString('الموظف', $xml);
        $this->assertStringContainsString('المدير المباشر', $xml);
        $this->assertStringContainsString('مدير الموارد البشرية', $xml);
        $this->assertStringContainsString('التوقيعات', $xml);
    }

    private function extractZipEntry(string $zipBinary, string $entryName): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cd020_test_');
        file_put_contents($tmpFile, $zipBinary);
        $zip = new \ZipArchive();
        $opened = $zip->open($tmpFile);
        $this->assertTrue($opened === true);
        $content = $zip->getFromName($entryName);
        $zip->close();
        unlink($tmpFile);
        $this->assertNotFalse($content);
        return $content;
    }
}
