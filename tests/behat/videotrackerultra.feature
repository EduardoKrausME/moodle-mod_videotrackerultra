@mod @mod_videotrackerultra
Feature: Configure Video Tracker Ultra validation and completion
  In order to validate observable video consumption
  As a teacher
  I need to configure independent rules and Moodle completion

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Ultra course | ULTRA | 0 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | ULTRA | editingteacher |
      | student1 | ULTRA | student |

  Scenario: Teacher can configure validation rules and custom completion
    Given I log in as "teacher1"
    And I am on "Ultra course" course homepage with editing mode on
    When I add a "Video Tracker Ultra" to section "1" and I fill the form with:
      | Activity name | Rules video |
      | Minimum effectively watched percentage | 90 |
      | Minimum real playback time (seconds) | 300 |
      | Maximum forward seeks | 2 |
      | Maximum forward seek size (seconds) | 60 |
      | Required segments | 00:00-03:00\n08:30-11:00 |
      | Required segment coverage (%) | 95 |
      | Require a valid Video Tracker Ultra evaluation | 1 |
    And I press "Save and display"
    Then I should see "Your validation status"
    And I should see "Not started"
    And I should see "This activity validates observable player behaviour"

  Scenario: Student does not receive completion before observable requirements are satisfied
    Given the following "activities" exist:
      | activity | name | course | idnumber | minpercent | completion | completionvalid |
      | videotrackerultra | Completion video | ULTRA | ultra1 | 90 | 2 | 1 |
    And I log in as "student1"
    When I am on the "Completion video" "videotrackerultra activity" page
    Then I should see "Not started"
    And the activity "Completion video" should be incomplete


  Scenario: Accepted server-side evidence completes the custom completion rule
    Given the following "activities" exist:
      | activity | name | course | idnumber | minpercent | completion | completionvalid |
      | videotrackerultra | Validated video | ULTRA | ultra2 | 90 | 2 | 1 |
    And the Video Tracker Ultra activity "Validated video" has valid playback evidence for "student1"
    And I log in as "student1"
    When I am on "Ultra course" course homepage
    Then the activity "Validated video" should be complete
