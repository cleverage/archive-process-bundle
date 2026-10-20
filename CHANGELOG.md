Latest
------

### Changes
* [#43](https://github.com/cleverage/archive-process-bundle/issues/43) Composer: require PHP `^8.2` instead of `>=8.2` (PHP 9 is no longer accepted before being tested), remove the authorization of the `symfony/flex` plugin, which is not required (the CI installs it globally), as [cleverage/process-bundle#319](https://github.com/cleverage/process-bundle/issues/319).
* [#38](https://github.com/cleverage/archive-process-bundle/issues/38) Define the public API (Symfony BC promise, as [cleverage/process-bundle#279](https://github.com/cleverage/process-bundle/issues/279)): `ZipTask`, `UnzipTask` and `CleverAgeArchiveProcessBundle` are marked `@final since 2.3`, they will be `final` in 3.0: extending them triggers a deprecation with the Symfony `DebugClassLoader`. `CleverAgeArchiveProcessExtension` is marked `@internal`.
* [#37](https://github.com/cleverage/archive-process-bundle/issues/37) `CleverAgeArchiveProcessExtension::findServices()` loads the service definition files with a glob pattern instead of a `Finder` (same files, as [cleverage/process-bundle#271](https://github.com/cleverage/process-bundle/issues/271)), `symfony/finder` is no longer required. Add tests.
* [#35](https://github.com/cleverage/archive-process-bundle/issues/35) Require `symfony/polyfill-php83`, `symfony/polyfill-php84` and `symfony/polyfill-php85` ([cleverage/process-bundle#299](https://github.com/cleverage/process-bundle/issues/299)): the functions of PHP 8.3 to 8.5 can be used while supporting PHP 8.2. Rector targets PHP 8.2 (`withPhpVersion()`, `withPhpSets()` read from `composer.json`) and applies the rules using a newer function when its polyfill is required (no change in the current code).
* [#31](https://github.com/cleverage/archive-process-bundle/issues/31) Give a code to the tasks ([cleverage/process-bundle#91](https://github.com/cleverage/process-bundle/issues/91)): `archive_zip` and `archive_unzip` (`task: archive_zip`, with `cleverage/process-bundle` 5.4 or later, referencing them by `service` being deprecated there). The tasks have their class name as service id; fetching them directly from the service container is deprecated (`container.private`), they will be private in 3.0. The former ids `cleverage_archive_process.task.zip` / `cleverage_archive_process.task.unzip` are deprecated aliases, removed in 3.0. Update documentation, add tests.
* [#29](https://github.com/cleverage/archive-process-bundle/issues/29) CI: test the lowest versions allowed by `composer.json` (`--prefer-lowest`, PHP 8.2).
* [#27](https://github.com/cleverage/archive-process-bundle/issues/27) Add a `.gitattributes`: the development files (`.docker/`, `.github/`, `tests/`, quality tools and PHPUnit configurations, `Makefile`) are no longer part of the archive installed by Composer (`dist`).

v2.2
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
