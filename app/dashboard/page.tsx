import { UserRole } from "@prisma/client";
import { cookies } from "next/headers";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getServerSession } from "next-auth";
import { getAuthOptions } from "@/auth";
import { DashboardPanel } from "@/components/dashboard/dashboard-panel";
import { Button } from "@/components/ui/button";
import { getCsrfCookieName } from "@/server/security/csrf";

export default async function DashboardPage() {
  const session = await getServerSession(getAuthOptions());
  const role = session?.user?.role;
  const isStaff = role === UserRole.ADMIN || role === UserRole.BARBER;

  if (!session?.user || !isStaff) {
    redirect("/login");
  }

  const cookieStore = await cookies();
  const csrfToken = cookieStore.get(getCsrfCookieName())?.value ?? "";

  return (
    <main className="container py-10 md:py-14">
      <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="luxury-heading ornament text-4xl font-semibold md:text-5xl">Barbier Beheer</h1>
          <p className="mt-3 text-muted-foreground">Beheer planning, capaciteit en actuele boekingen.</p>
        </div>
        <Button asChild variant="outline">
          <Link href="/agenda">Open Boekingsagenda</Link>
        </Button>
      </div>
      <DashboardPanel initialCsrfToken={csrfToken} />
    </main>
  );
}
