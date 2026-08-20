# ADR 0003: realtime contract

Status: accepted.
Implementation: complete.

AsyncAPI defines contracted Reverb channels and message payloads; OpenAPI remains limited to HTTP. Realtime messages report state that is already persisted, and consumers rebuild current state through the API after disconnection. Duplicates and loss are possible.

Hand-written TypeScript event types were rejected because they duplicate the neutral contract. Event sourcing was rejected because the starter needs change notifications rather than a durable event log. CI validates AsyncAPI syntax but does not detect drift between the document and PHP automatically.
