# SamplePal Leads Test Plan

This document outlines a comprehensive test plan focusing on core functionality without over-mocking.

## Unit Tests

### Models

#### Company Model
- Test creating a company with valid attributes
- Test relationship with contacts
- Test mass assignment protection
- Test attribute casting

#### Contact Model
- Test creating a contact with valid attributes
- Test relationship with company
- Test relationship with campaigns
- Test attribute casting for booleans and dates
- Test status transitions (e.g., marking as contacted)

#### Campaign Model
- Test creating a campaign with valid attributes
- Test relationship with contacts
- Test status constants and transitions
- Test filtering (JSON serialization/deserialization)
- Test with/without scheduled dates

#### CampaignContact (Pivot)
- Test creation with valid attributes
- Test status transitions
- Test relationship with campaigns and contacts

### Services

#### CampaignService
- Test campaign creation with and without contacts
- Test adding contacts by filter criteria
- Test adding specific contacts
- Test removing contacts
- Test campaign status updates
- Test statistics calculation
- Test error handling during transactions

#### MailService
- Test template parsing
- Test tracking pixel generation
- Test link tracking wrapping
- Test error handling
- Test email event creation

### Jobs

#### ProcessCampaignJob
- Test processing a campaign with contacts
- Test batch processing
- Test status transitions
- Test self-rescheduling for large campaigns
- Test completion handling

#### SendCampaignEmailJob
- Test sending email to a valid contact
- Test error handling for invalid contacts
- Test status updates on the campaign contact
- Test interaction with MailService (with mocking)

## Feature Tests

### Email Campaign Workflow
- Test creating a campaign and scheduling it
- Test processing a campaign from draft to completion
- Test email tracking events (opens, clicks)
- Test filtering contacts for campaigns

### Company Management
- Test creating, updating, deleting companies
- Test listing companies with pagination and filtering

### Contact Management
- Test creating, updating, deleting contacts
- Test listing contacts with pagination and filtering
- Test associating contacts with companies

## Integration Points to Test

### Mail Provider Integration
- Test the integration with the mail provider (minimal mocking)
- Test failover mechanisms

### User Authentication Flow
- Test the complete authentication flow

### Form Request Validation
- Test validation rules in form requests

## Test Data Strategy

- Use factories for model creation
- Use specific test cases for edge cases
- Reset database between tests
- Avoid mocking database when possible

## Avoiding Over-Mocking

- Use SQLite in-memory database for speed
- Only mock external services (email)
- Use real instances of internal services where possible
- Test actual email templates, not just mock calls
- Use Laravel's testing utilities for HTTP tests