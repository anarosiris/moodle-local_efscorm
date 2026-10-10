@local @local_efscorm
Feature: Direct SCORM player
  In order to get students straight into SCORM content
  As a site
  I need students to land on the player while teachers keep the activity page

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam       | Student  |
      | teacher1 | Terry     | Teacher  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name          | packagefilepath                                  | popup |
      | scorm    | C1     | Package       | mod/scorm/tests/packages/singlescobasic.zip     | 0     |
      | scorm    | C1     | Window package| mod/scorm/tests/packages/singlescobasic.zip     | 1     |

  Scenario: A student opening the activity lands on the player
    Given I am on the "Package" "scorm activity" page logged in as student1
    Then I should be on the SCORM player page
    And "Enter" "button" should not exist

  Scenario: A teacher keeps the activity page with the Enter button
    Given I am on the "Package" "scorm activity" page logged in as teacher1
    Then I should not be on the SCORM player page
    And "Enter" "button" should exist

  Scenario: A new-window activity opens in the same tab for the student
    Given I am on the "Window package" "scorm activity" page logged in as student1
    Then I should be on the SCORM player page
    And the SCORM player is in popup display mode

  Scenario: With the same-tab setting off, a new-window activity keeps its launcher page
    Given the following config values are set as admin:
      | samewindow | 0 | local_efscorm |
    And I am on the "Window package" "scorm activity" page logged in as student1
    Then I should not be on the SCORM player page

  Scenario: With direct entry off, the student sees the activity page
    Given the following config values are set as admin:
      | directentry | 0 | local_efscorm |
    And I am on the "Package" "scorm activity" page logged in as student1
    Then I should not be on the SCORM player page
    And "Enter" "button" should exist
