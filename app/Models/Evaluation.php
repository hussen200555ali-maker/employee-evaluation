<?php
// ط§ظ„ظ…ط³ط§ط±: app/Models/Evaluation.php

namespace App\Models;

use App\Core\Model;

class Evaluation extends Model {

    /**
     * Get all evaluations with related data for API
     */
    public function getAllForApi(string $employeeId = '', string $status = '', string $fromDate = '', string $toDate = ''): array
    {
        $sql = "SELECT e.*, ft.template_name
                FROM evaluations e
                LEFT JOIN form_templates ft ON e.template_id = ft.id
                WHERE 1=1";
        $params = [];

        if ($employeeId) {
            $sql .= " AND (e.evaluated_employee_id = :emp_id OR e.evaluator_employee_id = :emp_id2)";
            $params[':emp_id'] = $employeeId;
            $params[':emp_id2'] = $employeeId;
        }
        if ($status) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }
        if ($fromDate) {
            $sql .= " AND e.evaluation_date >= :from_date";
            $params[':from_date'] = $fromDate;
        }
        if ($toDate) {
            $sql .= " AND e.evaluation_date <= :to_date";
            $params[':to_date'] = $toDate;
        }
        $sql .= " ORDER BY e.evaluation_date DESC";

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        return $this->db->fetchAll();
    }

    /**
     * Get full evaluation data with answers for API
     */
    public function getFullEvaluationData(int $id): ?object
    {
        $sql = "SELECT e.*, ft.template_name,
                       eval_emp.full_name as evaluated_name,
                       eval_emp.emp_code as evaluated_code,
                       COALESCE(e.evaluator_name, eval_emp2.full_name) as evaluator_name,
                       COALESCE(e.evaluator_title, p.title) as evaluator_title
                FROM evaluations e
                LEFT JOIN form_templates ft ON e.template_id = ft.id
                LEFT JOIN employees eval_emp ON e.evaluated_employee_id = eval_emp.id
                LEFT JOIN employees eval_emp2 ON e.evaluator_employee_id = eval_emp2.id
                LEFT JOIN positions p ON eval_emp2.position_id = p.id
                WHERE e.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $evaluation = $this->db->fetch();

        if (!$evaluation) return null;

        // Fetch answers
        $this->db->query("SELECT ea.*, cs.section_name, c.criterion_name
                          FROM evaluation_answers ea
                          LEFT JOIN criteria_sections cs ON ea.section_id = cs.id
                          LEFT JOIN criteria c ON cs.criterion_id = c.id
                          WHERE ea.evaluation_id = :eid
                          ORDER BY cs.id");
        $this->db->bind(':eid', $id);
        $evaluation->answers = $this->db->fetchAll() ?? [];

        return $evaluation;
    }

    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¥ظ†ط´ط§ط، ط³ط¬ظ„ طھظ‚ظٹظٹظ… ط£ط³ط§ط³ظٹ ط¬ط¯ظٹط¯
     * =================================================================
     * ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© طھط­ظپط¸ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط£ظˆظ„ظٹط© ظ„ظ„طھظ‚ظٹظٹظ… ظˆطھط¹ظٹط¯ ط§ظ„ظ…ط¹ط±ظپ ط§ظ„ظپط±ظٹط¯
     * ظ„ظ„ط³ط¬ظ„ ط§ظ„ط¬ط¯ظٹط¯ ط§ظ„ط°ظٹ طھظ… ط¥ظ†ط´ط§ط¤ظ‡.
     *
     * @param array $data - ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ‚ط§ط¯ظ…ط© ظ…ظ† ط§ظ„ظƒظ†طھط±ظˆظ„ط±.
     * @return mixed - ID ط§ظ„طھظ‚ظٹظٹظ… ط§ظ„ط¬ط¯ظٹط¯ ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, ط£ظˆ false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
     public function create($data) {
        $isUnit = !empty($data['entity_type']) && in_array($data['entity_type'], ['branch','department','course','workshop']);
        if ($isUnit) {
            $sql = "INSERT INTO evaluations (
                        template_id, entity_type, entity_id, context_type, context_id, schedule_id,
                        evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluator_title,
                        evaluation_date, created_by_user_id, employee_name, employee_code, nickname,
                        branch_name, department_name, position_name, entity_snapshot, status
                    ) VALUES (
                        :template_id, :entity_type, :entity_id, :context_type, :context_id, :schedule_id,
                        :evaluated_employee_id, :evaluator_employee_id, :evaluator_name, :evaluator_title,
                        :evaluation_date, :created_by_user_id, :employee_name, :employee_code, :nickname,
                        :branch_name, :department_name, :position_name, :entity_snapshot, :status
                    )";
        } else {
            $sql = "INSERT INTO evaluations (
                        template_id, entity_type, entity_id,
                        evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluator_title,
                        evaluation_date, created_by_user_id, employee_name, employee_code, nickname,
                        branch_name, department_name, position_name, entity_snapshot
                    ) VALUES (
                        :template_id, :entity_type, :entity_id,
                        :evaluated_employee_id, :evaluator_employee_id, :evaluator_name, :evaluator_title,
                        :evaluation_date, :created_by_user_id, :employee_name, :employee_code, :nickname,
                        :branch_name, :department_name, :position_name, :entity_snapshot
                    )";
        }
        
        $this->db->query($sql);

        // ربط كل المتغيرات
        $this->db->bind(':template_id', $data['template_id']);
        $this->db->bind(':entity_type', $data['entity_type'] ?? 'employee');
        $this->db->bind(':entity_id', $data['entity_id'] ?? $data['evaluated_employee_id'] ?? null);
        if ($isUnit) {
            $this->db->bind(':context_type', $data['context_type'] ?? null);
            $this->db->bind(':context_id', $data['context_id'] ?? null);
            $this->db->bind(':schedule_id', $data['schedule_id'] ?? null);
            $this->db->bind(':status', $data['status'] ?? 'draft');
        }
        $this->db->bind(':evaluated_employee_id', $data['evaluated_employee_id'] ?? null);
        $this->db->bind(':evaluator_employee_id', $data['evaluator_employee_id']);
        $this->db->bind(':evaluator_name', $data['evaluator_name'] ?? null);
        $this->db->bind(':evaluator_title', $data['evaluator_title'] ?? null);
        $this->db->bind(':evaluation_date', $data['evaluation_date']);
        $this->db->bind(':created_by_user_id', $data['created_by_user_id']);
        $this->db->bind(':employee_name', $data['employee_name'] ?? $data['entity_name'] ?? 'وحدة');
        $this->db->bind(':employee_code', $data['employee_code'] ?? $data['entity_code'] ?? '');
        $this->db->bind(':nickname', $data['nickname'] ?? null);
        $this->db->bind(':branch_name', $data['branch_name'] ?? '');
        $this->db->bind(':department_name', $data['department_name'] ?? '');
        $this->db->bind(':position_name', $data['position_name'] ?? '');
        $this->db->bind(':entity_snapshot', isset($data['entity_snapshot']) ? json_encode($data['entity_snapshot'], JSON_UNESCAPED_UNICODE) : null);

        // طھظ†ظپظٹط° ط§ظ„ط§ط³طھط¹ظ„ط§ظ…
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

     /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط§ظ„ط¨ط­ط« ط¹ظ† طھظ‚ظٹظٹظ… ط¨ظˆط§ط³ط·ط© ID
     * =================================================================
     * @param int $id
     * @return mixed
     */
    public function findById($id) {
        // ظٹظ…ظƒظ†ظƒ ط¥ط¶ط§ظپط© JOINs ظ‡ظ†ط§ ظ…ط³طھظ‚ط¨ظ„ط§ظ‹ ظ„ط¬ظ„ط¨ ط£ط³ظ…ط§ط، ط§ظ„ظ…ظˆط¸ظپظٹظ† ظ…ط¨ط§ط´ط±ط©
        $this->db->query("SELECT * FROM evaluations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    
      public function saveEvaluationResults($evaluationId, $results) {
        // --- ط§ظ„ط®ط·ظˆط© 1: ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط© ط§ظ„ظ„ط§ط²ظ…ط© ظ„ظ„ظ…ط¹ط§ظ„ط¬ط© ---

        // ط£ظˆظ„ط§ظ‹طŒ ظ†ط­طھط§ط¬ ط¥ظ„ظ‰ ط¨ظٹط§ظ†ط§طھ ط§ظ„طھظ‚ظٹظٹظ… ظ†ظپط³ظ‡ ظ„ظ…ط¹ط±ظپط© ط§ظ„ظ‚ط§ظ„ط¨ ط§ظ„ظ…ط³طھط®ط¯ظ….
        $evaluation = $this->findById($evaluationId);
        if (!$evaluation) {
            // ط¥ط°ط§ ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ ط§ظ„طھظ‚ظٹظٹظ…طŒ ظ†ط³ط¬ظ„ ط®ط·ط£ ظˆظ†ط¹ظˆط¯ ط¨ظ‚ظٹظ…ط© false.
            error_log("saveEvaluationResults failed: Evaluation ID {$evaluationId} not found.");
            return false;
        }

        // ط«ط§ظ†ظٹط§ظ‹طŒ ظ†ط­طھط§ط¬ ط¥ظ„ظ‰ ط§ظ„ظ‡ظٹظƒظ„ ط§ظ„ظƒط§ظ…ظ„ ظ„ظ„ظ‚ط§ظ„ط¨ (ط¨ظ…ط§ ظپظٹ ط°ظ„ظƒ ط§ظ„ط£ظ‚ط³ط§ظ… ظˆط£ظˆط²ط§ظ†ظ‡ط§ ظˆط£ظ†ظˆط§ط¹ظ‡ط§).
        $templateModel = new \App\Models\Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);
        if (!$template) {
            // ط¥ط°ط§ ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ ط§ظ„ظ‚ط§ظ„ط¨طŒ ظپظ‡ط°ظ‡ ظ…ط´ظƒظ„ط© ظƒط¨ظٹط±ط©.
            error_log("saveEvaluationResults failed: Template for evaluation ID {$evaluationId} not found.");
            return false;
        }

        // --- ط§ظ„ط®ط·ظˆط© 2: طھط­ط¶ظٹط± ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ„ط³ظ‡ظˆظ„ط© ط§ظ„ظˆطµظˆظ„ ---

        // ظ†ظ‚ظˆظ… ط¨ط¥ظ†ط´ط§ط، "ط®ط±ظٹط·ط©" (associative array) ظ„ط¬ظ…ظٹط¹ ط§ظ„ط£ظ‚ط³ط§ظ… ط§ظ„ظ…ظˆط¬ظˆط¯ط© ظپظٹ ط§ظ„ظ‚ط§ظ„ط¨.
        // ظ‡ط°ط§ ظٹط³ظ…ط­ ظ„ظ†ط§ ط¨ط§ظ„ظˆطµظˆظ„ ط¥ظ„ظ‰ طھظپط§طµظٹظ„ ط£ظٹ ظ‚ط³ظ… ط¨ط³ط±ط¹ط© ط¨ط§ط³طھط®ط¯ط§ظ… `section_id` ظƒظ…ظپطھط§ط­.
        // ظ‡ط°ط§ ط£ظپط¶ظ„ ط¨ظƒط«ظٹط± ظ…ظ† ط§ظ„ط¨ط­ط« ط¯ط§ط®ظ„ ط§ظ„ط­ظ„ظ‚ط§طھ ط§ظ„ظ…طھط¯ط§ط®ظ„ط© ظپظٹ ظƒظ„ ظ…ط±ط©.
        $sectionsMap = [];
        foreach ($template['criteria'] as $criterion) {
            foreach ($criterion['sections'] as $section) {
                $sectionsMap[$section['id']] = $section;
            }
        }

        // --- ط§ظ„ط®ط·ظˆط© 3: ط¨ط¯ط، ط¹ظ…ظ„ظٹط© ط§ظ„ط­ظپط¸ ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ ---

        // ظ†ط³طھط®ط¯ظ… Transaction ظ„ط¶ظ…ط§ظ† ط£ظ† ط¬ظ…ظٹط¹ ط¹ظ…ظ„ظٹط§طھ ط§ظ„ط­ظپط¸ طھطھظ… ظ…ط¹ط§ظ‹ ظƒظˆط­ط¯ط© ظˆط§ط­ط¯ط©.
        // ط¥ط°ط§ ظپط´ظ„طھ ط£ظٹ ط¹ظ…ظ„ظٹط©طŒ ظٹطھظ… ط§ظ„طھط±ط§ط¬ط¹ ط¹ظ† ظƒظ„ ط§ظ„ط¹ظ…ظ„ظٹط§طھ ط§ظ„ط³ط§ط¨ظ‚ط© (rollback).
        $this->db->beginTransaction();
        try {
            // ط£- ط­ط°ظپ ط§ظ„ط¥ط¬ط§ط¨ط§طھ ط§ظ„ظ‚ط¯ظٹظ…ط©: ظ‚ط¨ظ„ ط¥ط¶ط§ظپط© ط§ظ„ط¥ط¬ط§ط¨ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©طŒ ظ…ظ† ط§ظ„ط£ظپط¶ظ„ ط­ط°ظپ ط£ظٹ ط¥ط¬ط§ط¨ط§طھ ظ‚ط¯ظٹظ…ط©
            // ظ‚ط¯ طھظƒظˆظ† ظ…ظˆط¬ظˆط¯ط© ظ„ظ‡ط°ط§ ط§ظ„طھظ‚ظٹظٹظ…. ظ‡ط°ط§ ظٹظ…ظ†ط¹ طھظƒط±ط§ط± ط§ظ„ط¨ظٹط§ظ†ط§طھ ط¥ط°ط§ ظ‚ط§ظ… ط§ظ„ظ…ط³طھط®ط¯ظ… ط¨ط¥ط¹ط§ط¯ط© ط§ظ„ط­ظپط¸.
            $this->db->query("DELETE FROM evaluation_answers WHERE evaluation_id = :evaluation_id");
            $this->db->bind(':evaluation_id', $evaluationId);
            $this->db->execute();

            // ط¨- طھط­ط¶ظٹط± ط¬ظ…ظ„ط© ط§ظ„ط¥ط¶ط§ظپط© (INSERT): ظ†ط¬ظ‡ط²ظ‡ط§ ظ…ط±ط© ظˆط§ط­ط¯ط© ط®ط§ط±ط¬ ط§ظ„ط­ظ„ظ‚ط© ظ„طھط­ط³ظٹظ† ط§ظ„ط£ط¯ط§ط،.
            $sql = "INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, notes, normalized_score) 
                    VALUES (:evaluation_id, :section_id, :raw_value, :notes, :normalized_score)";

            // ط¬- ط§ظ„ظ…ط±ظˆط± ط¹ظ„ظ‰ ظƒظ„ ط¥ط¬ط§ط¨ط© ظ‚ط§ط¯ظ…ط© ظ…ظ† ط§ظ„ظ†ظ…ظˆط°ط¬
            foreach ($results as $criterionId => $sections) {
                foreach ($sections as $sectionId => $answer) {
                    // ط§ظ„طھط­ظ‚ظ‚ ظ…ظ† ط£ظ† ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ‚ط§ط¯ظ…ط© طµط§ظ„ط­ط© (ظ…ط¹ط±ظپ ط§ظ„ظ‚ط³ظ… ط±ظ‚ظ…ظٹ ظˆظ…ظˆط¬ظˆط¯ ظپظٹ ط®ط±ظٹط·طھظ†ط§)
                    if (!is_numeric($sectionId) || !isset($sectionsMap[$sectionId])) {
                        continue; // ط¥ط°ط§ ظƒط§ظ†طھ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط؛ظٹط± طµط§ظ„ط­ط©طŒ طھط¬ط§ظ‡ظ„ ظ‡ط°ظ‡ ط§ظ„ط¥ط¬ط§ط¨ط© ظˆط§ظ†طھظ‚ظ„ ظ„ظ„طھط§ظ„ظٹط©.
                    }

                    // ط¬ظ„ط¨ طھظپط§طµظٹظ„ ط§ظ„ظ‚ط³ظ… ظ…ظ† ط§ظ„ط®ط±ظٹط·ط© ط§ظ„طھظٹ ط£ظ†ط´ط£ظ†ط§ظ‡ط§.
                    $section = $sectionsMap[$sectionId];
                    // ط¬ظ„ط¨ ط§ظ„ظ‚ظٹظ…ط© ط§ظ„ط®ط§ظ… ط§ظ„طھظٹ ط£ط¯ط®ظ„ظ‡ط§ ط§ظ„ظ…ط³طھط®ط¯ظ….
                    $answerValue = $answer['score'] ?? null;
                    
                    // ط¥ط°ط§ ظƒط§ظ†طھ ط§ظ„ظ‚ظٹظ…ط© ظپط§ط±ط؛ط© طھظ…ط§ظ…ط§ظ‹طŒ ظ†ط¹طھط¨ط±ظ‡ط§ طµظپط±ط§ظ‹ ظ„طھط¬ظ†ط¨ ط§ظ„ط£ط®ط·ط§ط،.
                    if ($answerValue === null || $answerValue === '') {
                        $answerValue = 0;
                    }

                    // --- ظ…ظ†ط·ظ‚ ط­ط³ط§ط¨ ط§ظ„ط¯ط±ط¬ط© ط§ظ„ظ…ظˆط­ط¯ط© (normalized_score) ---
                    $normalizedScore = 0.0;
                    // ظ†ظ‚ظˆظ… ط¨طھظ†ط¸ظٹظپ ظˆطھظˆط­ظٹط¯ ظ†ظˆط¹ ط§ظ„طھظ‚ظٹظٹظ… ظ„ط¶ظ…ط§ظ† ط§ظ„ظ…ظ‚ط§ط±ظ†ط© ط§ظ„طµط­ظٹط­ط©.
                    $evalType = trim(strtolower($section['evaluation_type']));

                    if ($evalType === 'numeric') {
                        // ظ„ظ„ط­ظ‚ظ„ ط§ظ„ط±ظ‚ظ…ظٹطŒ ظ†ط³طھط®ط¯ظ… ظ…ط¹ط§ط¯ظ„ط© ط§ظ„طھظˆط­ظٹط¯ ظ„طھط­ظˆظٹظ„ ط§ظ„ط¯ط±ط¬ط© ط¥ظ„ظ‰ ظ†ط³ط¨ط© ظ…ط¦ظˆظٹط©.
                        $max = (float)($section['max_value'] ?? 100);
                        $min = (float)($section['min_value'] ?? 0);
                        if ($max > $min) {
                            $normalizedScore = (((float)$answerValue - $min) / ($max - $min)) * 100;
                        }
                    } elseif ($evalType === 'grade' || $evalType === 'percentage') {
                        // ظ„ط­ظ‚ظˆظ„ ط§ظ„طھظ‚ط¯ظٹط± ظˆط§ظ„ظ†ط³ط¨ط© ط§ظ„ظ…ط¦ظˆظٹط©طŒ ط§ظ„ظ‚ظٹظ…ط© ط§ظ„ظ…ط¯ط®ظ„ط© ظ‡ظٹ ظ†ظپط³ظ‡ط§ ط§ظ„ظ†ط³ط¨ط© ط§ظ„ظ…ط¦ظˆظٹط©.
                        $normalizedScore = (float)$answerValue;
                    }
                    // ظٹظ…ظƒظ† ط¥ط¶ط§ظپط© ط£ظ†ظˆط§ط¹ ط£ط®ط±ظ‰ ظ…ط«ظ„ 'boolean' ط£ظˆ 'text' ظ‡ظ†ط§ ظ…ط³طھظ‚ط¨ظ„ط§ظ‹.

                    // --- طھظ†ظپظٹط° ط¹ظ…ظ„ظٹط© ط§ظ„ط¥ط¶ط§ظپط© ---
                    $this->db->query($sql);
                    $this->db->bind(':evaluation_id', $evaluationId);
                    $this->db->bind(':section_id', (int)$sectionId);
                    $this->db->bind(':raw_value', $answerValue);
                    $this->db->bind(':notes', $answer['notes'] ?? null);
                    // ظ†ظ‚ظˆظ… ط¨طھظ‚ط±ظٹط¨ ط§ظ„ط¯ط±ط¬ط© ط§ظ„ظ…ظˆط­ط¯ط© ظ‚ط¨ظ„ ط­ظپط¸ظ‡ط§ ظ„ط¶ظ…ط§ظ† ط¯ظ‚ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
                    $this->db->bind(':normalized_score', round($normalizedScore, 4));
                    $this->db->execute();
                }
            }

            // ط¥ط°ط§ ظ†ط¬ط­طھ ظƒظ„ ط§ظ„ط¹ظ…ظ„ظٹط§طھطŒ ظ‚ظ… ط¨طھط£ظƒظٹط¯ ط§ظ„ط­ظپط¸ ط§ظ„ظ†ظ‡ط§ط¦ظٹ.
            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            // ط¥ط°ط§ ط­ط¯ط« ط£ظٹ ط®ط·ط£ ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ ط£ط«ظ†ط§ط، ط§ظ„ط¹ظ…ظ„ظٹط©...
            // 1. طھط±ط§ط¬ط¹ ط¹ظ† ظƒظ„ ط§ظ„ط¹ظ…ظ„ظٹط§طھ ط§ظ„طھظٹ طھظ…طھ ط¯ط§ط®ظ„ ط§ظ„ظ€ transaction.
            $this->db->rollBack();
            // 2. ط³ط¬ظ„ ط±ط³ط§ظ„ط© ط§ظ„ط®ط·ط£ ظپظٹ ط³ط¬ظ„ط§طھ ط§ظ„ط®ط§ط¯ظ… ظ„ظ…ط³ط§ط¹ط¯طھظƒ ط¹ظ„ظ‰ طھطµط­ظٹط­ظ‡ط§ ظ„ط§ط­ظ‚ط§ظ‹.
            error_log("Evaluation Save Error for evaluation_id {$evaluationId}: " . $e->getMessage());
            // 3. ط£ط¹ط¯ ط§ظ„ظ‚ظٹظ…ط© false ظ„ظ„ط¥ط´ط§ط±ط© ط¥ظ„ظ‰ ظپط´ظ„ ط§ظ„ط¹ظ…ظ„ظٹط©.
            return false;
        }
    }

   /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: طھط­ط¯ظٹط« ط§ظ„ط¯ط±ط¬ط© ط§ظ„ظ†ظ‡ط§ط¦ظٹط© ظˆط­ط§ظ„ط© ط§ظ„طھظ‚ظٹظٹظ…
     * =================================================================
     * @param int $evaluationId
     * @param float $score
     * @param string $status
     * @return bool
     */
    public function updateScoreAndStatus($evaluationId, $score, $status) {
        $sql = "UPDATE evaluations SET final_score = :score, status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':score', $score);
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $evaluationId);
        return $this->db->execute();
    }
  
    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط­ط³ط§ط¨ ط§ظ„ط¯ط±ط¬ط© ط§ظ„ظ†ظ‡ط§ط¦ظٹط© (ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ†ظ‡ط§ط¦ظٹط© ظˆط§ظ„ظ…طµط­ط­ط©)
     * =================================================================
     */
    public function calculateFinalScore($evaluationId) {
        // 1. ط¬ظ„ط¨ ط¨ظٹط§ظ†ط§طھ ط§ظ„طھظ‚ظٹظٹظ… ط§ظ„ط£ط³ط§ط³ظٹط©
        $evaluation = $this->findById($evaluationId);
        if (!$evaluation) return null;

        // 2. ط¬ظ„ط¨ ظ‡ظٹظƒظ„ ط§ظ„ظ‚ط§ظ„ط¨
        $templateModel = new \App\Models\Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);
        if (!$template) return null;

        // 3. ط¬ظ„ط¨ ط§ظ„ط¥ط¬ط§ط¨ط§طھ ظ…ط¹ ط§ظ„ط¯ط±ط¬ط§طھ ط§ظ„ظ…ظˆط­ط¯ط© ط§ظ„ظ…ط­ظپظˆط¸ط©
        $this->db->query("SELECT section_id, normalized_score FROM evaluation_answers WHERE evaluation_id = :id");
        $this->db->bind(':id', $evaluationId);
        $answers = $this->db->fetchAll(\PDO::FETCH_KEY_PAIR); // [section_id => normalized_score]

        if (empty($answers)) return 0.0;

        // 4. ط¨ط¯ط، ط¹ظ…ظ„ظٹط© ط§ظ„ط­ط³ط§ط¨
        $totalScore = 0.0;

        foreach ($template['criteria'] as $criterion) {
            $criterionScore = 0.0;
            
            foreach ($criterion['sections'] as $section) {
                $sectionId = $section['id'];
                // ط¬ظ„ط¨ ط§ظ„ط¯ط±ط¬ط© ط§ظ„ظ…ظˆط­ط¯ط© ظ…ط¨ط§ط´ط±ط© ظ…ظ† ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ
                $normalizedScore = (float)($answers[$sectionId] ?? 0);
                
                // طھط·ط¨ظٹظ‚ ظˆط²ظ† ط§ظ„ظ‚ط³ظ…
                $sectionWeight = (float)($section['section_weight'] ?? 0);
                $criterionScore += ($normalizedScore * $sectionWeight / 100.0);
            }

            // طھط·ط¨ظٹظ‚ ظˆط²ظ† ط§ظ„ظ…ط¹ظٹط§ط±
            $criterionWeight = (float)($criterion['criterion_weight'] ?? 0);
            $totalScore += ($criterionScore * $criterionWeight / 100.0);
        }

        // طھظ‚ط±ظٹط¨ ط§ظ„ظ†طھظٹط¬ط© ط§ظ„ظ†ظ‡ط§ط¦ظٹط© ظ„ط£ظ‚ط±ط¨ ظ…ظ†ط²ظ„طھظٹظ† ط¹ط´ط±ظٹطھظٹظ†
        return round($totalScore, 2);
    }

     /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ط¥ط¬ط§ط¨ط§طھ طھظ‚ظٹظٹظ… ظ…ط¹ظٹظ†
     * =================================================================
     * @param int $evaluationId
     * @return array - ظ…طµظپظˆظپط© ظ…ظ† ط§ظ„ط¥ط¬ط§ط¨ط§طھ ظ…ظپطھط§ط­ظ‡ط§ ظ‡ظˆ section_id
     */
    public function getEvaluationAnswers($evaluationId) {
        $this->db->query("SELECT * FROM evaluation_answers WHERE evaluation_id = :id");
        $this->db->bind(':id', $evaluationId);
        $results = $this->db->fetchAll();

        // ط¥ط¹ط§ط¯ط© طھط±طھظٹط¨ ط§ظ„ظ…طµظپظˆظپط© ظ„طھظƒظˆظ† ط³ظ‡ظ„ط© ط§ظ„ط§ط³طھط®ط¯ط§ظ… ظپظٹ ط§ظ„ظˆط§ط¬ظ‡ط©
        // ط§ظ„ظ…ظپطھط§ط­ ط³ظٹظƒظˆظ† section_id ظˆط§ظ„ظ‚ظٹظ…ط© ظ‡ظٹ ظƒط§ط¦ظ† ط§ظ„ط¥ط¬ط§ط¨ط© ط¨ط§ظ„ظƒط§ظ…ظ„
        $answersMap = [];
        foreach ($results as $row) {
            $answersMap[$row->section_id] = $row;
        }

        return $answersMap;
    }
      /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ظ…ظ„ط®طµ طھظ‚ظٹظٹظ…ط§طھ ظ…ظˆط¸ظپ ظ…ط¹ظٹظ† ظ…ط¬ظ…ط¹ط© ط­ط³ط¨ ط§ظ„ط³ظ†ط©
     * =================================================================
     * @param int $employeeId
     * @return array
     */
    public function getEvaluationsByEmployeeGroupedByYear($employeeId) {
        // ظ‡ط°ط§ ظ‡ظˆ ط§ظ„ط§ط³طھط¹ظ„ط§ظ… ط§ظ„ط°ظٹ ظٹط³طھط®ط±ط¬ ط§ظ„ط³ظ†ط© ظˆظٹظ‚ظˆظ… ط¨طھط¬ظ…ظٹط¹ ط§ظ„ط¨ظٹط§ظ†ط§طھ
        $sql = "SELECT 
                    YEAR(evaluation_date) as evaluation_year,
                    COUNT(id) as evaluations_count,
                    ROUND(AVG(final_score), 2) as average_score,
                    MAX(final_score) as max_score,
                    MIN(final_score) as min_score
                FROM 
                    evaluations
                WHERE 
                    evaluated_employee_id = :employee_id AND status IN ('approved','completed')
                GROUP BY 
                    evaluation_year
                ORDER BY 
                    evaluation_year DESC";

        $this->db->query($sql);
        $this->db->bind(':employee_id', $employeeId);
        
        return $this->db->fetchAll();
    }
    /**
 * =================================================================
 *           ===>  ط§ظ„ط¯ط§ظ„ط© ط§ظ„ط¬ط¯ظٹط¯ط© ظˆط§ظ„ط¢ظ…ظ†ط© ظ‡ظ†ط§  <===
 * =================================================================
 * طھط¬ظ„ط¨ ظ…ظ„ط®طµ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ظ…ط¹ ط¥ط¶ط§ظپط© ط£ظˆظ„ طھط§ط±ظٹط® طھظ‚ظٹظٹظ… ظپط¹ظ„ظٹ ظپظٹ ط§ظ„ط³ظ†ط©.
 * ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© ظ…ط®طµطµط© ظ„ظ„ظˆط§ط¬ظ‡ط§طھ ط§ظ„طھظٹ طھط­طھط§ط¬ ط¥ظ„ظ‰ طھط­ظˆظٹظ„ ط¯ظ‚ظٹظ‚ ظ„ظ„ط³ظ†ط© ط§ظ„ظ‡ط¬ط±ظٹط©.
 *
 * @param int $employeeId
 * @return array
 */
