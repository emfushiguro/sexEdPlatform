# Sex Education Learning Platform

A comprehensive web-based sexual health education platform for users in Cavite, Philippines.

## Project Information

**Project Title:** Development of a Web-Based Sex Education Learning Platform with Mobile Application

**Academic Year:** 2026

**Status:** Development Phase 3 - Models & Relationships

## Technology Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade Templates, Tailwind CSS, Vanilla JavaScript
- **Database:** MySQL
- **Authentication:** Laravel Breeze
- **Permissions:** Spatie Laravel Permission
- **Version Control:** Git/GitHub

## Database Schema

The system includes 30 tables organized into:

### Core Tables
- Users & User Profiles
- Subscriptions & Payments
- Stakeholders (Counselors, Clinics, Organizations)

### Learning System
- Modules, Lessons, Quizzes
- Quiz Questions & Options
- Progress Tracking & Gamification
- Certificates & Achievements

### Features
- Seminar Management & Registration
- Counselor Consultations
- Activity Logs

## Installation

```bash
# Clone repository
git clone <repository-url>

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Run seeders (when available)
php artisan db:seed

# Start development server
php artisan serve
npm run dev
```

## Development Phases

- [x] **Phase 1:** Project Foundation
- [x] **Phase 2:** Database Migrations
- [ ] **Phase 3:** Models & Relationships
- [ ] **Phase 4:** Subscription System
- [ ] **Phase 5:** Stakeholder Modules
- [ ] **Phase 6:** System Polishing

## User Roles

1. **Public User/Guest** - View public content
2. **Learner** - Complete modules, take quizzes, earn certificates
3. **Organization** - Manage seminars and events
4. **Clinic** - Publish testing and treatment services
5. **Counselor** - Provide consultations (requires approval)
6. **Admin** - System management and moderation

## Features

### Free Users
- Access to all learning modules
- Limited quiz attempts (3 per day)
- Progress tracking

### Premium Users
- Unlimited quiz attempts
- Certificate generation
- Downloadable learning materials
- Free seminar access

## License

Academic Project - All Rights Reserved
