<?php
/**
 * اختبارات الدوال المساعدة (app/Core/helpers.php)
 */

class HelpersTest extends TestCase
{
    public function testCsrfFieldGeneratesHiddenInput(): void
    {
        $this->skipIfDbUnavailable();
        $_SESSION['user_id'] = 1;
        $html = csrf_field();
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertMatches('/value="[0-9a-f]{64}"/', $html);
    }

    public function testConvertToHijriNullForInvalidInput(): void
    {
        $this->skipIfNoExtension('intl');
        $this->assertNull(convertToHijri(null));
        $this->assertNull(convertToHijri(''));
        $this->assertNull(convertToHijri('invalid-date-value-xyz'));
    }

    public function testConvertToHijriValidDate(): void
    {
        $this->skipIfNoExtension('intl');
        $result = convertToHijri('2026-01-15');
        $this->assertNotNull($result);
        $this->assertObjectHasProperty('full_date', $result);
        $this->assertObjectHasProperty('day', $result);
        $this->assertObjectHasProperty('month', $result);
        $this->assertObjectHasProperty('month_name', $result);
        $this->assertObjectHasProperty('year', $result);
        $this->assertObjectHasProperty('weekday', $result);
        $this->assertTrue(is_numeric($result->year));
        $this->assertGreaterThan(1300, (int)$result->year);
        $this->assertLessThanOrEqual(1600, (int)$result->year);
    }
}
