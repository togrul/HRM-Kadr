# Module Spec Template

Use this template when defining a new module from business requirements.

## 1. Module Summary

- Module name:
- Business objective:
- Primary users:
- Primary owner:

## 2. Scope

### In scope

- 

### Out of scope

- 

## 3. Core Use Cases

1. 
2. 
3. 

## 4. Roles and Permissions

- Admin:
- Manager:
- Employee:
- Auditor:

Explicit permissions:

- 

## 5. Main Screens

1. Dashboard / list
2. Create / edit flow
3. Detail / review flow
4. Reports / exports
5. Settings / admin screen

For each screen define:

- route
- Livewire component
- data source
- actions
- empty state
- permissions

## 6. Domain Model

Main entities:

- 

Relationships:

- 

Required fields:

- 

## 7. Business Rules

- 

Validation rules:

- 

State transitions:

- 

## 8. Integrations

- source systems:
- outbound systems:
- files/import/export:
- schedulers/jobs:
- notifications:

## 9. Translation Rules

- View namespace:
- Translation namespace:
- Canonical translation key format: `module::file.key`

## 10. Audit and Security

- audit events:
- sensitive actions:
- locking / approval rules:

## 11. Reporting

- filters:
- exports:
- KPIs:

## 12. Technical Design

- provider:
- routes:
- Livewire components:
- services/use-cases:
- migrations:
- policies/permissions:
- jobs/commands:

## 13. Acceptance Criteria

- 

## 14. Test Plan

- unit tests:
- feature tests:
- smoke checks:
- failure scenarios:

## 15. Open Questions

- 
