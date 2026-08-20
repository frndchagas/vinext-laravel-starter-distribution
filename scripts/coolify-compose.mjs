import { readFileSync, writeFileSync } from "node:fs";
import { resolve } from "node:path";

const root = resolve(import.meta.dirname, "..");
const sourcePath = resolve(root, "compose.production.yaml");
const outputPath = resolve(root, "compose.coolify.yaml");
const marker = "  migrate:\n    <<: *api-image\n";
const replacement = "  migrate:\n    exclude_from_hc: true\n    <<: *api-image\n";

const source = readFileSync(sourcePath, "utf8");

if (!source.includes(marker)) {
  throw new Error("Could not find the migrate service in compose.production.yaml.");
}

const generated = source.replace(marker, replacement);

if (process.argv.includes("--check")) {
  const current = readFileSync(outputPath, "utf8");

  if (current !== generated) {
    throw new Error("compose.coolify.yaml drifted; run bun run coolify:build.");
  }

  console.log("Coolify Compose is current.");
} else {
  writeFileSync(outputPath, generated);
  console.log("Generated compose.coolify.yaml.");
}
