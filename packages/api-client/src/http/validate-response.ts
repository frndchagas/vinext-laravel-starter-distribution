import type { z } from "zod";

import { responseContracts } from "../generated/response-contracts";

type ContractSchema = z.ZodType;

function formatIssues(error: z.ZodError): string {
  return error.issues
    .map((issue) => `${issue.path.join(".") || "<root>"}: ${issue.message}`)
    .join("; ");
}

export function validateContractResponse(
  method: string,
  url: string,
  status: number,
  data: unknown,
): void {
  const path = new URL(url, "http://contract.local").pathname;
  const contract = responseContracts.find(
    (candidate) => candidate.method === method && candidate.path.test(path),
  );

  if (contract === undefined) {
    throw new Error(`No HTTP response contract for ${method} ${path}.`);
  }

  const schema = (contract.responses as Record<number, ContractSchema>)[status];

  if (schema === undefined) {
    throw new Error(
      `${contract.operationId} returned undocumented status ${status} for ${method} ${path}.`,
    );
  }

  const result = schema.safeParse(data);

  if (!result.success) {
    throw new Error(
      `${contract.operationId} returned an invalid ${status} response for ${method} ${path}: ${formatIssues(result.error)}`,
    );
  }
}
