import { describe, expect, test } from "bun:test";

import { findSnapshotDrift } from "./contracts-check.mjs";

describe("findSnapshotDrift", () => {
  test("accepts identical generated artifacts", () => {
    const snapshot = new Map([
      ["contracts/http/openapi/openapi.yaml", "http"],
      ["contracts/realtime/generated/index.ts", "realtime"],
    ]);

    expect(findSnapshotDrift(snapshot, new Map(snapshot))).toEqual([]);
  });

  test("reports added, changed and removed artifacts in stable order", () => {
    const before = new Map([
      ["generated/removed.ts", "old"],
      ["generated/changed.ts", "old"],
    ]);
    const after = new Map([
      ["generated/added.ts", "new"],
      ["generated/changed.ts", "new"],
    ]);

    expect(findSnapshotDrift(before, after)).toEqual([
      "generated/added.ts",
      "generated/changed.ts",
      "generated/removed.ts",
    ]);
  });
});
