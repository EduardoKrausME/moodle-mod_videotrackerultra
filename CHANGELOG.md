# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes verified against its immediately preceding published release. Changes made later on `master` are excluded.

## 1.0.6 - 2026-10-15

### Changed

- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, adjusting startup environment variables and the health-check command.
- Added the plugin icon at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.5` to `1.0.6` and its build number from `2026101200` to `2026101500`.

No changes to Video Tracker Ultra activity functionality were identified in this release comparison.

[Compare 1.0.5...1.0.6](https://github.com/EduardoKrausME/moodle-mod_videotrackerultra/compare/1.0.5...1.0.6)

## 1.0.5 - 2026-10-12

### Fixed

- Hardened the release workflow against shell injection by reading the head commit message from the `RELEASE_MESSAGE` environment variable instead of interpolating it directly into the shell script.

### Changed

- Updated the PHP 8.3 CI test entry from Moodle 5.2 (`MOODLE_502_STABLE`) to Moodle 5.3 (`MOODLE_503_STABLE`), retaining Moodle 4.5 coverage.
- Updated the plugin release identifier from `1.0.4` to `1.0.5` and its build number from `2026100604` to `2026101200`.

No changes to Video Tracker Ultra activity functionality were identified in this release comparison.

[Compare 1.0.4...1.0.5](https://github.com/EduardoKrausME/moodle-mod_videotrackerultra/compare/1.0.4...1.0.5)
