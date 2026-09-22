<?php
namespace App\Models;

use App\Core\Model;

/**
 * ظ…ظˆط¯ظ„ ط§ظ„طھط­ظ„ظٹظ„ (Analysis) - ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…ط­ط³ظ†ط©
 * ظ…ط³ط¤ظˆظ„ ط¹ظ† ط¥ط¬ط±ط§ط، ط§ظ„طھط­ظ„ظٹظ„ط§طھ ط§ظ„ظ…ط¹ظ‚ط¯ط© ط¹ظ„ظ‰ ط¨ظٹط§ظ†ط§طھ ط§ظ„ط£ط¯ط§ط، ظ…ط¹ طھط­ط³ظٹظ† ط§ظ„ط£ط¯ط§ط،
 */
class AnalysisModel extends Model {

    /**
     * =================================================================
     * ط§ظ„ط¯ط§ظ„ط© ط§ظ„ظ…ط±ظƒط²ظٹط© ظ„ظ„طھط­ظ„ظٹظ„ ط§ظ„ط¬ظ…ط§ط¹ظٹ (ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…ط­ط³ظ†ط© ط£ط¯ط§ط¦ظٹط§ظ‹)
     * =================================================================
     * ط§ظ„طھط­ط³ظٹظ†ط§طھ:
     * - ط§ط³طھط®ط¯ط§ظ… LIMIT ظپظٹ ط§ظ„ط§ط³طھط¹ظ„ط§ظ…ط§طھ ط§ظ„ظƒط¨ظٹط±ط©
     * - ط¥ط¶ط§ظپط© ظپظ„ط§طھط± ظ…ط¨ط¯ط¦ظٹط© ظ„طھظ‚ظ„ظٹظ„ ط­ط¬ظ… ط§ظ„ط¨ظٹط§ظ†ط§طھ
     * - طھط­ط³ظٹظ† ط¥ط¯ط§ط±ط© ط§ظ„ط°ط§ظƒط±ط©
     */
  public function getPerformanceAnalysis(array $employeeIds, $templateId = null, $positionFilter = null, $yearFilter = null) {
    if (empty($employeeIds)) {
        return $this->getEmptyAnalysisResult();
    }

    // ط§ط³طھط¹ظ„ط§ظ… ظ…ط­ط³ظ† ظ…ط¹ ظ…ط¹ط§ظ„ط¬ط© ط£ظپط¶ظ„ ظ„ظ„ط£ط®ط·ط§ط،
    try {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $params = $employeeIds;

        $sql = "SELECT 
                    cs.id as section_id,
                    cs.section_name,
                    c.id as criterion_id,
                    c.criterion_name,
                    ft.id as template_id,
                    ft.template_name,
                    ea.normalized_score as score,
                    e.id as employee_id,
                    e.full_name as employee_name,
                    p.title as employee_position,
                    eval.evaluation_date
                FROM evaluation_answers ea
                JOIN evaluations eval ON ea.evaluation_id = eval.id 
                JOIN criteria_sections cs ON ea.section_id = cs.id
                JOIN criteria c ON cs.criterion_id = c.id
                JOIN form_templates ft ON c.template_id = ft.id
                JOIN employees e ON eval.evaluated_employee_id = e.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE eval.evaluated_employee_id IN ($placeholders)
                    AND eval.status IN ('approved','completed')
                    AND ea.normalized_score IS NOT NULL
                    AND e.is_active = 1";

        // طھط·ط¨ظٹظ‚ ط§ظ„ظپظ„ط§طھط±
        if ($templateId) {
            $sql .= " AND ft.id = ?";
            $params[] = $templateId;
        }
        if ($positionFilter) {
            $sql .= " AND p.title = ?";
            $params[] = $positionFilter;
        }
        if ($yearFilter) {
            $sql .= " AND YEAR(eval.evaluation_date) = ?";
            $params[] = $yearFilter;
        }

        $sql .= " ORDER BY eval.evaluation_date DESC 
                 LIMIT 10000"; // ط­ط¯ ط£ظ‚طµظ‰ ظ„ظ„ط­ظ…ط§ظٹط©

        $this->db->query($sql);
        foreach ($params as $index => $param) {
            $this->db->bind($index + 1, $param);
        }
        
        $results = $this->db->fetchAll();
        return $this->processAnalysisResults($results);
        
    } catch (\Exception $e) {
        error_log("Performance Analysis Query Error: " . $e->getMessage());
        return $this->getEmptyAnalysisResult();
    }
}
    /**
     * طھظ†ظپظٹط° ط§ط³طھط¹ظ„ط§ظ… ظ…ط­ط³ظ† ط¨ط§ظ„ط£ط¯ط§ط،
     */
    private function executeOptimizedQuery($employeeIds, $templateId, $positionFilter, $yearFilter, $maxRecords) {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $params = $employeeIds;

        $sql = "SELECT 
                    cs.id as section_id,
                    cs.section_name,
                    c.id as criterion_id,
                    c.criterion_name,
                    ft.id as template_id,
                    ft.template_name,
                    ea.normalized_score as score,
                    e.id as employee_id,
                    e.full_name as employee_name,
                    p.title as employee_position,
                    eval.evaluation_date
                FROM evaluation_answers ea
                JOIN evaluations eval 
                    ON ea.evaluation_id = eval.id AND eval.status IN ('approved','completed')
                JOIN criteria_sections cs 
                    ON ea.section_id = cs.id
                JOIN criteria c ON cs.criterion_id = c.id
                JOIN form_templates ft ON c.template_id = ft.id
                JOIN employees e 
                    ON eval.evaluated_employee_id = e.id AND e.is_active = 1
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE eval.evaluated_employee_id IN ($placeholders)";

        // طھط·ط¨ظٹظ‚ ط§ظ„ظپظ„ط§طھط± ظ…ط¹ طھط­ط³ظٹظ† ط§ظ„ط£ط¯ط§ط،
        if ($templateId) {
            $sql .= " AND ft.id = ?";
            $params[] = $templateId;
        }
        if ($positionFilter) {
            $sql .= " AND p.title = ?";
            $params[] = $positionFilter;
        }
        if ($yearFilter) {
            $sql .= " AND YEAR(eval.evaluation_date) = ?";
            $params[] = $yearFilter;
        }

        $sql .= " ORDER BY eval.evaluation_date DESC LIMIT ?";
        $params[] = $maxRecords;

        $this->db->query($sql);
        foreach ($params as $index => $param) {
            $this->db->bind($index + 1, $param);
        }
        
        return $this->db->fetchAll();
    }

