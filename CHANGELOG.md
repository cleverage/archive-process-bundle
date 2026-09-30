Latest
------

### Changes
* [#13](https://github.com/cleverage/archive-process-bundle/issues/13) Add missing tests: ZipTask and UnzipTask (options precedence and validation, missing or unreadable files, overwriting, open failures), bundle and DI extension.

### Fixes
* [#15](https://github.com/cleverage/archive-process-bundle/issues/15) Fix ZipTask and UnzipTask: resolve the options on every execution, so that each input is used (the options of the first input were reused for the following ones). Update documentation, add tests.
* [#16](https://github.com/cleverage/archive-process-bundle/issues/16) Fix ZipTask: only remove the leading `files_base_path` from the file paths (every occurrence was removed), and read the files at the given path when `files_base_path` is empty (relative paths were resolved from the filesystem root). Update documentation, add tests.
* [#17](https://github.com/cleverage/archive-process-bundle/issues/17) Fix ZipTask and UnzipTask: throw a `\RuntimeException` when the archive cannot be written (ZipTask) or extracted (UnzipTask), instead of outputting the path anyway; add the `ZipArchive` error code to the UnzipTask open failure message. Update documentation, add tests.
* [#18](https://github.com/cleverage/archive-process-bundle/issues/18) Fix ZipTask and UnzipTask: throw an explicit `\UnexpectedValueException` on a non-array input (e.g. a `string` path) instead of an unrelated warning or `TypeError`. Update documentation, add tests.

v2.1
------

### Changes
* [#10](https://github.com/cleverage/archive-process-bundle/issues/10) Update quality stack: use Rector `withComposerBased()` sets (removed `SYMFONY_64` / `PHPUNIT_100` sets), declare used Symfony packages and PHPUnit range in composer.json, apply quality tools fixes
* [#12](https://github.com/cleverage/archive-process-bundle/issues/12) Add missing documentations: complete reference pages for every Task, complete index and cookbooks. Harmonize and fix existing documentation.

v2.0
------

### Changes
* [#4](https://github.com/cleverage/archive-process-bundle/issues/4) Add support for PHP 8.5 and Symfony 8.* Update phpunit/phpunit to version >10.0 Bump version to cleverage/process-bundle ^5.0

### BC breaks
* [#4](https://github.com/cleverage/archive-process-bundle/issues/4) Remove support for PHP 8.1 and Symfony 7.3



v1.1
------

### Changes
* [#2](https://github.com/cleverage/archive-process-bundle/issues/2) Upgrade to Symfony 7.3 & PHP 8.4

v1.0.0
------

* Initial release with ZipTask and UnzipTask.
