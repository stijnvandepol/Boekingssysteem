import type { NextRequest } from "next/server";

const CSRF_COOKIE = "csrf_token";

export function getCsrfCookieName(): string {
  return CSRF_COOKIE;
}

export function assertValidCsrf(request: NextRequest, bodyToken?: string): void {
  const cookieToken = request.cookies.get(CSRF_COOKIE)?.value;
  const headerToken = request.headers.get("x-csrf-token");

  if (!cookieToken || !headerToken || !bodyToken) {
    throw new Error("Missing CSRF token.");
  }

  if (cookieToken !== headerToken || cookieToken !== bodyToken) {
    throw new Error("Invalid CSRF token.");
  }
}
