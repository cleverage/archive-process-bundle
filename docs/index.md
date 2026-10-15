CleverAge/ArchiveProcessBundle
==============================

This bundle provides tasks to create and extract zip archives in
[CleverAge/ProcessBundle](https://github.com/cleverage/process-bundle) processes.

## Prerequisite

CleverAge/ProcessBundle must be [installed](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#installation).

The PHP [zip extension](https://www.php.net/manual/en/book.zip.php) (`ext-zip`) is required.

## Installation

Make sure Composer is installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and install it using composer:

```bash
composer require cleverage/archive-process-bundle
```

Remember to add the following line to `config/bundles.php` (not required if Symfony Flex is used):

```php
CleverAge\ArchiveProcessBundle\CleverAgeArchiveProcessBundle::class => ['all' => true],
```

## Configuration

This bundle has no configuration: its tasks are registered as services (non-shared) and are used in your processes by
their code, `archive_zip` and `archive_unzip` (e.g. `task: archive_zip`, requires `cleverage/process-bundle` 5.4 or
later, see its [task definition](https://github.com/cleverage/process-bundle/blob/main/docs/reference/02-task_definition.md)).
Referencing them by their class name (`service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'`) is deprecated since
`cleverage/process-bundle` 5.4.

Their service id is their class name. Fetching them directly from the service container is deprecated since 2.3 (they
will be private in 3.0); their former ids, `cleverage_archive_process.task.zip` and
`cleverage_archive_process.task.unzip`, are deprecated aliases since 2.3, removed in 3.0.

Both tasks accept their options either from the task configuration or from their input (an array merged with the
options), which allows computing paths in a previous task.

## Documentation

- Cookbooks
    - [Import the CSV files of an uploaded archive](cookbooks/import_archive.md)
    - [Export, archive and upload a file](cookbooks/export_archive_upload.md)
- Reference
    - Tasks
        - [UnzipTask](reference/tasks/unzip_task.md)
        - [ZipTask](reference/tasks/zip_task.md)
    - [ProcessBundle documentation](https://github.com/cleverage/process-bundle/blob/main/docs/index.md)
