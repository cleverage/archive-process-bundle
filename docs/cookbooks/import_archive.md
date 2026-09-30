Import the CSV files of an uploaded archive
==========================================

This recipe describes how to import data delivered as a zip archive: the archive path is given as the process input
(from the command line or uploaded through the [UI](https://github.com/cleverage/ui-process-bundle)), it is extracted,
then every CSV file it contains is read line by line.

```yaml
clever_age_process:
    configurations:
        app.import_archive:
            description: 'Import the CSV files of a zip archive'
            help: 'bin/console cleverage:process:execute app.import_archive --input=/path/to/archive.zip'
            entry_point: prepare # Required to receive the process input
            options:
                ui:
                    ui_launch_mode: form
                    entrypoint_type: file # The uploaded file path is used as input
                    constraints:
                        - Collection:
                              fields:
                                  input:
                                      - File:
                                            mimeTypes: [application/zip]
                                  context: ~
            tasks:
                prepare:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            wrapper:
                                wrapper_key: filename # '/path/to/archive.zip' => { filename: '/path/to/archive.zip' }
                    outputs: [unzip]

                unzip:
                    service: '@CleverAge\ArchiveProcessBundle\Task\UnzipTask'
                    options:
                        destination: '%kernel.project_dir%/var/imports/archive'
                    outputs: [browse]

                browse:
                    service: '@CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask'
                    options:
                        name_pattern: '*.csv'
                    outputs: [read]

                read:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
                    options:
                        delimiter: ';'
                    outputs: [count_rows, log_row]

                count_rows:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'

                log_row:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
                    options:
                        level: info
                        message: 'Imported line' # Replace this task by your own transformation / loading tasks
                        context: [input]
```

How it works:
- The process input is the path of the archive (`--input` option, or the uploaded file when launched from the UI with
  `entrypoint_type: file`). As the [UnzipTask](../reference/tasks/unzip_task.md) only accepts an array input, the
  [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  wraps it under the `filename` key with the
  [wrapper](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/wrapper_transformer.md)
  transformer.
- The [UnzipTask](../reference/tasks/unzip_task.md) merges this input with its options (`destination`), extracts the
  archive and outputs the destination directory.
- [InputFolderBrowserTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_folder_browser_task.md)
  is iterable: it outputs, one by one, the path of each CSV file found in the extracted directory (recursively).
- [InputCsvReaderTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_csv_reader_task.md)
  is iterable too: each line of the current file goes through the following tasks before the next one is read.
- [StatCounterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/stat_counter_task.md)
  logs the total count of imported lines at the end of the process, and the
  [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md) stands for
  your own import tasks.

Note that the extraction directory is not emptied before extracting: files of a previous archive are browsed again if
they are still present. Use a dedicated directory per execution (e.g. with a
[contextual option](https://github.com/cleverage/process-bundle/blob/main/docs/reference/02-task_definition.md)
`destination: '%kernel.project_dir%/var/imports/{{ import_id }}'` and `-c import_id:"'...'"`), or clean it after the
import.
