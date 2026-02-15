"use client";

import { addDays, addMinutes, format, parseISO } from "date-fns";
import { nl } from "date-fns/locale";
import { FormEvent, useEffect, useMemo, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

type SlotItem = {
  availabilityId: string;
  slotDateTime: string;
  remainingCapacity: number;
  totalCapacity: number;
};

type BookingResponse = {
  bookingId: string;
  slotDateTime: string;
  durationMinute: number;
};

type Props = {
  initialCsrfToken: string;
};

function translateBookingError(message: string) {
  const translations: Record<string, string> = {
    "No active availability for this slot.": "Er is geen actieve beschikbaarheid voor dit tijdslot.",
    "Invalid slot for selected availability block.": "Dit tijdslot is ongeldig voor het geselecteerde blok.",
    "This slot is already fully booked.": "Dit tijdslot is al volledig volgeboekt.",
    "Missing CSRF token.": "CSRF-token ontbreekt.",
    "Invalid CSRF token.": "CSRF-token is ongeldig.",
    Unauthorized: "Je bent niet gemachtigd om deze actie uit te voeren.",
    "Booking failed.": "Boeking mislukt.",
    "Boeking mislukt.": "Boeking mislukt."
  };

  return translations[message] ?? "Boeking mislukt. Probeer het opnieuw.";
}

function toCalendarUtc(date: Date) {
  return date.toISOString().replace(/[-:]/g, "").replace(/\.\d{3}/, "");
}

export function BookingFlow({ initialCsrfToken }: Props) {
  const [csrfToken, setCsrfToken] = useState(initialCsrfToken);
  const [loadingSlots, setLoadingSlots] = useState(true);
  const [slots, setSlots] = useState<SlotItem[]>([]);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<SlotItem | null>(null);

  const [customerName, setCustomerName] = useState("");
  const [email, setEmail] = useState("");
  const [notes, setNotes] = useState("");

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<BookingResponse | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        if (!csrfToken) {
          const csrfResponse = await fetch("/api/csrf", { cache: "no-store" });
          const csrfJson = (await csrfResponse.json()) as { csrfToken: string };
          setCsrfToken(csrfJson.csrfToken);
        }

        const from = new Date();
        const to = addDays(from, 30);
        const url = `/api/availability?from=${encodeURIComponent(from.toISOString())}&to=${encodeURIComponent(to.toISOString())}`;

        const response = await fetch(url, { cache: "no-store" });
        if (!response.ok) {
          throw new Error("Beschikbaarheid laden is mislukt.");
        }

        const payload = (await response.json()) as { slots: SlotItem[] };
        setSlots(payload.slots);

        if (payload.slots.length > 0) {
          const firstDateKey = format(parseISO(payload.slots[0].slotDateTime), "yyyy-MM-dd");
          setSelectedDate(firstDateKey);
        }
      } catch (requestError) {
        setError(requestError instanceof Error ? translateBookingError(requestError.message) : "Planning laden is mislukt.");
      } finally {
        setLoadingSlots(false);
      }
    })();
  }, [csrfToken]);

  const slotsByDate = useMemo(() => {
    const map = new Map<string, SlotItem[]>();

    for (const slot of slots) {
      const key = format(parseISO(slot.slotDateTime), "yyyy-MM-dd");
      const items = map.get(key) ?? [];
      items.push(slot);
      map.set(key, items);
    }

    for (const [key, value] of map.entries()) {
      map.set(
        key,
        value.sort((a, b) => a.slotDateTime.localeCompare(b.slotDateTime))
      );
    }

    return map;
  }, [slots]);

  const availableDates = useMemo(() => Array.from(slotsByDate.keys()).sort(), [slotsByDate]);

  const daySlots = selectedDate ? slotsByDate.get(selectedDate) ?? [] : [];

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (!selectedSlot) {
      setError("Selecteer eerst een tijdslot.");
      return;
    }

    setSubmitting(true);

    try {
      const response = await fetch("/api/bookings", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "x-csrf-token": csrfToken
        },
        body: JSON.stringify({
          slotDateTime: selectedSlot.slotDateTime,
          customerName,
          email,
          notes,
          csrfToken
        })
      });

      const payload = (await response.json()) as BookingResponse & { error?: string };

      if (!response.ok) {
        throw new Error(payload.error ?? "Boeking mislukt.");
      }

      setSuccess(payload);
      setCustomerName("");
      setEmail("");
      setNotes("");
      setSelectedSlot(null);
    } catch (requestError) {
      setError(requestError instanceof Error ? translateBookingError(requestError.message) : "Boeking mislukt.");
    } finally {
      setSubmitting(false);
    }
  }

  if (success) {
    const booking = success;
    const eventStart = parseISO(booking.slotDateTime);
    const eventEnd = addMinutes(eventStart, booking.durationMinute);
    const eventTitle = "Afspraak bij Barber";
    const eventDetails = "Online boeking via Barber.";

    function downloadIcs() {
      const ics = [
        "BEGIN:VCALENDAR",
        "VERSION:2.0",
        "PRODID:-//Barber//Boeking//NL",
        "BEGIN:VEVENT",
        `UID:${booking.bookingId}@atelierbarber.local`,
        `DTSTAMP:${toCalendarUtc(new Date())}`,
        `DTSTART:${toCalendarUtc(eventStart)}`,
        `DTEND:${toCalendarUtc(eventEnd)}`,
        `SUMMARY:${eventTitle}`,
        `DESCRIPTION:${eventDetails}`,
        "END:VEVENT",
        "END:VCALENDAR"
      ].join("\r\n");

      const blob = new Blob([ics], { type: "text/calendar;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = "afspraak.ics";
      document.body.appendChild(anchor);
      anchor.click();
      document.body.removeChild(anchor);
      URL.revokeObjectURL(url);
    }

    function openGoogleCalendar() {
      const params = new URLSearchParams({
        action: "TEMPLATE",
        text: eventTitle,
        details: eventDetails,
        dates: `${toCalendarUtc(eventStart)}/${toCalendarUtc(eventEnd)}`
      });

      window.open(`https://calendar.google.com/calendar/render?${params.toString()}`, "_blank", "noopener,noreferrer");
    }

    return (
      <Card className="mx-auto max-w-2xl animate-fade-up">
        <CardHeader>
          <CardTitle className="luxury-heading text-3xl">Afspraak Bevestigd</CardTitle>
          <CardDescription>
            {format(parseISO(booking.slotDateTime), "EEEE d MMMM 'om' HH:mm", { locale: nl })} staat nu voor je gereserveerd.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted-foreground">Wil je deze afspraak toevoegen aan je agenda?</p>
          <div className="flex flex-wrap gap-2">
            <Button type="button" onClick={downloadIcs}>
              Download Agenda Bestand
            </Button>
            <Button type="button" variant="outline" onClick={openGoogleCalendar}>
              Open In Google Agenda
            </Button>
          </div>
          <Button onClick={() => setSuccess(null)}>Nog Een Afspraak Boeken</Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
      <Card className="animate-fade-up">
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">1. Kies Datum En Tijd</CardTitle>
          <CardDescription>Alleen realtime beschikbare slots worden getoond.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {loadingSlots ? <p className="text-sm text-muted-foreground">Planning laden...</p> : null}
          {!loadingSlots && availableDates.length === 0 ? <p className="text-sm text-muted-foreground">Nog geen slots beschikbaar.</p> : null}
          {availableDates.length > 0 ? (
            <div className="space-y-5">
              <div className="flex flex-wrap gap-2">
                {availableDates.map((dateKey) => (
                  <button
                    key={dateKey}
                    className={`rounded-full border px-3 py-1 text-sm transition ${
                      selectedDate === dateKey
                        ? "border-primary bg-primary/15 text-primary"
                        : "border-border bg-background/50 text-muted-foreground hover:border-primary/60"
                    }`}
                    onClick={() => {
                      setSelectedDate(dateKey);
                      setSelectedSlot(null);
                    }}
                    type="button"
                  >
                    {format(new Date(`${dateKey}T00:00:00`), "EEE d MMM", { locale: nl })}
                  </button>
                ))}
              </div>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                {daySlots.map((slot) => (
                  <button
                    key={slot.slotDateTime}
                    className={`rounded-md border p-2 text-left text-sm transition ${
                      selectedSlot?.slotDateTime === slot.slotDateTime
                        ? "border-primary bg-primary/15"
                        : "border-border bg-background/50 hover:border-primary/60"
                    }`}
                    onClick={() => setSelectedSlot(slot)}
                    type="button"
                  >
                    <p className="font-medium">{format(parseISO(slot.slotDateTime), "HH:mm")}</p>
                    <p className="text-xs text-muted-foreground">{slot.remainingCapacity} plekken vrij</p>
                  </button>
                ))}
              </div>
            </div>
          ) : null}
        </CardContent>
      </Card>

      <Card className="animate-fade-up [animation-delay:120ms]">
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">2. Jouw Gegevens</CardTitle>
          <CardDescription>Veilig reserveren met server-side validatie.</CardDescription>
        </CardHeader>
        <CardContent>
          <form className="space-y-4" onSubmit={onSubmit}>
            <div className="rounded-md border border-border/70 bg-background/60 p-3 text-sm">
              <p className="text-muted-foreground">Geselecteerde tijd</p>
              <p className="font-medium text-foreground">
                {selectedSlot
                  ? format(parseISO(selectedSlot.slotDateTime), "EEEE d MMM 'om' HH:mm", { locale: nl })
                  : "Kies links een tijdslot"}
              </p>
              {selectedSlot ? <Badge className="mt-2 w-fit">{selectedSlot.remainingCapacity} vrij</Badge> : null}
            </div>
            <div className="space-y-2">
              <Label htmlFor="customerName">Naam</Label>
              <Input
                id="customerName"
                value={customerName}
                onChange={(event) => setCustomerName(event.target.value)}
                required
                maxLength={80}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">E-mailadres</Label>
              <Input id="email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">Opmerking (optioneel)</Label>
              <Textarea id="notes" value={notes} onChange={(event) => setNotes(event.target.value)} maxLength={500} />
            </div>
            {error ? <p className="text-sm text-destructive">{error}</p> : null}
            <Button type="submit" className="w-full" disabled={submitting || !selectedSlot || !csrfToken}>
              {submitting ? "Bevestigen..." : "Boeking Bevestigen"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