    /**
     * ظ…ط¹ط§ظ„ط¬ط© ط§ظ„ظ†طھط§ط¦ط¬ ظ…ط¹ طھط­ط³ظٹظ† ط§ط³طھط®ط¯ط§ظ… ط§ظ„ط°ط§ظƒط±ط©
     */
    private function processAnalysisResults($results) {
        if (empty($results)) {
            return $this->getEmptyAnalysisResult();
        }

        $competencies = [];
        $criteria = [];
        $totalScoresSum = 0;
        $totalScoresCount = 0;
        $availablePositions = [];
        $availableYears = [];

        foreach ($results as $row) {
            $this->processCompetencyRow($row, $competencies, $totalScoresSum, $totalScoresCount);
            $this->processCriteriaRow($row, $criteria);
            $this->collectFilterOptions($row, $availablePositions, $availableYears);
        }

        $classified = $this->classifyCompetencies($competencies);
        $criteriaPerformance = $this->calculateCriteriaPerformance($criteria);

        return [
            'classified_performance' => $classified,
            'criteria_performance' => $criteriaPerformance,
            'stats' => $this->calculateStats($classified, $totalScoresSum, $totalScoresCount),
            'available_positions' => array_unique($availablePositions),
            'available_years' => array_unique($availableYears)
        ];
    }

    /**
     * ط¯ظˆط§ظ„ ظ…ط³ط§ط¹ط¯ط© ظ…ط­ط³ظ†ط©
     */
    private function processCompetencyRow($row, &$competencies, &$totalScoresSum, &$totalScoresCount) {
        $sectionId = $row->section_id;
        
        if (!isset($competencies[$sectionId])) {
            $competencies[$sectionId] = [
                'section_name' => $row->section_name,
                'criterion_name' => $row->criterion_name,
                'template_name' => $row->template_name,
                'scores' => [],
                'strong_employees' => [],
                'medium_employees' => [],
                'weak_employees' => []
            ];
        }

        $score = (float)$row->score;
        $competencies[$sectionId]['scores'][] = $score;
        
        $employeeData = [
            'id' => $row->employee_id, 
            'name' => $row->employee_name, 
            'score' => $score, 
            'position' => $row->employee_position
        ];

        if ($score >= 80) {
            $competencies[$sectionId]['strong_employees'][] = $employeeData;
        } elseif ($score < 60) {
            $competencies[$sectionId]['weak_employees'][] = $employeeData;
        } else {
            $competencies[$sectionId]['medium_employees'][] = $employeeData;
        }

        $totalScoresSum += $score;
        $totalScoresCount++;
    }

    private function processCriteriaRow($row, &$criteria) {
        if (!isset($criteria[$row->criterion_id])) {
            $criteria[$row->criterion_id] = [
                'name' => $row->criterion_name, 
                'scores' => []
            ];
        }
        $criteria[$row->criterion_id]['scores'][] = (float)$row->score;
    }

    private function collectFilterOptions($row, &$positions, &$years) {
        if ($row->employee_position) {
            $positions[] = $row->employee_position;
        }
        $year = date('Y', strtotime($row->evaluation_date));
        $years[] = $year;
    }

/*  private function classifyCompetencies(&$competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    
    foreach ($competencies as &$competency) {
        $total = count($competency['scores']);
        $competency['average_score'] = $total > 0 ? round(array_sum($competency['scores']) / $total, 2) : 0;
        
        // ط¥ط¹ط§ط¯ط© ط­ط³ط§ط¨ ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        $competency['strong_count'] = 0;
        $competency['medium_count'] = 0;
        $competency['weak_count'] = 0;
        
        foreach ($competency['scores'] as $score) {
            if ($score >= 80) {
                $competency['strong_count']++;
            } elseif ($score >= 60) {
                $competency['medium_count']++;
            } else {
                $competency['weak_count']++;
            }
        }
        
        $competency['total_employees'] = $total;

        // ط­ط³ط§ط¨ ط§ظ„ظ†ط³ط¨ ط§ظ„ظ…ط¦ظˆظٹط© ط§ظ„ظ…ط­ط³ظ†ط©
        $this->calculatePercentages($competency);

        // ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        if ($competency['average_score'] >= 80) {
            $classified['strong'][] = $competency;
        } elseif ($competency['average_score'] >= 60) {
            $classified['medium'][] = $competency;
        } else {
            $classified['weak'][] = $competency;
        }
    }

    return $classified;
}
*//**
 * طھطµظ†ظٹظپ ط§ظ„ظƒظپط§ط،ط§طھ ط­ط³ط¨ ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط¬ط¯ظٹط¯ط©
 *//*
private function classifyCompetencies(&$competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    
    foreach ($competencies as &$competency) {
        $total = count($competency['scores']);
        $competency['average_score'] = $total > 0 ? round(array_sum($competency['scores']) / $total, 2) : 0;
        
        // ط¥ط¹ط§ط¯ط© ط­ط³ط§ط¨ ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        $competency['strong_count'] = 0;
        $competency['medium_count'] = 0;
        $competency['weak_count'] = 0;
        
        foreach ($competency['scores'] as $score) {
            if ($score >= 80) {
                $competency['strong_count']++;
            } elseif ($score >= 60) {
                $competency['medium_count']++;
            } else {
                $competency['weak_count']++;
            }
        }
        
        $competency['total_employees'] = $total;

        // ط­ط³ط§ط¨ ط§ظ„ظ†ط³ط¨ ط§ظ„ظ…ط¦ظˆظٹط© ط§ظ„ظ…ط­ط³ظ†ط©
        $this->calculatePercentages($competency);

        // ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        if ($competency['average_score'] >= 80) {
            $classified['strong'][] = $competency;
        } elseif ($competency['average_score'] >= 60) {
            $classified['medium'][] = $competency;
        } else {
            $classified['weak'][] = $competency;
        }
    }

    return $classified;
}
*/
/**
 * طھطµظ†ظٹظپ ط§ظ„ظƒظپط§ط،ط§طھ ط­ط³ط¨ ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط¬ط¯ظٹط¯ط©
 */
private function classifyCompetencies(&$competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    
    foreach ($competencies as &$competency) {
        $total = count($competency['scores']);
        $competency['average_score'] = $total > 0 ? round(array_sum($competency['scores']) / $total, 2) : 0;
        
        // ط¥ط¹ط§ط¯ط© ط­ط³ط§ط¨ ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        $competency['strong_count'] = 0;
        $competency['medium_count'] = 0;
        $competency['weak_count'] = 0;
        
        foreach ($competency['scores'] as $score) {
            if ($score >= 80) {
                $competency['strong_count']++;
            } elseif ($score >= 60) {
                $competency['medium_count']++;
            } else {
                $competency['weak_count']++;
            }
        }
        
        $competency['total_employees'] = $total;

        // ط­ط³ط§ط¨ ط§ظ„ظ†ط³ط¨ ط§ظ„ظ…ط¦ظˆظٹط© ط§ظ„ظ…ط­ط³ظ†ط©
        $this->calculatePercentages($competency);

        // ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        if ($competency['average_score'] >= 80) {
            $classified['strong'][] = $competency;
        } elseif ($competency['average_score'] >= 60) {
            $classified['medium'][] = $competency;
        } else {
            $classified['weak'][] = $competency;
        }
    }

