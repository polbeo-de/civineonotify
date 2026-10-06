# Inbound roadmap (not implemented)

Stage 2 will add a persistent `MatrixInboundEvent` inbox keyed uniquely by Matrix event ID, a `MatrixIdentity` mapping keyed by complete Matrix user ID, and a fixed command registry. Commands will require known identity, allowed room, permission profile, data scope, and a permitted domain transition.

Writing commands will produce a room-and-sender-bound confirmation token before an APIv4 command handler executes. There will be no generic Matrix-to-APIv4 shell. Polling is preferred initially for Uberspace; a service-managed listener may follow. Origin (`civicrm`, `matrix_command`, `system`) and correlation IDs already exist in outbound events to prevent feedback loops.
