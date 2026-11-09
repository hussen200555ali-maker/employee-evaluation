<?php

use App\Services\EvaluationProgramContextService;

class CD037ContextRulesTest extends TestCase
{
    public function testCourseTargetRequiresCourseContext(): void
    {
        $this->assertSame('course', EvaluationProgramContextService::contextTypeForTargetType('employee_in_course'));
        $this->assertSame('course', EvaluationProgramContextService::contextTypeForTargetType('course'));
    }

    public function testWorkshopTargetRequiresWorkshopContext(): void
    {
        $this->assertSame('workshop', EvaluationProgramContextService::contextTypeForTargetType('employee_in_workshop'));
        $this->assertSame('workshop', EvaluationProgramContextService::contextTypeForTargetType('workshop'));
    }

    public function testEmployeeAndSkillTemplatesDoNotRequireTrainingContext(): void
    {
        $this->assertNull(EvaluationProgramContextService::contextTypeForTargetType('employee'));
        $this->assertNull(EvaluationProgramContextService::contextTypeForTargetType('skill'));
    }

    public function testCourseTemplateMapsToExistingCourseEvaluationType(): void
    {
        $this->assertSame('course', EvaluationProgramContextService::evaluationTypeForTargetType('employee_in_course'));
    }

    public function testWorkshopTemplateMapsToExistingWorkshopEvaluationType(): void
    {
        $this->assertSame('workshop', EvaluationProgramContextService::evaluationTypeForTargetType('employee_in_workshop'));
    }

    public function testSkillTypeIsDerivedWithoutAddingAnEnumValue(): void
    {
        $this->assertSame('annual', EvaluationProgramContextService::evaluationTypeForTargetType('skill'));
        $this->assertSame('periodic', EvaluationProgramContextService::evaluationTypeForTargetType('skill', 'periodic'));
    }

    public function testPeriodicEmployeeEvaluationTypeIsPreserved(): void
    {
        $this->assertSame('periodic', EvaluationProgramContextService::evaluationTypeForTargetType('employee', 'periodic'));
    }

    public function testProgramApisAreBoundToEvaluationPermission(): void
    {
        $routes = file_get_contents(APP_ROOT . '/routes/web.php');
        $controller = file_get_contents(APP_ROOT . '/app/Controllers/BulkEvaluationController.php');
        $this->assertStringContainsString('BulkEvaluationController@apiPrograms', $routes);
        $this->assertStringContainsString('BulkEvaluationController@apiShow', $routes);
        $this->assertStringContainsString('BulkEvaluationController@apiPendingEmployees', $routes);
        $start = strpos($controller, 'public function apiPrograms');
        $end = strpos($controller, 'public function schema', $start);
        $apiMethods = substr($controller, $start, $end - $start);
        $this->assertSame(3, substr_count($apiMethods, "Auth::enforce('evaluations.create')"));
    }

    public function testBulkViewLoadsPendingEmployeesFromUnifiedProgramEndpoint(): void
    {
        $view = file_get_contents(APP_ROOT . '/app/Views/evaluations/bulk-create.php');
        $this->assertStringContainsString("/api/training-programs/' + courseId + '/pending-employees", $view);
        $this->assertStringContainsString("formData.append('evaluator_notes'", $view);
        $this->assertStringContainsString("evaluator_notes: document.getElementById('evaluatorNotes').value", $view);
    }

    public function testIndividualAndBulkSaveUseSharedContextPersistence(): void
    {
        $individual = file_get_contents(APP_ROOT . '/app/Controllers/EvaluationController.php');
        $bulk = file_get_contents(APP_ROOT . '/app/Controllers/BulkEvaluationController.php');
        $this->assertStringContainsString('$contextService->resolveContext(', $individual);
        $this->assertStringContainsString('$contextService->persist(', $individual);
        $this->assertTrue(substr_count($bulk, '$contextService->resolveContext(') >= 2);
        $this->assertTrue(substr_count($bulk, '$contextService->persist(') >= 2);
    }

    public function testIndividualFillShowsProgramFieldsOnlyForCourseAndWorkshopTargets(): void
    {
        $fill = file_get_contents(APP_ROOT . '/app/Views/evaluations/fill.php');
        $this->assertStringContainsString("if (\$targetType === 'employee_in_course'):", $fill);
        $this->assertStringContainsString("elseif (\$targetType === 'employee_in_workshop'):", $fill);
        $this->assertStringContainsString('name="context_course_id"', $fill);
        $this->assertStringContainsString('name="context_workshop_id"', $fill);
        $this->assertStringNotContainsString("if (\$targetType === 'skill'):", $fill);
        $this->assertStringNotContainsString("if (\$targetType === 'employee'):", $fill);
    }
}
