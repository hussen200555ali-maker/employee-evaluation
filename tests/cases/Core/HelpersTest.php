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

        // OOS-17: مُنسّق Intl (ar-SA-u-ca-islamic-umalqura) يُرجع السنة بأرقام
        // هندية (مثال «١٤٤٧»)، لا ASCII. القياس: is_numeric('١٤٤٧')=false و
        // (int)'١٤٤٧'=0. لذلك فشلت is_numeric وتحققت (int)$year>1300 زورًا.
        // نحوّل الأرقام العربية (٠-٩) إلى ASCII محليًا (لا نعتمد على واجهة
        // خارجية غير موجودة)، فنتحقق من القيمة الحقيقية لا من تمثيلها.
        $yearDigits = strtr((string)$result->year, [
            "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3',
            "\u{0664}" => '4', "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7',
            "\u{0668}" => '8', "\u{0669}" => '9',
        ]);
        $this->assertTrue(ctype_digit($yearDigits), 'سنة هجرية متوقعة كأرقام: ' . $result->year);
        $this->assertGreaterThan(1300, (int)$yearDigits);
        $this->assertLessThanOrEqual(1600, (int)$yearDigits);
    }
}
