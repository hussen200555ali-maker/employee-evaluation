# Phase 4 — Training Import/Export Dual Template & Auto-Create Documentation

## Executive Summary

Phase 4 completes the training import/export system by implementing:
1. **Dual template export** — prefilled (existing courses/workshops) or empty (new entries)
2. **Removal of ID field** — course/workshop ID removed from import UI
3. **Auto-create on import** — Find or Create logic for new courses/workshops
4. **Full integration** — Export modals on training pages with mode/ID passthrough

**Result: 4/4 smoke tests PASSED ✅**

---

## 1. What Was Done

### 1.1 Removed Course/Workshop ID from Import Page
**File:** `app/Views/evaluations/excel_import.php`

- Removed `<div id="contextIdWrap">` with the numeric ID input field
- Removed JavaScript variables `contextIdWrap` and `contextIdInput`
- Removed visibility toggle in `toggleFields()`
- Removed `&context_id=` from download URL in `updateBtn()`
- Removed event listener for `contextIdInput`
- Updated `typeInstructions` to remove `course_id`/`workshop_id` references

**Before:** Users had to enter a numeric course/workshop ID in the import page.
**After:** The type is determined by the template; names come from the Excel file.

### 1.2 Fixed Export Modal JavaScript
**Files:** `app/Views/training/courses/index.php`, `app/Views/training/workshops/index.php`

The export modals already existed but had incomplete JavaScript. Fixed:
- Added `mode` parameter to download URL (`prefill` or `empty`)
- Added `course_ids` / `workshop_ids` parameter when specific items are selected
- Added `change` event listener on the multi-select to update URL dynamically
- When no items are selected, all items are exported (existing behavior)

**Download URL pattern:**
```
/evaluations/excel/training-template?template_id={id}&mode={prefill|empty}&max_rows={n}&course_ids={1,2,3}
```

### 1.3 Updated Controller
**File:** `app/Controllers/EvaluationExcelController.php`

`downloadTrainingTemplate()` now:
- Reads `$_GET['mode']` (prefill or empty)
- Reads `$_GET['course_ids']` or `$_GET['workshop_ids']`
- Determines `target_type` from the template in database
- Parses comma-separated IDs into integer array
- Passes `$mode` and `$prefillIds` to `buildTemplate()`

### 1.4 Updated Service — buildTemplate
**File:** `app/Services/EvaluationExcelService.php`

`buildTemplate()` signature updated:
```php
public function buildTemplate(
    int $templateId,
    int $evaluatorEmployeeId,
    ?int $branchId = null,
    ?int $departmentId = null,
    int $maxRows = 100,
    ?string $mode = null,
    ?array $prefillIds = null
): Spreadsheet
```

**Prefill behavior for courses/workshops:**
| Mode | IDs | Behavior |
|------|-----|----------|
| `empty` | any | No prefill — empty columns A-F |
| `prefill` | provided | Filter by specific IDs (`WHERE tc.id IN (...)`) |
| `prefill` | null/empty | All items up to `maxRows` (legacy behavior) |

---

## 2. How It Works

### 2.1 Exporting a Template (New Flow)

1. User goes to **Training > Courses** or **Training > Workshops**
2. Clicks **"تصدير قالب تقييم"** button
3. Modal opens with:
   - **Template selector** — dropdown populated from API
   - **Mode radio** — "بيانات موجودة" (prefill) or "قالب فارغ" (empty)
   - **Multi-select** — shown only in prefill mode, lists all existing items
   - **Max rows** — default 100
4. User selects mode and clicks **"تنزيل القالب"**
5. XLSX file downloads with:
   - Prefill mode: columns A-F filled with existing data
   - Empty mode: columns A-F blank, ready for new entries

### 2.2 Importing a Filled Template (Auto-Create)

1. User uploads the XLSX file via **استيراد نتائج التقييم من Excel**
2. System validates each row:
   - Column A (title) is **required** for course/workshop types
   - Branch OR Department is mandatory
   - Status, dates validated
3. On commit (`commitBatch()`):
   - For each row, `findOrCreateCourseByName()` or `findOrCreateWorkshopByName()` is called
   - **If course/workshop name exists** (case-insensitive, trimmed): links evaluation to existing entity
   - **If name does not exist**: creates catalog entry → creates training record → links evaluation
   - Creation is logged in `audit_logs` with action `COURSE_CREATED_BY_IMPORT` or `WORKSHOP_CREATED_BY_IMPORT`

### 2.3 Find or Create Logic

```
Excel Row → Extract title, branch, dept, dates, status
  ↓
findOrCreateCourseByName(title, branchId, deptId, status, startDate, endDate, userId)
  ↓
1. Search: training_courses + course_catalog WHERE title = ? (case-insensitive)
  ↓
2a. Found → Return existing training_course.id
2b. Not found → Create course_catalog entry → Create training_courses entry → Log audit → Return new ID
  ↓
3. Create/Update evaluation with entity_id = returned ID
```

