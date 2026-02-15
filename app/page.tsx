import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function HomePage() {
  return (
    <main className="container flex min-h-screen flex-col items-center justify-center py-12">
      <div className="mx-auto max-w-3xl text-center">
        <p className="mb-4 text-sm uppercase tracking-[0.22em] text-primary/80">Atelier Barber</p>
        <h1 className="luxury-heading ornament mb-8 text-5xl font-semibold text-foreground md:text-7xl">Strak Plannen, Zonder Gedoe.</h1>
        <p className="mx-auto mb-10 max-w-xl text-balance text-base text-muted-foreground md:text-lg">
          Boek in seconden met realtime beschikbaarheid. Geen bellen, geen onzekerheid, geen dubbele boekingen.
        </p>
        <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
          <Button asChild size="lg" className="min-w-[180px]">
            <Link href="/book">Afspraak Boeken</Link>
          </Button>
          <Button asChild variant="outline" size="lg" className="min-w-[180px] border-primary/50 text-primary hover:bg-primary/10">
            <Link href="/login">Inloggen</Link>
          </Button>
          <Button asChild variant="outline" size="lg" className="min-w-[180px] border-primary/50 text-primary hover:bg-primary/10">
            <Link href="/agenda">Boekingsagenda</Link>
          </Button>
        </div>
      </div>
    </main>
  );
}
