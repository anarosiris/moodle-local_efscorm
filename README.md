# Direct SCORM player (local_efscorm)

Free plugin for Moodle 5.1+ by eLearning Fácil. It changes how SCORM activities are opened, without touching mod_scorm:

- **Enter directly.** Students go straight into the content when they click the activity (no "Enter" page).
  Teachers and report viewers keep the normal page, as Moodle does with "Skip content structure page: Always".
- **Full-screen player on the same page.** The core player page gets the theme's minimal layout (no navigation bar,
  blocks or footer), a top bar with the activity name and an **Exit activity** button, and the content fills the
  window, with scrollbars when it is larger. It is still the activity page, so time-tracking plugins
  (local_efreport, local_eftimeclock) keep measuring it and show their clock.
- Activities set to open in a new window are left as they are.

Settings: Site administration → Plugins → Local plugins → Direct SCORM player.

Status: prototype 0.1.0 (ALPHA). Specification and open questions: `docs/SPEC.md`.

## Licence

GNU GPL v3 or later. Moodle™ is a registered trademark of Moodle Pty Ltd. SCORM® is a registered trademark of
Advanced Distributed Learning (ADL). eLearning Fácil is not affiliated with either.
