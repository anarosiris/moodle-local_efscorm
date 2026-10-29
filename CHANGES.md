# Changelog — Direct SCORM player (local_efscorm)

All notable changes to this plugin are listed here.

## [0.3.0] - 2026-10-29

### Changed
- The player's JavaScript now lives in an AMD module (`amd/src/player.js`, loaded with `js_call_amd()`) and its CSS
  in the plugin's `styles.css`, instead of being printed inline on the page, as suggested in the Moodle Plugins
  Directory review (MMR-425). The "hide the table of contents" setting is now a body class the stylesheet acts on.
  No change in behaviour, settings or capabilities.
- The two output hooks (`before_standard_head_html_generation`, `before_footer_html_generation`) are no longer
  registered: the body classes and the module call are set when the page is prepared.

## [0.2.2] - 2026-10-13

- Documentation only: changelog and README rewritten in English; user guide available in English and Spanish.
  No code changes.

## [0.2.1] - 2026-10-10

First public release.

### Fixed
- Single-activity course format: **Exit activity** no longer loops back into the player; it goes to the site home
  page, as Moodle does.
- The content now fills exactly the available space when the table of contents is shown, collapsed or hidden, and on
  small windows; the navigation panel between SCOs stays inside the window.
- A real pop-up window (teachers, or direct entry turned off) is left untouched by the player.
- Activities set to the pop-up display mode by hand are shown full screen like the rest.
- Activity names containing `&` are displayed correctly in the top bar.
- The “Hide the table of contents” setting also hides the drop-down table of contents.

### Changed
- Direct entry checks the `mod/scorm:skipview` capability and the open and close dates, exactly as Moodle core does.
- Accessibility: the top bar is a `header` with the activity name as a heading, visible focus and a 40 px exit button.
- Uses `100dvh` on mobile devices.
- Maturity: stable.

## [0.2.0] - 2026-10-10

### Added
- Setting “New-window activities in the same tab” (on by default): activities configured to open in a new window are
  shown full screen in the current tab, with the top bar and the **Exit activity** button. No more blocked pop-ups.
- Neutral top bar colours (dark grey and white) that suit any theme.
- PHPUnit and Behat tests and continuous integration on Moodle 5.1, 5.2 and 5.3.

## [0.1.0] - 2026-10-03

- Initial prototype (not published): direct entry, full-screen player on the same page with a top bar and an
  **Exit activity** button, and site settings.
