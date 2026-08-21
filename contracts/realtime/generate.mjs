import { mkdirSync, rmSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";

import { DiagnosticSeverity, Parser, fromFile } from "@asyncapi/parser";

const workspace = fileURLToPath(new URL(".", import.meta.url));
const contractPath = fileURLToPath(new URL("./asyncapi.yaml", import.meta.url));
const outputPath = fileURLToPath(new URL("./generated", import.meta.url));

if (!outputPath.endsWith("/contracts/realtime/generated")) {
  throw new Error(`Refusing to clean unexpected generated path: ${outputPath}`);
}

rmSync(outputPath, { recursive: true, force: true });
mkdirSync(outputPath, { recursive: true });

const parser = new Parser();
const { document, diagnostics } = await fromFile(parser, contractPath).parse();
const errors = diagnostics.filter(({ severity }) => severity === DiagnosticSeverity.Error);

if (document === undefined || errors.length > 0) {
  throw new Error("AsyncAPI must be valid before conformance metadata can be generated.");
}

const parsed = document.json();
const message = parsed.components.messages.taskStatusChanged;
const channel = parsed.channels.taskStatus;
const required = new Set(message.payload.required ?? []);
const taskStates = message.payload.properties.state.enum;

function propertyType(name, schema) {
  if (name === "state") return "TaskState";
  if (schema.type === "string") return "string";
  if (schema.type === "integer" || schema.type === "number") return "number";
  if (schema.type === "boolean") return "boolean";

  throw new Error(`Unsupported realtime property type for ${name}: ${schema.type}`);
}

const properties = Object.entries(message.payload.properties).map(([name, schema]) => {
  const comment = schema.description ? `  /** ${schema.description} */\n` : "";
  const optional = required.has(name) ? "" : "?";
  return `${comment}  ${name}${optional}: ${propertyType(name, schema)};`;
});

writeFileSync(
  `${outputPath}/TaskState.ts`,
  `export type TaskState = ${taskStates.map((state) => JSON.stringify(state)).join(" | ")};\n`,
);
writeFileSync(
  `${outputPath}/TaskStatusChangedPayload.ts`,
  `import type { TaskState } from "./TaskState";\n\nexport interface TaskStatusChangedPayload {\n${properties.join("\n")}\n}\n`,
);

function removeParserMetadata(value) {
  if (Array.isArray(value)) return value.map(removeParserMetadata);

  if (value !== null && typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value)
        .filter(([key]) => !key.startsWith("x-parser-"))
        .map(([key, item]) => [key, removeParserMetadata(item)]),
    );
  }

  return value;
}

writeFileSync(
  new URL("./generated/index.ts", import.meta.url),
  'export type { TaskState } from "./TaskState";\nexport type { TaskStatusChangedPayload } from "./TaskStatusChangedPayload";\n',
);
writeFileSync(
  new URL("./generated/task-status-changed.contract.json", import.meta.url),
  `${JSON.stringify(
    {
      channel: channel.address,
      event: message.name,
      payload: removeParserMetadata(message.payload),
    },
    null,
    2,
  )}\n`,
);

const formatter = Bun.spawnSync(["bunx", "oxfmt", "--write", "generated"], {
  cwd: workspace,
  stdout: "inherit",
  stderr: "inherit",
});

if (formatter.exitCode !== 0) {
  throw new Error(`oxfmt exited with ${formatter.exitCode}.`);
}

console.log(`Generated realtime artifacts: ${outputPath}`);
