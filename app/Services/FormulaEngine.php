<?php
namespace App\Services;

/**
 * محرك المعادلات الحسابية للقوالب الإدارية
 * يدعم العمليات الحسابية والدوال الشرطية والمالية
 */
class FormulaEngine {

    private $data = [];
    private $columns = [];
    private $errors = [];
    private $currentColumn = 'A';

    /**
     * تحليل وتنفيذ صيغة حسابية
     * @param string $formula الصيغة مثل =B2/C2*100
     * @param array $rowData بيانات الصف الحالي
     * @param array $allRows جميع صفوف القالب
     * @param array $columns معلومات الأعمدة
     * @return float|bool النتيجة أو false عند الخطأ
     */
    public function evaluate(string $formula, array $rowData, array $allRows, array $columns) {
        $this->data = $rowData;
        $this->columns = $columns;
        $this->errors = [];

        // إزالة علامة البداية
        $formula = ltrim($formula, '=');

        // استبدال مراجع الأعمدة بالقيم
        $processedFormula = $this->replaceReferences($formula, $allRows);

        // معالجة الدوال المدمجة
        $processedFormula = $this->processFunctions($processedFormula, $allRows);

        // تقييم التعبير الحسابي
        return $this->calculate($processedFormula);
    }

    /**
     * استبدال مراجع الأعمدة بالقيم
     * مثال: B2 → 25, C3 → "نص"
     */
    private function replaceReferences(string $formula, array $allRows) {
        // نمط المرجع: حرف العمود + رقم الصف (مثل B2, C3, A1:A10)
        $pattern = '/([A-Z]+)(\d+)/';

        return preg_replace_callback($pattern, function($matches) use ($allRows) {
            $colKey = $matches[1];
            $rowIndex = (int)$matches[2];

            // البحث عن القيمة في جميع الصفوف
            foreach ($allRows as $rIndex => $row) {
                if (($rIndex + 1) == $rowIndex && isset($row['columns'][$colKey])) {
                    $value = $row['columns'][$colKey];
                    return is_numeric($value) ? $value : 0;
                }
            }
            return 0;
        }, $formula);
    }

    /**
     * معالجة الدوال المدمجة
     */
    private function processFunctions(string $formula, array $allRows) {
        // دالة SUM - المجموع
        $formula = preg_replace_callback('/SUM\(([^)]+)\)/i', function($matches) use ($allRows) {
            return $this->calculateSum($matches[1], $allRows);
        }, $formula);

        // دالة AVG - المتوسط
        $formula = preg_replace_callback('/AVG\(([^)]+)\)/i', function($matches) use ($allRows) {
            return $this->calculateAvg($matches[1], $allRows);
        }, $formula);

        // دالة MIN - أقل قيمة
        $formula = preg_replace_callback('/MIN\(([^)]+)\)/i', function($matches) use ($allRows) {
            return $this->calculateMin($matches[1], $allRows);
        }, $formula);

        // دالة MAX - أعلى قيمة
        $formula = preg_replace_callback('/MAX\(([^)]+)\)/i', function($matches) use ($allRows) {
            return $this->calculateMax($matches[1], $allRows);
        }, $formula);

        // دالة COUNT - العدد
        $formula = preg_replace_callback('/COUNT\(([^)]+)\)/i', function($matches) use ($allRows) {
            return $this->calculateCount($matches[1], $allRows);
        }, $formula);

        // دالة IF - شرطي
        $formula = preg_replace_callback('/IF\(([^,]+),([^,]+),([^)]+)\)/i', function($matches) {
            return $this->calculateIf($matches[1], $matches[2], $matches[3]);
        }, $formula);

        // دالة AND
        $formula = preg_replace_callback('/AND\(([^)]+)\)/i', function($matches) {
            return $this->calculateAnd($matches[1]);
        }, $formula);

        // دالة OR
        $formula = preg_replace_callback('/OR\(([^)]+)\)/i', function($matches) {
            return $this->calculateOr($matches[1]);
        }, $formula);

        // دالة TOTAL - المجموع الكلي
        $formula = preg_replace_callback('/TOTAL\(\)/i', function() use ($allRows) {
            return $this->calculateTotal($allRows);
        }, $formula);

        // دالة AVERAGE - المتوسط الكلي
        $formula = preg_replace_callback('/AVERAGE\(\)/i', function() use ($allRows) {
            return $this->calculateAverage($allRows);
        }, $formula);

        return $formula;
    }

