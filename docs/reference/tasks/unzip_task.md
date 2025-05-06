UnzipTask
===============

Unzip a file, requires the destination path in options.

Task reference
--------------

* **Task Service**: `CleverAge\ArchiveProcessBundle\Task\UnzipTask`

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

`string`: the destination directory where zip file was extracted.

Options
-------

| Code          | Type     | Required | Default  | Description                                           |
|---------------|----------|:--------:|----------|-------------------------------------------------------|
| `filename`    | `string` |  **X**   |          | Zip filename to extract                               |
| `destination` | `string` |  **X**   |          | Destination directory where zip content was extracted |

Examples
--------

### Task

```yaml
# Task configuration level
code:
  service: '@CleverAge\ArchiveProcessBundle\Task\UnzipTask'
  options:
    filename: '%kernel.project_dir%/var/data/archive.zip'
    destination: '%kernel.project_dir%/var/data/unzip_archive'
```
