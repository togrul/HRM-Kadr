# Orders Template UI Config (Phase 3)

This document defines the `order_template_fields.ui_config` keys currently supported by Add/Edit Order metadata rendering.

## Supported keys

- `field` (`string`)
  - Component form field name (example: `personnel_id`, `day`, `structure_id`).
- `input` (`string`)
  - Supported: `select`, `radio-list`, `date-input`, `numeric-input`, `text-input`.
- `model` (`string`)
  - Render payload model key (example: `_personnels`, `_ranks`, `_structures`).
- `searchField` (`string`)
  - Livewire search binding (example: `search.personnel`).
- `selectedName` (`string`)
  - Optional selected label resolver key.

## Mapping-level keys (`order_template_mappings.mapping_config`)

- `transform` (`string|object|array`)
  - Optional transform override for this specific placeholder mapping.
  - Passed to `OrderFieldTransformPipeline`.
  - Examples:
    - `"suffix.number"`
    - `{"type":"date.format","options":{"format":"d.m.Y"}}`
    - `{"transforms":[{"type":"trim"},{"type":"upper"}]}`

## Group/layout keys

- `group` (`string`, default: `main`)
  - Group identifier for row fields.
- `group_title` (`string|null`)
  - Optional visible title above group fields.
- `group_order` (`int`, default: `0`)
  - Sorting order between groups.
- `field_order` (`int`, default: field `sort_order`)
  - Sorting order inside group.
- `grid_cols` (`int|object`)
  - Group grid columns.
  - Examples:
    - `3` -> `{default: 3}`
    - `{default: 1, sm: 2, md: 3, lg: 4}`
- `col_span` (`int|object`)
  - Per-field responsive span.
  - Examples:
    - `2` -> `{default: 2}`
    - `{default: 1, sm: 2}`

## Example

```json
{
  "field": "personnel_id",
  "input": "select",
  "model": "_personnels",
  "searchField": "search.personnel",
  "group": "personnel",
  "group_title": "Personnel",
  "group_order": 1,
  "field_order": 10,
  "grid_cols": { "default": 1, "sm": 2, "md": 3 },
  "col_span": { "default": 1, "sm": 2 }
}
```

## Notes

- Layout grouping is applied only when metadata mode is active (`row_field_keys` is not empty).
- Orders form rendering is metadata-driven only; active metadata version + row mappings are mandatory.
- UI config mutations (generate metadata, add/remove field, save config) are recorded in
  `order_template_version_audits` for traceability.
- Set Type UI config modal shows latest audit entries (`Recent changes`) from
  `order_template_version_audits`.
