import { UserRole } from "@prisma/client";
import { BookingsAgenda } from "@/components/dashboard/bookings-agenda";
import { requireRole } from "@/lib/auth";

export default async function AgendaPage() {
  await requireRole([UserRole.ADMIN, UserRole.BARBER]);

  return (
    <main className="container py-10 md:py-14">
      <BookingsAgenda />
    </main>
  );
}
