interface ProblemLike {
  detail?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

function asProblem(data: unknown): ProblemLike | undefined {
  if (data !== null && typeof data === "object") {
    return data;
  }

  return undefined;
}

export function validationErrors(data: unknown): Record<string, string[]> {
  return asProblem(data)?.errors ?? {};
}

export function problemDetail(data: unknown, fallback: string): string {
  const problem = asProblem(data);

  return problem?.detail ?? problem?.message ?? fallback;
}
