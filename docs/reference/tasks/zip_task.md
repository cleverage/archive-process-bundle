ZipTask
===============

Zip files into a given filename.

Task reference
--------------

* **Task Service**: `CleverAge\ArchiveProcessBundle\Task\ZipTask`

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

`string`: the zip created filename.

Options
-------

| Code              | Type                | Required  | Default  | Description                           |
|-------------------|---------------------|:---------:|----------|---------------------------------------|
| `filename`        | `string`            |   **X**   |          | Zip to create filename                |
| `files`           | `string` or `array` |   **X**   |          | Files to add on archive               |
| `files_base_path` | `string`            |           | ''       | Base directory where files to add are |

Examples
--------

### Task

```yaml
# Task configuration level
code:
  service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'
  options:
    filename: '%kernel.project_dir%/var/data/zip_archive.zip'
    files:
      - '%kernel.project_dir%/var/data/sample.txt'
    files_base_path: '%kernel.project_dir%/var/data'
```
