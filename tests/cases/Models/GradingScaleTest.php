<?php

use App\Core\Database;
use App\Models\GradingScale;

class GradingScaleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
        if (!db_has_table('grading_scales')) {
            $this->markSkipped('جدول grading_scales غير موجود.');
        }
    }

    public function testCreateUpdateOverlapAndDelete(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $model = new GradingScale();
            $scaleName = 'CD016-Test-' . bin2hex(random_bytes(6));
            $id = $model->create([
                'scale_name' => $scaleName,
                'grade_label' => 'Example',
                'min_value' => 0,
                'max_value' => 49.99,
                'numeric_value' => 1,
                'sort_order' => 1,
                'is_active' => 1,
            ]);

            $created = $model->findById($id);
            $this->assertNotNull($created);
            $this->assertSame($scaleName, $created->scale_name);
            $this->assertTrue($model->hasOverlappingRange($scaleName, 49.99, 60));
            $this->assertFalse($model->hasOverlappingRange($scaleName, 50, 60));

            $model->update($id, [
                'scale_name' => $scaleName,
                'grade_label' => 'Updated',
                'min_value' => 0,
                'max_value' => 39.99,
                'numeric_value' => 2,
                'sort_order' => 2,
                'is_active' => 0,
            ]);
            $this->assertSame('Updated', $model->findById($id)->grade_label);

            $model->delete($id);
            $this->assertNull($model->findById($id));
        } finally {
            $db->rollBack();
        }
    }
}
