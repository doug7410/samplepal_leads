# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SamplePal Leads is a lead management application for manufacturing companies. It allows users to track companies and their contacts, with features for filtering, sorting, and managing contact status.

## Build/Lint/Test Commands

- **Build:** `npm run build` or `npm run build:ssr` (for server-side rendering)
- **Development:** `composer dev` (runs server, queue, logs, and Vite)
- **Format:** `npm run format` (Prettier)
- **Lint:**
  - JS/TS: `npm run lint` (ESLint)
  - PHP: `./vendor/bin/pint` (Laravel Pint)
- **Type checking:** `npm run types` (TypeScript)
- **Tests:**
  - Run all tests: `composer test` or `./vendor/bin/pest`
  - Run single test: `./vendor/bin/pest tests/path/to/test.php --filter=testName`

## Database Setup

- **Default:** SQLite for development (database/database.sqlite)
- **Production:** PostgreSQL recommended
- **Migrations:** Run `php artisan migrate` to set up the database schema
- **Seed Data:** Run `php artisan db:seed` to populate with sample data

## Key Architecture Components

- **Backend:** Laravel 12 with MVC architecture
- **Frontend:** React 19 with TypeScript
- **UI Framework:** Tailwind CSS 4 with Radix UI components
- **Page Rendering:** Inertia.js for server-side rendering

## Project Structure

- **Models:** `app/Models/` contains Company, Contact, and User models
- **Controllers:** `app/Http/Controllers/` handles business logic
- **React Components:** `resources/js/components/` contains reusable UI components
- **Pages:** `resources/js/pages/` contains page components rendered by Inertia
- **Routes:** `routes/` defines backend API and web routes
- **Middleware:** `app/Http/Middleware/` contains request middleware
- **Prompts:** `app/Prompts/` contains text templates for AI prompts

## Development Workflow

- **Database Changes:** Create migrations using `php artisan make:migration`
- **Model Changes:** Update models and run migrations
- **UI Changes:** Modify React components in resources/js
- **API Changes:** Update controllers and routes as needed
- **Testing Routes:** Use `php artisan route:list` to see all available routes

## Code Style Guidelines

- **Formatting:** Use Prettier for JS/TS and Laravel Pint for PHP
- **Imports:** Organize imports using prettier-plugin-organize-imports
- **Naming:** Follow Laravel and React conventions (camelCase for JS variables, PascalCase for components)
- **Types:** Use TypeScript for all JS/TS files
- **Error Handling:** Follow Laravel's exception handling patterns
- **Components:** Use Radix UI components and follow existing patterns in resources/js/components
- **CSS:** Use Tailwind CSS with the tailwind-merge utility

## Environment Configuration

- **Local Development:** Copy `.env.example` to `.env` and update as needed
- **Database:** Configure database connection in `.env`
- **App Settings:** Update app name, URL, and other settings in `.env`

## Claude Interaction Guidelines

- Do not add features that I don't ask for. Only add exactly what I ask for. Do not add comments unless they are really necessary