public function getAnnualSummaryWithActualDate($employeeId) {
    $sql = "SELECT 
                YEAR(evaluation_date) as evaluation_year,
                MIN(evaluation_date) as first_evaluation_date, -- <-- ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¥ط¶ط§ظپظٹ ظ„ظ„ط¯ظ‚ط©
                COUNT(id) as evaluations_count,
                ROUND(AVG(final_score), 2) as average_score,
                MAX(final_score) as max_score,
                MIN(final_score) as min_score
            FROM 
                evaluations
            WHERE 
                evaluated_employee_id = :employee_id AND status IN ('approved','completed')
            GROUP BY 
                evaluation_year
            ORDER BY 
                evaluation_year DESC";

    $this->db->query($sql);
    $this->db->bind(':employee_id', $employeeId);
    
    return $this->db->fetchAll();
}

  /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ظƒظ„ طھظ‚ظٹظٹظ…ط§طھ ظ…ظˆط¸ظپ ظ…ط¹ظٹظ† ظ„ط¹ط§ظ… ظ…ط­ط¯ط¯
     * =================================================================
     * @param int $employeeId
     * @param int $year
     * @return array
     */
     public function getEvaluationsByEmployeeForYear($employeeId, $year) {
        $sql = "SELECT 
                    eval.*,
                    t.template_name,
                    COALESCE(eval.evaluator_name, evaluator.full_name) as evaluator_name
                FROM 
                    evaluations eval
                LEFT JOIN 
                    form_templates t ON eval.template_id = t.id
                LEFT JOIN 
                    employees evaluator ON eval.evaluator_employee_id = evaluator.id
                WHERE 
                    eval.evaluated_employee_id = :employee_id 
                    AND YEAR(eval.evaluation_date) = :year
                    AND eval.status IN ('approved','completed')
                ORDER BY 
                    eval.evaluation_date DESC";

        $this->db->query($sql);
        $this->db->bind(':employee_id', $employeeId);
        $this->db->bind(':year', $year);
        
        return $this->db->fetchAll();
    }

    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط§ط­طھط±ط§ظپظٹط©: ط¬ظ„ط¨ ط¥ط­طµط§ط¦ظٹط§طھ ظ…ظ‚ط§ط±ظ†ط© ظ„ظ„ط£ط¯ط§ط،
     * =================================================================
     * @param int $year
     * @param int $departmentId
     * @return object
     */
    public function getYearRadarData(int $employeeId, int $year): array
    {
        $sql = "SELECT c.criterion_name, ROUND(AVG(ea.normalized_score),1) as avg_score
                FROM evaluations e
                JOIN criteria c ON c.template_id = e.template_id
                JOIN criteria_sections cs ON cs.criterion_id = c.id
                JOIN evaluation_answers ea ON ea.evaluation_id = e.id AND ea.section_id = cs.id
                WHERE e.evaluated_employee_id = :eid AND YEAR(e.evaluation_date)=:year AND e.status IN ('approved','completed')
                GROUP BY c.id, c.criterion_name ORDER BY c.order_index";
        $this->db->query($sql);
        $this->db->bind(':eid', $employeeId);
        $this->db->bind(':year', $year);
        $rows = $this->db->fetchAll();
        $labels=[]; $scores=[];
        foreach($rows as $r){ $labels[]=$r->criterion_name; $scores[]=(float)$r->avg_score; }
        return ['labels'=>$labels,'scores'=>$scores];
    }
    public function getYearTrendData(int $employeeId, int $year): array
    {
        $sql = "SELECT evaluation_date, final_score, template_id FROM evaluations WHERE evaluated_employee_id=:eid AND YEAR(evaluation_date)=:year AND status IN ('approved','completed') AND final_score IS NOT NULL ORDER BY evaluation_date ASC";
        $this->db->query($sql);
        $this->db->bind(':eid', $employeeId);
        $this->db->bind(':year', $year);
        $rows=$this->db->fetchAll();
        $labels=[]; $scores=[]; $templates=[];
        foreach($rows as $r){ $labels[]=date('m/d', strtotime($r->evaluation_date)); $scores[]=(float)$r->final_score; $templates[]=$r->template_id; }
        return ['labels'=>$labels,'scores'=>$scores,'templates'=>$templates];
    }
    public function getYearTemplateBreakdown(int $employeeId, int $year): array
    {
        $sql = "SELECT ft.template_name, c.criterion_name, ROUND(AVG(ea.normalized_score),1) as avg_score
                FROM evaluations e
                JOIN form_templates ft ON ft.id=e.template_id
                JOIN criteria c ON c.template_id=e.template_id
                JOIN criteria_sections cs ON cs.criterion_id=c.id
                JOIN evaluation_answers ea ON ea.evaluation_id=e.id AND ea.section_id=cs.id
                WHERE e.evaluated_employee_id=:eid AND YEAR(e.evaluation_date)=:year AND e.status IN ('approved','completed')
                GROUP BY ft.id, ft.template_name, c.id, c.criterion_name ORDER BY ft.template_name, c.order_index";
        $this->db->query($sql);
        $this->db->bind(':eid', $employeeId);
        $this->db->bind(':year', $year);
        $rows=$this->db->fetchAll();
        $templates=[]; $criteriaMap=[];
        foreach($rows as $r){
            $t=$r->template_name;
            if(!isset($templates[$t])) $templates[$t]=[];
            $templates[$t][$r->criterion_name]=(float)$r->avg_score;
            if(!in_array($r->criterion_name, $criteriaMap)) $criteriaMap[]=$r->criterion_name;
        }
        return ['templates'=>array_keys($templates),'criteria'=>$criteriaMap,'data'=>$templates];
    }
    public function getYearSectionBreakdown(int $employeeId, int $year): array
    {
        $sql = "SELECT cs.section_name, ROUND(AVG(ea.normalized_score),1) as avg_score
                FROM evaluations e
                JOIN criteria_sections cs ON cs.criterion_id IN (SELECT id FROM criteria WHERE template_id=e.template_id)
                JOIN evaluation_answers ea ON ea.section_id=cs.id AND ea.evaluation_id=e.id
                WHERE e.evaluated_employee_id=:eid AND YEAR(e.evaluation_date)=:year AND e.status IN ('approved','completed')
                GROUP BY cs.section_name ORDER BY avg_score ASC LIMIT 12";
        $this->db->query($sql);
        $this->db->bind(':eid', $employeeId);
        $this->db->bind(':year', $year);
        $rows=$this->db->fetchAll();
        $labels=[]; $scores=[];
        foreach($rows as $r){ $labels[]=$r->section_name; $scores[]=(float)$r->avg_score; }
        return ['labels'=>$labels,'scores'=>$scores];
    }
    public function getComparisonStatsForYear($year, $departmentId) {
        $sql = "SELECT
                    (SELECT ROUND(AVG(final_score), 2) FROM evaluations WHERE YEAR(evaluation_date) = :year_all AND status IN ('approved','completed') AND final_score IS NOT NULL) as company_average,
                    (SELECT ROUND(AVG(final_score), 2) FROM evaluations WHERE YEAR(evaluation_date) = :year_dept AND status IN ('approved','completed') AND final_score IS NOT NULL AND department_name = (SELECT name FROM departments WHERE id = :dept_id)) as department_average";
        
        $this->db->query($sql);
        $this->db->bind(':year_all', $year);
        $this->db->bind(':year_dept', $year);
        $this->db->bind(':dept_id', $departmentId);

        return $this->db->fetch();
    }
    
    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط­ط°ظپ طھظ‚ظٹظٹظ… ظ…ظ† ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ
     * =================================================================
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        // ط¨ظپط¶ظ„ ط¥ط¹ط¯ط§ط¯ ON DELETE CASCADE ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھطŒ
        // ط³ظٹطھظ… ط­ط°ظپ ط§ظ„ط¥ط¬ط§ط¨ط§طھ ط§ظ„ظ…ط±طھط¨ط·ط© ظپظٹ `evaluation_answers` طھظ„ظ‚ط§ط¦ظٹط§ظ‹.
        $this->db->query("DELETE FROM evaluations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    


    /**
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ط£ظ†ظˆط§ط¹ ط§ظ„ظ‚ظˆط§ظ„ط¨ ط§ظ„طھظٹ طھظ… طھظ‚ظٹظٹظ… ط§ظ„ظ…ظˆط¸ظپ ط¨ظ‡ط§
     */
    public function getUsedTemplateTypesForEmployee($employeeId) {
        $sql = "SELECT DISTINCT t.id, t.template_name
                FROM evaluations eval
                JOIN form_templates t ON eval.template_id = t.id
                WHERE eval.evaluated_employee_id = :employee_id AND eval.status IN ('approved','completed')
                ORDER BY t.template_name ASC";
        $this->db->query($sql);
        $this->db->bind(':employee_id', $employeeId);
        return $this->db->fetchAll();
    }

// ظپظٹ ظ…ظ„ظپ: app/Mod
public function getEvaluationYearsForEmployee($employeeId) {
    $sql = "SELECT DISTINCT YEAR(evaluation_date) as year
            FROM evaluations
            WHERE evaluated_employee_id = :employee_id AND status IN ('approved','completed')
            ORDER BY year DESC";
    $this->db->query($sql);
    $this->db->bind(':employee_id', $employeeId);
    return $this->db->fetchAll();
}
  
    public function getPerformanceAnalysis(array $employeeIds, $templateId = null) {
        // ط¥ط°ط§ ظƒط§ظ†طھ ظ…طµظپظˆظپط© ط§ظ„ظ…ظˆط¸ظپظٹظ† ظپط§ط±ط؛ط©طŒ ظ„ط§ ط¯ط§ط¹ظٹ ظ„ط¥ظƒظ…ط§ظ„ ط§ظ„ط¹ظ…ظ„ظٹط©.
        if (empty($employeeIds)) {
            return $this->getEmptyAnalysisResult();
        }

        // --- 1. ط¨ظ†ط§ط، ط§ظ„ط§ط³طھط¹ظ„ط§ظ… ط§ظ„ط¯ظٹظ†ط§ظ…ظٹظƒظٹ ظ„ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط®ط§ظ… ---
        $sql = "SELECT 
                    eval.evaluated_employee_id,
                    cs.section_name,
                    ans.normalized_score
                FROM 
                    evaluation_answers ans
                JOIN 
                    evaluations eval ON ans.evaluation_id = eval.id
                JOIN 
                    criteria_sections cs ON ans.section_id = cs.id
                WHERE 
                    eval.evaluated_employee_id IN (" . implode(',', array_fill(0, count($employeeIds), '?')) . ")
                    AND eval.status IN ('approved','completed')
                    AND ans.normalized_score IS NOT NULL";

        $params = $employeeIds;

        if ($templateId) {
            $sql .= " AND eval.template_id = ?";
            $params[] = $templateId;
        }

           // 1. ظ†ط­ط¶ط± ط§ظ„ط§ط³طھط¹ظ„ط§ظ…
        $this->db->query($sql);

        // 2. ظ†ظ‚ظˆظ… ط¨ط±ط¨ط· ظƒظ„ ط¨ط§ط±ط§ظ…طھط± ظٹط¯ظˆظٹط§ظ‹
        $i = 1;
        foreach ($params as $param) {
            // ظ†ط³طھط®ط¯ظ… ط¯ط§ظ„ط© bind ط§ظ„ظ…ظˆط¬ظˆط¯ط© ظپظٹ ظƒظ„ط§ط³ Database ط§ظ„ط®ط§طµ ط¨ظƒ
            $this->db->bind($i, $param);
            $i++;
        }
  // 3. ظ†ظ†ظپط° ط§ظ„ط§ط³طھط¹ظ„ط§ظ… ظˆظ†ط¬ظ„ط¨ ظƒظ„ ط§ظ„ظ†طھط§ط¦ط¬
        $allAnswers = $this->db->fetchAll();

        if (empty($allAnswers)) {
            return $this->getEmptyAnalysisResult();
        }

        // --- 2. طھط¬ظ…ظٹط¹ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط¨ط·ط±ظٹظ‚ط© ط§ط­طھط±ط§ظپظٹط© ---
        // ط§ظ„ظ‡ظٹظƒظ„: [ 'ط§ط³ظ… ط§ظ„ظƒظپط§ط،ط©' => [ 'employee_id' => [ط¯ط±ط¬ط§طھظ‡], ... ], ... ]
        $competencyData = [];
        foreach ($allAnswers as $answer) {
            $competencyData[$answer->section_name][$answer->evaluated_employee_id][] = (float)$answer->normalized_score;
        }

        // --- 3. طھط­ظ„ظٹظ„ ظƒظ„ ظƒظپط§ط،ط© ط¹ظ„ظ‰ ط­ط¯ط© ---
        $analysisByCategory = [
            'excellent' => [],
            'good' => [],
            'needs_improvement' => []
        ];
        $performanceAverages = [];

        $EXCELLENT_THRESHOLD = 85;
        $NEEDS_IMPROVEMENT_THRESHOLD = 60;

        foreach ($competencyData as $sectionName => $employeeScores) {
            $allScores = [];
            $strongEmployeesCount = 0;
            $weakEmployeesCount = 0;

            // ط­ط³ط§ط¨ ظ…طھظˆط³ط· ط£ط¯ط§ط، ظƒظ„ ظ…ظˆط¸ظپ ظپظٹ ظ‡ط°ظ‡ ط§ظ„ظƒظپط§ط،ط©طŒ ط«ظ… طھط­ط¯ظٹط¯ ظ…ط§ ط¥ط°ط§ ظƒط§ظ† ظ‚ظˆظٹط§ظ‹ ط£ظ… ط¶ط¹ظٹظپط§ظ‹
            foreach ($employeeScores as $scores) {
                $employeeAverage = array_sum($scores) / count($scores);
                $allScores[] = $employeeAverage; // ظ†ط¬ظ…ط¹ ظ…طھظˆط³ط·ط§طھ ط§ظ„ظ…ظˆط¸ظپظٹظ†
                if ($employeeAverage >= $EXCELLENT_THRESHOLD) {
                    $strongEmployeesCount++;
                }
                if ($employeeAverage < $NEEDS_IMPROVEMENT_THRESHOLD) {
                    $weakEmployeesCount++;
                }
            }

            // ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط· ط§ظ„ط¹ط§ظ… ط§ظ„ط­ظ‚ظٹظ‚ظٹ ظ„ظ„ظƒظپط§ط،ط© ط¨ظ†ط§ط،ظ‹ ط¹ظ„ظ‰ ظ…طھظˆط³ط·ط§طھ ط£ط¯ط§ط، ط§ظ„ظ…ظˆط¸ظپظٹظ†
            $overallAverage = array_sum($allScores) / count($allScores);
            $performanceAverages[$sectionName] = round($overallAverage, 2);

            $result = [
                'average' => round($overallAverage, 2),
                'strong_count' => $strongEmployeesCount,
                'weak_count' => $weakEmployeesCount,
                'total_employees' => count($employeeScores)
            ];

            // طھطµظ†ظٹظپ ط§ظ„ظƒظپط§ط،ط©
            if ($overallAverage >= $EXCELLENT_THRESHOLD) {
                $analysisByCategory['excellent'][$sectionName] = $result;
            } elseif ($overallAverage < $NEEDS_IMPROVEMENT_THRESHOLD) {
                $analysisByCategory['needs_improvement'][$sectionName] = $result;
            } else {
                $analysisByCategory['good'][$sectionName] = $result;
            }
        }

        // --- 4. ظپط±ط² ط§ظ„ظ†طھط§ط¦ط¬ ظˆطھط¬ظ‡ظٹط²ظ‡ط§ ظ„ظ„ط¹ط±ط¶ ---
        arsort($performanceAverages); // ظپط±ط² ط§ظ„ط±ط³ظ… ط§ظ„ط¨ظٹط§ظ†ظٹ ظ…ظ† ط§ظ„ط£ط¹ظ„ظ‰ ظ„ظ„ط£ط¯ظ†ظ‰

        return [
            'analysis' => $analysisByCategory,
            'performanceChartData' => [
                'labels' => array_keys($performanceAverages),
                'scores' => array_values($performanceAverages)
            ],
            'totalEvaluations' => count($allAnswers),
            'totalEmployees' => count($employeeIds)
        ];
    }

    /**
     * ط¯ط§ظ„ط© ظ…ط³ط§ط¹ط¯ط© ظ„ط¥ط±ط¬ط§ط¹ ظ‡ظٹظƒظ„ طھط­ظ„ظٹظ„ ظپط§ط±ط؛
     */
    private function getEmptyAnalysisResult() {
        return [
            'analysis' => [
                'excellent' => [],
                'good' => [],
                'needs_improvement' => []
            ],
            'performanceChartData' => ['labels' => [], 'scores' => []],
            'totalEvaluations' => 0,
            'totalEmployees' => 0
        ];
    }
/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ط³ط¬ظ„ ط§ظ„ط£ط¯ط§ط، ط§ظ„طھط§ط±ظٹط®ظٹ ظ„ظ…ظˆط¸ظپ
 * =================================================================
 * طھط¬ظ„ط¨ ظ‚ط§ط¦ظ…ط© ط¨ظƒظ„ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ط§ظ„ظ…ظƒطھظ…ظ„ط© ظ„ظ…ظˆط¸ظپ ظ…ط¹ظٹظ† ظ…ط¹ طھظپط§طµظٹظ„ظ‡ط§ ط§ظ„ط£ط³ط§ط³ظٹط©.
 *
 * @param int $employeeId
 * @return array
 */
public function getEmployeePerformanceHistory($employeeId) {
    $sql = "SELECT 
                e.id,
                e.evaluation_date,
                e.final_score,
                t.template_name
            FROM 
                evaluations e
            LEFT JOIN 
                form_templates t ON e.template_id = t.id
            WHERE 
                e.evaluated_employee_id = :employee_id
                AND e.status IN ('approved','completed')
                AND e.final_score IS NOT NULL
            ORDER BY 
                e.evaluation_date ASC"; // ط§ظ„طھط±طھظٹط¨ طھطµط§ط¹ط¯ظٹط§ظ‹ ظ…ظ‡ظ… ظ„ظ„ط±ط³ظ… ط§ظ„ط¨ظٹط§ظ†ظٹ

    $this->db->query($sql);
    $this->db->bind(':employee_id', $employeeId);
    return $this->db->fetchAll();
}

public function analyzeSingleEmployeePerformance(array $employeeIds, $templateId = null) {
    if (count($employeeIds) !== 1) {
        // ظ„ط§ طھظ†ط³ ط§ظ„طھط£ظƒط¯ ظ…ظ† ط£ظ† ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© ط§ظ„ظ…ط³ط§ط¹ط¯ط© طھط¹ظٹط¯ ظ…طµظپظˆظپط© 'counts' ظپط§ط±ط؛ط© ط£ظٹط¶ط§ظ‹
        return $this->getEmptyAnalysisResultForSingle();
    }
    $employeeId = $employeeIds[0];

    // 1. ط§ط³طھط¹ظ„ط§ظ… SQL (ظٹط¨ظ‚ظ‰ ظƒظ…ط§ ظ‡ظˆ طھظ…ط§ظ…ط§ظ‹)
    $sql = "SELECT 
                c.criterion_name, 
                cs.id as section_id,
                cs.section_name,
                cs.evaluation_type,
                cs.evaluation_options,
                ans.raw_value,
                ans.normalized_score,
                eval.evaluation_date,
                tpl.template_name
            FROM evaluation_answers ans
            JOIN evaluations eval ON ans.evaluation_id = eval.id
            JOIN form_templates tpl ON eval.template_id = tpl.id
            JOIN criteria_sections cs ON ans.section_id = cs.id
            JOIN criteria c ON cs.criterion_id = c.id
            WHERE eval.evaluated_employee_id = :employee_id 
              AND eval.status IN ('approved','completed') 
              AND ans.normalized_score IS NOT NULL";
    
    $params = [':employee_id' => $employeeId];
    if ($templateId) {
        $sql .= " AND eval.template_id = :template_id";
        $params[':template_id'] = $templateId;
    }
    $sql .= " ORDER BY cs.id, eval.evaluation_date DESC";
    
    $this->db->query($sql);
    foreach ($params as $key => $value) { $this->db->bind($key, $value); }
    $allAnswers = $this->db->fetchAll();

    if (empty($allAnswers)) {
        return $this->getEmptyAnalysisResultForSingle();
    }

    // 2. ط¨ظ†ط§ط، ط§ظ„ظ‡ظٹظƒظ„ ط§ظ„طھط­ظ„ظٹظ„ظٹ ط§ظ„ط°ظƒظٹ (ظٹط¨ظ‚ظ‰ ظƒظ…ط§ ظ‡ظˆ طھظ…ط§ظ…ط§ظ‹)
    $analysis = [];
    foreach ($allAnswers as $answer) {
        $sectionId = $answer->section_id;
        if (!isset($analysis[$sectionId])) {
            $analysis[$sectionId] = [
                'criterion_name' => $answer->criterion_name,
                'section_name'   => $answer->section_name,
                'history'        => [],
                'total_score'    => 0,
                'count'          => 0
            ];
        }
        $analysis[$sectionId]['history'][] = [
            'date'              => $answer->evaluation_date,
            'template_name'     => $answer->template_name,
            'raw_value'         => $answer->raw_value,
            'score'             => (float)$answer->normalized_score,
            'evaluation_type'   => $answer->evaluation_type,
            'evaluation_options'=> $answer->evaluation_options
        ];
        $analysis[$sectionId]['total_score'] += (float)$answer->normalized_score;
        $analysis[$sectionId]['count']++;
    }

    
    // =================================================================
    //          ===>  ط¨ط¯ط§ظٹط© ط§ظ„طھط¹ط¯ظٹظ„ ط§ظ„ظ…ط·ظ„ظˆط¨ ظ‡ظ†ط§  <===
    // =================================================================

    // 3. ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط·ط§طھ ظˆطھط­ط¯ظٹط¯ ظ†ظˆط¹ ط§ظ„ط¹ط±ط¶ ظ„ظƒظ„ ظƒظپط§ط،ط©
    $classifiedPerformance = ['strong' => [], 'medium' => [], 'weak' => []];
    // â–¼â–¼â–¼ طھط¹ط¯ظٹظ„ 1: طھط؛ظٹظٹط± ط¨ط³ظٹط· ظپظٹ ظ‡ظٹظƒظ„ ط§ظ„ظ…طµظپظˆظپط© ظ„طھط³طھظˆط¹ط¨ ط§ظ„ط¹ط¯ط¯
    $allPerformanceForChart = []; 
    $STRONG_THRESHOLD = 85;
    $WEAK_THRESHOLD = 50;

    foreach ($analysis as $sectionId => &$competency) {
        $competency['average_score'] = round($competency['total_score'] / $competency['count'], 2);
        $competency['is_detailed'] = $competency['count'] > 1;

        $uniqueNameForChart = $competency['section_name'] . ' (' . $competency['criterion_name'] . ')';
        
        // â–¼â–¼â–¼ طھط¹ط¯ظٹظ„ 2: طھط®ط²ظٹظ† ط§ظ„ظ…طھظˆط³ط· ظˆط§ظ„ط¹ط¯ط¯ ظ…ط¹ط§ظ‹
        $allPerformanceForChart[$uniqueNameForChart] = [
            'score' => $competency['average_score'],
            'count' => $competency['count'] // <-- طھط®ط²ظٹظ† ط§ظ„ط¹ط¯ط¯ ظ‡ظ†ط§
        ];

        if ($competency['average_score'] >= $STRONG_THRESHOLD) {
            $classifiedPerformance['strong'][$sectionId] = $competency;
        } elseif ($competency['average_score'] < $WEAK_THRESHOLD) {
            $classifiedPerformance['weak'][$sectionId] = $competency;
        } else {
            $classifiedPerformance['medium'][$sectionId] = $competency;
        }
    }
    unset($competency);

    // 4. ظپط±ط² ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط®ط·ط· ط§ظ„ط¯ط§ط¦ط±ظٹ ط¨ظ†ط§ط،ظ‹ ط¹ظ„ظ‰ ط§ظ„ط¯ط±ط¬ط©
    // â–¼â–¼â–¼ طھط¹ط¯ظٹظ„ 3: ط§ط³طھط®ط¯ط§ظ… uasort ظ„ظ„ظپط±ط² ظ…ط¹ ط§ظ„ط­ظپط§ط¸ ط¹ظ„ظ‰ ط§ظ„ظ…ظپط§طھظٹط­
    uasort($allPerformanceForChart, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // â–¼â–¼â–¼ طھط¹ط¯ظٹظ„ 4: ط¨ظ†ط§ط، ظ…طµظپظˆظپط© chartData ط§ظ„ظ†ظ‡ط§ط¦ظٹط© ط¨ط§ظ„ط´ظƒظ„ ط§ظ„طµط­ظٹط­
    $chartData = [
        'labels' => array_keys($allPerformanceForChart),
        'scores' => array_column($allPerformanceForChart, 'score'),
        'counts' => array_column($allPerformanceForChart, 'count') // <-- ط¥ط¶ط§ظپط© ط§ظ„ظ…ظپطھط§ط­ ط§ظ„ظ…ظپظ‚ظˆط¯
    ];

    // =================================================================
    //          ===>  ظ†ظ‡ط§ظٹط© ط§ظ„طھط¹ط¯ظٹظ„ ط§ظ„ظ…ط·ظ„ظˆط¨  <===
    // =================================================================

    // 5. ط¥ط±ط¬ط§ط¹ ط§ظ„ظ‡ظٹظƒظ„ ط§ظ„ظ†ظ‡ط§ط¦ظٹ ط§ظ„ظƒط§ظ…ظ„
    return [
        'classified_performance' => $classifiedPerformance,
        'performance_chart_data' => $chartData
    ];
}
/**
 * ط¯ط§ظ„ط© ظ…ط³ط§ط¹ط¯ط© ظ„ط¬ظ„ط¨ ط¹ط¯ط¯ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ظ„ظ„ظ…ظˆط¸ظپ
 */
public function getEmployeeEvaluationCount($employeeId, $templateId = null) {
    $sql = "SELECT COUNT(DISTINCT id) as evaluation_count 
            FROM evaluations 
            WHERE evaluated_employee_id = :employee_id 
            AND status IN ('approved','completed')";
    
    $params = [':employee_id' => $employeeId];
    
    if ($templateId) {
        $sql .= " AND template_id = :template_id";
        $params[':template_id'] = $templateId;
    }
    
    $this->db->query($sql);
    foreach ($params as $key => $value) {
        $this->db->bind($key, $value);
    }
    
    $result = $this->db->fetch();
    return $result ? (int)$result->evaluation_count : 0;
}
public function getDetailedPerformanceTrend($employeeId) {
    // ط§ظ„ط®ط·ظˆط© 1: طھط­ط¯ظٹط« ط§ظ„ط§ط³طھط¹ظ„ط§ظ… ظ„ظٹط´ظ…ظ„ ط§ظ„ط­ظ‚ظˆظ„ ط§ظ„ط¬ط¯ظٹط¯ط©
    $sql = "SELECT 
                eval.id as evaluation_id,
                eval.evaluation_date,
                eval.final_score,
                c.criterion_name,
                cs.section_name,
                ans.normalized_score,
                ans.raw_value,                  -- <-- ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¬ط¯ظٹط¯ 1
                cs.evaluation_type,             -- <-- ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¬ط¯ظٹط¯ 2
                cs.evaluation_options           -- <-- ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¬ط¯ظٹط¯ 3
            FROM 
                evaluation_answers ans
            JOIN 
                evaluations eval ON ans.evaluation_id = eval.id
            JOIN 
                criteria_sections cs ON ans.section_id = cs.id
            JOIN 
                criteria c ON cs.criterion_id = c.id
            WHERE 
                eval.evaluated_employee_id = :employee_id 
                AND eval.status IN ('approved','completed')
            ORDER BY 
                eval.evaluation_date DESC, c.order_index ASC, cs.order_index ASC";

    $this->db->query($sql);
    $this->db->bind(':employee_id', $employeeId);
    $results = $this->db->fetchAll();

    if (empty($results)) {
        return [];
    }

    // ط§ظ„ط®ط·ظˆط© 2: ط¥ط¹ط§ط¯ط© ظ‡ظٹظƒظ„ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ„طھط¶ظ…ظٹظ† ط§ظ„ط­ظ‚ظˆظ„ ط§ظ„ط¬ط¯ظٹط¯ط©
    $structuredData = [];
    foreach ($results as $row) {
        $eval_id = $row->evaluation_id;

        if (!isset($structuredData[$eval_id])) {
            $structuredData[$eval_id] = [
                'evaluation_date' => $row->evaluation_date,
                'final_score'     => $row->final_score,
                'criteria'        => []
            ];
        }

        $crit_name = $row->criterion_name;

        if (!isset($structuredData[$eval_id]['criteria'][$crit_name])) {
            $structuredData[$eval_id]['criteria'][$crit_name] = [
                'criterion_score' => 0,
                'sections' => []
            ];
        }

        // ط¥ط¶ط§ظپط© ظƒظ„ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط© ط¥ظ„ظ‰ ظ…طµظپظˆظپط© ط§ظ„ظ‚ط³ظ…
        $structuredData[$eval_id]['criteria'][$crit_name]['sections'][] = [
            'section_name'      => $row->section_name,
            'score'             => round($row->normalized_score, 2),
            'raw_value'         => $row->raw_value,
            'evaluation_type'   => $row->evaluation_type,
            'evaluation_options'=> $row->evaluation_options
        ];
    }

    // (ط§ط®طھظٹط§ط±ظٹ) ط­ط³ط§ط¨ ط¯ط±ط¬ط© ظƒظ„ ظ…ط¹ظٹط§ط±
    foreach ($structuredData as &$evaluation) {
        foreach ($evaluation['criteria'] as &$criterion) {
            $total_score = 0;
            $count = count($criterion['sections']);
            if ($count > 0) {
                foreach ($criterion['sections'] as $section) {
                    $total_score += $section['score'];
                }
                $criterion['criterion_score'] = round($total_score / $count, 2);
            }
        }
    }

    return $structuredData;
}

/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط© ظˆط§ط­طھط±ط§ظپظٹط©: ط®ط§طµط© ظپظ‚ط· ط¨ط¨ظٹط§ظ†ط§طھ ظ…ط®ط·ط· طھط·ظˆط± ط§ظ„ظƒظپط§ط،ط§طھ
 * =================================================================
 * ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© ظ…ط¹ط²ظˆظ„ط© طھظ…ط§ظ…ط§ظ‹ ظˆظ…ط³ط¤ظˆظ„ط© ظپظ‚ط· ط¹ظ† طھط¬ظ‡ظٹط² ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ„ظ„ظ…ط®ط·ط· ط§ظ„ط®ط·ظٹ.
 *
 * @param int $employeeId
 * @return array
 */
public function getCompetencyTrendData($employeeId) {
    // 1. ط§ط³طھط¹ظ„ط§ظ… SQL ظ…ظڈط­ط³ظژظ‘ظ† ظ„ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط·ظ„ظˆط¨ط© ظپظ‚ط·
    $sql = "SELECT 
                eval.evaluation_date,
                cs.section_name,
                c.criterion_name,
                ans.normalized_score
            FROM evaluation_answers ans
            JOIN evaluations eval ON ans.evaluation_id = eval.id
            JOIN criteria_sections cs ON ans.section_id = cs.id
            JOIN criteria c ON cs.criterion_id = c.id
            WHERE eval.evaluated_employee_id = :employee_id 
              AND eval.status IN ('approved','completed')
              AND ans.normalized_score IS NOT NULL
            ORDER BY 
                eval.evaluation_date ASC, cs.id ASC"; // ASC ظ„ظ„طھظˆط§ط±ظٹط® ظ…ظ‡ظ… ظ„ظ„ظ…ط®ط·ط·

    $this->db->query($sql);
    $this->db->bind(':employee_id', $employeeId);
    $results = $this->db->fetchAll();

    if (empty($results)) {
        return ['labels' => [], 'datasets' => []];
    }

    // 2. ط¥ط¹ط§ط¯ط© ظ‡ظٹظƒظ„ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ„طھظ†ط§ط³ط¨ Chart.js
    $trendData = [];
    $dates = [];
    $competencyNames = [];

    foreach ($results as $row) {
        $uniqueName = $row->section_name . ' (' . $row->criterion_name . ')';
        if (!in_array($uniqueName, $competencyNames)) {
            $competencyNames[] = $uniqueName;
        }
        $trendData[$uniqueName][$row->evaluation_date] = round($row->normalized_score, 2);
        if (!in_array($row->evaluation_date, $dates)) {
            $dates[] = $row->evaluation_date;
        }
    }

    $datasets = [];
    $colors = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1', '#fd7e14', '#20c997', '#d63384'];
    $colorIndex = 0;

    foreach ($competencyNames as $uniqueName) {
        $dataPoints = [];
        foreach ($dates as $date) {
            $dataPoints[] = $trendData[$uniqueName][$date] ?? null;
        }
        $color = $colors[$colorIndex % count($colors)];
        $datasets[] = [
            'label' => $uniqueName,
            'data' => $dataPoints,
            'borderColor' => $color,
            'backgroundColor' => $color . '33',
            'fill' => false,
            'tension' => 0.3
        ];
        $colorIndex++;
    }

    // 3. ط¥ط±ط¬ط§ط¹ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط¬ط§ظ‡ط²ط© ظ„ظ„ظ…ط®ط·ط·
    return ['labels' => $dates, 'datasets' => $datasets];
}

/**
 * ط§ظ„ظ…ط­ط±ظƒ ط§ظ„طھط­ظ„ظٹظ„ظٹ ظ„ظ„ط£ط¯ط§ط، ط§ظ„ط¬ظ…ط§ط¹ظٹ (ظ‚ط³ظ…طŒ ظپط±ط¹طŒ ط´ط±ظƒط©)
 *
 * @param array $employeeIds ظ…طµظپظˆظپط© ط¨ظ…ط¹ط±ظپط§طھ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ…ط³طھظ‡ط¯ظپظٹظ†.
 * @param int|null $templateId ظپظ„طھط± ط§ط®طھظٹط§ط±ظٹ ظ„ظ†ظˆط¹ ط§ظ„ظ‚ط§ظ„ط¨.
 * @return array ظ…طµظپظˆظپط© طھط­ظ„ظٹظ„ظٹط© ظ…ظپطµظ„ط©.
 */
public function analyzeGroupPerformance(array $employeeIds, $templateId = null) {
    if (empty($employeeIds)) {
        return ['classified_performance' => ['strong' => [], 'medium' => [], 'weak' => []], 'total_employees' => 0];
    }

    // 1. ط§ط³طھط¹ظ„ط§ظ… ظ„ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط®ط§ظ… ظ…ط¹ ط£ط³ظ…ط§ط، ط§ظ„ظ…ظˆط¸ظپظٹظ†
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $sql = "SELECT 
                e.full_name as employee_name,
                cs.section_name,
                ans.normalized_score
            FROM evaluation_answers ans
            JOIN evaluations eval ON ans.evaluation_id = eval.id
            JOIN employees e ON eval.evaluated_employee_id = e.id
            JOIN criteria_sections cs ON ans.section_id = cs.id
            WHERE eval.evaluated_employee_id IN ($placeholders)
              AND eval.status IN ('approved','completed') AND ans.normalized_score IS NOT NULL";

    $params = $employeeIds;
    if ($templateId) {
        $sql .= " AND eval.template_id = ?";
        $params[] = $templateId;
    }
    
    $this->db->query($sql);
    $i = 1;
    foreach ($params as $param) { $this->db->bind($i++, $param); }
    $results = $this->db->fetchAll();

    if (empty($results)) { return false; }

    // 2. طھط¬ظ…ظٹط¹ ط¯ط±ط¬ط§طھ ظƒظ„ ظ…ظˆط¸ظپ ظپظٹ ظƒظ„ ظƒظپط§ط،ط©
    $performanceByEmployee = [];
    foreach ($results as $row) {
        $performanceByEmployee[$row->section_name][$row->employee_name][] = (float)$row->normalized_score;
    }

    // 3. طھط­ظ„ظٹظ„ ظƒظ„ ظƒظپط§ط،ط© ط¹ظ„ظ‰ ط­ط¯ط©
    $classifiedPerformance = ['strong' => [], 'medium' => [], 'weak' => []];
    $STRONG_THRESHOLD = 85;
    $WEAK_THRESHOLD = 50;

    foreach ($performanceByEmployee as $sectionName => $employeesData) {
        $allAverages = [];
        $strong_employees = [];
        $weak_employees = [];

        foreach ($employeesData as $employeeName => $scores) {
            $employeeAverage = array_sum($scores) / count($scores);
            $allAverages[] = $employeeAverage;
            if ($employeeAverage >= $STRONG_THRESHOLD) {
                $strong_employees[] = $employeeName;
            }
            if ($employeeAverage < $WEAK_THRESHOLD) {
                $weak_employees[] = $employeeName;
            }
        }

        $overallAverage = array_sum($allAverages) / count($allAverages);
        
        $performanceItem = [
            'section_name' => $sectionName,
            'average_score' => round($overallAverage, 2),
            'strong_count' => count($strong_employees),
            'weak_count' => count($weak_employees),
            'strong_names' => $strong_employees, // <-- ط¥ط¶ط§ظپط© ط£ط³ظ…ط§ط، ط§ظ„ظ…طھظ…ظٹط²ظٹظ†
            'weak_names' => $weak_employees,     // <-- ط¥ط¶ط§ظپط© ط£ط³ظ…ط§ط، ط§ظ„ظ…ط­طھط§ط¬ظٹظ† ظ„ظ„طھط·ظˆظٹط±
            'total_employees' => count($employeesData)
        ];

        if ($overallAverage >= $STRONG_THRESHOLD) {
            $classifiedPerformance['strong'][] = $performanceItem;
        } elseif ($overallAverage < $WEAK_THRESHOLD) {
            $classifiedPerformance['weak'][] = $performanceItem;
        } else {
            $classifiedPerformance['medium'][] = $performanceItem;
        }
    }

    return [
        'classified_performance' => $classifiedPerformance,
        'total_employees' => count($employeeIds)
    ];
}
 
/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ط§ظ„ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ط¹ط§ظ…ط© ظ„ط£ط¯ط§ط، ظ…ظˆط¸ظپ
 * =================================================================
 * طھط­ط³ط¨ ظ…طھظˆط³ط· ط§ظ„ط£ط¯ط§ط، ظˆط¹ط¯ط¯ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ط§ظ„ظ…ظƒطھظ…ظ„ط© ظ„ظ…ظˆط¸ظپ ظ…ط¹ظٹظ†.
 *
 * @param int $employeeId
 * @return object|null
 */
public function getEmployeeOverallStats($employeeId) {
    $sql = "SELECT 
                COUNT(id) as total_evaluations,
                AVG(final_score) as average_score
            FROM 
                evaluations
            WHERE 
                evaluated_employee_id = :employee_id
                AND status IN ('approved','completed')
                AND final_score IS NOT NULL";

    $this->db->query($sql);
    $this->db->bind(':employee_id', (int)$employeeId);
    
    // ط§ط³طھط®ط¯ظ… fetch() ظ„ط£ظ†ظ†ط§ ظ†طھظˆظ‚ط¹ طµظپظ‹ط§ ظˆط§ط­ط¯ظ‹ط§ ظ…ظ† ط§ظ„ظ†طھط§ط¦ط¬
    $stats = $this->db->fetch();

    // ط§ظ„طھط£ظƒط¯ ظ…ظ† ط£ظ† ط§ظ„ظ‚ظٹظ… ظ„ظٹط³طھ NULL (ظپظٹ ط­ط§ظ„ط© ط¹ط¯ظ… ظˆط¬ظˆط¯ طھظ‚ظٹظٹظ…ط§طھ)
    if ($stats) {
        $stats->total_evaluations = (int) $stats->total_evaluations;
        $stats->average_score = (float) $stats->average_score;
    }

    return $stats;
}
/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط§ظ„طھط­ظ‚ظ‚ ظ…ظ…ط§ ط¥ط°ط§ ظƒط§ظ† ط§ظ„ظ‚ط§ظ„ط¨ ظ…ط³طھط®ط¯ظ…ط§ظ‹
 * =================================================================
 * @param int $templateId - ظ…ط¹ط±ظپ ط§ظ„ظ‚ط§ظ„ط¨ ط§ظ„ظ…ط±ط§ط¯ ط§ظ„طھط­ظ‚ظ‚ ظ…ظ†ظ‡.
 * @return bool - true ط¥ط°ط§ ظƒط§ظ† ط§ظ„ظ‚ط§ظ„ط¨ ظ…ط³طھط®ط¯ظ…ط§ظ‹طŒ ظˆ false ط¥ط°ط§ ظ„ظ… ظٹظƒظ†.
 */
public function isTemplateInUse($templateId) {
    $this->db->query("SELECT COUNT(id) as count FROM evaluations WHERE template_id = :template_id");
    $this->db->bind(':template_id', $templateId);
    $result = $this->db->fetch();
    
    // ط¥ط°ط§ ظƒط§ظ† ط¹ط¯ط¯ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ط§ظ„طھظٹ طھط³طھط®ط¯ظ… ظ‡ط°ط§ ط§ظ„ظ‚ط§ظ„ط¨ ط£ظƒط¨ط± ظ…ظ† طµظپط±طŒ ظپظ‡ط°ط§ ظٹط¹ظ†ظٹ ط£ظ†ظ‡ ظ…ط³طھط®ط¯ظ….
    return ($result && $result->count > 0);
}

/**
 * =================================================================
 * دوال التقارير الإدارية
 * =================================================================
 */

/**
 * جلب جميع التقارير الإدارية
 */
public function getAdminReports(): array {
    $sql = "SELECT e.*, ft.template_name, 
                   emp.branch_id, emp.department_id,
                   b.name as branch_name, d.name as department_name
            FROM evaluations e
            LEFT JOIN form_templates ft ON e.template_id = ft.id
            LEFT JOIN employees emp ON e.evaluated_employee_id = emp.id
            LEFT JOIN branches b ON emp.branch_id = b.id
            LEFT JOIN departments d ON emp.department_id = d.id
            WHERE e.entity_type = 'admin'
            ORDER BY e.created_at DESC";
    $this->db->query($sql);
    return $this->db->fetchAll();
}

/**
 * جلب أرشيف التقارير الإدارية
 */
public function getAdminReportsArchived(): array {
    $sql = "SELECT e.*, ft.template_name,
                   emp.branch_id, emp.department_id,
                   b.name as branch_name, d.name as department_name
            FROM evaluations e
            LEFT JOIN form_templates ft ON e.template_id = ft.id
            LEFT JOIN employees emp ON e.evaluated_employee_id = emp.id
            LEFT JOIN branches b ON emp.branch_id = b.id
            LEFT JOIN departments d ON emp.department_id = d.id
            WHERE e.entity_type = 'admin' AND e.status IN ('expired','archived')
            ORDER BY e.evaluation_date DESC";
    $this->db->query($sql);
    return $this->db->fetchAll();
}

/**
 * جلب تقرير إداري بالتفصيل
 */
public function getAdminReportById(int $id): ?object {
    $sql = "SELECT e.*, ft.template_name,
                   b.name as branch_name, d.name as department_name
            FROM evaluations e
            LEFT JOIN form_templates ft ON e.template_id = ft.id
            LEFT JOIN branches b ON e.entity_id = b.id AND e.entity_type = 'branch'
            LEFT JOIN departments d ON e.entity_id = d.id AND e.entity_type = 'department'
            WHERE e.id = :id AND e.entity_type = 'admin'";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}

/**
 * إنشاء تقرير إداري جديد
 */
public function createAdminReport(array $data): int {
    $sql = "INSERT INTO evaluations 
            (template_id, entity_type, entity_id, evaluated_employee_id, 
             evaluator_employee_id, evaluation_date, period_type, period_start, period_end,
             actual_force, applied_force, compliance_rate, final_score, status, notes, 
             report_title, created_by_user_id, created_at)
            VALUES 
            (:template_id, 'admin', :entity_id, :evaluated_employee_id,
             :evaluator_employee_id, CURDATE(), :period_type, :period_start, :period_end,
             :actual_force, :applied_force, :compliance_rate, :final_score, :status, :notes,
             :report_title, :created_by_user_id, NOW())";
    
    $this->db->query($sql);
    $this->db->bind(':template_id', $data['template_id']);
    $this->db->bind(':entity_id', $data['entity_id'] ?? null);
    $this->db->bind(':evaluated_employee_id', null);
    $this->db->bind(':evaluator_employee_id', null);
    $this->db->bind(':period_type', $data['period_type']);
    $this->db->bind(':period_start', $data['period_start']);
    $this->db->bind(':period_end', $data['period_end']);
    $this->db->bind(':actual_force', $data['actual_force'] ?? 0);
    $this->db->bind(':applied_force', $data['applied_force'] ?? 0);
    $this->db->bind(':compliance_rate', $data['compliance_rate'] ?? null);
    $this->db->bind(':final_score', $data['final_score'] ?? null);
    $this->db->bind(':status', $data['status'] ?? 'draft');
    $this->db->bind(':notes', $data['notes'] ?? '');
    $this->db->bind(':report_title', $data['report_title'] ?? '');
    $this->db->bind(':created_by_user_id', $data['created_by'] ?? 0);
    
    $this->db->execute();
    return $this->db->lastInsertId();
}

/**
 * تحديث تقرير إداري
 */
public function updateAdminReport(int $id, array $data): bool {
    $sql = "UPDATE evaluations SET
            report_title = :report_title,
            period_type = :period_type,
            period_start = :period_start,
            period_end = :period_end,
            actual_force = :actual_force,
            applied_force = :applied_force,
            compliance_rate = :compliance_rate,
            final_score = :final_score,
            status = :status,
            notes = :notes
            WHERE id = :id AND entity_type = 'admin'";
    
    $this->db->query($sql);
    $this->db->bind(':report_title', $data['report_title'] ?? '');
    $this->db->bind(':period_type', $data['period_type']);
    $this->db->bind(':period_start', $data['period_start']);
    $this->db->bind(':period_end', $data['period_end']);
    $this->db->bind(':actual_force', $data['actual_force'] ?? 0);
    $this->db->bind(':applied_force', $data['applied_force'] ?? 0);
    $this->db->bind(':compliance_rate', $data['compliance_rate'] ?? null);
    $this->db->bind(':final_score', $data['final_score'] ?? null);
    $this->db->bind(':status', $data['status'] ?? 'draft');
    $this->db->bind(':notes', $data['notes'] ?? '');
    $this->db->bind(':id', $id);
    
    return $this->db->execute();
}

/**
 * حفظ إجابة تقرير إداري
 */
public function saveAdminAnswer(int $evaluationId, int $sectionId, $value): bool {
    $sql = "INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value)
            VALUES (:evaluation_id, :section_id, :raw_value)
            ON DUPLICATE KEY UPDATE raw_value = :raw_value2";
    $this->db->query($sql);
    $this->db->bind(':evaluation_id', $evaluationId);
    $this->db->bind(':section_id', $sectionId);
    $this->db->bind(':raw_value', $value);
    $this->db->bind(':raw_value2', $value);
    return $this->db->execute();
}

/**
 * جلب إجابات تقرير إداري
 */
public function getAdminAnswers(int $evaluationId): array {
    $sql = "SELECT ea.*, cs.section_name, cs.evaluation_type, cs.section_weight,
                   c.id as criterion_id, c.criterion_name, c.criterion_weight
            FROM evaluation_answers ea
            LEFT JOIN criteria_sections cs ON ea.section_id = cs.id
            LEFT JOIN criteria c ON cs.criterion_id = c.id
            WHERE ea.evaluation_id = :evaluation_id
            ORDER BY c.order_index, cs.order_index";
    $this->db->query($sql);
    $this->db->bind(':evaluation_id', $evaluationId);
    return $this->db->fetchAll();
}

/**
 * حذف إجابات تقرير إداري
 */
public function deleteAdminAnswers(int $evaluationId): bool {
    $sql = "DELETE FROM evaluation_answers WHERE evaluation_id = :evaluation_id";
    $this->db->query($sql);
    $this->db->bind(':evaluation_id', $evaluationId);
    return $this->db->execute();
}

/**
 * إضافة مقيّم للتقرير
 */
public function addReportEvaluator(int $evaluationId, int $evaluatorId): bool {
    $sql = "INSERT INTO admin_report_evaluators (evaluation_id, evaluator_id, assigned_at)
            VALUES (:evaluation_id, :evaluator_id, NOW())
            ON DUPLICATE KEY UPDATE assigned_at = NOW()";
    $this->db->query($sql);
    $this->db->bind(':evaluation_id', $evaluationId);
    $this->db->bind(':evaluator_id', $evaluatorId);
    return $this->db->execute();
}

/**
 * جلب مقيّمي التقرير
 */
public function getReportEvaluators(int $evaluationId): array {
    $sql = "SELECT are.*, e.full_name, e.emp_code
            FROM admin_report_evaluators are
            LEFT JOIN employees e ON are.evaluator_id = e.id
            WHERE are.evaluation_id = :evaluation_id";
    $this->db->query($sql);
    $this->db->bind(':evaluation_id', $evaluationId);
    return $this->db->fetchAll();
}

/**
 * أرشفة تقرير إداري
 */
public function archiveAdminReport(int $id): bool {
    $sql = "UPDATE evaluations SET status = 'archived' WHERE id = :id AND entity_type = 'admin'";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->execute();
}

}

