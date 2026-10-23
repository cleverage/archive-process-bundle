Export, archive and upload a file
=================================

This recipe describes a typical export flow: read data from a database, write it to a CSV file, zip this file, then
upload the archive to a remote storage (e.g. an SFTP server of a partner).

It requires, besides this bundle, the
[DoctrineProcessBundle](https://github.com/cleverage/doctrine-process-bundle) and the
[FlysystemProcessBundle](https://github.com/cleverage/flysystem-process-bundle), with two Flysystem storages:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        local.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/local'
        remote.storage:
            adapter: 'sftp'
            options:
                host: '%env(string:SFTP_HOST)%'
                port: 22
                username: '%env(string:SFTP_USERNAME)%'
                password: '%env(string:SFTP_PASSWORD)%'
                root: '%env(string:SFTP_ROOT)%'
```

```yaml
clever_age_process:
    configurations:
        app.export_archive_upload:
            description: 'Export the books, zip the file and upload it to the partner SFTP'
            help: 'bin/console cleverage:process:execute app.export_archive_upload'
            tasks:
                read:
                    task: doctrine_database_reader
                    options:
                        table: 'book'
                        sql: 'SELECT b.id, b.title FROM book b ORDER BY b.id'
                    outputs: [write]

                write:
                    task: csv_writer
                    options:
                        file_path: '%kernel.project_dir%/var/exports/books_{date}.csv'
                        headers: [id, title]
                    outputs: [to_zip_input]

                to_zip_input:
                    task: transformer
                    options:
                        transformers:
                            wrapper:
                                wrapper_key: files # '/.../books_20260930.csv' => { files: '/.../books_20260930.csv' }
                    outputs: [zip]

                zip:
                    task: archive_zip
                    options:
                        filename: '%kernel.project_dir%/var/storage/local/books.zip' # Inside the local.storage root
                        files_base_path: '%kernel.project_dir%/var/exports' # The archive entry is 'books_20260930.csv'
                    outputs: [to_storage_path]

                to_storage_path:
                    task: transformer
                    options:
                        transformers:
                            callback:
                                callback: basename # Path relative to the local.storage root: 'books.zip'
                    outputs: [upload]

                upload:
                    task: flysystem_file_fetch
                    options:
                        source_filesystem: 'local.storage'
                        destination_filesystem: 'remote.storage'
                        remove_source: true
                    outputs: [log_upload]

                log_upload:
                    task: logger
                    options:
                        level: info
                        message: 'Archive uploaded'
                        context: [input]
```

How it works:
- [DatabaseReaderTask](https://github.com/cleverage/doctrine-process-bundle/blob/main/docs/reference/tasks/database_reader_task.md)
  is iterable: each row of the query goes to the next task.
- [CsvWriterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_writer_task.md) is
  blocking: it writes every row, then outputs the path of the CSV file once all rows have been received.
- As the [ZipTask](../reference/tasks/zip_task.md) only accepts an array input, the
  [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  wraps this path under the `files` key with the
  [wrapper](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/wrapper_transformer.md)
  transformer.
- The [ZipTask](../reference/tasks/zip_task.md) merges this input with its options and creates the archive. As
  `files_base_path` is removed from the file path, the CSV file is stored at the root of the archive. The task outputs
  the absolute path of the archive.
- The [callback](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/callback_transformer.md)
  transformer turns this absolute path into a path relative to the `local.storage` root, as expected by the
  [FileFetchTask](https://github.com/cleverage/flysystem-process-bundle/blob/main/docs/reference/tasks/file_fetch_task.md),
  which copies the archive to `remote.storage` and removes the local one (`remove_source: true`).
- The [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md) logs the
  uploaded file name.

Note that the exported CSV file is kept in `var/exports`. To delete it once archived, add a
[FileRemoverTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/file_remover_task.md)
as a second output of the CsvWriterTask (`outputs: [to_zip_input, remove_csv]`): outputs are processed in order, so the
file is removed after the archive has been created.
