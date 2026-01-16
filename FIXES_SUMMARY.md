# Language Assessment Platform - Fixes Summary

## Overview
This document summarizes all fixes applied to resolve routing, database, session, and AI integration issues in the Language Level Assessment Platform.

---

## Files Changed

### 1. **includes/base_path.php** (NEW)
- **Purpose**: Dynamic BASE_PATH detection for case-insensitive Windows XAMPP
- **Changes**: Detects project folder from `SCRIPT_NAME` and builds paths accordingly
- **Why**: Fixes redirect issues when folder name differs (Seng321 vs seng321)

### 2. **includes/auth_guard.php**
- **Fixed**: Session warning by checking `session_status()` before `session_start()`
- **Fixed**: Redirect path to use dynamic BASE_PATH pointing to `/login_part/index.php`
- **Changes**: 
  - Added `require_once __DIR__ . '/base_path.php'`
  - Changed redirect from hardcoded `/Seng321/includes/login.php` to dynamic path

### 3. **config/db.php**
- **Fixed**: Port changed from 3307 to 3306 (standard XAMPP)
- **Added**: Environment variable support (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
- **Added**: Context-aware error handling (JSON for API, HTML for pages)
- **Changes**:
  - Reads from `.env` file via `includes/env.php`
  - Defaults to XAMPP standard: host=127.0.0.1, port=3306, db=language_platform, user=root, pass=""

### 4. **includes/mock_data.php**
- **Fixed**: Removed references to non-existent `ai_test_results` table
- **Updated**: `addTestResult()` now uses `assessments` + `assessment_results` tables
- **Updated**: `getTestResults()` uses proper error handling and PDO connection
- **Changes**:
  - Creates assessment record, then assessment_result record
  - Properly calculates score_percent, correct_count, wrong_count

### 5. **includes/ai_service.php** (COMPLETE REWRITE)
- **Added**: Session-based caching (10 min for questions, 1 hour for evaluations)
- **Added**: File-based caching (optional, persistent across sessions)
- **Added**: Throttling (2-second minimum between API calls)
- **Added**: `fetchAIWritingEvaluation()` function with CEFR/IELTS/TOEFL mapping
- **Added**: AI_MODE support (mock/live) with deterministic fallbacks
- **Fixed**: Reading passage validation (ensures non-empty)
- **Fixed**: JSON parsing with robust error handling
- **Changes**:
  - All functions now use caching to reduce API quota usage
  - Writing evaluation returns structured JSON with diagnostic, strengths, improvements
  - Fallback mode is deterministic (not random) for stable testing

### 6. **pages/api/start_attempt.php**
- **Fixed**: Error display disabled, JSON-only output
- **Fixed**: Proper try/catch error handling
- **Changes**: Added error logging, JSON error responses

### 7. **pages/api/save_progress.php**
- **Fixed**: Error display disabled, JSON-only output
- **Fixed**: Proper try/catch error handling
- **Changes**: Added error logging, JSON error responses

### 8. **pages/api/submit_attempt.php**
- **Fixed**: Error display disabled, JSON-only output
- **Fixed**: Proper try/catch error handling
- **Changes**: Added error logging, JSON error responses

### 9. **pages/api/evaluate_attempt.php**
- **Fixed**: Now calls `fetchAIWritingEvaluation()` from ai_service.php
- **Fixed**: Reads essay text from attempt answers if not provided
- **Fixed**: Saves evaluation to `ai_results` table
- **Fixed**: Error display disabled, JSON-only output
- **Changes**: 
  - Integrates with AI service for real evaluation
  - Returns structured evaluation with CEFR, IELTS, TOEFL estimates

### 10. **pages/grammar.php**
- **Added**: 30-minute timer with auto-submit on expiry
- **Fixed**: DB storage using `addTestResult()` with proper structure
- **Changes**:
  - Timer display and JavaScript countdown
  - Auto-submits when time expires
  - Saves results to assessments + assessment_results tables

### 11. **pages/vocabulary.php**
- **Added**: 25-minute timer with auto-submit on expiry
- **Fixed**: DB storage using `addTestResult()` with proper structure
- **Changes**:
  - Timer display and JavaScript countdown
  - Auto-submits when time expires
  - Saves results to assessments + assessment_results tables

### 12. **pages/reading.php**
- **Status**: Already correct (passage display, dual test flow, 15-min timers)
- **Verified**: Passage is displayed, questions are based on passage, "Start Next Test" flow works

### 13. **pages/writing.php**
- **Added**: AI-generated prompt display (cached per session)
- **Added**: Word counter with color coding (red <250, green 250-450, orange >450)
- **Fixed**: Integration with evaluate_attempt.php API
- **Fixed**: Evaluation results display (CEFR, IELTS, TOEFL, diagnostic, strengths, improvements)
- **Changes**:
  - Prompt generated on start (cached in session)
  - 50-minute timer with auto-submit
  - Evaluation saved to ai_results table

### 14. **includes/header.php**
- **Fixed**: Uses dynamic BASE_PATH instead of hardcoded `/Seng321`
- **Changes**: Added `require_once __DIR__ . '/base_path.php'` and `$base = get_base_path()`

### 15. **login_part/login.php**
- **Fixed**: All redirects use dynamic BASE_PATH
- **Changes**: Added base path detection, updated all redirect URLs

### 16. **login_part/index.php**
- **Fixed**: Form actions use dynamic BASE_PATH
- **Changes**: Added base path detection, updated form action URLs

---

## Requirements Compliance

### FR17 - Reading Assessment ✅
- Two sequential tests (Intermediate-Easy, Advanced)
- Each: 10 questions, 15-minute timer
- "Start Next Test" button after first test
- Passage displayed, questions based on passage

### FR18 - Writing Assessment ✅
- AI-generated prompt (cached per session)
- 250-450 word guidance with word counter
- 50-minute timer with auto-submit
- AI evaluation with CEFR + IELTS + TOEFL estimates
- Results saved to ai_results table

### FR19 - Listening Assessment ✅
- Two sequential tests (already implemented via start_attempt.php)
- Each: 10 minutes, 10 seconds preview
- Auto-submit on expiry

### FR20 - Grammar Assessment ✅
- 20 AI-assigned questions
- 30-minute timer
- Can submit early or auto-submit on expiry
- Results saved to DB with review screen

### FR21 - Vocabulary Assessment ✅
- 20 AI-generated questions
- 25-minute timer
- Can submit early or auto-submit on expiry
- Results saved to DB with review screen

### FR22 - Review Correct/Incorrect ✅
- All assessment pages show review screen after completion
- Displays user answer, correct answer, and correctness status

### FR23 - Past Performance Storage ✅
- Results saved to assessments + assessment_results tables
- Writing/speaking/listening saved to ai_results table
- Reports page can query these tables

### FR10, FR11, FR12 - AI Evaluation & CEFR Classification ✅
- AI-based evaluation for writing (via Gemini API)
- CEFR classification with IELTS/TOEFL mapping
- Results stored in ai_results table

---

## Configuration

### Database Configuration
Edit `config/db.php` or create `.env` file in project root:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=language_platform
DB_USER=root
DB_PASS=
```

### AI Configuration
Create `.gemini_key` file in project root with your Gemini API key, OR add to `.env`:

```env
GEMINI_API_KEY=your_api_key_here
AI_MODE=live
```

If `AI_MODE=mock` or no key provided, system uses deterministic fallback mode.

---

## Runbook

### 1. Start XAMPP
- Start Apache and MySQL from XAMPP Control Panel
- Ensure MySQL is running on port 3306 (default)

### 2. Database Setup
- Database name: `language_platform`
- Ensure tables exist: `users`, `assessments`, `assessment_results`, `assessment_attempts`, `assessment_answers`, `ai_results`

### 3. Configuration
- Create `.env` file in project root (optional, for DB/AI config)
- OR create `.gemini_key` file with your Gemini API key (optional)
- Default DB config in `config/db.php` should work for standard XAMPP

### 4. Access Application
- Open browser: `http://localhost/Seng321/login_part/index.php`
- OR: `http://localhost/seng321/login_part/index.php` (case-insensitive on Windows)
- Login with existing user or register new account

### 5. Test Each Module

#### Grammar Test
- URL: `http://localhost/Seng321/pages/grammar.php`
- Click "Start"
- Answer 20 questions (30-minute timer)
- Review correct/incorrect answers after completion

#### Vocabulary Test
- URL: `http://localhost/Seng321/pages/vocabulary.php`
- Click "Start"
- Answer 20 questions (25-minute timer)
- Review correct/incorrect answers after completion

#### Reading Test
- URL: `http://localhost/Seng321/pages/reading.php`
- Click "Start Reading Test"
- Read passage, answer 10 questions (15-minute timer)
- After submit, click "Start Next Test" for second test
- Review results after both tests complete

#### Writing Test
- URL: `http://localhost/Seng321/pages/writing.php`
- Click "Start Writing"
- View AI-generated prompt
- Write essay (250-450 words, 50-minute timer)
- Submit to get AI evaluation (CEFR, IELTS, TOEFL estimates)

#### Reports
- URL: `http://localhost/Seng321/pages/reports.php`
- View past assessment results
- No errors about missing `ai_test_results` table

### 6. API Endpoints (for debugging)
All return JSON only:
- `http://localhost/Seng321/pages/api/start_attempt.php` (POST: type, part)
- `http://localhost/Seng321/pages/api/save_progress.php` (POST: attempt_id, question_index, answer_text)
- `http://localhost/Seng321/pages/api/submit_attempt.php` (POST: attempt_id)
- `http://localhost/Seng321/pages/api/evaluate_attempt.php` (POST: attempt_id, skill, text)

---

## Testing Checklist

- [x] Login redirects work (no 404 errors)
- [x] No PHP session warnings
- [x] No database connection errors
- [x] Reports page loads without `ai_test_results` errors
- [x] Grammar: 20 questions, 30 min timer, saves to DB
- [x] Vocabulary: 20 questions, 25 min timer, saves to DB
- [x] Reading: Passage visible, 10 questions per test, "Start Next Test" works
- [x] Writing: Prompt visible, word counter, 50 min timer, evaluation saves
- [x] All API endpoints return valid JSON (no HTML/notices)
- [x] AI caching works (reload doesn't call API repeatedly)

---

## Notes

- **Caching**: AI responses are cached for 10 minutes (questions) or 1 hour (evaluations) to reduce API quota usage
- **Throttling**: Minimum 2 seconds between API calls to prevent rate limiting
- **Fallback Mode**: If AI_MODE=mock or no API key, system uses deterministic fallback (not random) for stable testing
- **Base Path**: Automatically detects project folder name (works with Seng321 or seng321)
- **Error Handling**: All API endpoints log errors and return JSON error responses (no HTML output)

---

## Troubleshooting

### Database Connection Refused
- Check MySQL is running in XAMPP
- Verify port in `config/db.php` (should be 3306 for standard XAMPP)
- Check database name matches your schema

### AI Service Not Working
- Verify `.gemini_key` file exists with valid API key
- OR set `GEMINI_API_KEY` in `.env` file
- Check `AI_MODE` setting (mock/live)
- Check error logs for API errors

### Redirect 404 Errors
- Ensure `includes/base_path.php` exists
- Check that login file exists at `login_part/index.php`
- Verify Apache mod_rewrite is enabled (if using .htaccess)

### JSON Parse Errors in Frontend
- Check browser console for API response
- Verify API endpoint returns valid JSON (no PHP notices/warnings)
- Check that `display_errors` is disabled in API files

---

## Summary of Changes

**Total Files Modified**: 16
**New Files Created**: 1 (includes/base_path.php)
**Major Fixes**:
1. Dynamic base path detection (fixes redirect issues)
2. Session warning fixed
3. Database connection with env support
4. Removed non-existent table references
5. Complete AI service rewrite with caching/throttling
6. All API endpoints return JSON only
7. Assessment pages match requirements (counts, timers, DB storage)
8. Writing evaluation integrated with AI service

All requirements from SENG321 Part2 report are now implemented and working.
