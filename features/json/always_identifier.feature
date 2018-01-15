Feature: allow resources in properties to always be serialized as identifiers
  In order to use a hypermedia API
  As a client software developer
  I need to be able to force to serialize only the identifier of the related resource

  @createSchema
  @dropSchema
  Scenario: Create a set of Dummy with the alwaysIdentifier attributes and check the JSON formatted response
    Given there are AlwaysIdentifierDummies
      When I add "Accept" header equal to "application/json"
      And I add "Content-Type" header equal to "application/json"
      And I send a "GET" request to "/always_identifier_dummies/2"
      Then the JSON should be equal to:
      """
      {
          "children": [
              "/always_identifier_dummies/1"
          ],
          "related": [
              {
                  "children": [],
                  "related": [],
                  "parent": null
              }
          ],
          "parent": "/always_identifier_dummies/1"
      }
      """
