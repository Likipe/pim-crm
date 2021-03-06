@javascript
Feature: Browse export profiles
  In order to view the list of export jobs that have been created
  As a user
  I need to be able to view a list of them

  Background:
    Given the following jobs:
      | connector            | alias            | code                  | label                       | type   |
      | Akeneo CSV Connector | product_export   | acme_product_export   | Product export for Acme.com | export |
      | Akeneo CSV Connector | attribute_export | acme_attribute_export | Attribute export            | export |
      | Akeneo CSV Connector | product_export   | foo_product_export    | Product export for foo      | export |
      | Akeneo CSV Connector | product_import   | acme_product_import   | Product import for Acme.com | import |
    Given I am logged in as "admin"

  Scenario: Successfully display the export jobs
    Given I am on the exports page
    Then the grid should contain 3 elements
    And I should see the columns Code, Label, Job, Connector and Status
    And I should see export profiles acme_product_export, acme_attribute_export and foo_product_export
    And I should not see export profile acme_product_import
    And the row "acme_product_export" should contain:
      | column    | value                |
      | connector | Akeneo CSV Connector |
