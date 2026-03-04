# Candidates Dual-Mode (Military + Civilian)

This document defines the runtime behavior for the Candidates module when the
project needs to support both military and non-military institutions.

## Goal

Keep one Candidates module and switch behavior by mode without duplicating
screens/components.

## Configuration

- `APP_CANDIDATE_MODE=military|civilian|auto`
- Config file: `config/candidates.php`
- When `auto`, mode is resolved from active profile (`APP_TYPE`) via:
  - `config('candidates.profile_mode_map')`

Default mapping:

- `default` -> `military`
- `military` -> `military`
- `public` -> `civilian`
- `private` -> `civilian`

## Runtime Resolver

- Class: `App\Modules\Candidates\Support\CandidateModeResolver`
- Constants:
  - `military`
  - `civilian`
  - `auto`
- Fallback: invalid mappings resolve to `military` (safe default).

## Form Behavior (Current Phase)

Implemented in `CandidateCrud` + `includes/candidate-action.blade.php`.

### Shared fields (both modes)

- Name/Surname/Patronymic
- Structure
- Birthdate/Gender
- Height/Phone
- Knowledge test
- Research/appeal/application/requisition dates
- Initial docs / completeness
- Characteristics
- Note / Presented by / Discrediting info
- Status

### Military-only visible fields

- Military service
- Physical fitness exam
- HHK date/result
- Useless info
- Attitude to military

### Validation differences

- Military mode:
  - `candidate.physical_fitness_exam` required
  - `candidate.attitude_to_military` required + enum
- Civilian mode:
  - both above nullable

## List + Export Behavior

- `CandidateList` table:
  - `Tests` column is visible only in military mode.
  - `Test results` filter is visible only in military mode.
  - Status tabs and enabled filters are preset-driven by mode via
    `config('candidates.list_presets')`:
    - `default_status`
    - `show_deleted_tab`
    - `status_whitelist`
    - `enabled_filters`
- Excel export (`CandidateExport`):
  - military-only columns are included only in military mode.

## UI Indicator

Candidate add/edit side modal now shows active mode as badge:

- `Mode: Military`
- `Mode: Civilian`

## Test Coverage

- `tests/Unit/Modules/Candidates/CandidateModeResolverTest.php`
  - explicit mode
  - auto mode via profile map
  - invalid mapping fallback

## Operational Notes

- This phase is intentionally **non-breaking**:
  - existing military flow stays default.
  - no DB schema change needed.
- Next phase (optional):
  - mode-based filter presets in list
  - domain-specific candidate field groups for civilian onboarding
  - policy/permission matrix by mode