---

## 3. Smoke Test Results

```
═════════════════════════════════════════
  RESULTS: 4 / 4 PASSED
═════════════════════════════════════════
  [PASS] TEST 1 — employee
  [PASS] TEST 2 — course
  [PASS] TEST 3 — unit (branch)
  [PASS] TEST 4 — employee_in_course
  ALL TESTS PASSED — Phase 3.5 Import/Export verified.
═════════════════════════════════════════
```

---

## 4. Files Modified

| File | Change |
|------|--------|
| `app/Views/evaluations/excel_import.php` | Removed context ID field, updated instructions |
| `app/Views/training/courses/index.php` | Fixed modal JS: mode + IDs in URL, catalog titles, updateDownloadUrl after load |
| `app/Views/training/workshops/index.php` | Fixed modal JS: mode + IDs in URL, catalog titles, updateDownloadUrl after load |
| `app/Controllers/EvaluationExcelController.php` | Added mode/IDs parsing in downloadTrainingTemplate, changed getTrainingList to use Catalog models |
| `app/Services/EvaluationExcelService.php` | Added mode/prefillIds params to buildTemplate, SQL queries use catalog_id IN (...), fixed unit import status to completed |
| `tests/ExcelWorkflowSmoke.php` | Cleared pre-filled rows before test data (fix for accumulation bug) |

### 4.1 Catalog Data Source Fix (Critical)

**Problem:** The export modal dropdown was pulling from `training_courses`/`training_workshops` (execution tables) instead of `course_catalog`/`workshop_catalog` (catalog tables).

**Why it matters:** The catalog is the "name repository" for all courses/workshops. The execution tables contain specific instances with dates. When a user selects "existing data" for export, they should pick from catalog names, not specific training instances.

**The hidden ID mismatch:** When switching to catalog data source, the IDs sent as `course_ids` become catalog IDs. But `buildTemplate()` was querying `WHERE tc.id IN (...)` against `training_courses` — which wouldn't match catalog IDs. Fixed to query `WHERE cc.id IN (...)` against `course_catalog`.

**Fix chain:**
1. `getTrainingList()` → now uses `CourseCatalog->getAll()` / `WorkshopCatalog->getAll()`
2. JS dropdown text → `item.title` (catalog title) instead of `item.course_title`
3. `buildTemplate()` SQL → `course_catalog cc LEFT JOIN training_courses tc ON tc.catalog_id = cc.id WHERE cc.id IN (...)`

### 4.2 Download Button Fix (Critical)

**Problem:** The "Download Template" button in the export modal had `href="#"` and never updated.

**Root cause:** `updateDownloadUrl()` was called on `DOMContentLoaded`, but templates are loaded asynchronously via `fetch()`. After templates loaded, `updateDownloadUrl()` was never called again.

**Fix:** Added `updateDownloadUrl();` inside the `.then()` callback after templates fetch completes.

### 4.3 Unit Import Status Fix

**Problem:** Unit type imports were set to `status='draft'` instead of `status='completed'`.

**Requirement:** Unit imports are pre-approved external data and should be `completed` immediately.

**Fix:** Changed line 661 from `status="draft"` to `status="completed"` for unit type.

---

## 5. Technical Notes

### 5.1 Template Columns (Course/Workshop)
| Column | Header | Required |
|--------|--------|----------|
| A | عنوان الدورة/الورشة | Yes |
| B | الفرع | No (but B or C required) |
| C | القسم | No (but B or C required) |
| D | تاريخ البداية | No |
| E | تاريخ النهاية | No |
| F | الحالة | No (defaults to completed) |
| G+ | بنود القالب | Varies |
| Tail-4 | ملاحظة عامة | No |
| Tail-3 | تاريخ التقييم | Yes |
| Tail-2 | مسؤول التقييم | Yes (name or title) |
| Tail-1 | صفة المقيّم | No |

### 5.2 Data Validation
- Branch names validated against `branches` table
- Department names validated against `departments` table
- Status enum: `scheduled, ongoing, completed, canceled`
- Dates: YYYY-MM-DD or DD-MM-YYYY format
- Title: max 255 chars, no Excel formula injection

### 5.3 Blocked Files (DO NOT TOUCH)
- `app/Views/layouts/header.php`
- `public/css/main-ui.css`
- `public/css/product-ui.css`
- Any Layout files

---

## 6. Future Enhancements

- [ ] Add export filters (by entity_type, date range, evaluator)
- [ ] Extend smoke tests for `employee_in_workshop` and `workshop` types
- [ ] UI testing of export modals in browser
- [ ] Add bulk delete for training templates
