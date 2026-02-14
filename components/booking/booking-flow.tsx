"use client";

import { addDays, format, parseISO } from "date-fns";
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
  cancellationToken: string;
};

type Props = {
  initialCsrfToken: string;
};

export function BookingFlow({ initialCsrfToken }: Props) {
  const [csrfToken, setCsrfToken] = useState(initialCsrfToken);
  const [loadingSlots, setLoadingSlots] = useState(true);
  const [slots, setSlots] = useState<SlotItem[]>([]);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<SlotItem | null>(null);

  const [customerName, setCustomerName] = useState("");
  const [email, setEmail] = useState("");
  const [notes, setNotes] = useState("");
  const [serviceName, setServiceName] = useState("Classic Cut");

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
          throw new Error("Failed to load availability.");
        }

        const payload = (await response.json()) as { slots: SlotItem[] };
        setSlots(payload.slots);

        if (payload.slots.length > 0) {
          const firstDateKey = format(parseISO(payload.slots[0].slotDateTime), "yyyy-MM-dd");
          setSelectedDate(firstDateKey);
        }
      } catch (requestError) {
        setError(requestError instanceof Error ? requestError.message : "Failed to load schedule.");
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
      setError("Please select a time slot.");
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
          serviceName,
          customerName,
          email,
          notes,
          csrfToken
        })
      });

      const payload = (await response.json()) as BookingResponse & { error?: string };

      if (!response.ok) {
        throw new Error(payload.error ?? "Booking failed.");
      }

      setSuccess(payload);
      setCustomerName("");
      setEmail("");
      setNotes("");
      setServiceName("Classic Cut");
      setSelectedSlot(null);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Booking failed.");
    } finally {
      setSubmitting(false);
    }
  }

  if (success) {
    return (
      <Card className="mx-auto max-w-2xl animate-fade-up">
        <CardHeader>
          <CardTitle className="luxury-heading text-3xl">Appointment Confirmed</CardTitle>
          <CardDescription>
            {format(parseISO(success.slotDateTime), "EEEE, MMMM d 'at' HH:mm")} is now reserved.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Save this cancellation token for support: <span className="font-mono text-foreground">{success.cancellationToken}</span>
          </p>
          <Button onClick={() => setSuccess(null)}>Book Another Slot</Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
      <Card className="animate-fade-up">
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">1. Select Date & Time</CardTitle>
          <CardDescription>Only real-time available slots are shown.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {loadingSlots ? <p className="text-sm text-muted-foreground">Loading schedule...</p> : null}
          {!loadingSlots && availableDates.length === 0 ? <p className="text-sm text-muted-foreground">No slots available yet.</p> : null}
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
                    {format(new Date(`${dateKey}T00:00:00`), "EEE d MMM")}
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
                    <p className="text-xs text-muted-foreground">{slot.remainingCapacity} seats left</p>
                  </button>
                ))}
              </div>
            </div>
          ) : null}
        </CardContent>
      </Card>

      <Card className="animate-fade-up [animation-delay:120ms]">
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">2. Your Details</CardTitle>
          <CardDescription>Secure checkout with server-side validation.</CardDescription>
        </CardHeader>
        <CardContent>
          <form className="space-y-4" onSubmit={onSubmit}>
            <div className="rounded-md border border-border/70 bg-background/60 p-3 text-sm">
              <p className="text-muted-foreground">Selected slot</p>
              <p className="font-medium text-foreground">
                {selectedSlot
                  ? format(parseISO(selectedSlot.slotDateTime), "EEEE, MMM d 'at' HH:mm")
                  : "Pick a slot on the left"}
              </p>
              {selectedSlot ? <Badge className="mt-2 w-fit">{selectedSlot.remainingCapacity} remaining</Badge> : null}
            </div>
            <div className="space-y-2">
              <Label htmlFor="serviceName">Service</Label>
              <select
                id="serviceName"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={serviceName}
                onChange={(event) => setServiceName(event.target.value)}
              >
                <option value="Classic Cut">Classic Cut</option>
                <option value="Fade + Beard">Fade + Beard</option>
                <option value="Beard Trim">Beard Trim</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="customerName">Full Name</Label>
              <Input
                id="customerName"
                value={customerName}
                onChange={(event) => setCustomerName(event.target.value)}
                required
                maxLength={80}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">Notes (optional)</Label>
              <Textarea id="notes" value={notes} onChange={(event) => setNotes(event.target.value)} maxLength={500} />
            </div>
            {error ? <p className="text-sm text-destructive">{error}</p> : null}
            <Button type="submit" className="w-full" disabled={submitting || !selectedSlot || !csrfToken}>
              {submitting ? "Confirming..." : "Confirm Booking"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
