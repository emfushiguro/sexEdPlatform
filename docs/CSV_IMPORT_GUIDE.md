# Quiz CSV Import Guide

## Overview
The CSV import feature allows instructors to create multiple quiz questions at once by uploading a properly formatted CSV file.

---

## CSV Template Structure

### Column Definitions

| Column Name | Required | Description | Examples |
|-------------|----------|-------------|----------|
| `question_text` | ✅ Yes | The question text. Use `_____` (5 underscores) for blanks | `What is 2 + 2?` |
| `question_type` | ✅ Yes | Type of question | `multiple_choice`, `true_false`, `multiple_select`, `fill_blank_text`, `fill_blank_select`, `identification` |
| `points` | ✅ Yes | Points awarded for correct answer | `1`, `2`, `5` |
| `option_a` | Conditional | First option (required for MC/MS, not for TF) | `Apple` |
| `option_b` | Conditional | Second option (required for MC/MS, not for TF) | `Banana` |
| `option_c` | Optional | Third option | `Carrot` |
| `option_d` | Optional | Fourth option | `Date` |
| `correct_answer` | Conditional | Correct answer index or value | `0` (for option A), `0,2` (for MS) |
| `acceptable_answers` | Conditional | Accepted answers for text-based questions | `blue\|Blue`, `grass;sky` |
| `word_bank` | Conditional | Words to select from (fill_blank_select) | `grass,sky,sun,moon` |
| `case_sensitive` | Optional | Whether answer is case-sensitive (0 or 1) | `0` (no), `1` (yes) |
| `image_filename` | Optional | Image filename from Image Library | `plant_leaf.jpg` |

---

## Question Types Guide

### 1. Multiple Choice (`multiple_choice`)
**Required Fields:** question_text, question_type, points, option_a, option_b, correct_answer

**correct_answer format:** Index of correct option (0=A, 1=B, 2=C, 3=D)

```csv
What is 2 + 2?,multiple_choice,1,Two,Three,Four,Five,2,,,,0,
```
**Result:** 4 options shown, "Four" (index 2) is correct

---

### 2. True/False (`true_false`)
**Required Fields:** question_text, question_type, points, correct_answer

**correct_answer format:** `0` for True, `1` for False

**⚠️ IMPORTANT:** Leave option_a through option_d **empty** - they are auto-generated!

```csv
Earth orbits the Sun.,true_false,1,,,,,0,,,0,
```
**Result:** Two options "True" and "False" generated automatically, "True" is correct

---

### 3. Multiple Select (`multiple_select`)
**Required Fields:** question_text, question_type, points, option_a, option_b, correct_answer

**correct_answer format:** Comma-separated indices (0=A, 1=B, 2=C, 3=D)

```csv
Which are fruits?,multiple_select,2,Apple,Carrot,Banana,Potato,0,2,,,0,
```
**Result:** Apple (0) and Banana (2) are correct answers

---

### 4. Fill in the Blank - Text (`fill_blank_text`)
**Required Fields:** question_text (with `_____`), question_type, points, acceptable_answers

**acceptable_answers format:**
- **Single blank with alternatives:** Use `|` (pipe) to separate acceptable variations
- **Multiple blanks:** Use `;` (semicolon) to separate answers for each blank

#### Single Blank Examples:
```csv
The sky is _____.,fill_blank_text,1,,,,,,blue|Blue,,0,
```
**Result:** Accepts "blue" OR "Blue" (case-insensitive)

```csv
The capital of France is _____.,fill_blank_text,1,,,,,,Paris,,1,
```
**Result:** Only accepts "Paris" (case-sensitive)

#### Multiple Blanks Examples:
```csv
The _____ is green and the _____ is blue.,fill_blank_text,2,,,,,,grass|Grass;sky|Sky,,0,
```
**Result:** 
- Blank 1: Accepts "grass" OR "Grass"
- Blank 2: Accepts "sky" OR "Sky"

---

### 5. Fill in the Blank - Select (`fill_blank_select`)
**Required Fields:** question_text (with `_____`), question_type, points, acceptable_answers, word_bank

**acceptable_answers format:** Use `;` (semicolon) to separate answers for multiple blanks

**word_bank format:** Comma-separated list of words to choose from

```csv
The _____ is green and the _____ is blue.,fill_blank_select,2,,,,,,grass;sky,"grass,sky,sun,moon,cloud",0,
```
**Result:** 
- Students select from: grass, sky, sun, moon, cloud
- Blank 1 expects "grass"
- Blank 2 expects "sky"

---

### 6. Identification (`identification`)
**Required Fields:** question_text, question_type, points, acceptable_answers

**Optional:** image_filename (upload to Image Library first)

**acceptable_answers format:** Pipe-separated alternatives

```csv
Identify this plant part.,identification,2,,,,,,leaf|leaves,,,0,plant_leaf.jpg
```
**Result:** Shows image, accepts "leaf" OR "leaves"

---

## Special Characters & Format Rules

### Blanks
- Use exactly **5 underscores:** `_____`
- Number of blanks must match number of answer groups

### Separators
- **Pipe `|`** - Separates alternative answers for the SAME blank
  - Example: `blue|Blue|BLUE` accepts any capitalization
