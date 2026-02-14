import { UserRole } from "@prisma/client";
import { getServerSession } from "next-auth";
import { getAuthOptions } from "@/auth";

export async function requireApiRole(allowedRoles: UserRole[]): Promise<{ id: string; role: UserRole }> {
  const session = await getServerSession(getAuthOptions());

  if (!session?.user || !allowedRoles.includes(session.user.role)) {
    throw new Error("UNAUTHORIZED");
  }

  return {
    id: session.user.id,
    role: session.user.role
  };
}
