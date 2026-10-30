# Direct SCORM Player (local_efscorm)

Free plugin for Moodle 5.1+ by eLearning Fácil. It changes how SCORM activities are opened, without touching mod_scorm:

- **Enter directly.** Students go straight into the content when they click the activity (no "Enter" page).
  Teachers and report viewers keep the normal page, as Moodle does with "Skip content structure page: Always".
- **Full-screen player on the same page.** The core player page gets the theme's minimal layout (no navigation bar,
  blocks or footer), a top bar with the activity name and an **Exit activity** button, and the content fills the
  window, with scrollbars when it is larger. It is still the activity page, so time-tracking plugins
  (such as the free Active Time Clock) keep measuring it and show their clock.
- **New-window activities in the same tab.** Activities set to open in a new window are shown in that same
  full-screen player in the current tab (setting on by default): no pop-up blockers, and the time is measured like on
  any other page. Untick the setting to keep the new window.

Settings: Site administration → Plugins → Local plugins → Direct SCORM Player (full-screen player, direct entry,
new-window activities in the same tab, hide the table of contents). No data is stored: the plugin has no tables and a
null privacy provider.

Current version: **0.3.2** — see [CHANGES.md](CHANGES.md).

## Documentation

- User guide (PDF, English): [docs/manual/Guide_local_efscorm_en.pdf](docs/manual/Guide_local_efscorm_en.pdf)
- Guía de uso (PDF, español): [docs/manual/Guia_local_efscorm.pdf](docs/manual/Guia_local_efscorm.pdf)

## Installation

1. **Back up your site first** (database, moodledata and code) — good practice with any plugin.
2. Site administration → Plugins → Install plugins → upload the ZIP and confirm.
3. It is enabled site-wide by default. Check with a student account that clicking a SCORM goes straight in.

Requirements: Moodle 5.1, 5.2 or 5.3 · PHP 8.2+ · MySQL/MariaDB or PostgreSQL · a Boost-based theme.

## Support

Report bugs and ask questions in this repository's [issue tracker](https://github.com/anarosiris/moodle-local_efscorm/issues).

Developed and maintained by [eLearning Fácil](https://elearningfacil.com), an independent company that builds Moodle™ plugins and themes for training providers and companies. Not affiliated with Moodle Pty Ltd.

## Privacy

The plugin stores no personal data and creates no database tables: it only changes how SCORM activities are opened and displayed. It implements Moodle's Privacy API with a null provider, so nothing is exported or deleted on data requests because nothing is kept.

## Licence

GNU GPL v3 or later. Moodle™ is a registered trademark of Moodle Pty Ltd. SCORM® is a registered trademark of
Advanced Distributed Learning (ADL). eLearning Fácil is not affiliated with either.
