# ADR 0003: realtime contract

Status: accepted.
Implementation: complete.

AsyncAPI defines contracted Reverb channels and message payloads; OpenAPI remains limited to HTTP. Realtime messages report state that is already persisted, and consumers rebuild current state through the API after disconnection. Duplicates and loss are possible. The official AsyncAPI parser validates and dereferences the document before a small repository generator emits TypeScript models. PHP conformance tests check the event name, channel and payload without adding validation to the production broadcast path.

Hand-written TypeScript event types were rejected because they duplicate the neutral contract. Event sourcing was rejected because the starter needs change notifications rather than a durable event log. The AsyncAPI CLI and Modelina were rejected for this single payload because they add old parser and Studio dependency trees with known high-severity advisories. The local generator supports the JSON Schema types used by contracted messages and fails when it encounters an unsupported type.
