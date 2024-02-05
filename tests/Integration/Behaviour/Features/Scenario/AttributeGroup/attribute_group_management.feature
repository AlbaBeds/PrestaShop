# ./vendor/bin/behat -c tests/Integration/Behaviour/behat.yml -s attribute_group
@restore-all-tables-before-feature
@attribute-management
Feature: Attribute group management
  PrestaShop allows BO users to manage product attribute groups
  As a BO user
  I must be able to create, edit and delete product features

  Background:
    Given shop "shop1" with name "test_shop" exists
    And language "en" with locale "en-US" exists
    And language "fr" with locale "fr-FR" exists
    And language with iso code "en" is the default one
    And I create attribute group "attributeGroup1" with specified properties:
      | name[en-US]        | Color          |
      | name[fr-FR]        | Couleur        |
      | public_name[en-US] | Public Color   |
      | public_name[fr-FR] | Public couleur |
      | type               | color          |
    And I create attribute group "attributeGroup2" with specified properties:
      | name[en-US]        | Color2          |
      | name[fr-FR]        | Couleur2        |
      | public_name[en-US] | Public Color 2   |
      | public_name[fr-FR] | Public couleur 2 |
      | type               | color          |

  Scenario: Adding new attribute
    And I create attribute "attribute1" with specified properties:
      | attribute_group | attributeGroup1 |
      | name[en-US]    | Color           |
      | name[fr-FR]    | Couleur         |
      | color           | #44DB6A         |
    Then attribute "attribute1" should have the following properties:
      | attribute_group | attributeGroup1 |
      | name[en-US]    | Color           |
      | name[fr-FR]    | Couleur         |
      | color           | #44DB6A         |
  Scenario: Editing attribute
    When I edit attribute "attribute1" with specified properties:
      | attribute_group | attributeGroup2 |
      | name[en-US]    | Colores         |
      | name[fr-FR]    | Couleures       |
      | color           | #44DB6B         |
    Then attribute "attribute1" should have the following properties:
      | attribute_group | attributeGroup2 |
      | name[en-US]    | Colores         |
      | name[fr-FR]    | Couleures       |
      | color           | #44DB6B         |

  Scenario: Deleting attribute
    When I delete attribute "attribute1"
    Then attribute "attribute1" should be deleted
