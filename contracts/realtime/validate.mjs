import { DiagnosticSeverity, Parser, fromFile } from "@asyncapi/parser";
import { fileURLToPath } from "node:url";

const contractPath = fileURLToPath(new URL("./asyncapi.yaml", import.meta.url));
const parser = new Parser();
const { document, diagnostics } = await fromFile(parser, contractPath).parse();
const errors = diagnostics.filter(({ severity }) => severity === DiagnosticSeverity.Error);

if (document === undefined || errors.length > 0) {
  for (const diagnostic of errors) {
    const location = diagnostic.path?.join(".") ?? "document";
    console.error(`${location}: ${diagnostic.message}`);
  }

  process.exitCode = 1;
} else {
  console.log(`AsyncAPI contract is valid: ${contractPath}`);
}
