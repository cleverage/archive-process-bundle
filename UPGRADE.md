Upgrade Guide
=============

## v2.4

No deprecation and no change requiring an action. `archive_unzip` has new `max_files` and `max_size` options (no limit
by default): set them when the archives come from an untrusted source.

## v2.3

Unless stated otherwise, the deprecations below trigger a notice (`Since cleverage/archive-process-bundle 2.3: ...`)
and will be removed in 3.0.

### Process bundle 5.4

`cleverage/process-bundle` `^5.4` is required: follow its
[upgrade guide](https://github.com/cleverage/process-bundle/blob/main/UPGRADE.md#v54).

### Configuration key

The configuration key of the bundle is `cleverage_archive_process`: `clever_age_archive_process` is no longer
recognized. The bundle has no configuration, so remove the key: configuring one under `cleverage_archive_process` is
deprecated (the keys were ignored), it will be rejected in 3.0.

### Task codes and service ids

Reference the tasks by their code instead of their service:

```yaml
tasks:
    zip:
        # Before (deprecated)
        service: '@CleverAge\ArchiveProcessBundle\Task\ZipTask'
        # After
        task: archive_zip
```

| Former service id (deprecated alias)   | Class       | Code            |
|----------------------------------------|-------------|-----------------|
| `cleverage_archive_process.task.zip`   | `ZipTask`   | `archive_zip`   |
| `cleverage_archive_process.task.unzip` | `UnzipTask` | `archive_unzip` |

The service id of the tasks is their class name. Fetching them from the service container (`$container->get()`) is
deprecated: they will be private in 3.0.

### Final classes

`ZipTask`, `UnzipTask` and `CleverAgeArchiveProcessBundle` are marked `@final`, they will be `final` in 3.0
(extending them triggers a deprecation with the Symfony `DebugClassLoader`). `CleverAgeArchiveProcessExtension` is
`@internal`.

### Exceptions

The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle, extending the SPL
exceptions thrown before: existing `catch` blocks keep working.