- **Semicolon `;`** - Separates answers for DIFFERENT blanks
  - Example: `red;blue` for 2 blanks
- **Comma `,`** - Separates word bank items or multiple select indices
  - Example: `apple,banana,carrot`

### Case Sensitivity
- `0` = Case-insensitive (default) - "Blue", "blue", "BLUE" all accepted
- `1` = Case-sensitive - Only exact match accepted

---

## Image Upload Workflow

### Step 1: Upload Images First
1. Go to **Image Library** (link on quiz page)
2. Upload images (JPG, PNG, max 2MB)
3. Note that the system adds timestamps (e.g., `1771746300_plant_leaf.jpg`)

### Step 2: Reference in CSV
You can use **either**:
- Simple filename: `plant_leaf.jpg`
- Full timestamped name: `1771746300_plant_leaf.jpg`

The system automatically finds images with or without timestamp prefixes!

---

## Common Errors & Solutions

### ❌ "Number of blanks must match number of answers"
**Problem:** Mismatch between `_____` count and answer groups

**Example:**
```csv
The _____ is _____ and warm.,fill_blank_text,1,,,,,,blue,,0,
```
❌ 2 blanks but only 1 answer

**Solution:**
```csv
The _____ is _____ and warm.,fill_blank_text,1,,,,,,sky;blue,,0,
```
✅ 2 blanks with 2 answers (separated by semicolon)

---

### ❌ "Options A and B are required"
**Problem:** Multiple choice/select needs at least 2 options

**Solution:** Provide at least option_a and option_b

---

### ❌ "Image file not found in image library"
**Problem:** Image filename doesn't exist in storage

**Solution:** 
1. Upload image to Image Library first
2. Use "Copy Name" button to get exact filename
3. Paste into CSV

---

### ❌ True/False validation error
**Problem:** Provided options for true/false question

**Solution:** Leave option columns EMPTY for true_false type:
```csv
Earth is round.,true_false,1,,,,,0,,,0,
```

---

## Import Process

### Step 1: Download Template
- Click "Download Template" button
- Opens CSV with example questions for all 6 types
- Use as reference or starting point

### Step 2: Upload CSV
- Click "Choose CSV File"
- Select your formatted CSV
- System validates each row automatically

### Step 3: Preview & Fix Errors
- **Green section:** Valid questions ready to import
- **Red section:** Invalid questions with specific error messages
- Fix errors in your CSV and re-upload

### Step 4: Confirm Import
- Review valid questions count
- Click "Confirm Import"
- Questions are created/updated in database

---

## Duplicate Handling

**Matching Logic:** Questions are matched by `question_text` within the same quiz

**Behavior:**
- ✅ If question text already exists → **Updates** the existing question
- ✅ If question text is new → **Creates** a new question

**Use Case:** Update quiz questions without re-creating quiz

---

## Tips & Best Practices

### ✅ DO:
- Download and study the template first
- Test with 2-3 questions before bulk import
- Upload images before creating CSV
- Use descriptive question text
- Double-check blank count matches answers
- Use pipe `|` for alternative answers (improves student success)

### ❌ DON'T:
- Mix up separators (pipe vs semicolon)
- Use less/more than 5 underscores for blanks
- Forget to upload images before CSV import
- Leave required fields empty
- Use special characters that break CSV format

---

## Excel Tips

### Using Excel for CSV Creation

1. **Data Validation for question_type:**
   - Select column B → Data → Validation
   - Allow: List
   - Source: `multiple_choice,true_false,multiple_select,fill_blank_text,fill_blank_select,identification`

2. **Conditional Formatting:**
   - Highlight cells where blanks don't match answers
   - Color-code by question type

3. **Formulas:**
   - Count blanks: `=LEN(A2)-LEN(SUBSTITUTE(A2,"_____",""))/5`
   - Count answer groups: `=LEN(H2)-LEN(SUBSTITUTE(H2,";",""))+1`

4. **Save as CSV:**
   - File → Save As → CSV UTF-8 (Comma delimited)

---

## Quick Reference Chart

| Question Type | Needs Options? | Needs Answers? | Needs Word Bank? | Can Have Image? |
|---------------|----------------|----------------|------------------|-----------------|
| Multiple Choice | ✅ Yes (2-4) | ✅ Yes (index) | ❌ No | ❌ No |
| True/False | ❌ No (auto) | ✅ Yes (0/1) | ❌ No | ❌ No |
| Multiple Select | ✅ Yes (2-4) | ✅ Yes (indices) | ❌ No | ❌ No |
| Fill Blank Text | ❌ No | ✅ Yes (text) | ❌ No | ❌ No |
| Fill Blank Select | ❌ No | ✅ Yes (text) | ✅ Yes | ❌ No |
| Identification | ❌ No | ✅ Yes (text) | ❌ No | ✅ Optional |

---

## Example: Complete Quiz CSV

Download the template from the platform for a working example with all 6 question types!

---

## Support

If you encounter issues:
1. Check the Preview screen for specific error messages
2. Verify CSV format matches this guide
3. Test with template examples first
4. Contact technical support with the error message

---

**Last Updated:** February 22, 2026  
**Version:** 1.0
