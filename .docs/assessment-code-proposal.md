# Assessment Code Feature Proposal

## Feature: Multi-Quiz CSV Import with Assessment Codes

### Overview
Allow instructors to create/update multiple quizzes from a single CSV file using an `assessment_code` identifier.

---

## Current vs Proposed Workflow

### Current (Single Quiz)
1. Navigate to specific quiz
2. Upload CSV → questions added to THAT quiz only

### Proposed (Multi-Quiz)
1. Navigate to "Bulk Import" page
2. Upload CSV with assessment_code column
3. System creates/updates multiple quizzes based on code
4. Preview shows grouped by quiz
---

## CSV Format Extension

### New Column: assessment_code

**Format:**
```csv
assessment_code,quiz_title,module_id,lesson_id,passing_score,time_limit,question_text,question_type,...
MIDTERM_SEM1,Midterm Examination,1,,75,60,What is 2+2?,multiple_choice,1,Two,Three,Four,Five,2,,,,0,
MIDTERM_SEM1,Midterm Examination,1,,75,60,Earth is round.,true_false,1,,,,,0,,,0,
FINAL_SEM1,Final Examination,1,,80,90,Identify this cell.,identification,2,,,,,,nucleus|Nucleus,,,0,cell.jpg
```

### Column Definitions

| Column | Required | Description |
|--------|----------|-------------|
| `assessment_code` | ✅ Yes | Unique identifier for quiz grouping |
| `quiz_title` | ✅ Yes | Quiz display name |
| `module_id` | Optional | Attach to module |
| `lesson_id` | Optional | Attach to lesson |
| `passing_score` | Optional | Default: 75 |
| `time_limit` | Optional | Minutes, blank = unlimited |

**Behavior:**
- Same `assessment_code` → Questions go to same quiz
- Quiz created if it doesn't exist
- Quiz updated if it exists (match by assessment_code)

---

## Implementation Options

### Option A: Separate Bulk Import Page
**Route:** `/instructor/bulk-import`

**Pros:**
- Clear distinction from single-quiz import
- Can show quiz grouping statistics
- Easier to add future bulk features

**Cons:**
- Extra navigation step
- Separate UI to maintain

### Option B: Toggle Mode on Existing Page
**UI:** "Import to This Quiz" / "Bulk Import Multiple Quizzes" toggle

**Pros:**
- Single entry point
- Reuses existing validation/preview logic

**Cons:**
- More complex UI
- Two modes to handle

### Recommendation: **Option A** (Separate Page)

---

## Preview Screen Enhancements

### Grouped Display

```
✓ Quiz: Midterm Examination (MIDTERM_SEM1) - 15 questions
  ├─ ✓ Valid: 12 questions
  └─ ✗ Invalid: 3 questions

✓ Quiz: Final Examination (FINAL_SEM1) - 20 questions
  ├─ ✓ Valid: 18 questions
  └─ ✗ Invalid: 2 questions

Summary:
• 2 quizzes will be created/updated
• 30 valid questions total
• 5 invalid questions (fix errors and re-upload)
```

---

## Additional Features with Assessment Code

### 1. Question Bank System
Store questions without attaching to specific quiz:
```csv
assessment_code: QUESTION_BANK_BIOLOGY
```
Later, randomly select questions for quizzes

### 2. Quiz Duplication
```csv
assessment_code: QUIZ1_COPY
source_code: QUIZ1_ORIGINAL
```
Copy existing quiz with modifications

### 3. Cross-Quiz Analytics
Track performance across quiz families:
- All MIDTERM_* quizzes
- All CH1_* quizzes

### 4. Dynamic Quiz Generation
```csv
assessment_code: RANDOM_EASY_BIOLOGY
selection_criteria: difficulty=easy,topic=biology,count=10
```
Auto-select 10 easy biology questions

---

## Database Changes Required

### Migration: Add assessment_code to quizzes table

```php
Schema::table('quizzes', function (Blueprint $table) {
    $table->string('assessment_code')->nullable()->unique()->after('id');
    $table->index('assessment_code');
});
```

### Why Nullable + Unique?
- Nullable: Existing quizzes created manually don't have codes
- Unique: Prevents duplicate quiz creation
- Index: Fast lookup during CSV import

