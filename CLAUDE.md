# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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

## Code Style Guidelines

- **Formatting:** Use Prettier for JS/TS and Laravel Pint for PHP
- **Imports:** Organize imports using prettier-plugin-organize-imports
- **Naming:** Follow Laravel and React conventions (camelCase for JS variables, PascalCase for components)
- **Types:** Use TypeScript for all JS/TS files
- **Error Handling:** Follow Laravel's exception handling patterns
- **Components:** Use Radix UI components and follow existing patterns in resources/js/components
- **CSS:** Use Tailwind CSS with the tailwind-merge utility