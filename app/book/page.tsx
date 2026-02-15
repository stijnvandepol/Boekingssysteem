import { cookies } from "next/headers";
import { BookingFlow } from "@/components/booking/booking-flow";
import { getCsrfCookieName } from "@/server/security/csrf";

export default async function BookPage() {
  const cookieStore = await cookies();
  const csrfToken = cookieStore.get(getCsrfCookieName())?.value ?? "";

  return (
    <main className="container py-10 md:py-16">
      <div className="mb-8 max-w-2xl">
        <h1 className="luxury-heading ornament text-4xl font-semibold md:text-5xl">Afspraak Boeken</h1>
        <p className="mt-4 text-muted-foreground">Kies een tijd, vul je gegevens in en reserveer je plek in minder dan 30 seconden.</p>
      </div>
      <BookingFlow initialCsrfToken={csrfToken} />
    </main>
  );
}
