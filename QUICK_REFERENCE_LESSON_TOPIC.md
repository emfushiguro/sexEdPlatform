# Quick Reference: New Lesson & Topic Workflow

## 🎯 Quick Start Guide

### Creating Content (Step-by-Step)

#### 1. Create a Module
```
Admin Dashboard → Modules → Create New Module

Required Fields:
- Title
- Description  
- Thumbnail (optional)
- Age Bracket (Kids/Teens/Adults)

Publishing:
☑️ Publish immediately (or leave unchecked for draft)

Duration: Auto-calculated ✨
```

#### 2. Create a Lesson (Container)
```
Modules → [Select Module] → Lessons → Create New Lesson

Required Fields:
- Module (dropdown)
- Title
- Description

Auto-Generated:
✨ Duration (0, will calculate from topics)
✨ Order (auto-numbered)

Click "Create Lesson" → Redirects to Lesson Overview
```

#### 3. Add Topics to Lesson
```
Lesson Overview → Click "Add Topic" Button

Step 1: Basic Info
- Title (e.g., "Introduction Video")
- Duration (e.g., 10 minutes)
- ☐ Prerequisite (check if required)

Step 2: Select Topic Type
Choose one:
📹 Video
📝 Text
📄 Worksheet
📊 Quiz
🎮 Interactive

Step 3: Fill Content (based on type selected)

Click "Create Topic" → Topic added, durations auto-update ✨
```

---

## 📋 Topic Types Quick Reference

### 📹 Video Topic
**When to use:** Video lessons, demonstrations, recorded lectures

**Content Fields:**
- Video Source: "Embed URL" or "Upload File"
  
**If URL:**
- Video URL (YouTube/Vimeo)
- Example: `https://www.youtube.com/watch?v=VIDEO_ID`

**If Upload:**
- Video File (MP4/WebM, max 100MB)

---

### 📝 Text Topic
**When to use:** Reading materials, explanations, articles

**Content Fields:**
- Rich Text Editor (TinyMCE)
  - Bold, italic, underline
  - Lists, headings
  - Links, formatting
- Images (optional, max 5)
  - Upload images
  - Add captions
  - Display Mode:
    - ○ None (inline)
    - ○ Gallery (grid)
    - ○ Slideshow (with transitions)

**Slideshow Options:**
- Transition: Fade / Slide / Zoom

---

### 📄 Worksheet Topic
**When to use:** Downloadable activities, printable exercises

**Content Fields:**
- Worksheet File (PDF/DOC/DOCX, max 10MB)
- Instructions (how to complete the worksheet)

---

### 📊 Quiz Topic
**When to use:** Assessments, knowledge checks

**Content Fields:**
- Quiz Selection (dropdown of existing quizzes)

**Note:** Create quizzes separately in Quiz Management first!

---

### 🎮 Interactive Topic
**When to use:** Activities, simulations, exercises

**Content Fields:**
- Activity Type: Activity / Simulation / Exercise
- Instructions (how to interact)

---

## ⚡ Quick Actions Reference

### Lesson Overview Page
```
[Lesson Title]
├─ Details Card
│  └─ Edit Lesson (button)
└─ Topics Card
   ├─ + Add Topic (green button)
   └─ Topics Table
      ├─ Edit (per topic)
      └─ Delete (per topic)
```

### Navigation Shortcuts
```
Dashboard
 └─ Modules
     └─ [Module Title]
         └─ Lessons Tab
             └─ [Lesson Title]
                 └─ Topics List
                     ├─ Add Topic
                     ├─ Edit Topic
                     └─ Delete Topic
```

---

## 🔢 Auto-Calculation Examples

### Example 1: Empty Lesson
```
Create Lesson "Understanding Puberty"
→ Duration: 0 minutes

Add Topic: "Introduction Video" (5 min)
→ Lesson Duration: 5 minutes

Add Topic: "Reading: Body Changes" (10 min)
→ Lesson Duration: 15 minutes

Add Topic: "Quiz: Test Your Knowledge" (5 min)
→ Lesson Duration: 20 minutes

Delete Topic: "Introduction Video"
→ Lesson Duration: 15 minutes ✨
```

### Example 2: Module Duration
```
Module "Sexual Health Basics"
└─ Lesson 1: "Anatomy" (20 min)
└─ Lesson 2: "Puberty" (15 min)
└─ Lesson 3: "Hygiene" (10 min)

→ Module Duration: 45 minutes ✨
```

---

## ✅ Best Practices

### Lesson Titles
✅ **Good:**
- "Understanding Your Body"
- "Healthy Relationships"
- "Consent and Boundaries"