---

## Import Logic Flow

```
1. Parse CSV → Group rows by assessment_code
2. For each assessment_code:
   a. Check if quiz exists (by assessment_code)
   b. If exists → Load quiz
   c. If not → Create new quiz with metadata from first row
3. For each question in group:
   a. Validate question data
   b. Check if question_text exists in quiz (duplicate handling)
   c. Add to valid/invalid list
4. Preview screen → Show grouping
5. Confirm → Batch insert/update
```

---

## Example Use Cases

### Use Case 1: Semester Question Bank
**Scenario:** Upload 500 questions for entire semester at once

```csv
assessment_code,quiz_title,question_text,...
BIO_CH1,Biology Chapter 1 Quiz,What is a cell?,multiple_choice,...
BIO_CH1,Biology Chapter 1 Quiz,Define mitosis.,identification,...
BIO_CH2,Biology Chapter 2 Quiz,Describe DNA.,fill_blank_text,...
...
BIO_CH10,Biology Chapter 10 Quiz,Explain evolution.,multiple_choice,...
```
**Result:** 10 quizzes created with 50 questions each

### Use Case 2: Question Updates
**Scenario:** Fix typos in multiple quizzes

```csv
assessment_code,question_text (old),question_text (new),...
MIDTERM_2026,What is photosynthisis?,What is photosynthesis?,...
FINAL_2026,Earth is flat.,Earth is round.,...
```
**Matching:** By assessment_code + old question_text

### Use Case 3: Quiz Variations
**Scenario:** Create A/B test versions

```csv
assessment_code,quiz_title,question_text,...
QUIZ1_A,Quiz 1 Version A,Easy question 1,...
QUIZ1_B,Quiz 1 Version B,Hard question 1,...
```
**Result:** Two quiz versions for comparison

---

## Security Considerations

1. **Ownership Validation:** Only import to quizzes owned by instructor
2. **Assessment Code Uniqueness:** Prevent code hijacking
3. **Row Limits:** Cap at 1000 rows per CSV to prevent abuse
4. **Preview Required:** Never skip validation step

---

## UI Mockup

### Bulk Import Page

```
[Breadcrumb: Dashboard > Bulk Import]

┌─────────────────────────────────────────┐
│ 📦 Bulk Quiz Import                     │
│ Create or update multiple quizzes       │
│ from a single CSV file.                 │
│                                         │
│ [Download Bulk Template]                │
│ [Choose CSV File]                       │
│                                         │
│ ℹ️ Template includes assessment_code   │
│    for quiz grouping                    │
└─────────────────────────────────────────┘
```

### Preview Screen Enhancement

```
┌─────────────────────────────────────────┐
│ 📊 Import Preview                       │
│                                         │
│ [✓ 2 Quizzes] [✓ 30 Valid] [✗ 5 Invalid]│
│                                         │
│ ▼ Midterm Examination (MIDTERM_SEM1)   │
│   • Module: Reproductive Health         │
│   • Passing Score: 75% | Time: 60 min  │
│   • 12 valid questions, 3 invalid       │
│   [View Questions ▼]                    │
│                                         │
│ ▼ Final Examination (FINAL_SEM1)       │
│   • Module: Comprehensive Review        │
│   • Passing Score: 80% | Time: 90 min  │
│   • 18 valid questions, 2 invalid       │
│   [View Questions ▼]                    │
│                                         │
│ [← Cancel] [✓ Import Valid Questions]  │
└─────────────────────────────────────────┘
```

---

## Alternative: Maintain Current Single-Quiz Import

If multi-quiz import is too complex, keep current system and use assessment_code for:
- **Categorization only** (metadata field)
- **Search/filter** by code
- **Grouping in reports**

Assessment code becomes an optional tag rather than import driver.

---

## Recommendation

**Phase 1 (Now):** Add assessment_code as optional metadata field
**Phase 2 (Later):** Implement bulk multi-quiz import when instructors request it

**Start simple → Expand based on usage**

---

**Question for Discussion:**
Based on your previous "tech-ad" CSV experience:
1. Was assessment_code used for batch quiz creation?
2. What other columns did it have?
3. What problems did it solve for your instructors?

This will help finalize the exact implementation approach!
