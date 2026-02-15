import crypto from "node:crypto";
import { NextRequest, NextResponse } from "next/server";
import { getCsrfCookieName } from "@/server/security/csrf";

export async function GET(request: NextRequest) {
  const token = crypto.randomBytes(32).toString("hex");
  const forwardedProto = request.headers.get("x-forwarded-proto");
  const isHttps = request.nextUrl.protocol === "https:" || forwardedProto === "https";

  const response = NextResponse.json({ csrfToken: token });
  response.cookies.set({
    name: getCsrfCookieName(),
    value: token,
    httpOnly: true,
    secure: isHttps,
    sameSite: "strict",
    path: "/"
  });

  return response;
}
