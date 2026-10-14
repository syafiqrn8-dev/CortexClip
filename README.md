<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# CortexClip — Project Documentation

**Status:** Initial project context  
**Last updated:** 2026-10-10  
**Backend:** Laravel 13 / PHP 8.3  
**Client:** Flutter (Android first; iOS later)  
**Database:** PostgreSQL  
**API style:** REST JSON, prefix `/api/v1`

## Purpose

CortexClip is an AI co-creator for beginner creators who want to make short-form videos for TikTok, Instagram Reels, and YouTube Shorts with fewer apps and less manual work.

The product helps users move from an idea to an exported video. AI proposes content and assets, while the user reviews and controls the result.

## Current development phase

- Product definition: complete at a high level.
- UI/UX design: frozen as `CortexClip_UIUX_Design_v1.0_Design_Freeze.pen` (2026-10-07). The design file should be visually reviewed by the project owner before implementation details are assumed to be correct.
- Technical setup: Laravel project exists, PostgreSQL is configured, and initial Laravel migrations have run.
- Current task: document and validate the MVP data model before writing custom migrations.

## Read these documents in order

1. `PRODUCT_REQUIREMENTS.md` — product scope and MVP.
2. `ARCHITECTURE.md` — technical boundaries and conventions.
3. `ERD.md` — proposed data model; still a design proposal until explicitly approved.
4. `DECISIONS.md` — decisions already agreed.
5. `IMPLEMENTATION_PLAN.md` — recommended incremental implementation sequence.
6. `OPENCODE_GUIDE.md` — instructions for the coding agent.

## Important working rules

- Work incrementally. Do not implement several phases at once.
- Before changing architecture or schema, explain the reason and identify trade-offs.
- Do not invent product requirements. If a requirement is unclear and affects architecture, ask a concise question.
- Prefer the smallest reliable MVP implementation over premature abstraction.
- Keep secrets out of source control and logs.
- Do not commit `.env`, API keys, passwords, user data, generated media, or local credentials.
- Do not alter the frozen UI/UX design file unless explicitly requested.
- Existing Laravel files and migrations must be inspected before changing them.
- Do not run destructive database commands without explicit approval.


## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


