<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/** Produces permission-aware dashboard data; the browser never chooses a data scope. */
final class RoleDashboardService
{
    private Database $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? Database::getInstance();
    }

    /** @return array<string,mixed> */
    public function forUser(int $userId, int $employeeId, array $permissions, array $roles, string $userName): array
    {
        $persona = $this->resolvePersona($employeeId, $permissions, $roles);
        [$where, $params, $scopeLabel] = $this->evaluationScope($persona, $employeeId);
        $pdo = $this->database->getPdo();

        $summarySql = 'SELECT COUNT(ev.id) AS evaluations, COALESCE(SUM(ev.status="completed"),0) AS completed, COALESCE(SUM(ev.status="draft"),0) AS open_count, COALESCE(AVG(CASE WHEN ev.status="completed" THEN ev.final_score END),0) AS average_score FROM evaluations ev JOIN employees e ON e.id=ev.evaluated_employee_id WHERE ' . $where;
        $summaryStatement = $pdo->prepare($summarySql);
        $summaryStatement->execute($params);
        $summary = $summaryStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $employeeMetric = $this->employeeMetric($persona, $employeeId);
        $trendStatement = $pdo->prepare('SELECT DATE_FORMAT(ev.evaluation_date,"%Y-%m") AS month_key, COUNT(*) AS total, ROUND(AVG(CASE WHEN ev.status="completed" THEN ev.final_score END),2) AS average_score FROM evaluations ev JOIN employees e ON e.id=ev.evaluated_employee_id WHERE ' . $where . ' AND ev.evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key ORDER BY month_key');
        $trendStatement->execute($params);
        $trend = $trendStatement->fetchAll(PDO::FETCH_ASSOC);

        $distributionStatement = $pdo->prepare('SELECT SUM(ev.final_score >= 85) AS excellent, SUM(ev.final_score >= 70 AND ev.final_score < 85) AS strong, SUM(ev.final_score >= 50 AND ev.final_score < 70) AS developing, SUM(ev.final_score < 50) AS critical_count FROM evaluations ev JOIN employees e ON e.id=ev.evaluated_employee_id WHERE ' . $where . ' AND ev.status="completed"');
        $distributionStatement->execute($params);
        $distribution = $distributionStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $recentStatement = $pdo->prepare('SELECT ev.id,ev.employee_name,ev.employee_code,ev.status,ev.final_score,ev.evaluation_date,ft.template_name FROM evaluations ev JOIN employees e ON e.id=ev.evaluated_employee_id LEFT JOIN form_templates ft ON ft.id=ev.template_id WHERE ' . $where . ' ORDER BY ev.updated_at DESC,ev.id DESC LIMIT 6');
        $recentStatement->execute($params);
        $recent = $recentStatement->fetchAll(PDO::FETCH_ASSOC);

        $tasksStatement = $pdo->prepare('SELECT ev.id,ev.employee_name,ev.evaluation_date,ft.template_name FROM evaluations ev JOIN employees e ON e.id=ev.evaluated_employee_id LEFT JOIN form_templates ft ON ft.id=ev.template_id WHERE ' . $where . ' AND ev.status="draft" ORDER BY ev.evaluation_date ASC,ev.id DESC LIMIT 5');
        $tasksStatement->execute($params);
        $tasks = $tasksStatement->fetchAll(PDO::FETCH_ASSOC);

        return [
            'persona' => $persona,
            'persona_label' => $this->personaLabel($persona),
            'scope_label' => $scopeLabel,
            'user_name' => $userName,
            'permissions' => array_fill_keys($permissions, true),
            'summary' => [
                'evaluations' => (int)($summary['evaluations'] ?? 0),
                'completed' => (int)($summary['completed'] ?? 0),
                'open' => (int)($summary['open_count'] ?? 0),
                'average' => round((float)($summary['average_score'] ?? 0), 1),
                'employees' => $employeeMetric,
            ],
            'trend' => $this->normaliseTrend($trend),
            'distribution' => [
                'labels' => ['متميز', 'قوي', 'قيد التطوير', 'يحتاج تدخلاً'],
                'values' => [(int)($distribution['excellent'] ?? 0), (int)($distribution['strong'] ?? 0), (int)($distribution['developing'] ?? 0), (int)($distribution['critical_count'] ?? 0)],
            ],
            'recent' => $recent,
            'tasks' => $tasks,
        ];
    }

    private function resolvePersona(int $employeeId, array $permissions, array $roles): string
    {
        if (in_array('Admin', $roles, true) || in_array('admin', array_map('strtolower', $roles), true)) { return 'admin'; }
        if (in_array('reports.view.all', $permissions, true) && in_array('employees.view', $permissions, true)) { return 'hr'; }
        if (in_array('reports.view.team', $permissions, true) && $employeeId > 0) { return 'manager'; }
        if (in_array('evaluations.fill', $permissions, true) && $employeeId > 0) { return 'evaluator'; }
        return 'self';
    }

    /** @return array{0:string,1:array<string,int>,2:string} */
    private function evaluationScope(string $persona, int $employeeId): array
    {
        return match ($persona) {
            'admin', 'hr' => ['1=1', [], 'نطاق مؤسسي'],
            'manager' => ['e.manager_id=:scope_employee', ['scope_employee'=>$employeeId], 'فريقك المباشر'],
            'evaluator' => ['ev.evaluator_employee_id=:scope_employee', ['scope_employee'=>$employeeId], 'التقييمات المسندة إليك'],
            default => ['ev.evaluated_employee_id=:scope_employee', ['scope_employee'=>$employeeId], 'ملفك الشخصي'],
        };
    }

    private function employeeMetric(string $persona, int $employeeId): int
    {
        $pdo = $this->database->getPdo();
        if ($persona === 'admin' || $persona === 'hr') { return (int)$pdo->query('SELECT COUNT(*) FROM employees WHERE is_active=1')->fetchColumn(); }
        if ($persona === 'manager') { $statement = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE manager_id=:employee_id AND is_active=1'); $statement->execute(['employee_id'=>$employeeId]); return (int)$statement->fetchColumn(); }
        if ($persona === 'evaluator') { $statement = $pdo->prepare('SELECT COUNT(DISTINCT evaluated_employee_id) FROM evaluations WHERE evaluator_employee_id=:employee_id'); $statement->execute(['employee_id'=>$employeeId]); return (int)$statement->fetchColumn(); }
        return $employeeId > 0 ? 1 : 0;
    }

    /** @return array{labels:array<int,string>,counts:array<int,int>,averages:array<int,float>} */
    private function normaliseTrend(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) { $map[(string)$row['month_key']] = $row; }
        $labels = []; $counts = []; $averages = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $key = date('Y-m', strtotime('-' . $offset . ' months'));
            $labels[] = date('m/Y', strtotime($key . '-01'));
            $counts[] = (int)($map[$key]['total'] ?? 0);
            $averages[] = (float)($map[$key]['average_score'] ?? 0);
        }
        return ['labels'=>$labels,'counts'=>$counts,'averages'=>$averages];
    }

    private function personaLabel(string $persona): string
    {
        return ['admin'=>'مدير النظام','hr'=>'الموارد البشرية','manager'=>'مدير الفريق','evaluator'=>'المقيّم','self'=>'المستخدم']['' . $persona] ?? 'المستخدم';
    }
}
