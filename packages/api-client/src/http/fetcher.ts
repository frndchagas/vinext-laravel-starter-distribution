const CSRF_COOKIE = "XSRF-TOKEN";
const MUTATING_METHODS = new Set(["POST", "PUT", "PATCH", "DELETE"]);

function readCookie(name: string): string | undefined {
  if (typeof document === "undefined") {
    return undefined;
  }

  const match = document.cookie
    .split("; ")
    .find((cookie) => cookie.startsWith(`${name}=`));

  return match ? decodeURIComponent(match.slice(name.length + 1)) : undefined;
}

async function ensureCsrfCookie(): Promise<void> {
  if (readCookie(CSRF_COOKIE)) {
    return;
  }

  await fetch("/sanctum/csrf-cookie", {
    credentials: "include",
    headers: { Accept: "application/json" },
  });
}

async function parseBody(response: Response): Promise<unknown> {
  if (response.status === 204) {
    return undefined;
  }

  const contentType = response.headers.get("content-type") ?? "";

  if (contentType.includes("json")) {
    return response.json();
  }

  return response.text();
}

export async function apiFetch<T>(url: string, options: RequestInit): Promise<T> {
  const method = (options.method ?? "GET").toUpperCase();

  if (MUTATING_METHODS.has(method)) {
    await ensureCsrfCookie();
  }

  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  const csrfToken = readCookie(CSRF_COOKIE);

  if (csrfToken && MUTATING_METHODS.has(method)) {
    headers.set("X-XSRF-TOKEN", csrfToken);
  }

  const response = await fetch(url, {
    ...options,
    credentials: "include",
    headers,
  });

  const data = await parseBody(response);

  return { data, status: response.status, headers: response.headers } as T;
}