    return $classified;
}
    private function calculatePercentages(&$competency) {
        if ($competency['total_employees'] > 0) {
            $competency['strong_percentage'] = round(($competency['strong_count'] / $competency['total_employees']) * 100, 3);
            $competency['medium_percentage'] = round(($competency['medium_count'] / $competency['total_employees']) * 100, 3);
            $competency['weak_percentage'] = round(($competency['weak_count'] / $competency['total_employees']) * 100, 3);
        } else {
            $competency['strong_percentage'] = 0;
            $competency['medium_percentage'] = 0;
            $competency['weak_percentage'] = 0;
        }
    }

    private function calculateCriteriaPerformance($criteria) {
        $criteriaPerformance = [];
        foreach ($criteria as $id => $data) {
            $total = count($data['scores']);
            $criteriaPerformance[] = [
                'criterion_id' => $id,
                'criterion_name' => $data['name'],
                'average_score' => $total > 0 ? round(array_sum($data['scores']) / $total, 2) : 0
            ];
        }
        usort($criteriaPerformance, fn($a, $b) => $b['average_score'] <=> $a['average_score']);
        return $criteriaPerformance;
    }

    private function calculateStats($classified, $totalScoresSum, $totalScoresCount) {
        return [
            'average_performance' => $totalScoresCount > 0 ? round($totalScoresSum / $totalScoresCount, 2) : 0,
            'strong_competencies_count' => count($classified['strong']),
            'weak_competencies_count' => count($classified['weak']),
            'total_employees_analyzed' => $totalScoresCount
        ];
    }

    private function getEmptyAnalysisResult() {
        return [
            'classified_performance' => ['strong' => [], 'medium' => [], 'weak' => []],
            'criteria_performance' => [],
            'stats' => [
                'average_performance' => 0, 
                'strong_competencies_count' => 0, 
                'weak_competencies_count' => 0,
                'total_employees_analyzed' => 0
            ],
            'available_positions' => [],
            'available_years' => []
        ];
    }

    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط© ظ„ظ„طھط­ظ„ظٹظ„ ط§ظ„طھط¬ظ…ظٹط¹ظٹ ظ…ط¹ ط§ظ„طھط®ط²ظٹظ† ط§ظ„ظ…ط¤ظ‚طھ
     * =================================================================
     */
    public function getCachedPerformanceAnalysis($employeeIds, $templateId = null, $positionFilter = null, $yearFilter = null, $cacheTimeout = 300) {
        $cacheKey = $this->generateCacheKey($employeeIds, $templateId, $positionFilter, $yearFilter);
        
        // ظ…ط­ط§ظˆظ„ط© ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ…ظ† ط§ظ„طھط®ط²ظٹظ† ط§ظ„ظ…ط¤ظ‚طھ
        $cachedResult = $this->getFromCache($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // ط¥ط°ط§ ظ„ظ… طھظˆط¬ط¯ ظپظٹ ط§ظ„طھط®ط²ظٹظ† ط§ظ„ظ…ط¤ظ‚طھطŒ طھظ†ظپظٹط° ط§ظ„طھط­ظ„ظٹظ„
        $result = $this->getPerformanceAnalysis($employeeIds, $templateId, $positionFilter, $yearFilter);
        
        // طھط®ط²ظٹظ† ط§ظ„ظ†طھظٹط¬ط© ظپظٹ ط§ظ„ط°ط§ظƒط±ط© ط§ظ„ظ…ط¤ظ‚طھط©
        $this->storeInCache($cacheKey, $result, $cacheTimeout);
        
        return $result;
    }

    private function generateCacheKey($employeeIds, $templateId, $positionFilter, $yearFilter) {
        sort($employeeIds);
        return 'analysis_' . md5(
            implode(',', $employeeIds) . 
            '_' . $templateId . 
            '_' . $positionFilter . 
            '_' . $yearFilter .
            '_' . date('Y-m-d') // طھط¶ظ…ظٹظ† ط§ظ„طھط§ط±ظٹط® ظ„ظ„طھط­ط¯ظٹط« ط§ظ„ظٹظˆظ…ظٹ
        );
    }

    private function getFromCache($cacheKey) {
        // طھظ†ظپظٹط° ط¨ط³ظٹط· ظ„ظ„طھط®ط²ظٹظ† ط§ظ„ظ…ط¤ظ‚طھ - ظٹظ…ظƒظ† ط§ط³طھط¨ط¯ط§ظ„ظ‡ ط¨ظ€ Redis ط£ظˆ Memcached
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            return json_decode(file_get_contents($cacheFile), true);
        }
        return null;
    }

    private function storeInCache($cacheKey, $data, $timeout) {
        $cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheDir . $cacheKey . '.json', json_encode($data));
    }

    /**
     * =================================================================
     * ط§ظ„ط¯ظˆط§ظ„ ط§ظ„ط¬ط¯ظٹط¯ط© ظ„ظ„طھط­ظ„ظٹظ„ ط§ظ„طھظپطµظٹظ„ظٹ ظ„ظ„ظ…ظˆط¸ظپظٹظ† ظˆط§ظ„ظ‡ظٹظƒظ„ ط§ظ„ظ‡ط±ظ…ظٹ
     * =================================================================
     */

    /**
     * ط¬ظ„ط¨ ط§ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظ…ظپطµظ„ط© ظ„ظ…ظˆط¸ظپ ظ…ط¹ظٹظ† ظ…ط¹ ط§ظ„طھطµظ†ظٹظپ ط§ظ„ط¬ط¯ظٹط¯
     */
    public function getEmployeeCompetenciesDetailed($employeeId, $templateId = null, $yearFilter = null) {
        $params = [$employeeId];
        $sql = "SELECT 
                    cs.section_name,
                    c.criterion_name,
                    ft.template_name,
                    ea.normalized_score as score,
                    eval.evaluation_date
                FROM evaluation_answers ea
                JOIN evaluations eval ON ea.evaluation_id = eval.id 
                JOIN criteria_sections cs ON ea.section_id = cs.id
                JOIN criteria c ON cs.criterion_id = c.id
                JOIN form_templates ft ON c.template_id = ft.id
                WHERE eval.evaluated_employee_id = ?
                    AND eval.status IN ('approved','completed')
                    AND ea.normalized_score IS NOT NULL";

        if ($templateId) {
            $sql .= " AND ft.id = ?";
            $params[] = $templateId;
        }
        if ($yearFilter) {
            $sql .= " AND YEAR(eval.evaluation_date) = ?";
            $params[] = $yearFilter;
        }

        $sql .= " ORDER BY eval.evaluation_date DESC, c.criterion_name, cs.section_name";

        $this->db->query($sql);
        foreach ($params as $index => $param) {
            $this->db->bind($index + 1, $param);
        }
        
        $results = $this->db->fetchAll();
        return $this->classifyEmployeeCompetencies($results);
    }

    /**
     * طھطµظ†ظٹظپ ظƒظپط§ط،ط§طھ ط§ظ„ظ…ظˆط¸ظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
     */
  /*  private function classifyEmployeeCompetencies($competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    $totalScore = 0;
    $count = 0;

    foreach ($competencies as $comp) {
        // ط§ظ„طھط­ظ‚ظ‚ ظ…ظ† ط£ظ† ط§ظ„ظ†طھظٹط¬ط© ظ…ظˆط¬ظˆط¯ط© ظˆظ„ظٹط³طھ null
        if ($comp->score === null) {
            continue;
        }
        
        $score = (float)$comp->score;
        $totalScore += $score;
        $count++;

        $compData = [
            'section_name' => $comp->section_name ?? 'ط؛ظٹط± ظ…ط¹ط±ظˆظپ',
            'criterion_name' => $comp->criterion_name ?? '',
            'template_name' => $comp->template_name ?? '',
            'score' => $score,
            'evaluation_date' => $comp->evaluation_date ?? ''
        ];

        if ($score >= 80) {
            $classified['strong'][] = $compData;
        } elseif ($score >= 60) {
            $classified['medium'][] = $compData;
        } else {
            $classified['weak'][] = $compData;
        }
    }

    // ط­ط³ط§ط¨ ظ…طھظˆط³ط· ط£ط¯ط§ط، ط§ظ„ظ…ظˆط¸ظپ ظ…ط¹ ط§ظ„ط­ظ…ط§ظٹط© ظ…ظ† ط§ظ„ظ‚ط³ظ…ط© ط¹ظ„ظ‰ طµظپط±
    $average = $count > 0 ? round($totalScore / $count, 2) : 0;

    return [
        'competencies' => $classified,
        'average_performance' => $average,
        'total_competencies' => $count
    ];
}*/
/**
 * طھطµظ†ظٹظپ ظƒظپط§ط،ط§طھ ط§ظ„ظ…ظˆط¸ظپ ط­ط³ط¨ ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط¬ط¯ظٹط¯ط©
 */
