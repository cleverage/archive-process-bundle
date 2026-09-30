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

This bundle has no configuration: its tasks are registered as services (public, non-shared) and can be used directly
in your processes by referencing their class name, e.g. `service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'`.

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
