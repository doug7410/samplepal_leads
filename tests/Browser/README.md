# Browser Acceptance Tests

This directory contains browser acceptance tests for the SamplePal Leads application using Laravel Dusk.

## Structure

- **Auth/**: Authentication-related tests (login, register, password reset)
- **Settings/**: User settings tests (profile, password, appearance)
- **Pages/**: Page objects that encapsulate page interactions
  - **Settings/**: Page objects for settings pages
- **Components/**: Reusable UI components for tests

## Running Tests

To run all browser tests:

```bash
php artisan dusk
```

To run a specific test:

```bash
php artisan dusk tests/Browser/Auth/LoginTest.php
```

## Test Categories

1. **Authentication Tests**
   - Login
   - Registration
   - Password Reset

2. **Dashboard Tests**
   - Dashboard visibility and navigation

3. **Companies Tests**
   - Listing companies
   - Searching companies

4. **Contacts Tests**
   - Listing contacts
   - Searching contacts

5. **Campaigns Tests**
   - Listing campaigns
   - Creating campaigns
   - Viewing campaign details

6. **Settings Tests**
   - Profile management
   - Password updates
   - Appearance settings

## CI Setup

The browser tests are configured to run in GitHub Actions. The configuration file is located at `.github/workflows/dusk.yml`.

## Adding New Tests

1. Create a new Page object in the `Pages` directory if needed
2. Create a new test class that extends `DuskTestCase`
3. Implement test methods using Laravel Dusk API

## Best Practices

1. Use Page objects to encapsulate page interactions
2. Use selectors with data-testid attributes when possible
3. Add appropriate assertions for UI state
4. Minimize test dependencies
5. Clean up test data after each test