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
    <main className="container py-6 md:py-14">
      <div className="mb-6 flex flex-wrap items-start justify-between gap-4 md:mb-8 md:items-end">
        <div>
          <h1 className="luxury-heading ornament text-3xl font-semibold sm:text-4xl md:text-5xl">Barbier Beheer</h1>
          <p className="mt-2 text-sm text-muted-foreground md:mt-3 md:text-base">Beheer planning, capaciteit en actuele boekingen.</p>
        </div>
        <Button asChild variant="outline" className="w-full sm:w-auto">
          <Link href="/agenda">Open Boekingsagenda</Link>
        </Button>
      </div>
      <DashboardPanel initialCsrfToken={csrfToken} />
    </main>
  );
}
