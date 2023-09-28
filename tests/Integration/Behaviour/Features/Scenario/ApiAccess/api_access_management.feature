# ./vendor/bin/behat -c tests/Integration/Behaviour/behat.yml -s api-access --tags api-access-management
@restore-api-access-before-feature
@api-access-management
Feature: Api Access Management
  PrestaShop provides an API to manage and export BO data
  As a API user
  I must be able to create, save and edit api access

  Scenario: Create a simple api access and edit it
    When I create an api access "AA-1" with following properties:
      | clientName   | Thomas               |
      | apiClientId  | test-id              |
      | enabled      | true                 |
      | description  | a simple description |
    Then api access "AA-1" should have the following properties:
      | clientName   | Thomas               |
      | apiClientId  | test-id              |
      | enabled      | true                 |
      | description  | a simple description |
    When I edit api access "AA-1" with the following values:
      | clientName   | Toto                 |
      | apiClientId  | test-id-toto         |
      | enabled      | false                |
      | description  | another description  |
    Then api access "AA-1" should have the following properties:
      | clientName   | Toto                 |
      | apiClientId  | test-id-toto         |
      | enabled      | false                |
      | description  | another description  |
    When I edit api access "AA-1" with the following values:
    # Just a quick edition to show partial update is possible
      | apiClientId  | test-id-toto-2       |
    Then api access "AA-1" should have the following properties:
      | apiClientId  | test-id-toto-2       |


  Scenario: Create an api access with non unique properties:
    When I create an api access "AA-2" with following properties:
      | clientName   | Thomas2              |
      | apiClientId  | test-id-2            |
      | enabled      | true                 |
      | description  | a simple description |
    When I create an api access "AA-3" with following properties:
      | clientName   | Thomas3              |
      | apiClientId  | test-id-2            |
      | enabled      | true                 |
      | description  | a simple description |
    Then I should get an error that clientId is not unique
    When I create an api access "AA-4" with following properties:
      | clientName   | Thomas2              |
      | apiClientId  | test-id-3            |
      | enabled      | true                 |
      | description  | a simple description |
    Then I should get an error that clientName is not unique

  Scenario: Create an api access with invalid properties:
    When I create an api access "AA-1" with following properties:
      | clientName   |                      |
      | apiClientId  | test-id-1              |
      | enabled      | true                 |
      | description  | a simple description |
    Then I should get an error that clientName is invalid
    When I create an api access "AA-2" with following properties:
      | clientName   | Thomas-1               |
      | apiClientId  |                      |
      | enabled      | true                 |
      | description  | a simple description |
    Then I should get an error that apiClientId is invalid
    When I create an api access "AA-4" with following properties:
      | clientName   | Thomas-5             |
      | apiClientId  | test-id-5            |
      | enabled      | true                 |
      | description  |                      |
    Then I should get an error that description is invalid
    When I create an api access "AA-4" with following properties:
      | clientName   | Thomas-5             |
      | apiClientId  | test-id-5            |
      | enabled      | true                 |
      | description  |                      |
    Then I should get an error that description is invalid

  Scenario: Create api access with values over max length:
    When I create an api access "AA-6" with a large value in apiClientId:
      | clientName   | Thomas-6             |
      | apiClientId  | valueToBeGenerated   |
      | enabled      | true                 |
      | description  | test description     |
    Then I should get an error that apiClientId is too large
    When I create an api access "AA-7" with a large value in clientName:
      | clientName   | valueToBeGenerated   |
      | apiClientId  | test-client-id       |
      | enabled      | true                 |
      | description  | test description     |
    Then I should get an error that clientName is too large
    When I create an api access "AA-8" with a large value in description:
      | clientName   | Thomas-7             |
      | apiClientId  | test-client-id-2     |
      | enabled      | true                 |
      | description  | valueToBeGenerated   |
    Then I should get an error that description is too large

  Scenario: Edit api access with values over max length:
    When I create an api access "AA-9" with following properties:
      | clientName   | Thomas-8            |
      | apiClientId  | test-client-id-3    |
      | enabled      | true                |
      | description  | description         |
    When I edit api access "AA-9" with a large value in apiClientId:
    Then I should get an error that apiClientId is too large
    When I create an api access "AA-10" with following properties:
      | clientName   | Thomas-9            |
      | apiClientId  | test-client-id-4    |
      | enabled      | true                |
      | description  | description         |
    When I edit api access "AA-10" with a large value in clientName:
    Then I should get an error that clientName is too large
    When I create an api access "AA-11" with following properties:
      | clientName   | Thomas-10           |
      | apiClientId  | test-client-id-5    |
      | enabled      | true                |
      | description  | description         |
    When I edit api access "AA-11" with a large value in description:
    Then I should get an error that description is too large
