@core @core_filepicker @_only_local @_file_upload
Feature: Delete files and folders from the file manager
  In order to clean the file manager contents
  As a user
  I need to delete files from file areas

  @javascript @_bug_phantomjs
  Scenario: Delete a file and a folder
    Given I log in as "admin"
    And I expand "My profile" node
    And I follow "My private files"
    And I upload "lib/tests/fixtures/empty.txt" file to "Files" filemanager
    And I create "Delete me" folder in "Files" filemanager
    And I press "Save changes"
    When I delete "empty.txt" from "Files" filemanager
    And I press "Save changes"
    Then I should not see "empty.txt"
    And I delete "Delete me" from "Files" filemanager
    And I press "Save changes"
    And I should not see "Delete me"
    And I press "Cancel"
