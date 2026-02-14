"use client";

import { format, parseISO } from "date-fns";
import { signOut } from "next-auth/react";
import { FormEvent, useEffect, useMemo, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

type AvailabilityBlock = {
  id: string;
  startDateTime: string;
  endDateTime: string;
  slotDurationMinute: number;
  capacity: number;
  isBlocked: boolean;
  note?: string | null;
};

type BookingRow = {
  id: string;
  slotDateTime: string;
  serviceName?: string | null;
  customerName: string;
  email: string;
  status: "CONFIRMED" | "CANCELED";
  notes?: string | null;
};

type Props = {
  initialCsrfToken: string;
};

export function DashboardPanel({ initialCsrfToken }: Props) {
  const [csrfToken, setCsrfToken] = useState(initialCsrfToken);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [blocks, setBlocks] = useState<AvailabilityBlock[]>([]);
  const [bookings, setBookings] = useState<BookingRow[]>([]);

  const [editingId, setEditingId] = useState<string | null>(null);
  const [startDateTime, setStartDateTime] = useState("");
  const [endDateTime, setEndDateTime] = useState("");
  const [slotDurationMinute, setSlotDurationMinute] = useState(30);
  const [capacity, setCapacity] = useState(1);
  const [isBlocked, setIsBlocked] = useState(false);
  const [note, setNote] = useState("");
  const [savingBlock, setSavingBlock] = useState(false);

  const groupedBlocks = useMemo(() => {
    const map = new Map<string, AvailabilityBlock[]>();

    for (const block of blocks) {
      const key = format(parseISO(block.startDateTime), "yyyy-MM-dd");
      const existing = map.get(key) ?? [];
      existing.push(block);
      map.set(key, existing);
    }

    for (const [key, value] of map.entries()) {
      map.set(
        key,
        value.sort((a, b) => a.startDateTime.localeCompare(b.startDateTime))
      );
    }

    return map;
  }, [blocks]);

  async function ensureCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }

    const response = await fetch("/api/csrf", { cache: "no-store" });
    const payload = (await response.json()) as { csrfToken: string };
    setCsrfToken(payload.csrfToken);
    return payload.csrfToken;
  }

  async function loadData() {
    setLoading(true);
    setError(null);

    try {
      await ensureCsrfToken();

      const [blocksResponse, bookingsResponse] = await Promise.all([
        fetch("/api/availability?view=blocks", { cache: "no-store" }),
        fetch("/api/bookings", { cache: "no-store" })
      ]);

      if (!blocksResponse.ok || !bookingsResponse.ok) {
        throw new Error("Failed to load dashboard data.");
      }

      const blocksPayload = (await blocksResponse.json()) as { blocks: AvailabilityBlock[] };
      const bookingsPayload = (await bookingsResponse.json()) as { bookings: BookingRow[] };

      setBlocks(blocksPayload.blocks);
      setBookings(bookingsPayload.bookings);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Failed to load dashboard data.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadData();
  }, []);

  function resetForm() {
    setEditingId(null);
    setStartDateTime("");
    setEndDateTime("");
    setSlotDurationMinute(30);
    setCapacity(1);
    setIsBlocked(false);
    setNote("");
  }

  async function submitBlock(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSavingBlock(true);
    setError(null);

    try {
      const token = await ensureCsrfToken();
      const endpoint = editingId ? `/api/availability/${editingId}` : "/api/availability";
      const method = editingId ? "PATCH" : "POST";

      const payload = {
        startDateTime: new Date(startDateTime).toISOString(),
        endDateTime: new Date(endDateTime).toISOString(),
        slotDurationMinute,
        capacity,
        isBlocked,
        note,
        csrfToken: token
      };

      const response = await fetch(endpoint, {
        method,
        headers: {
          "Content-Type": "application/json",
          "x-csrf-token": token
        },
        body: JSON.stringify(payload)
      });

      const result = (await response.json()) as { error?: string };

      if (!response.ok) {
        throw new Error(result.error ?? "Unable to save availability block.");
      }

      resetForm();
      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Unable to save block.");
    } finally {
      setSavingBlock(false);
    }
  }

  function startEditing(block: AvailabilityBlock) {
    setEditingId(block.id);
    setStartDateTime(format(parseISO(block.startDateTime), "yyyy-MM-dd'T'HH:mm"));
    setEndDateTime(format(parseISO(block.endDateTime), "yyyy-MM-dd'T'HH:mm"));
    setSlotDurationMinute(block.slotDurationMinute);
    setCapacity(block.capacity);
    setIsBlocked(block.isBlocked);
    setNote(block.note ?? "");
  }

  async function deleteBlock(id: string) {
    try {
      const token = await ensureCsrfToken();
      const response = await fetch(`/api/availability/${id}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "x-csrf-token": token
        },
        body: JSON.stringify({ csrfToken: token })
      });

      const payload = (await response.json()) as { error?: string };
      if (!response.ok) {
        throw new Error(payload.error ?? "Failed to delete block.");
      }

      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Failed to delete block.");
    }
  }

  async function cancelBooking(id: string) {
    try {
      const token = await ensureCsrfToken();
      const response = await fetch(`/api/bookings/${id}/cancel`, {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          "x-csrf-token": token
        },
        body: JSON.stringify({ csrfToken: token })
      });

      const payload = (await response.json()) as { error?: string };
      if (!response.ok) {
        throw new Error(payload.error ?? "Failed to cancel booking.");
      }

      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Failed to cancel booking.");
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-end">
        <Button variant="outline" onClick={() => signOut({ callbackUrl: "/" })}>
          Sign out
        </Button>
      </div>

      {error ? <p className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</p> : null}

      <div className="grid gap-6 xl:grid-cols-[420px_1fr]">
        <Card>
          <CardHeader>
            <CardTitle className="luxury-heading text-2xl">Availability Block</CardTitle>
            <CardDescription>Create, edit, or block time ranges.</CardDescription>
          </CardHeader>
          <CardContent>
            <form className="space-y-4" onSubmit={submitBlock}>
              <div className="space-y-2">
                <Label htmlFor="start">Start</Label>
                <Input id="start" type="datetime-local" value={startDateTime} onChange={(event) => setStartDateTime(event.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="end">End</Label>
                <Input id="end" type="datetime-local" value={endDateTime} onChange={(event) => setEndDateTime(event.target.value)} required />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                  <Label htmlFor="duration">Slot duration</Label>
                  <select
                    id="duration"
                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    value={slotDurationMinute}
                    onChange={(event) => setSlotDurationMinute(Number(event.target.value))}
                  >
                    {[15, 30, 45, 60, 90, 120].map((value) => (
                      <option key={value} value={value}>
                        {value} min
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="capacity">Capacity</Label>
                  <Input
                    id="capacity"
                    type="number"
                    min={0}
                    max={6}
                    value={capacity}
                    onChange={(event) => setCapacity(Number(event.target.value))}
                    required
                  />
                </div>
              </div>
              <div className="flex items-center gap-2">
                <input
                  id="blocked"
                  type="checkbox"
                  checked={isBlocked}
                  onChange={(event) => setIsBlocked(event.target.checked)}
                  className="h-4 w-4 rounded border-input"
                />
                <Label htmlFor="blocked">Block this range (vacation/downtime)</Label>
              </div>
              <div className="space-y-2">
                <Label htmlFor="note">Note</Label>
                <Textarea id="note" value={note} onChange={(event) => setNote(event.target.value)} maxLength={300} />
              </div>
              <div className="flex gap-2">
                <Button type="submit" disabled={savingBlock}>
                  {savingBlock ? "Saving..." : editingId ? "Update block" : "Create block"}
                </Button>
                {editingId ? (
                  <Button type="button" variant="outline" onClick={resetForm}>
                    Cancel edit
                  </Button>
                ) : null}
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="luxury-heading text-2xl">Calendar Overview</CardTitle>
            <CardDescription>Visual schedule grouped by day.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? <p className="text-sm text-muted-foreground">Loading blocks...</p> : null}
            {!loading && groupedBlocks.size === 0 ? <p className="text-sm text-muted-foreground">No blocks configured yet.</p> : null}
            <div className="space-y-4">
              {Array.from(groupedBlocks.entries())
                .sort(([a], [b]) => a.localeCompare(b))
                .map(([day, dayBlocks]) => (
                  <div key={day} className="rounded-lg border border-border/70 bg-background/40 p-4">
                    <p className="mb-3 text-sm font-medium text-primary">{format(new Date(`${day}T00:00:00`), "EEEE d MMM")}</p>
                    <div className="space-y-2">
                      {dayBlocks.map((block) => (
                        <div key={block.id} className="flex flex-col gap-3 rounded-md border border-border/60 p-3 md:flex-row md:items-center md:justify-between">
                          <div className="space-y-1">
                            <p className="text-sm font-medium">
                              {format(parseISO(block.startDateTime), "HH:mm")} - {format(parseISO(block.endDateTime), "HH:mm")}
                            </p>
                            <p className="text-xs text-muted-foreground">
                              {block.isBlocked ? "Blocked" : `${block.slotDurationMinute} min slots | cap ${block.capacity}`}
                            </p>
                            {block.note ? <p className="text-xs text-muted-foreground">{block.note}</p> : null}
                          </div>
                          <div className="flex items-center gap-2">
                            <Badge variant={block.isBlocked ? "secondary" : "default"}>{block.isBlocked ? "Blocked" : "Bookable"}</Badge>
                            <Button type="button" variant="outline" size="sm" onClick={() => startEditing(block)}>
                              Edit
                            </Button>
                            <Button type="button" variant="destructive" size="sm" onClick={() => deleteBlock(block.id)}>
                              Delete
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">Bookings</CardTitle>
          <CardDescription>Live appointment list with cancellation control.</CardDescription>
        </CardHeader>
        <CardContent>
          {bookings.length === 0 ? <p className="text-sm text-muted-foreground">No bookings yet.</p> : null}
          <div className="space-y-2">
            {bookings.map((booking) => (
              <div key={booking.id} className="flex flex-col gap-3 rounded-md border border-border/60 p-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <p className="text-sm font-medium">
                    {format(parseISO(booking.slotDateTime), "EEE d MMM HH:mm")} - {booking.customerName}
                  </p>
                  {booking.serviceName ? <p className="text-xs text-muted-foreground">{booking.serviceName}</p> : null}
                  <p className="text-xs text-muted-foreground">{booking.email}</p>
                  {booking.notes ? <p className="text-xs text-muted-foreground">{booking.notes}</p> : null}
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={booking.status === "CONFIRMED" ? "default" : "secondary"}>{booking.status}</Badge>
                  {booking.status === "CONFIRMED" ? (
                    <Button size="sm" variant="outline" onClick={() => cancelBooking(booking.id)}>
                      Cancel booking
                    </Button>
                  ) : null}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
