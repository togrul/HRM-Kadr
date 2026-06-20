# Order Template DOCX Doctor

This check exists to catch the exact production problem where an active metadata template version stores HTML or another invalid value instead of a real DOCX file path.

## Create A Valid Template

1. Open the order template administration screen.
2. Select the order type.
3. Upload or select a real `.docx` file. Do not paste HTML into the template path.
4. Configure metadata fields and placeholder mappings.
5. Publish the version and make it active.

The stored path should look like one of these:

```text
templates/hire-v1.docx
order-templates/vacation/v2.docx
storage/app/public/templates/hire-v1.docx
```

It must not look like this:

```html
<div>No</div>
```

## Verify

Run:

```bash
php artisan orders:templates:doctor --fail-on-issues
```

For one order type:

```bash
php artisan orders:templates:doctor --order-type=12 --fail-on-issues
```

For CI or machine-readable output:

```bash
php artisan orders:templates:doctor --json --fail-on-issues
```

Status meanings:

- `ok`: active version has a valid DOCX path, file exists, mappings exist.
- `invalid_docx_path`: active version path is blank, HTML, or not a `.docx`.
- `file_missing`: path format is valid, but file is missing from disk.
- `mapping_missing`: DOCX exists, but placeholder mappings are not configured.

After fixing a template, clear caches if needed:

```bash
php artisan optimize:clear
```
