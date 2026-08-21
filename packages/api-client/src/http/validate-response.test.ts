import { describe, expect, it } from "vitest";

import { validateContractResponse } from "./validate-response";

describe("validateContractResponse", () => {
  it("accepts a documented response through a parameterized route", () => {
    expect(() =>
      validateContractResponse("GET", "/api/v1/tasks/task-id?ignored=true", 200, {
        id: "task-id",
        input: "contract",
        output: null,
        state: "queued",
        version: 1,
        error_code: null,
        correlation_id: "correlation-id",
        started_at: null,
        finished_at: null,
        created_at: "2026-08-21T12:00:00Z",
      }),
    ).not.toThrow();
  });

  it("reports the operation and field when a payload drifts", () => {
    expect(() =>
      validateContractResponse("GET", "/api/v1/me", 200, {
        id: "user-id",
        name: "User",
        email: "user@example.com",
        email_verified: "yes",
        two_factor_enabled: false,
        two_factor_confirmed: false,
        roles: [],
        permissions: [],
      }),
    ).toThrow(/getMe returned an invalid 200 response.*email_verified/);
  });

  it("rejects an undocumented status", () => {
    expect(() => validateContractResponse("GET", "/api/v1/me", 418, {})).toThrow(
      /getMe returned undocumented status 418/,
    );
  });

  it("validates every field in a validation error map", () => {
    expect(() =>
      validateContractResponse("POST", "/api/v1/auth/register", 422, {
        type: "about:blank",
        title: "Unprocessable Content",
        status: 422,
        errors: { email: "not-an-array" },
      }),
    ).toThrow(/register returned an invalid 422 response.*errors.email/);

    expect(() =>
      validateContractResponse("POST", "/api/v1/auth/register", 422, {
        type: "about:blank",
        title: "Unprocessable Content",
        status: 422,
        errors: { email: ["The email field is invalid."] },
      }),
    ).not.toThrow();
  });

  it("accepts a documented rate-limit response", () => {
    expect(() =>
      validateContractResponse("POST", "/api/v1/auth/login", 429, {
        type: "about:blank",
        title: "Too Many Requests",
        status: 429,
        detail: "Too Many Attempts.",
      }),
    ).not.toThrow();
  });
});
