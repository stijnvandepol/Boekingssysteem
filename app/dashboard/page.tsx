import { UserRole } from "@prisma/client";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { getServerSession } from "next-auth";
import { getAuthOptions } from "@/auth";
import { DashboardPanel } from "@/components/dashboard/dashboard-panel";
import { getCsrfCookieName } from "@/server/security/csrf";

export default async function DashboardPage() {
  const session = await getServerSession(getAuthOptions());

  if (!session?.user || ![UserRole.ADMIN, UserRole.BARBER].includes(session.user.role)) {
    redirect("/login");
  }

  const cookieStore = await cookies();
  const csrfToken = cookieStore.get(getCsrfCookieName())?.value ?? "";

  return (
    <main className="container py-10 md:py-14">
      <div className="mb-8">
        <h1 className="luxury-heading ornament text-4xl font-semibold md:text-5xl">Barber Dashboard</h1>
        <p className="mt-3 text-muted-foreground">Manage schedule, capacity, and live bookings.</p>
      </div>
      <DashboardPanel initialCsrfToken={csrfToken} />
    </main>
  );
}
