ZipTask
=======

Creates a zip archive containing the given files, then outputs the archive path.
Typical use case: archive exported files before sending them to a partner or storing them.

Task reference
--------------

* **Service**: `CleverAge\ArchiveProcessBundle\Task\ZipTask`

Accepted inputs
---------------

`array` or `null`: the input array is merged with the task options, input values taking precedence over the
configured ones. It may only contain the `filename`, `files` and/or `files_base_path` keys (any other key throws an
`UndefinedOptionsException`). A `null` input is handled as an empty array, so the options come from the task
configuration only.

Any other input type (e.g. a `string` file path) is not supported: convert it into an array first, for instance with a
[TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md) and
the [wrapper](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/wrapper_transformer.md)
transformer (`wrapper_key: files`).

Possible outputs
----------------

`string`: the `filename` of the created archive.

Options
-------

| Code              | Type              | Required | Default | Description                                                                                                  |
|-------------------|-------------------|:--------:|---------|--------------------------------------------------------------------------------------------------------------|
| `filename`        | `string`          |  **X**   |         | Path of the zip archive to create. An existing archive is overwritten                                        |
| `files`           | `string\|array`   |  **X**   |         | Path of the file, or list of paths of the files, to add to the archive. Paths are relative to `files_base_path`, or absolute and starting with `files_base_path` |
| `files_base_path` | `string`          |          | `''`    | Base directory of the files to add. It is removed from the file paths to build the names of the archive entries |

Options can be provided either in the task configuration or in the input.
They are validated when the task is executed, not at process initialization.

Examples
--------

* Archive files whose paths are set in the options: the archive contains `sample.txt` and `exports/books.csv`

```yaml
# Task configuration level
zip:
  service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'
  options:
    filename: '%kernel.project_dir%/var/data/zip_archive.zip'
    files:
      - '%kernel.project_dir%/var/data/sample.txt' # Absolute path, starting with files_base_path
      - 'exports/books.csv'                         # Path relative to files_base_path
    files_base_path: '%kernel.project_dir%/var/data'
  outputs: [next_task]
```

* Archive the file written by a previous task (e.g. a
  [CsvWriterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_writer_task.md),
  which outputs the path of the written file)

```yaml
# Task configuration level
write:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/exports/books_{date}.csv'
  outputs: [to_zip_input]
to_zip_input:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      wrapper:
        wrapper_key: files # 'path/to/books_20260930.csv' => { files: 'path/to/books_20260930.csv' }
  outputs: [zip]
zip:
  service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'
  options:
    filename: '%kernel.project_dir%/var/exports/books.zip'
    files_base_path: '%kernel.project_dir%/var/exports'
```

Notes
-----

* Underlying class is [ZipArchive](https://www.php.net/manual/en/class.ziparchive.php), the archive is opened with the
  `ZipArchive::CREATE | ZipArchive::OVERWRITE` flags. A `\RuntimeException` is thrown if it cannot be opened.
* For each file, the entry name is the file path with every occurrence of `files_base_path` removed and leading
  directory separators trimmed; the file actually read is `files_base_path` + directory separator + entry name.
  Hence:
  * all files must be located under `files_base_path`;
  * with the default empty `files_base_path`, paths must be absolute (a relative path would be resolved from the
    filesystem root), and the entries keep their full path (without the leading `/`) inside the archive.
* Each file must exist and be readable, otherwise an `\UnexpectedValueException` is thrown. Only files can be added:
  directories are not browsed.
* Options are resolved (and cached) on the first execution of the task: when the task receives several inputs during
  the same process execution (e.g. after an iterable task), the values of the first input are reused for the following
  ones. To archive a list of files, aggregate them first (e.g. with an
  [AggregateIterableTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/aggregate_iterable_task.md))
  and send them all at once in the `files` key.
* See the [Export, archive and upload a file](../../cookbooks/export_archive_upload.md) cookbook.