❌ **Avoid:**
- "Lesson 1" (not descriptive)
- "Video Lesson [25 min]" (duration is auto-calculated)

### Topic Titles
✅ **Good:**
- "Introduction: What is Puberty?"
- "Video: Body Changes Explained"
- "Activity: Identifying Emotions"

❌ **Avoid:**
- "Topic 1" (not descriptive)
- Just "Video" (specify what video is about)

### Duration Guidelines
- **Video Topics:** Actual video length
- **Text Topics:** ~1 minute per 200 words
- **Worksheet Topics:** Estimated completion time
- **Quiz Topics:** ~1 minute per question
- **Interactive Topics:** Estimated activity time

### Topic Order (Automatic)
```
Topics are automatically ordered:
1. First topic created → Order 1
2. Second topic created → Order 2
3. Third topic created → Order 3
...

Future: Drag-and-drop reordering (planned)
```

---

## 🎨 UI Elements Reference

### Status Badges (Topics Table)

**Type:**
- 🔴 Video (red badge)
- 🔵 Text (blue badge)
- 🟢 Worksheet (green badge)
- 🟣 Quiz (purple badge)
- 🟠 Interactive (orange badge)

**Prerequisite:**
- 🟡 Required (yellow badge)
- ⚪ Optional (gray badge)

### Buttons
- 🟢 **Green**: Add/Create actions
- 🔵 **Blue**: Edit/Update actions
- 🔴 **Red**: Delete/Remove actions
- ⚪ **Gray**: Cancel/Back actions

---

## 🚨 Common Issues & Solutions

### Issue: "Duration showing 0"
**Cause:** No topics added yet
**Solution:** Add topics to the lesson, duration will auto-calculate

### Issue: "Can't find Add Topic button"
**Cause:** On lesson index page instead of lesson show page
**Solution:** Click on lesson title to go to lesson overview

### Issue: "Video not playing"
**Cause:** Invalid YouTube/Vimeo URL
**Solution:** 
- Use full URL (e.g., `https://www.youtube.com/watch?v=VIDEO_ID`)
- Or use upload option for local videos

### Issue: "Images not displaying"
**Cause:** File too large or invalid format
**Solution:**
- Max 2MB per image
- Use JPG, PNG, or GIF
- Compress images before upload

### Issue: "Topic order wrong"
**Cause:** Topics created in wrong order
**Solution:**
- Currently: Delete and recreate in correct order
- Future: Use drag-and-drop reordering (planned)

---

## 📞 Support Checklist

**Before asking for help:**
1. ✅ Clear browser cache
2. ✅ Check file sizes (videos < 100MB, images < 2MB)
3. ✅ Verify file formats (MP4 for videos, PDF/DOC for worksheets)
4. ✅ Check internet connection (for URL embedding)
5. ✅ Try in different browser

**Include in bug report:**
- [ ] What were you trying to do?
- [ ] What happened instead?
- [ ] Screenshots/error messages
- [ ] Topic type (video/text/worksheet/quiz/interactive)
- [ ] File size/format (if uploading)

---

## 🎓 Content Creator Tips

### Planning Your Module
```
1. List all topics you want to cover
2. Group related topics into lessons
3. Decide content type for each topic:
   - Video for demonstrations
   - Text for detailed explanations
   - Worksheets for practice
   - Quizzes for assessment
   - Interactive for engagement

4. Estimate durations
5. Order lessons logically
```

### Mixing Content Types
**Example Lesson: "Understanding Consent"**
1. 📹 Video: "What is Consent?" (5 min)
2. 📝 Text: "Types of Consent" (10 min reading)
3. 🎮 Interactive: "Scenario Analysis" (8 min)
4. 📄 Worksheet: "Reflection Questions" (12 min)
5. 📊 Quiz: "Consent Knowledge Check" (5 min)

**Total:** 40 minutes (auto-calculated!)

### Engagement Strategy
- Start with video (hook attention)
- Follow with text (provide depth)
- Add interactive (reinforce learning)
- End with quiz (assess understanding)
- Optional worksheet (extra practice)

---

## 🔮 Coming Soon

### Planned Features:
- 🎯 Drag-and-drop topic reordering
- 📋 Bulk topic actions
- 📱 Topic preview (learner view)
- 📊 Progress tracking per topic
- 🎨 Topic templates
- 📁 Content library (reusable topics)

---

**Last Updated:** February 3, 2026
**Version:** 1.0
**Need Help?** See full documentation in `LESSON_TOPIC_RESTRUCTURING_SUMMARY.md`