    /**
     * حساب دالة SUM
     */
    private function calculateSum(string $range, array $allRows) {
        $values = $this->getRangeValues($range, $allRows);
        return array_sum($values);
    }

    /**
     * حساب دالة AVG
     */
    private function calculateAvg(string $range, array $allRows) {
        $values = $this->getRangeValues($range, $allRows);
        $count = count($values);
        return $count > 0 ? array_sum($values) / $count : 0;
    }

    /**
     * حساب دالة MIN
     */
    private function calculateMin(string $range, array $allRows) {
        $values = $this->getRangeValues($range, $allRows);
        return !empty($values) ? min($values) : 0;
    }

    /**
     * حساب دالة MAX
     */
    private function calculateMax(string $range, array $allRows) {
        $values = $this->getRangeValues($range, $allRows);
        return !empty($values) ? max($values) : 0;
    }

    /**
     * حساب دالة COUNT
     */
    private function calculateCount(string $range, array $allRows) {
        $values = $this->getRangeValues($range, $allRows);
        return count($values);
    }

    /**
     * حساب دالة IF
     */
    private function calculateIf(string $condition, string $trueValue, string $falseValue) {
        $conditionResult = $this->evaluateCondition($condition);
        return $conditionResult ? $trueValue : $falseValue;
    }

    /**
     * حساب دالة AND
     */
    private function calculateAnd(string $conditions) {
        $parts = explode(',', $conditions);
        foreach ($parts as $part) {
            if (!$this->evaluateCondition(trim($part))) {
                return 0;
            }
        }
        return 1;
    }

