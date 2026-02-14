import { UserRole } from "@prisma/client";
import { getServerSession } from "next-auth";
import { redirect } from "next/navigation";
import { getAuthOptions } from "@/auth";

export async function requireRole(allowedRoles: UserRole[]): Promise<void> {
  const session = await getServerSession(getAuthOptions());

  if (!session?.user || !allowedRoles.includes(session.user.role)) {
    redirect("/login");
  }
}

export async function getSessionUser() {
  const session = await getServerSession(getAuthOptions());
  return session?.user ?? null;
}
