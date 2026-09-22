import re
path = r"C:\xampp\htdocs\employee-evaluation\app\Services\EvaluationExcelService.php"
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

# Patch validateSingleRow to handle 6 types
old_validate = """    private function validateSingleRow(array &$payload, int $evaluatorEmployeeId, array $seenEmpCodes, array $seenNames, array $branchMap, array $departmentMap, array $sections): array
    {
        $errors=[];
        $empCode=trim((string)($payload['emp_code']??''));
        $fullName=trim((string)($payload['full_name']??''));
        $nickname=trim((string)($payload['nickname']??''));
        $branchName=trim((string)($payload['branch_name']??''));
        $departmentName=trim((string)($payload['department_name']??''));"""

new_validate = """    private function validateSingleRow(array &$payload, int $evaluatorEmployeeId, array $seenEmpCodes, array $seenNames, array $branchMap, array $departmentMap, array $sections): array
    {
        $errors=[];
        $targetType = $payload['target_type'] ?? ($payload['template_id'] ? $this->getTemplateTargetType((int)$payload['template_id']) : 'employee');
        $payload['target_type'] = $targetType;
        if (in_array($targetType, ['course','workshop'], true)) {
            $cid = trim((string)($payload['course_id'] ?? $payload['workshop_id'] ?? $payload['emp_code'] ?? ''));
            if ($cid === '' || !ctype_digit($cid)) { $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'معرف الدورة/الورشة مطلوب ورقمي.','column'=>'A']; }
            else {
                $exists = $targetType==='course' ? $this->findCourseById((int)$cid) : $this->findWorkshopById((int)$cid);
                if (!$exists) $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'الدورة/الورشة غير موجودة: '.$cid,'column'=>'A'];
            }
            // skip employee checks for course/workshop entity
        } elseif ($targetType === 'unit') {
            $ut = trim((string)($payload['unit_type'] ?? ''));
            $uc = trim((string)($payload['unit_code'] ?? ''));
            $period = trim((string)($payload['period'] ?? ''));
            if (!in_array($ut, ['branch','department'], true)) $errors[]=['code'=>'UNIT_TYPE_INVALID','message'=>'نوع الوحدة يجب أن يكون branch أو department','column'=>'A'];
            if ($uc === '') $errors[]=['code'=>'UNIT_NOT_FOUND','message'=>'رمز الوحدة (اسم الفرع/القسم) مطلوب','column'=>'B'];
            else {
                $found = $ut==='branch' ? ($this->getBranchMap()[$uc] ?? null) : ($this->getDepartmentMap()[$uc] ?? null);
                if (!$found) $errors[]=['code'=>'UNIT_NOT_FOUND','message'=>'الوحدة غير موجودة: '.$uc,'column'=>'B'];
            }
            if ($period === '') $errors[]=['code'=>'SCHEDULE_MISMATCH','message'=>'الفترة (YYYY-MM أو schedule_id) مطلوبة للوحدة','column'=>'C'];
            else {
                // try schedule lookup
                if (ctype_digit($period)) {
                    $sched = $this->findScheduleById((int)$period);
                    if (!$sched) $errors[]=['code'=>'SCHEDULE_MISMATCH','message'=>'الجدولة غير موجودة: '.$period,'column'=>'C'];
                }
            }
        } elseif (in_array($targetType, ['employee_in_course','employee_in_workshop'], true)) {
            $ctxId = trim((string)($payload['course_id'] ?? $payload['workshop_id'] ?? ''));
            if ($ctxId === '' || !ctype_digit($ctxId)) $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'معرف السياق (الدورة/الورشة) مطلوب','column'=>'C'];
            else {
                $exists = $targetType==='employee_in_course' ? $this->findCourseById((int)$ctxId) : $this->findWorkshopById((int)$ctxId);
                if (!$exists) $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'السياق غير موجود: '.$ctxId,'column'=>'C'];
            }
            $empCode=trim((string)($payload['emp_code']??''));
            $fullName=trim((string)($payload['full_name']??''));
            if($empCode==='' && $fullName===''){ $errors[]=['code'=>'EMP_OR_NAME_REQUIRED','message'=>'الرقم أو الاسم مطلوب','column'=>'A']; }
        } else {
            $empCode=trim((string)($payload['emp_code']??''));
            $fullName=trim((string)($payload['full_name']??''));
            if($empCode==='' && $fullName===''){ $errors[]=['code'=>'EMP_OR_NAME_REQUIRED','message'=>'يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل في الصف '.($payload['excel_row']??'?').'.','column'=>'A']; }
        }
        // Common checks for employee types
        $empCode=trim((string)($payload['emp_code']??''));
        $fullName=trim((string)($payload['full_name']??''));
        $nickname=trim((string)($payload['nickname']??''));
        $branchName=trim((string)($payload['branch_name']??''));
        $departmentName=trim((string)($payload['department_name']??''));"""

if old_validate in c:
    c = c.replace(old_validate, new_validate)
    print("patched validate header")
else:
    print("validate header not found")

# Also need to add helper methods at end before isValueValid if not exist
if "function getTemplateTargetType" not in c:
    c = c.replace("    private function isValueValid", """    private function getTemplateTargetType(int $tid): string {
        $q=$this->database->getPdo()->prepare('SELECT target_type FROM form_templates WHERE id=:id');
        $q->execute(['id'=>$tid]);
        $r=$q->fetch(PDO::FETCH_ASSOC);
        return $r['target_type'] ?? 'employee';
    }
    private function findCourseById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM training_courses WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function findWorkshopById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM training_workshops WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function findScheduleById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM evaluation_schedules WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function isValueValid""")
    print("added helpers")
else:
    print("helpers exist")

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)
print("done")
