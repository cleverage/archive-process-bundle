UnzipTask
=========

Extracts the whole content of a zip archive into a destination directory, then outputs this directory path.
Typical use case: extract an archive received from a partner (upload, SFTP, ...) before browsing and reading its files.

Task reference
--------------

* **Service**: `CleverAge\ArchiveProcessBundle\Task\UnzipTask`

Accepted inputs
---------------

`array` or `null`: the input array is merged with the task options, input values taking precedence over the
configured ones. It may only contain the `filename` and/or `destination` keys (any other key throws an
`UndefinedOptionsException`). A `null` input is handled as an empty array, so the options come from the task
configuration only.

Any other input type (e.g. a `string` file path) is not supported and throws an `\UnexpectedValueException`: convert it into an array first, for instance with a
[TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md) and
the [wrapper](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/wrapper_transformer.md)
transformer (`wrapper_key: filename`).

Possible outputs
----------------

`string`: the `destination` directory where the archive was extracted.

Options
-------

| Code          | Type     | Required | Default | Description                                                                                       |
|---------------|----------|:--------:|---------|---------------------------------------------------------------------------------------------------|
| `filename`    | `string` |  **X**   |         | Path of the zip archive to extract. It must exist and be readable (`\UnexpectedValueException`)   |
| `destination` | `string` |  **X**   |         | Directory where the archive content is extracted                                                  |

Both options are required, but they can be provided either in the task configuration or in the input.
They are validated when the task is executed, not at process initialization.

Examples
--------

* Extract an archive whose paths are set in the options

```yaml
# Task configuration level
unzip:
  service: '@CleverAge\ArchiveProcessBundle\Task\UnzipTask'
  options:
    filename: '%kernel.project_dir%/var/data/archive.zip'
    destination: '%kernel.project_dir%/var/data/unzip_archive'
  outputs: [browse]
```

* Provide the paths through the input (e.g. from a previous task), then browse the extracted files

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      filename: '%kernel.project_dir%/var/data/archive.zip'
      destination: '%kernel.project_dir%/var/data/unzip_archive'
  outputs: [unzip]
unzip:
  service: '@CleverAge\ArchiveProcessBundle\Task\UnzipTask'
  outputs: [browse]
browse:
  service: '@CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask'
```

Notes
-----

* Underlying method is [ZipArchive::extractTo()](https://www.php.net/manual/en/ziparchive.extractto.php): the whole
  archive is extracted, existing files with the same name are overwritten and other files already present in the
  destination directory are kept.
* A `\RuntimeException` is thrown if the file cannot be opened as a zip archive (with the `ZipArchive` error code,
  e.g. `19` for `ZipArchive::ER_NOZIP`), or cannot be extracted (e.g. `destination` is not writable or is a file). In
  the latter case, the files extracted before the failure are kept.
* Options are resolved on each execution of the task: when the task receives several inputs (e.g. after an iterable
  task), each input is extracted with its own `filename` and `destination`.
* See the [Import the CSV files of an uploaded archive](../../cookbooks/import_archive.md) cookbook.