private function classifyEmployeeCompetencies($competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    $totalScore = 0;
    $count = 0;

    foreach ($competencies as $comp) {
        if ($comp->score === null) {
            continue;
        }
        
        $score = (float)$comp->score;
        $totalScore += $score;
        $count++;

        $compData = [
            'section_name' => $comp->section_name ?? 'ط؛ظٹط± ظ…ط¹ط±ظˆظپ',
            'criterion_name' => $comp->criterion_name ?? '',
            'template_name' => $comp->template_name ?? '',
            'score' => $score,
            'evaluation_date' => $comp->evaluation_date ?? ''
        ];

        // ط§ظ„طھطµظ†ظٹظپ ط­ط³ط¨ ط§ظ„ظ†ط·ط§ظ‚ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط©
        if ($score >= 80) {
            $classified['strong'][] = $compData;
        } elseif ($score >= 60) {
            $classified['medium'][] = $compData;
        } else {
            $classified['weak'][] = $compData;
        }
    }

    $average = $count > 0 ? round($totalScore / $count, 2) : 0;

    return [
        'competencies' => $classified,
        'average_performance' => $average,
        'total_competencies' => $count
    ];
}
    /**
     * ط­ط³ط§ط¨ ظ…طھظˆط³ط· ط£ط¯ط§ط، ط§ظ„ظ‚ط³ظ…
     */
  public function calculateDepartmentAverage($departmentId, $templateId = null, $yearFilter = null) {
    $employeeIds = $this->getEmployeeIdsByDepartment($departmentId);
    if (empty($employeeIds)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $params = $employeeIds;

    $sql = "SELECT AVG(ea.normalized_score) as avg_score
            FROM evaluation_answers ea
            JOIN evaluations eval ON ea.evaluation_id = eval.id 
            WHERE eval.evaluated_employee_id IN ($placeholders)
                AND eval.status IN ('approved','completed')
                AND ea.normalized_score IS NOT NULL";

    if ($templateId) {
        $sql .= " AND EXISTS (SELECT 1 FROM criteria_sections cs 
                             JOIN criteria c ON cs.criterion_id = c.id 
                             WHERE cs.id = ea.section_id AND c.template_id = ?)";
        $params[] = $templateId;
    }
    if ($yearFilter) {
        $sql .= " AND YEAR(eval.evaluation_date) = ?";
        $params[] = $yearFilter;
    }

    $this->db->query($sql);
    foreach ($params as $index => $param) {
        $this->db->bind($index + 1, $param);
    }
    
    $result = $this->db->fetch();
    
    // ط§ظ„طھطµط­ظٹط­: ط§ظ„طھط­ظ‚ظ‚ ظ…ظ† ظˆط¬ظˆط¯ ط§ظ„ظ†طھظٹط¬ط© ظˆط£ظ†ظ‡ط§ ظ„ظٹط³طھ null
    if (!$result || $result->avg_score === null) {
        return 0;
    }
    
    return round((float)$result->avg_score, 2);
}

/**
 * ط­ط³ط§ط¨ ظ…طھظˆط³ط· ط£ط¯ط§ط، ط§ظ„ظپط±ط¹
 */