    /**
     * حساب دالة OR
     */
    private function calculateOr(string $conditions) {
        $parts = explode(',', $conditions);
        foreach ($parts as $part) {
            if ($this->evaluateCondition(trim($part))) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * حساب دالة TOTAL (مجموع جميع الصفوف)
     */
    private function calculateTotal(array $allRows) {
        $total = 0;
        foreach ($allRows as $row) {
            if (isset($row['columns'][$this->getCurrentColumn()])) {
                $value = $row['columns'][$this->getCurrentColumn()];
                if (is_numeric($value)) {
                    $total += $value;
                }
            }
        }
        return $total;
    }

    /**
     * حساب دالة AVERAGE (متوسط جميع الصفوف)
     */
    private function calculateAverage(array $allRows) {
        $values = [];
        foreach ($allRows as $row) {
            if (isset($row['columns'][$this->getCurrentColumn()])) {
                $value = $row['columns'][$this->getCurrentColumn()];
                if (is_numeric($value)) {
                    $values[] = $value;
                }
            }
        }
        $count = count($values);
        return $count > 0 ? array_sum($values) / $count : 0;
    }

    /**
     * تقييم شرط
     */
    private function evaluateCondition(string $condition) {
        // دعم المقارنات: >=, <=, >, <, =, !=
        if (preg_match('/(.+?)\s*(>=|<=|>|<|=|!=)\s*(.+)/', $condition, $matches)) {
            $left = $this->evaluateSimpleExpression(trim($matches[1]));
            $operator = $matches[2];
            $right = $this->evaluateSimpleExpression(trim($matches[3]));

            switch ($operator) {
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
                case '>': return $left > $right;
                case '<': return $left < $right;
                case '=': return $left == $right;
                case '!=': return $left != $right;
            }
        }
        return false;
    }

    /**
     * تقييم تعبير بسيط (رقم أو مرجع عمود)
     */
    private function evaluateSimpleExpression(string $expr) {
        // إذا كان رقم
        if (is_numeric($expr)) {
            return $expr;
        }
        // إذا كان نص بين أقواس
        if (preg_match("/^'(.+)'$/", $expr, $matches)) {
            return $matches[1];
        }
        return 0;
    }

    /**
     * الحصول على قيم النطاق
     * مثال: B2:B10 → [قيمة B2, قيمة B3, ..., قيمة B10]
     */
    private function getRangeValues(string $range, array $allRows) {
        $values = [];
        $parts = explode(':', $range);

        if (count($parts) == 2) {
            $start = $parts[0];
            $end = $parts[1];

            $startCol = preg_replace('/\d+/', '', $start);
            $startRow = (int)preg_replace('/[A-Z]+/', '', $start);
            $endRow = (int)preg_replace('/[A-Z]+/', '', $end);

            for ($i = $startRow; $i <= $endRow; $i++) {
                foreach ($allRows as $row) {
                    if ($row['row_index'] == $i && isset($row['columns'][$startCol])) {
                        $value = $row['columns'][$startCol];
                        if (is_numeric($value)) {
                            $values[] = $value;
                        }
                    }
                }
            }
        }

        return $values;
    }

    /**
     * تقييم تعبير حسابي بسيط
     */
    private function calculate(string $expression) {
        // تنظيف التعبير
        $expression = str_replace(' ', '', $expression);

        // استبدال الفاصلة بالنقطة العشرية
        $expression = str_replace(',', '.', $expression);

        // محاولة حساب التعبير بشكل آمن
        try {
            // التحقق من أن التعبير يحتوي فقط على أرقام وعمليات حسابية
            if (preg_match('/^[\d\+\-\*\/\(\)\.]+$/', $expression)) {
                // التحقق من القسمة على صفر
                if (preg_match('/\/\s*0(?![\d\.])/', $expression)) {
                    return 0;
                }
                $result = @eval("return $expression;");
                return is_numeric($result) ? round($result, 2) : 0;
            }
            return 0;
        } catch (\Throwable $e) {
            $this->errors[] = "خطأ في حساب التعبير: $expression";
            return 0;
        }
    }

    /**
     * الحصول على أخطاء التحليل
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * التحقق من صحة الصيغة
     */
    public function validate(string $formula): array {
        $errors = [];

        // التحقق من التنسيق
        if (empty($formula)) {
            $errors[] = 'الصيغة فارغة';
        }

        // التحقق من الأقواس المBalance
        $openCount = substr_count($formula, '(');
        $closeCount = substr_count($formula, ')');
        if ($openCount !== $closeCount) {
            $errors[] = 'الأقواس غير متوازنة';
        }

        // التحقق من الدوال المدعومة
        $validFunctions = ['SUM', 'AVG', 'MIN', 'MAX', 'COUNT', 'IF', 'AND', 'OR', 'TOTAL', 'AVERAGE'];
        preg_match_all('/([A-Z]+)\(/', $formula, $matches);
        foreach ($matches[1] as $func) {
            if (!in_array($func, $validFunctions)) {
                $errors[] = "الدالة $func غير مدعومة";
            }
        }

        return $errors;
    }

    /**
     * الحصول على العمود الحالي (للدوال TOTAL و AVERAGE)
     */
    private function getCurrentColumn() {
        // سيتم تمريره من الخارج عند الحاجة
        return $this->currentColumn ?? 'C';
    }

    /**
     * تعيين العمود الحالي
     */
    public function setCurrentColumn(string $column) {
        $this->currentColumn = $column;
    }

    /**
     * حساب جميع الأقسام في القالب (عادية + صيغ + تلقائية + مساعد)
     *
     * @param array $template هيكل القالب من getTemplateWithStructure()
     * @param array $userAnswers قيم المستخدم المدخلة [sectionId => value]
     * @param int $actualForce القوة الفعلية
     * @return array [sectionId => finalValue] شامل كل الأقسام
     */
    public function computeAllSections(array $template, array $userAnswers, int $actualForce): array {
        $computed = [];
        $rows = [];
        $sectionColMap = [];

        // بناء صفوف وربط كل قسم بعموده
        foreach ($template['criteria'] as $criterion) {
            $row = ['columns' => []];
            $colIndex = 0;

            foreach ($criterion['sections'] as $section) {
                $colIndex++;
                $colLetter = chr(64 + $colIndex);
                $sid = $section['id'];
                $sectionType = $section['evaluation_type'] ?? '';
                $sectionColMap[$sid] = $colLetter;

                if (in_array($sectionType, ['formula', 'auto'])) {
                    $row['columns'][$colLetter] = 0;
                } else {
                    $row['columns'][$colLetter] = (float)($userAnswers[$sid] ?? 0);
                }
            }
            $rows[] = $row;
        }

        // حساب التلقائيات
        foreach ($rows as $rIndex => &$row) {
            $colIndex = 0;
            foreach ($template['criteria'] as $criterion) {
                foreach ($criterion['sections'] as $section) {
                    $colIndex++;
                    $sectionType = $section['evaluation_type'] ?? '';
                    if ($sectionType !== 'auto') continue;

                    $config = json_decode($section['formula_config'] ?? '{}', true);
                    $autoSource = $config['auto_source'] ?? '';
                    $colLetter = chr(64 + $colIndex);

                    switch ($autoSource) {
                        case 'actual_force':
                            $row['columns'][$colLetter] = $actualForce;
                            break;
                        case 'total':
                            $total = 0;
                            foreach ($rows as $r) {
                                foreach ($r['columns'] as $k => $v) {
                                    if ($k !== $colLetter && is_numeric($v)) $total += $v;
                                }
                            }
                            $row['columns'][$colLetter] = $total;
                            break;
                        case 'employee_count':
                            $row['columns'][$colLetter] = 0;
                            break;
                    }
                    $computed[$section['id']] = $row['columns'][$colLetter];
                }
            }
        }
        unset($row);

        // حساب الصيغ (3 مرات لدعم الصيغ المعتمدة على صيغ أخرى)
        for ($pass = 0; $pass < 3; $pass++) {
            $rowIndex = 0;
            foreach ($template['criteria'] as $criterion) {
                if (!isset($rows[$rowIndex])) { $rowIndex++; continue; }
                $colIndex = 0;

                foreach ($criterion['sections'] as $section) {
                    $colIndex++;
                    $sectionType = $section['evaluation_type'] ?? '';
                    if ($sectionType !== 'formula') continue;

                    $config = json_decode($section['formula_config'] ?? '{}', true);
                    $formula = $config['formula'] ?? '';
                    $colLetter = chr(64 + $colIndex);

                    $this->currentColumn = $colLetter;
                    $result = $this->evaluate($formula, $rows[$rowIndex], $rows, []);

                    if (is_numeric($result)) {
                        $result = round((float)$result, 4);
                    } else {
                        $result = 0;
                    }

                    $rows[$rowIndex]['columns'][$colLetter] = $result;
                    $computed[$section['id']] = $result;
                }
                $rowIndex++;
            }
        }

        // القيم العادية + المساعدات
        foreach ($template['criteria'] as $criterion) {
            foreach ($criterion['sections'] as $section) {
                $sid = $section['id'];
                $sectionType = $section['evaluation_type'] ?? '';
                if (in_array($sectionType, ['formula', 'auto'])) continue;
                if (!isset($computed[$sid])) {
                    $computed[$sid] = $userAnswers[$sid] ?? '';
                }
            }
        }

        return $computed;
    }
}