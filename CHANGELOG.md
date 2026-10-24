# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes verified by comparing the release tag against the immediately preceding published release. Changes made later on `master` are excluded.

## 1.1.4 - 2026-10-15

### Changed

- Added `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.1.3` to `1.1.4` and its build number from `2026101300` to `2026101500`.

No functional changes to the Socratic activity were identified in this tag comparison.

[Compare 1.1.3...1.1.4](https://github.com/EduardoKrausME/moodle-mod_socratic/compare/1.1.3...1.1.4)

## 1.1.3 - 2026-10-13

### Added

- Added an opt-in course-reset setting for deleting Socratic conversations and messages. The default now preserves conversation history when resetting a course; when selected, the reset also clears completion state for affected activities.
- Added a "No activity-specific rule" completion criterion and corresponding English and Brazilian Portuguese language strings.
- Added a GitHub Actions packaging workflow that creates a Moodle-installable ZIP with the required `socratic/` root folder and uploads it as a workflow artifact without creating a release.
- Added ZIP archive generation to the release workflow and attached `mod_socratic.zip` to published releases.
- Added PHPUnit coverage for opt-in course resets, preservation of Moodle completion tracking modes, and conversation operations with `cm_info`.
- Documented the installable ZIP and GitHub issue tracker in the README.

### Fixed

- Removed manual updates of the core `course_modules.completion` setting from activity create/update callbacks, allowing Moodle to retain its configured completion tracking mode.
- Updated custom completion-rule handling so manual completion and the new "none" option do not enable custom activity rules, and interaction-count validation applies when automatic completion is configured.
- Updated conversation service method signatures to accept both `cm_info` and `stdClass` course-module objects.
- Added Moodle internal access guards and explicit steps-library includes in the backup and restore task classes.

### Changed

- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17 and the MariaDB service from version 10 to 11.4, including revised startup environment variables and health checks.
- Added licensing metadata headers to the compiled AMD JavaScript and Mustache templates.
- Updated the plugin release identifier from `1.1.2` to `1.1.3` and its build number from `2026101200` to `2026101300`.

[Compare 1.1.2...1.1.3](https://github.com/EduardoKrausME/moodle-mod_socratic/compare/1.1.2...1.1.3)
