# ADR 0004: UUIDv7 primary identifiers

Status: accepted.
Implementation: complete.

Users and domain resources use UUIDv7 primary keys, including compatible polymorphic relations. HTTP clients treat every identifier as an opaque string and must not infer time or ordering from it.

Separate internal integers and public UUIDs were rejected because the unpublished schema did not justify two identifiers and their mappings. Random UUIDs were rejected in favor of UUIDv7 index locality. Changing this decision requires a migration and a new ADR.