public function calculateBranchAverage($branchId, $templateId = null, $yearFilter = null) {
    $employeeIds = $this->getEmployeeIdsByBranch($branchId);
    if (empty($employeeIds)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $params = $employeeIds;

    $sql = "SELECT AVG(ea.normalized_score) as avg_score
            FROM evaluation_answers ea
            JOIN evaluations eval ON ea.evaluation_id = eval.id 
            WHERE eval.evaluated_employee_id IN ($placeholders)
                AND eval.status IN ('approved','completed')
                AND ea.normalized_score IS NOT NULL";

    if ($templateId) {
        $sql .= " AND EXISTS (SELECT 1 FROM criteria_sections cs 
                             JOIN criteria c ON cs.criterion_id = c.id 
                             WHERE cs.id = ea.section_id AND c.template_id = ?)";
        $params[] = $templateId;
    }
    if ($yearFilter) {
        $sql .= " AND YEAR(eval.evaluation_date) = ?";
        $params[] = $yearFilter;
    }

    $this->db->query($sql);
    foreach ($params as $index => $param) {
        $this->db->bind($index + 1, $param);
    }
    
    $result = $this->db->fetch();
    
    // ط§ظ„طھطµط­ظٹط­: ط§ظ„طھط­ظ‚ظ‚ ظ…ظ† ظˆط¬ظˆط¯ ط§ظ„ظ†طھظٹط¬ط© ظˆط£ظ†ظ‡ط§ ظ„ظٹط³طھ null
    if (!$result || $result->avg_score === null) {
        return 0;
    }
    
    return round((float)$result->avg_score, 2);
}

/**
 * =================================================================
 * ط§ظ„ط¯ظˆط§ظ„ ط§ظ„ظ…ظپظ‚ظˆط¯ط© ط§ظ„طھظٹ ظٹط­طھط§ط¬ظ‡ط§ ط§ظ„ظ†ط¸ط§ظ… ط§ظ„ط¬ط¯ظٹط¯
 * =================================================================
 */

/**
 * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ظ‚ط³ظ… ظ…ط¹ظٹظ†
 */
public function getEmployeeIdsByDepartment($departmentId) {
    $this->db->query("SELECT id FROM employees WHERE department_id = :dept_id AND is_active = 1");
    $this->db->bind(':dept_id', (int)$departmentId);
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}

/**
 * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ظپط±ط¹ ظ…ط¹ظٹظ†
 */
public function getEmployeeIdsByBranch($branchId) {
    $this->db->query("SELECT id FROM employees WHERE branch_id = :branch_id AND is_active = 1");
    $this->db->bind(':branch_id', (int)$branchId);
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}

/**
 * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ط§ظ„ط´ط±ظƒط©
 */
public function getAllActiveEmployeeIds() {
    $this->db->query("SELECT id FROM employees WHERE is_active = 1");
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}

/**
 * ظٹط¨ط­ط« ط¹ظ† ط§ظ„ط£ظ‚ط³ط§ظ… ط§ظ„طھط§ط¨ط¹ط© ظ„ظپط±ط¹ ظ…ط¹ظٹظ†
 */
public function getDepartmentsByBranch($branchId) {
    $this->db->query("SELECT id, name FROM departments WHERE branch_id = :branch_id ORDER BY name ASC");
    $this->db->bind(':branch_id', (int)$branchId);
    return $this->db->fetchAll();
}
    /**
     * ط¬ظ„ط¨ ظ‡ظٹظƒظ„ ط§ظ„ط´ط±ظƒط© ظƒظƒظ„ ظ…ط¹ ط§ظ„ظ…طھظˆط³ط·ط§طھ
     */
    public function getCompanyHierarchy($templateId = null, $yearFilter = null) {
    $branchModel = new Branch();
    $branches = $branchModel->getAll();
    
    $hierarchy = [];
    $totalAverage = 0;
    $branchCount = 0;

    foreach ($branches as $branch) {
        $branchAverage = $this->calculateBranchAverage($branch->id, $templateId, $yearFilter);
        $totalAverage += $branchAverage;
        $branchCount++;

        $branchHierarchy = $this->getBranchHierarchy($branch->id, $templateId, $yearFilter);
        $hierarchy[] = [
            'type' => 'branch',
            'id' => $branch->id,
            'name' => $branch->name,
            'average_performance' => $branchAverage,
            'entities' => $branchHierarchy
        ];
    }

    $companyAverage = $branchCount > 0 ? round($totalAverage / $branchCount, 2) : 0;

    return [
        'type' => 'company',
        'average_performance' => $companyAverage,
        'entities' => $hierarchy
    ];
}

/**
 * ط¬ظ„ط¨ ظ‡ظٹظƒظ„ ظپط±ط¹ ظ…ط¹ظٹظ†
 */
