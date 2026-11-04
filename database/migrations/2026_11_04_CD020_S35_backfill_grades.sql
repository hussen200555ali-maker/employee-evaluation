UPDATE evaluations e
INNER JOIN grading_scales gs
    ON gs.scale_name = 'السلم الخماسي'
   AND gs.is_active = 1
   AND e.final_score >= gs.min_value
   AND e.final_score <= gs.max_value
SET e.final_grade_label = gs.grade_label,
    e.final_grade_value = gs.numeric_value
WHERE e.final_score IS NOT NULL
  AND e.final_grade_label IS NULL;
