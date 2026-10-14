# Inbound roadmap (not implemented)

A future inbound capability will use a persistent `CiviNeoNotifyInboundEvent`
inbox keyed by the transport and remote event ID, a transport-scoped identity
mapping, and a fixed command registry. Commands will require a known identity,
an allowed origin, a permission profile, a data scope, and a permitted domain
transition.

Writing commands will produce an origin-and-sender-bound confirmation token
before an APIv4 command handler executes. There will be no generic chat- or
network-to-APIv4 shell. A future adapter may poll or use a service-managed
listener, according to the remote protocol. Origin (`civicrm`,
`inbound_command`, `system`) and correlation IDs prevent feedback loops.