public function getBranchHierarchy($branchId, $templateId = null, $yearFilter = null) {
    $departments = $this->getDepartmentsByBranch($branchId);
    
    $hierarchy = [];
    $totalAverage = 0;
    $deptCount = 0;

    foreach ($departments as $department) {
        $deptAverage = $this->calculateDepartmentAverage($department->id, $templateId, $yearFilter);
        $totalAverage += $deptAverage;
        $deptCount++;

        $deptHierarchy = $this->getDepartmentEmployees($department->id, $templateId, $yearFilter);
        $hierarchy[] = [
            'type' => 'department',
            'id' => $department->id,
            'name' => $department->name,
            'average_performance' => $deptAverage,
            'entities' => $deptHierarchy
        ];
    }

    return $hierarchy;
}
    /**
     * ط¬ظ„ط¨ ظ…ظˆط¸ظپظٹ ظ‚ط³ظ… ظ…ط¹ظٹظ† ظ…ط¹ طھط­ظ„ظٹظ„ظ‡ظ… ط§ظ„ظ…ظپطµظ„
     */
  public function getDepartmentEmployees($departmentId, $templateId = null, $yearFilter = null) {
    $employeeModel = new Employee();
    $employeeIds = $employeeModel->getEmployeeIdsByDepartment($departmentId);
    
    $employees = [];
    foreach ($employeeIds as $empId) {
        $employee = $employeeModel->findById($empId);
        if (!$employee) {
            continue; // طھط®ط·ظٹ ط¥ط°ط§ ظ„ظ… ظٹطھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظ‰ ط§ظ„ظ…ظˆط¸ظپ
        }
        
        $analysis = $this->getEmployeeCompetenciesDetailed($empId, $templateId, $yearFilter);
        
        $employees[] = [
            'type' => 'employee',
            'id' => $empId,
            'name' => $employee->full_name,
            'emp_code' => $employee->emp_code,
            'average_performance' => $analysis['average_performance'],
            'competencies' => $analysis['competencies'],
            'total_competencies' => $analysis['total_competencies']
        ];
    }

    return $employees;
}
// ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط© ظپظٹ AnalysisModel
public function getCompanyReportData() {
    return [
        'branches' => [
            [
                'name' => 'ط§ظ„ظ…ط±ظƒط² ط§ظ„ط±ط¦ظٹط³ظٹ',
                'department_count' => 6,
                'employee_count' => 25,
                'average_performance' => 86.26,
                'departments' => [
                    [
                        'name' => 'ط§ظ„ظ…ط¨ظٹط¹ط§طھ',
                        'average_performance' => 85.0,
                        'templates' => [
                            [
                                'template_name' => 'طھظ‚ظٹظٹظ… ط§ظ„ط£ط¯ط§ط، ط§ظ„ط±ط¨ط¹ ط³ظ†ظˆظٹ',
                                'criteria' => [
                                    [
                                        'criterion_name' => 'ط§ظ„ط¬ظˆط¯ط©',
                                        'sections' => [
                                            ['section_name' => 'ط¯ظ‚ط© ط§ظ„ط¹ظ…ظ„', 'strong_count' => 15, 'medium_count' => 8, 'weak_count' => 2, 'average_score' => 82],
                                            ['section_name' => 'ط§ظ„ط§ظ„طھط²ط§ظ… ط¨ط§ظ„ظ…ظˆط§ط¹ظٹط¯', 'strong_count' => 12, 'medium_count' => 10, 'weak_count' => 3, 'average_score' => 78]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
}
// ظپظٹ App\Models\AnalysisModel - ط¥ط¶ط§ظپط© ظ‡ط°ظ‡ ط§ظ„ط¯ظˆط§ظ„

/**
 * ط­ط³ط§ط¨ ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ظپط±ظˆط¹ ظˆط§ظ„ط£ظ‚ط³ط§ظ…
 */
public function getBranchDepartmentStats($branchId = null) {
    $sql = "SELECT 
                b.id as branch_id,
                b.name as branch_name,
                COUNT(DISTINCT d.id) as department_count,
                COUNT(DISTINCT e.id) as employee_count,
                COALESCE(AVG(eval.final_score), 0) as average_performance
            FROM branches b
            LEFT JOIN departments d ON b.id = d.branch_id
            LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
            LEFT JOIN evaluations eval ON e.id = eval.evaluated_employee_id AND eval.status IN ('approved','completed')";
    
    if ($branchId) {
        $sql .= " WHERE b.id = ?";
        $this->db->query($sql);
        $this->db->bind(1, $branchId);
    } else {
        $sql .= " GROUP BY b.id, b.name";
        $this->db->query($sql);
    }
    
    return $this->db->fetchAll();
}

/**
 * ط­ط³ط§ط¨ ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ط£ظ‚ط³ط§ظ… ظپظٹ ظپط±ط¹ ظ…ط¹ظٹظ†
 */
public function getDepartmentStatsByBranch($branchId) {
    $sql = "SELECT 
                d.id as department_id,
                d.name as department_name,
                COUNT(DISTINCT e.id) as employee_count,
                COALESCE(AVG(eval.final_score), 0) as average_performance
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
            LEFT JOIN evaluations eval ON e.id = eval.evaluated_employee_id AND eval.status IN ('approved','completed')
            WHERE d.branch_id = ?
            GROUP BY d.id, d.name";
    
    $this->db->query($sql);
    $this->db->bind(1, $branchId);
    return $this->db->fetchAll();
}

/**
 * ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط·ط§طھ ط§ظ„ط¹ط§ظ…ط©
 */
public function getOverallAverages() {
    $sql = "SELECT 
                COUNT(DISTINCT e.id) as total_employees,
                COUNT(DISTINCT d.id) as total_departments, 
                COUNT(DISTINCT b.id) as total_branches,
                COALESCE(AVG(eval.final_score), 0) as overall_performance
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN branches b ON d.branch_id = b.id
            LEFT JOIN evaluations eval ON e.id = eval.evaluated_employee_id AND eval.status IN ('approved','completed')
            WHERE e.is_active = 1";
    
    $this->db->query($sql);
    return $this->db->fetch();
}
// ظپظٹ App\Models\AnalysisModel - ط£ط¶ظپ ظ‡ط°ظ‡ ط§ظ„ط¯ظˆط§ظ„

/**
 * ط¬ظ„ط¨ ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ظپط±ظˆط¹ ظ…ط¹ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط­ظ‚ظٹظ‚ظٹط© ظ„ظ„ظ…ظˆط¸ظپظٹظ†
 */
public function getBranchStatsWithRealData() {
    $sql = "SELECT 
                b.id,
                b.name as branch_name,
                COUNT(DISTINCT d.id) as department_count,
                COUNT(DISTINCT e.id) as employee_count,
                COALESCE(AVG(eval.final_score), 0) as average_performance,
                SUM(CASE WHEN ea.normalized_score >= 80 THEN 1 ELSE 0 END) as strong_competencies,
                SUM(CASE WHEN ea.normalized_score >= 60 AND ea.normalized_score < 80 THEN 1 ELSE 0 END) as medium_competencies
            FROM branches b
            LEFT JOIN departments d ON b.id = d.branch_id
            LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
            LEFT JOIN evaluations eval ON e.id = eval.evaluated_employee_id AND eval.status IN ('approved','completed')
            LEFT JOIN evaluation_answers ea ON eval.id = ea.evaluation_id
            GROUP BY b.id, b.name
            ORDER BY b.name";
    
    $this->db->query($sql);
    return $this->db->fetchAll();
}

/**
 * ط¬ظ„ط¨ ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ظƒظپط§ط،ط§طھ ظ„ظ„ظپط±ط¹
 */
public function getBranchCompetencyStats($branchId) {
    $sql = "SELECT 
                COUNT(DISTINCT e.id) as total_employees,
                SUM(CASE WHEN ea.normalized_score >= 80 THEN 1 ELSE 0 END) as strong_count,
                SUM(CASE WHEN ea.normalized_score >= 60 AND ea.normalized_score < 80 THEN 1 ELSE 0 END) as medium_count,
                SUM(CASE WHEN ea.normalized_score < 60 THEN 1 ELSE 0 END) as weak_count
            FROM employees e
            JOIN departments d ON e.department_id = d.id
            JOIN branches b ON d.branch_id = b.id
            JOIN evaluations eval ON e.id = eval.evaluated_employee_id AND eval.status IN ('approved','completed')
            JOIN evaluation_answers ea ON eval.id = ea.evaluation_id
            WHERE b.id = ? AND e.is_active = 1";
    
    $this->db->query($sql);
    $this->db->bind(1, $branchId);
    return $this->db->fetch();
}
/**
 * طھط­ظ„ظٹظ„ ط£ط¯ط§ط، ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ظپط±ط¯ظٹ ظ…ط¹ ظ…ظ†ط·ظ‚ ط§ظ„ط¹ط±ط¶ ط§ظ„ط°ظƒظٹ ظ„ظ„ظ…ط®ط·ط·
 */
public function analyzeSingleEmployeePerformance(array $employeeIds, $templateId = null) {
    if (count($employeeIds) !== 1) {
        return $this->getEmptyAnalysisResultForSingle();
    }
    $employeeId = $employeeIds[0];

    // 1. ط§ط³طھط¹ظ„ط§ظ… SQL ظ„ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ
    $sql = "SELECT 
                c.criterion_name, 
                cs.id as section_id,
                cs.section_name,
                cs.evaluation_type,
                cs.evaluation_options,
                ans.raw_value,
                ans.normalized_score,
                eval.evaluation_date,
                tpl.template_name,
                eval.id as evaluation_id
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
    $sql .= " ORDER BY eval.evaluation_date DESC, cs.id ASC";
    
    $this->db->query($sql);
    foreach ($params as $key => $value) { 
        $this->db->bind($key, $value); 
    }
    $allAnswers = $this->db->fetchAll();

    if (empty($allAnswers)) {
        return $this->getEmptyAnalysisResultForSingle();
    }

    // 2. طھط­ط¯ظٹط¯ ط¹ط¯ط¯ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ ط§ظ„ظ…ظ…ظٹط²ط©
    $uniqueEvaluations = [];
    foreach ($allAnswers as $answer) {
        $uniqueEvaluations[$answer->evaluation_id] = true;
    }
    $evaluationCount = count($uniqueEvaluations);

    // 3. ط¨ظ†ط§ط، ط§ظ„ظ‡ظٹظƒظ„ ط§ظ„طھط­ظ„ظٹظ„ظٹ ط§ظ„ط°ظƒظٹ ط¨ظ†ط§ط،ظ‹ ط¹ظ„ظ‰ ط¹ط¯ط¯ ط§ظ„طھظ‚ظٹظٹظ…ط§طھ
    if ($evaluationCount === 1) {
        // ط­ط§ظ„ط© طھظ‚ظٹظٹظ… ظˆط§ط­ط¯: ط¹ط±ط¶ ط¬ظ…ظٹط¹ ط§ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظپط±ط¹ظٹط© ظ…ط¨ط§ط´ط±ط©
        return $this->buildSingleEvaluationAnalysis($allAnswers);
    } else {
        // ط­ط§ظ„ط© طھظ‚ظٹظٹظ…ط§طھ ظ…طھط¹ط¯ط¯ط©: ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط· ظ„ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظ…طھط´ط§ط¨ظ‡ط©
        return $this->buildMultiEvaluationAnalysis($allAnswers);
    }
}

/**
 * ط¨ظ†ط§ط، ط§ظ„طھط­ظ„ظٹظ„ ظ„طھظ‚ظٹظٹظ… ظˆط§ط­ط¯ (ط¹ط±ط¶ ط¬ظ…ظٹط¹ ط§ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظپط±ط¹ظٹط©)
 */
private function buildSingleEvaluationAnalysis($allAnswers) {
    $analysis = [];
    $chartLabels = [];
    $chartScores = [];
    $chartCounts = [];

    foreach ($allAnswers as $answer) {
        $sectionId = $answer->section_id;
        
        if (!isset($analysis[$sectionId])) {
            $analysis[$sectionId] = [
                'criterion_name' => $answer->criterion_name,
                'section_name'   => $answer->section_name,
                'history'        => [],
                'total_score'    => 0,
                'count'          => 0,
                'is_detailed'    => false // طھظ‚ظٹظٹظ… ظˆط§ط­ط¯ = ط؛ظٹط± ظ…ظپطµظ„
            ];
        }

        // ط¥ط¶ط§ظپط© ط§ظ„ط³ط¬ظ„ ط§ظ„طھط§ط±ظٹط®ظٹ
        $analysis[$sectionId]['history'][] = [
            'date'              => $answer->evaluation_date,
            'template_name'     => $answer->template_name,
            'raw_value'         => $answer->raw_value,
            'score'             => (float)$answer->normalized_score,
            'evaluation_type'   => $answer->evaluation_type,
            'evaluation_options'=> $answer->evaluation_options
        ];

        // طھط­ط¯ظٹط« ط§ظ„ظ…ط¬ظ…ظˆط¹ ظˆط§ظ„ط¹ط¯ط¯
        $analysis[$sectionId]['total_score'] += (float)$answer->normalized_score;
        $analysis[$sectionId]['count']++;
    }

    // ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط·ط§طھ ط§ظ„ظ†ظ‡ط§ط¦ظٹط©
    foreach ($analysis as $sectionId => &$competency) {
        $competency['average_score'] = round($competency['total_score'] / $competency['count'], 2);
        
        // ط¥ط¹ط¯ط§ط¯ ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط®ط·ط·
        $uniqueNameForChart = $competency['section_name'];
        $chartLabels[] = $uniqueNameForChart;
        $chartScores[] = $competency['average_score'];
        $chartCounts[] = 1; // طھظ‚ظٹظٹظ… ظˆط§ط­ط¯ ظپظ‚ط·
    }
    unset($competency);

    // طھطµظ†ظٹظپ ط§ظ„ط£ط¯ط§ط،
    $classifiedPerformance = $this->classifyCompetenciesForChart($analysis);

    return [
        'classified_performance' => $classifiedPerformance,
        'performance_chart_data' => [
            'labels' => $chartLabels,
            'scores' => $chartScores,
            'counts' => $chartCounts
        ]
    ];
}

/**
 * ط¨ظ†ط§ط، ط§ظ„طھط­ظ„ظٹظ„ ظ„طھظ‚ظٹظٹظ…ط§طھ ظ…طھط¹ط¯ط¯ط© (ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط· ظ„ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظ…طھط´ط§ط¨ظ‡ط©)
 */
private function buildMultiEvaluationAnalysis($allAnswers) {
    $analysis = [];
    
    foreach ($allAnswers as $answer) {
        $sectionId = $answer->section_id;
        
        if (!isset($analysis[$sectionId])) {
            $analysis[$sectionId] = [
                'criterion_name' => $answer->criterion_name,
                'section_name'   => $answer->section_name,
                'history'        => [],
                'total_score'    => 0,
                'count'          => 0,
                'is_detailed'    => true // طھظ‚ظٹظٹظ…ط§طھ ظ…طھط¹ط¯ط¯ط© = ظ…ظپطµظ„
            ];
        }

        // ط¥ط¶ط§ظپط© ط§ظ„ط³ط¬ظ„ ط§ظ„طھط§ط±ظٹط®ظٹ
        $analysis[$sectionId]['history'][] = [
            'date'              => $answer->evaluation_date,
            'template_name'     => $answer->template_name,
            'raw_value'         => $answer->raw_value,
            'score'             => (float)$answer->normalized_score,
            'evaluation_type'   => $answer->evaluation_type,
            'evaluation_options'=> $answer->evaluation_options
        ];

        // طھط­ط¯ظٹط« ط§ظ„ظ…ط¬ظ…ظˆط¹ ظˆط§ظ„ط¹ط¯ط¯
        $analysis[$sectionId]['total_score'] += (float)$answer->normalized_score;
        $analysis[$sectionId]['count']++;
    }

    // ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط·ط§طھ ظˆطھط¬ظ‡ظٹط² ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط®ط·ط·
    $allPerformanceForChart = [];
    
    foreach ($analysis as $sectionId => &$competency) {
        $competency['average_score'] = round($competency['total_score'] / $competency['count'], 2);
        
        // طھط¬ظ…ظٹط¹ ط§ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظ…طھط´ط§ط¨ظ‡ط© ط¨ظ†ط§ط،ظ‹ ط¹ظ„ظ‰ ط§ظ„ط§ط³ظ… ظˆط§ظ„ظ…ط¹ظٹط§ط±
        $uniqueKey = $competency['section_name'] . '|' . $competency['criterion_name'];
        
        if (!isset($allPerformanceForChart[$uniqueKey])) {
            $allPerformanceForChart[$uniqueKey] = [
                'score' => 0,
                'count' => 0,
                'total_evaluations' => 0
            ];
        }
        
        $allPerformanceForChart[$uniqueKey]['score'] += $competency['average_score'];
        $allPerformanceForChart[$uniqueKey]['count']++;
        $allPerformanceForChart[$uniqueKey]['total_evaluations'] = $competency['count'];
    }
    unset($competency);

    // ط­ط³ط§ط¨ ط§ظ„ظ…طھظˆط³ط·ط§طھ ط§ظ„ظ†ظ‡ط§ط¦ظٹط© ظ„ظ„ظƒظپط§ط،ط§طھ ط§ظ„ظ…طھط´ط§ط¨ظ‡ط©
    $chartLabels = [];
    $chartScores = [];
    $chartCounts = [];
    
    foreach ($allPerformanceForChart as $uniqueKey => $data) {
        $averageScore = $data['score'] / $data['count'];
        
        // ط§ط³طھط®ط±ط§ط¬ ط§ط³ظ… ط§ظ„ظƒظپط§ط،ط© ظ…ظ† ط§ظ„ظ…ظپطھط§ط­ ط§ظ„ظپط±ظٹط¯
        $parts = explode('|', $uniqueKey);
        $chartLabels[] = $parts[0]; // ط§ط³ظ… ط§ظ„ظƒظپط§ط،ط© ظپظ‚ط·
        
        $chartScores[] = round($averageScore, 2);
        $chartCounts[] = $data['total_evaluations'];
    }

    // طھطµظ†ظٹظپ ط§ظ„ط£ط¯ط§ط،
    $classifiedPerformance = $this->classifyCompetenciesForChart($analysis);

    return [
        'classified_performance' => $classifiedPerformance,
        'performance_chart_data' => [
            'labels' => $chartLabels,
            'scores' => $chartScores,
            'counts' => $chartCounts
        ]
    ];
}

/**
 * طھطµظ†ظٹظپ ط§ظ„ظƒظپط§ط،ط§طھ ظ„ظ„ظ…ط®ط·ط· ط§ظ„ط¯ط§ط¦ط±ظٹ
 */
/*private function classifyCompetenciesForChart($analysis) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    $STRONG_THRESHOLD = 85;
    $WEAK_THRESHOLD = 50;

    foreach ($analysis as $sectionId => $competency) {
        if ($competency['average_score'] >= $STRONG_THRESHOLD) {
            $classified['strong'][$sectionId] = $competency;
        } elseif ($competency['average_score'] < $WEAK_THRESHOLD) {
            $classified['weak'][$sectionId] = $competency;
        } else {
            $classified['medium'][$sectionId] = $competency;
        }
    }

    return $classified;
}*/
/**
 * طھطµظ†ظٹظپ ط§ظ„ظƒظپط§ط،ط§طھ ظ„ظ„ظ…ط®ط·ط· ط§ظ„ط¯ط§ط¦ط±ظٹ ط­ط³ط¨ ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط¬ط¯ظٹط¯ط©
 */
private function classifyCompetenciesForChart($analysis) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    $STRONG_THRESHOLD = 80; // طھط؛ظٹظٹط± ظ…ظ† 85 ط¥ظ„ظ‰ 80
    $WEAK_THRESHOLD = 60;   // طھط؛ظٹظٹط± ظ…ظ† 50 ط¥ظ„ظ‰ 60

    foreach ($analysis as $sectionId => $competency) {
        if ($competency['average_score'] >= $STRONG_THRESHOLD) {
            $classified['strong'][$sectionId] = $competency;
        } elseif ($competency['average_score'] < $WEAK_THRESHOLD) {
            $classified['weak'][$sectionId] = $competency;
        } else {
            $classified['medium'][$sectionId] = $competency;
        }
    }

    return $classified;
}
/**
 * طھظ„ظˆظٹظ† ط§ظ„ظ†ظ‚ط§ط· ظپظٹ ط§ظ„ظ…ط®ط·ط·ط§طھ ط­ط³ط¨ ط§ظ„طھطµظ†ظٹظپ ط§ظ„ط¬ط¯ظٹط¯
 */
private function getColorForScore($score) {
    if ($score >= 80) {
        return 'rgba(25, 135, 84, 1)';    // ط£ط®ط¶ط± ظ„ظ„ظ‚ظˆط©
    } elseif ($score < 60) {
        return 'rgba(220, 53, 69, 1)';     // ط£ط­ظ…ط± ظ„ظ„ط¶ط¹ظپ
    } else {
        return 'rgba(255, 193, 7, 1)';     // ط£طµظپط± ظ„ظ„ظ…طھظˆط³ط·
    }
}
}
