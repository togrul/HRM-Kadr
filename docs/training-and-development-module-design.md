# Training and Development Module Design

This document defines the target design for the `Training and Development` module.

## 1. Module goal

The module exists to:

- register all internal and external trainings completed by employees
- identify and approve development needs
- generate the annual training plan from real need data
- manage training calendar execution
- provide reporting and analytics for HR and management

## 2. Submodules

The module should be split into four submodules:

1. Trainings
2. Training Needs
3. Training Calendar
4. Results

## 3. Business users

- HR Admin
- HR Specialist
- Structure/Department Manager
- Employee
- Auditor

## 4. Trainings

This submodule stores completed training records.

### Required fields

- employee
- training title
- duration
- internal or external type
- provider or organizer
- start date
- end date
- completion status
- proof document
- result or outcome note
- source

### Allowed sources

- manual HR entry
- manual employee entry
- automatic creation from training calendar session

### Main rules

- document upload must support certificate, reference, or proof file
- one training session may have multiple participants
- employee training history must remain permanent
- training records must be visible in employee professional development history

## 5. Training Needs

This submodule is the intake and prioritization layer.

### Training need sources

- performance evaluation form results
- electronic tests and skill measurement results
- manual HR request
- manual manager request
- employee self-request from personal profile

### Need categories

- functional
- technical
- managerial
- soft skills
- mentoring
- coaching

### Workflow

1. Need is created
2. Source and reason are captured
3. HR reviews and approves/rejects
4. Approved needs become visible in:
   - Training Needs queue
   - employee Individual Development Plan
5. Approved needs are used in annual planning

### Required fields

- employee
- source module
- source record id
- category
- skill area
- weakness statement
- requested training or development action
- priority
- approver
- approval status
- target year/quarter

## 6. Annual Training Plan

The annual plan should be formed automatically from approved needs.

### Planning logic

- aggregate approved needs by category and department
- identify repeated needs across employees
- create yearly plan candidates
- estimate budget, trainer type, and participant volume

### Planner outputs

- yearly plan
- quarterly plan
- budget estimate
- department plan view
- employee-to-plan mapping

## 7. Training Calendar

This submodule is the execution board for planned trainings.

### Required capabilities

- HR adds planned trainings to calendar
- each calendar event supports participant list
- attendance is recorded per participant
- predefined forms can be attached
- after training, participant feedback is collected
- feedback is passed into Results

### Required fields

- session title
- training program
- planned date/time
- duration
- internal/external type
- location or meeting link
- trainer
- budget
- participant list
- attendance status
- feedback form template

### Session statuses

- draft
- planned
- ongoing
- completed
- cancelled

## 8. Results and Analytics

Reports must support yearly and quarterly views.

### Minimum report outputs

- total trainings completed
- total training hours per employee
- internal vs external split
- training outcomes
- budget usage
- comparison between planned need and delivered training

### KPI layer

- completion rate
- attendance rate
- training hours per employee
- budget burn rate
- unmet need count
- need-to-delivery coverage ratio
- department training distribution

## 9. Employee profile integration

Employee profile must show:

- completed trainings
- individual development plan
- approved development needs
- related mentoring/coaching actions
- academic/professional development records

### Additional profile data to support

- publication count
- publications in ranked journals
- conference and seminar participation
- project participation

## 10. Domain model proposal

### Core entities

- `training_programs`
- `training_sessions`
- `training_session_participants`
- `training_records`
- `training_need_items`
- `training_need_approvals`
- `training_annual_plans`
- `training_plan_items`
- `training_feedback_forms`
- `training_feedback_responses`
- `training_result_snapshots`

### Optional supporting entities

- `training_categories`
- `training_providers`
- `training_budget_lines`
- `development_actions`

## 11. Roles and permissions

- HR Admin
  - full CRUD, approval, reporting, settings
- HR Specialist
  - create/manage trainings, calendar, results
- Manager
  - submit needs, review team plans, view reports
- Employee
  - submit self-development needs, see own records, join feedback
- Auditor
  - read-only visibility

Suggested permissions:

- `show-trainings`
- `manage-trainings`
- `approve-training-needs`
- `manage-training-calendar`
- `export-training-reports`

## 12. Integrations

- Performance Evaluation module
  - weak performance creates training needs automatically
- Electronic Tests and Skill Measurement
  - weak skills create training needs automatically
- Personnel profile
  - development plan and training history surface here
- Notifications
  - reminders, approvals, session invites, feedback requests

## 13. Main screens

1. Training dashboard
2. Training records list
3. Training need approval board
4. Annual plan board
5. Training calendar
6. Session detail + participants
7. Results and analytics dashboard
8. Employee development plan panel

## 14. Acceptance criteria

- HR can create and maintain training records
- approved training needs feed the annual plan
- calendar sessions track participants and attendance
- feedback flows into results
- analytics compare need vs delivered training
- employee profile shows development plan and training history
