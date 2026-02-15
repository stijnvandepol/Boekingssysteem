"use client";

import { addDays, addMinutes, eachDayOfInterval, format, parseISO, startOfWeek, subDays } from "date-fns";
import { nl } from "date-fns/locale";
import { useEffect, useMemo, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

type BookingItem = {
  id: string;
  slotDateTime: string;
  durationMinute: number;
  customerName: string;
  email: string;
  status: "CONFIRMED" | "CANCELED";
  serviceName?: string | null;
  notes?: string | null;
};

type ViewMode = "week" | "day";

function dayKey(date: Date) {
  return format(date, "yyyy-MM-dd");
}

export function BookingsAgenda() {
  const [bookings, setBookings] = useState<BookingItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [viewMode, setViewMode] = useState<ViewMode>("week");
  const [focusDate, setFocusDate] = useState(new Date());

  async function loadBookings() {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch("/api/bookings", { cache: "no-store" });
      if (!response.ok) {
        throw new Error("Boekingen laden is mislukt.");
      }

      const payload = (await response.json()) as { bookings: BookingItem[] };
      setBookings(payload.bookings);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Boekingen laden is mislukt.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadBookings();
  }, []);

  const confirmedBookings = useMemo(() => {
    return bookings
      .filter((booking) => booking.status === "CONFIRMED")
      .sort((a, b) => a.slotDateTime.localeCompare(b.slotDateTime));
  }, [bookings]);

  const weekStart = useMemo(() => startOfWeek(focusDate, { weekStartsOn: 1 }), [focusDate]);
  const weekDays = useMemo(
    () =>
      eachDayOfInterval({
        start: weekStart,
        end: addDays(weekStart, 6)
      }),
    [weekStart]
  );

  const bookingsByDay = useMemo(() => {
    const map = new Map<string, BookingItem[]>();

    for (const booking of confirmedBookings) {
      const key = dayKey(parseISO(booking.slotDateTime));
      const items = map.get(key) ?? [];
      items.push(booking);
      map.set(key, items);
    }

    return map;
  }, [confirmedBookings]);

  const focusDayBookings = useMemo(() => {
    return bookingsByDay.get(dayKey(focusDate)) ?? [];
  }, [bookingsByDay, focusDate]);

  function shiftWindow(direction: "prev" | "next") {
    if (viewMode === "week") {
      setFocusDate((current) => (direction === "prev" ? subDays(current, 7) : addDays(current, 7)));
      return;
    }

    setFocusDate((current) => (direction === "prev" ? subDays(current, 1) : addDays(current, 1)));
  }

  return (
    <Card>
      <CardHeader className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <CardTitle className="luxury-heading text-3xl">Boekingsagenda</CardTitle>
            <CardDescription>Bekijk duidelijk wie wanneer aan de beurt is.</CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Button type="button" variant={viewMode === "day" ? "default" : "outline"} size="sm" onClick={() => setViewMode("day")}>
              Dag
            </Button>
            <Button type="button" variant={viewMode === "week" ? "default" : "outline"} size="sm" onClick={() => setViewMode("week")}>
              Week
            </Button>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => shiftWindow("prev")}>
            Vorige
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={() => setFocusDate(new Date())}>
            Vandaag
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={() => shiftWindow("next")}>
            Volgende
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={() => void loadBookings()}>
            Vernieuwen
          </Button>
        </div>

        <p className="text-sm text-muted-foreground">
          {viewMode === "week"
            ? `${format(weekDays[0], "EEEE d MMMM", { locale: nl })} t/m ${format(weekDays[6], "EEEE d MMMM yyyy", { locale: nl })}`
            : format(focusDate, "EEEE d MMMM yyyy", { locale: nl })}
        </p>
      </CardHeader>

      <CardContent>
        {error ? <p className="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</p> : null}
        {loading ? <p className="text-sm text-muted-foreground">Agenda laden...</p> : null}

        {!loading && viewMode === "week" ? (
          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
            {weekDays.map((day) => {
              const entries = bookingsByDay.get(dayKey(day)) ?? [];

              return (
                <div key={dayKey(day)} className="rounded-lg border border-border/70 bg-background/40 p-3">
                  <p className="text-xs font-medium uppercase tracking-[0.08em] text-primary">{format(day, "EEE d MMM", { locale: nl })}</p>
                  <div className="mt-3 space-y-2">
                    {entries.length === 0 ? <p className="text-xs text-muted-foreground">Geen afspraken.</p> : null}
                    {entries.map((booking) => {
                      const start = parseISO(booking.slotDateTime);
                      const end = addMinutes(start, booking.durationMinute);

                      return (
                        <div key={booking.id} className="rounded-md border border-border/60 bg-background/60 p-2">
                          <p className="text-xs font-semibold text-foreground">
                            {format(start, "HH:mm")} - {format(end, "HH:mm")}
                          </p>
                          <p className="text-sm font-medium">{booking.customerName}</p>
                          <p className="text-xs text-muted-foreground">{booking.email}</p>
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>
        ) : null}

        {!loading && viewMode === "day" ? (
          <div className="space-y-2">
            {focusDayBookings.length === 0 ? <p className="text-sm text-muted-foreground">Geen afspraken op deze dag.</p> : null}
            {focusDayBookings.map((booking) => {
              const start = parseISO(booking.slotDateTime);
              const end = addMinutes(start, booking.durationMinute);

              return (
                <div key={booking.id} className="rounded-lg border border-border/70 bg-background/40 p-3">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-semibold">
                      {format(start, "HH:mm")} - {format(end, "HH:mm")}
                    </p>
                    <Badge>Bevestigd</Badge>
                  </div>
                  <p className="mt-1 text-base font-medium">{booking.customerName}</p>
                  <p className="text-sm text-muted-foreground">{booking.email}</p>
                  {booking.notes ? <p className="mt-2 text-xs text-muted-foreground">{booking.notes}</p> : null}
                </div>
              );
            })}
          </div>
        ) : null}
      </CardContent>
    </Card>
  );
}
