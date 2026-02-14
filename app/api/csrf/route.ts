import crypto from "node:crypto";
import { NextResponse } from "next/server";
import { getCsrfCookieName } from "@/server/security/csrf";

export async function GET() {
  const token = crypto.randomBytes(32).toString("hex");

  const response = NextResponse.json({ csrfToken: token });
  response.cookies.set({
    name: getCsrfCookieName(),
    value: token,
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "strict",
    path: "/"
  });

  return response;
}
