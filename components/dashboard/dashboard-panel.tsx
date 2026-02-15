"use client";

import { addDays, addMinutes, eachDayOfInterval, format, parseISO, startOfWeek } from "date-fns";
import { nl } from "date-fns/locale";
import { signOut } from "next-auth/react";
import { useEffect, useMemo, useState } from "react";
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

type ParsedAvailabilityBlock = AvailabilityBlock & {
  start: Date;
  end: Date;
};

const AGENDA_WEEK_STARTS_ON = 1 as const;
const AGENDA_START_HOUR = 7;
const AGENDA_END_HOUR = 22;
const DEFAULT_AGENDA_SLOT_MINUTES = 30;

function getDayKey(date: Date) {
  return format(date, "yyyy-MM-dd");
}

function toLocalInputValue(date: Date) {
  return format(date, "yyyy-MM-dd'T'HH:mm");
}

function dateAtMinute(day: Date, minuteOfDay: number) {
  const date = new Date(day);
  date.setHours(0, minuteOfDay, 0, 0);
  return date;
}

function overlapsRange(slotStart: Date, slotEnd: Date, block: ParsedAvailabilityBlock) {
  return block.start < slotEnd && block.end > slotStart;
}

function bookingStatusLabel(status: BookingRow["status"]) {
  return status === "CONFIRMED" ? "Bevestigd" : "Geannuleerd";
}

function translateDashboardError(message: string) {
  const translations: Record<string, string> = {
    Unauthorized: "Je bent niet gemachtigd om deze actie uit te voeren.",
    UNAUTHORIZED: "Je bent niet gemachtigd om deze actie uit te voeren.",
    "Missing CSRF token.": "CSRF-token ontbreekt.",
    "Invalid CSRF token.": "CSRF-token is ongeldig.",
    "This time range overlaps an existing block.": "Deze tijdsperiode overlapt met een bestaand blok.",
    "Updated time range overlaps an existing block.": "Deze aangepaste tijdsperiode overlapt met een bestaand blok.",
    "Unable to save availability block.": "Beschikbaarheidsblok opslaan is mislukt.",
    "Unable to save one or more selected blocks.": "Eén of meer geselecteerde blokken konden niet worden opgeslagen.",
    "Unable to create selected blocks.": "Geselecteerde blokken aanmaken is mislukt.",
    "Failed to delete block.": "Blok verwijderen is mislukt.",
    "Failed to cancel booking.": "Boeking annuleren is mislukt."
  };

  return translations[message] ?? message;
}

function groupSlotsIntoRanges(slotStarts: Date[], slotMinutes: number) {
  if (slotStarts.length === 0) {
    return [] as Array<{ start: Date; end: Date }>;
  }

  const slotMs = slotMinutes * 60_000;
  const ranges: Array<{ start: Date; end: Date }> = [];
  let currentStart = slotStarts[0];
  let previous = slotStarts[0];

  for (let index = 1; index < slotStarts.length; index += 1) {
    const current = slotStarts[index];
    const isSameDay = getDayKey(previous) === getDayKey(current);
    const isContinuous = current.getTime() - previous.getTime() === slotMs;

    if (isSameDay && isContinuous) {
      previous = current;
      continue;
    }

    ranges.push({ start: currentStart, end: addMinutes(previous, slotMinutes) });
    currentStart = current;
    previous = current;
  }

  ranges.push({ start: currentStart, end: addMinutes(previous, slotMinutes) });
  return ranges;
}

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
  const [weekStart, setWeekStart] = useState(() => startOfWeek(new Date(), { weekStartsOn: AGENDA_WEEK_STARTS_ON }));
  const [mobileDayKey, setMobileDayKey] = useState(() => getDayKey(new Date()));
  const [agendaSlotMinutes, setAgendaSlotMinutes] = useState<number>(DEFAULT_AGENDA_SLOT_MINUTES);
  const [selectedSlotKeys, setSelectedSlotKeys] = useState<string[]>([]);

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

  const parsedBlocks = useMemo(() => {
    return blocks
      .map((block) => ({
        ...block,
        start: parseISO(block.startDateTime),
        end: parseISO(block.endDateTime)
      }))
      .sort((a, b) => a.start.getTime() - b.start.getTime());
  }, [blocks]);

  const weekDays = useMemo(() => {
    return eachDayOfInterval({
      start: weekStart,
      end: addDays(weekStart, 6)
    });
  }, [weekStart]);

  const agendaMinutes = useMemo(() => {
    const slots: number[] = [];
    for (let minute = AGENDA_START_HOUR * 60; minute < AGENDA_END_HOUR * 60; minute += agendaSlotMinutes) {
      slots.push(minute);
    }
    return slots;
  }, [agendaSlotMinutes]);
  const selectedSlotCount = selectedSlotKeys.length;
  const selectedSlotStarts = useMemo(() => {
    return selectedSlotKeys
      .map((value) => new Date(value))
      .filter((date) => !Number.isNaN(date.getTime()))
      .sort((a, b) => a.getTime() - b.getTime());
  }, [selectedSlotKeys]);
  const selectedRanges = useMemo(() => {
    return groupSlotsIntoRanges(selectedSlotStarts, agendaSlotMinutes);
  }, [selectedSlotStarts, agendaSlotMinutes]);
  const firstSelectedSlot = selectedSlotStarts[0];
  const lastSelectedSlot = selectedSlotStarts[selectedSlotStarts.length - 1];
  const mobileDayDate = useMemo(() => {
    return weekDays.find((day) => getDayKey(day) === mobileDayKey) ?? weekDays[0] ?? new Date();
  }, [weekDays, mobileDayKey]);
  const selectedSlotSet = useMemo(() => new Set(selectedSlotKeys), [selectedSlotKeys]);

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
        throw new Error("Dashboardgegevens laden is mislukt.");
      }

      const blocksPayload = (await blocksResponse.json()) as { blocks: AvailabilityBlock[] };
      const bookingsPayload = (await bookingsResponse.json()) as { bookings: BookingRow[] };

      setBlocks(blocksPayload.blocks);
      setBookings(bookingsPayload.bookings);
    } catch (requestError) {
      setError(requestError instanceof Error ? translateDashboardError(requestError.message) : "Dashboardgegevens laden is mislukt.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadData();
  }, []);

  useEffect(() => {
    if (weekDays.length === 0) {
      return;
    }

    const isCurrentDayVisible = weekDays.some((day) => getDayKey(day) === mobileDayKey);
    if (!isCurrentDayVisible) {
      setMobileDayKey(getDayKey(weekDays[0]));
    }
  }, [weekDays, mobileDayKey]);

  useEffect(() => {
    if (editingId || selectedSlotKeys.length === 0) {
      return;
    }

    if (selectedRanges.length === 1) {
      setStartDateTime(toLocalInputValue(selectedRanges[0].start));
      setEndDateTime(toLocalInputValue(selectedRanges[0].end));
    }
  }, [editingId, selectedSlotKeys.length, selectedRanges]);

  function resetForm() {
    setEditingId(null);
    setStartDateTime("");
    setEndDateTime("");
    setSlotDurationMinute(30);
    setCapacity(1);
    setIsBlocked(false);
    setNote("");
    setSelectedSlotKeys([]);
  }

  async function upsertBlockFromInputs() {
    if (!startDateTime || !endDateTime) {
      setError("Start en eindtijd zijn verplicht.");
      return;
    }

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
        throw new Error(result.error ?? "Beschikbaarheidsblok opslaan is mislukt.");
      }

      resetForm();
      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? translateDashboardError(requestError.message) : "Blok opslaan is mislukt.");
    } finally {
      setSavingBlock(false);
    }
  }

  function startEditing(block: AvailabilityBlock) {
    setEditingId(block.id);
    const blockStart = parseISO(block.startDateTime);
    setStartDateTime(toLocalInputValue(blockStart));
    setEndDateTime(toLocalInputValue(parseISO(block.endDateTime)));
    setSlotDurationMinute(block.slotDurationMinute);
    setCapacity(block.capacity);
    setIsBlocked(block.isBlocked);
    setNote(block.note ?? "");
    setWeekStart(startOfWeek(blockStart, { weekStartsOn: AGENDA_WEEK_STARTS_ON }));
    setMobileDayKey(getDayKey(blockStart));
    setSelectedSlotKeys([]);
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
        throw new Error(payload.error ?? "Blok verwijderen is mislukt.");
      }

      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? translateDashboardError(requestError.message) : "Blok verwijderen is mislukt.");
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
        throw new Error(payload.error ?? "Boeking annuleren is mislukt.");
      }

      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? translateDashboardError(requestError.message) : "Boeking annuleren is mislukt.");
    }
  }

  function findBlockForSlot(slotStart: Date) {
    const slotEnd = addMinutes(slotStart, agendaSlotMinutes);
    return parsedBlocks.find((block) => overlapsRange(slotStart, slotEnd, block));
  }

  function isSlotSelected(slotStart: Date) {
    return selectedSlotSet.has(slotStart.toISOString());
  }

  function handleAgendaSlotClick(slotStart: Date) {
    const existingBlock = findBlockForSlot(slotStart);
    if (existingBlock) {
      startEditing(existingBlock);
      return;
    }

    const key = slotStart.toISOString();
    setEditingId(null);
    setSelectedSlotKeys((current) => {
      if (current.includes(key)) {
        return current.filter((item) => item !== key);
      }
      return [...current, key].sort();
    });
  }

  async function createBlocksFromSelection() {
    if (selectedSlotKeys.length === 0) {
      setError("Selecteer minimaal één tijdslot in de agenda.");
      return;
    }

    setSavingBlock(true);
    setError(null);

    try {
      if (selectedRanges.length === 0) {
        throw new Error("Geen geldige selectie gevonden.");
      }

      const token = await ensureCsrfToken();

      for (const range of selectedRanges) {
        const response = await fetch("/api/availability", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "x-csrf-token": token
          },
          body: JSON.stringify({
            startDateTime: range.start.toISOString(),
            endDateTime: range.end.toISOString(),
            slotDurationMinute,
            capacity,
            isBlocked,
            note,
            csrfToken: token
          })
        });

        const payload = (await response.json()) as { error?: string };
        if (!response.ok) {
          throw new Error(payload.error ?? "Eén of meer geselecteerde blokken konden niet worden opgeslagen.");
        }
      }

      setSelectedSlotKeys([]);
      setStartDateTime("");
      setEndDateTime("");
      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? translateDashboardError(requestError.message) : "Geselecteerde blokken aanmaken is mislukt.");
    } finally {
      setSavingBlock(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-end">
        <Button variant="outline" onClick={() => signOut({ callbackUrl: "/" })}>
          Uitloggen
        </Button>
      </div>

      {error ? <p className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</p> : null}

      <div>
        <Card>
          <CardHeader>
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <CardTitle className="luxury-heading text-2xl">Visuele Agenda</CardTitle>
                <CardDescription>Selecteer zoveel tijdvakken als je wilt en maak daarna in bulk beschikbaarheid aan.</CardDescription>
              </div>
              <div className="flex items-center gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => setWeekStart((current) => addDays(current, -7))}>
                  Vorige Week
                </Button>
                <Button type="button" variant="outline" size="sm" onClick={() => setWeekStart((current) => addDays(current, 7))}>
                  Volgende Week
                </Button>
              </div>
            </div>
            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
              <span className="inline-flex items-center gap-1.5">
                <span className="h-3 w-3 rounded-sm bg-primary/30" />
                Beschikbaar
              </span>
              <span className="inline-flex items-center gap-1.5">
                <span className="h-3 w-3 rounded-sm bg-destructive/30" />
                Geblokkeerd
              </span>
              <span className="inline-flex items-center gap-1.5">
                <span className="h-3 w-3 rounded-sm border border-primary/80 bg-primary/15" />
                Geselecteerd Slot
              </span>
            </div>
            <div className="flex flex-wrap items-end gap-2">
              <div className="space-y-1">
                <Label htmlFor="agenda-step" className="text-xs text-muted-foreground">
                  Agenda-stap
                </Label>
                <select
                  id="agenda-step"
                  className="h-9 rounded-md border border-input bg-background px-2.5 text-sm"
                  value={agendaSlotMinutes}
                  onChange={(event) => {
                    const value = Number(event.target.value);
                    setAgendaSlotMinutes(value);
                    setSlotDurationMinute(value);
                    setSelectedSlotKeys([]);
                  }}
                >
                  <option value={30}>30 min</option>
                  <option value={60}>60 min</option>
                </select>
              </div>
              <p className="text-xs text-muted-foreground">Selecteer tijdvakken in de agenda, daarna opent het instellingenvenster.</p>
            </div>
            <p className="text-xs text-muted-foreground">
              {format(weekDays[0], "d MMM", { locale: nl })} - {format(weekDays[6], "d MMM yyyy", { locale: nl })} | Geselecteerd: {selectedSlotCount} tijdvak(ken)
              {selectedSlotCount > 0 ? ` (${((selectedSlotCount * agendaSlotMinutes) / 60).toFixed(1)}h)` : ""}
              {editingId ? " | Bewerkmodus actief (annuleer bewerken om in bulk te maken)." : ""}
            </p>
          </CardHeader>
          <CardContent>
            {loading ? <p className="text-sm text-muted-foreground">Agenda laden...</p> : null}
            {!loading ? (
              <>
                <div className="space-y-4 lg:hidden">
                  <div className="flex gap-2 overflow-x-auto pb-1">
                    {weekDays.map((day) => {
                      const dayKey = getDayKey(day);
                      const isActive = mobileDayKey === dayKey;

                      return (
                        <button
                          key={dayKey}
                          type="button"
                          onClick={() => setMobileDayKey(dayKey)}
                          className={`whitespace-nowrap rounded-full border px-3 py-1.5 text-xs transition ${
                            isActive
                              ? "border-primary bg-primary/15 text-primary"
                              : "border-border bg-background/50 text-muted-foreground hover:border-primary/60"
                          }`}
                        >
                          {format(day, "EEE d MMM", { locale: nl })}
                        </button>
                      );
                    })}
                  </div>
                  <div className="space-y-1">
                    {agendaMinutes.map((minute) => {
                      const slotStart = dateAtMinute(mobileDayDate, minute);
                      const existingBlock = findBlockForSlot(slotStart);
                      const isSelected = isSlotSelected(slotStart);
                      const statusLabel = existingBlock
                        ? existingBlock.isBlocked
                          ? "Geblokkeerd"
                          : `Beschikbaar (capaciteit ${existingBlock.capacity})`
                        : "Leeg";

                      return (
                        <button
                          key={`${getDayKey(mobileDayDate)}-${minute}`}
                          type="button"
                          onClick={() => handleAgendaSlotClick(slotStart)}
                          className={`flex w-full items-center justify-between rounded-md border px-3 py-2 text-left text-sm transition ${
                            existingBlock
                              ? existingBlock.isBlocked
                                ? "border-destructive/40 bg-destructive/15"
                                : "border-primary/40 bg-primary/15"
                              : "border-border/60 bg-background/40 hover:border-primary/50 hover:bg-primary/10"
                          } ${isSelected ? "ring-1 ring-primary/80" : ""}`}
                          title={
                            existingBlock
                              ? `${format(existingBlock.start, "HH:mm")} - ${format(existingBlock.end, "HH:mm")}`
                              : "Selecteer dit slot"
                          }
                        >
                          <span className="font-medium">{format(slotStart, "HH:mm")}</span>
                          <span className="text-xs text-muted-foreground">{statusLabel}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                <div className="hidden lg:block">
                  <div className="overflow-x-auto">
                    <div className="grid min-w-[980px] grid-cols-[78px_repeat(7,minmax(110px,1fr))] gap-px rounded-lg bg-border/40 p-px">
                      <div className="flex h-11 items-center justify-center bg-background/70 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">
                        Tijd
                      </div>
                      {weekDays.map((day) => (
                        <div key={getDayKey(day)} className="flex h-11 items-center justify-center bg-background/70 text-xs font-medium text-foreground">
                          {format(day, "EEE d MMM", { locale: nl })}
                        </div>
                      ))}

                      {agendaMinutes.map((minute) => (
                        <div key={`row-${minute}`} className="contents">
                          <div className="flex h-10 items-center justify-center bg-background/70 text-xs text-muted-foreground">
                            {format(dateAtMinute(weekStart, minute), "HH:mm")}
                          </div>
                          {weekDays.map((day) => {
                            const slotStart = dateAtMinute(day, minute);
                            const existingBlock = findBlockForSlot(slotStart);
                            const isSelected = isSlotSelected(slotStart);
                            const label = existingBlock ? (existingBlock.isBlocked ? "Geblokkeerd" : `Cap. ${existingBlock.capacity}`) : "";

                            return (
                              <button
                                key={`${getDayKey(day)}-${minute}`}
                                type="button"
                                onClick={() => handleAgendaSlotClick(slotStart)}
                                className={`h-10 border-0 text-[11px] transition ${
                                  existingBlock
                                    ? existingBlock.isBlocked
                                      ? "bg-destructive/15 text-destructive hover:bg-destructive/20"
                                      : "bg-primary/15 text-primary hover:bg-primary/20"
                                    : "bg-background/45 text-muted-foreground hover:bg-primary/10 hover:text-foreground"
                                } ${isSelected ? "ring-1 ring-inset ring-primary/80" : ""}`}
                                title={
                                  existingBlock
                                    ? `${format(existingBlock.start, "HH:mm")} - ${format(existingBlock.end, "HH:mm")} ${
                                        existingBlock.note ? `| ${existingBlock.note}` : ""
                                      }`
                                    : "Selecteer dit slot"
                                }
                              >
                                {label}
                              </button>
                            );
                          })}
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </>
            ) : null}
          </CardContent>
        </Card>
      </div>

      {selectedSlotCount > 0 || editingId ? (
        <div className="fixed inset-x-3 bottom-3 z-40 lg:inset-x-auto lg:right-6 lg:w-[420px]">
          <div className="rounded-xl border border-border/80 bg-card/95 p-4 shadow-2xl backdrop-blur">
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-sm font-semibold text-foreground">{editingId ? "Blok bewerken" : "Nieuwe beschikbaarheid"}</p>
                {!editingId ? (
                  <p className="text-xs text-muted-foreground">
                    {selectedSlotCount} tijdvak(ken) geselecteerd, wordt {selectedRanges.length} blok(ken)
                  </p>
                ) : null}
                {!editingId && firstSelectedSlot && lastSelectedSlot ? (
                  <p className="mt-1 text-xs text-muted-foreground">
                    {format(firstSelectedSlot, "EEE d MMM HH:mm", { locale: nl })} -{" "}
                    {format(addMinutes(lastSelectedSlot, agendaSlotMinutes), "EEE d MMM HH:mm", { locale: nl })}
                  </p>
                ) : null}
              </div>
              <Button type="button" size="sm" variant="ghost" onClick={resetForm}>
                Sluiten
              </Button>
            </div>

            {editingId ? (
              <div className="mt-4 grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="quick-start">Start</Label>
                  <Input id="quick-start" type="datetime-local" value={startDateTime} onChange={(event) => setStartDateTime(event.target.value)} required />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="quick-end">Einde</Label>
                  <Input id="quick-end" type="datetime-local" value={endDateTime} onChange={(event) => setEndDateTime(event.target.value)} required />
                </div>
              </div>
            ) : null}

            <div className="mt-4 grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="quick-duration">Slotduur</Label>
                <select
                  id="quick-duration"
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
              <div className="space-y-1.5">
                <Label htmlFor="quick-capacity">Capaciteit</Label>
                <Input
                  id="quick-capacity"
                  type="number"
                  min={0}
                  max={6}
                  value={capacity}
                  onChange={(event) => setCapacity(Number(event.target.value))}
                  required
                />
              </div>
            </div>

            <div className="mt-3 flex items-center gap-2">
              <input
                id="quick-blocked"
                type="checkbox"
                checked={isBlocked}
                onChange={(event) => setIsBlocked(event.target.checked)}
                className="h-4 w-4 rounded border-input"
              />
              <Label htmlFor="quick-blocked">Markeer als geblokkeerd (vrij/gesloten)</Label>
            </div>

            <div className="mt-3 space-y-1.5">
              <Label htmlFor="quick-note">Notitie</Label>
              <Textarea id="quick-note" value={note} onChange={(event) => setNote(event.target.value)} maxLength={300} rows={2} />
            </div>

            <div className="mt-4 flex gap-2">
              {editingId ? (
                <Button type="button" onClick={() => void upsertBlockFromInputs()} disabled={savingBlock}>
                  {savingBlock ? "Bijwerken..." : "Blok bijwerken"}
                </Button>
              ) : (
                <Button type="button" onClick={() => void createBlocksFromSelection()} disabled={savingBlock}>
                  {savingBlock ? "Opslaan..." : "Opslaan"}
                </Button>
              )}
              <Button type="button" variant="outline" onClick={resetForm} disabled={savingBlock}>
                {editingId ? "Annuleren" : "Selectie wissen"}
              </Button>
            </div>
          </div>
        </div>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">Blokkenoverzicht</CardTitle>
          <CardDescription>Gedetailleerde lijstweergave voor snel bewerken en verwijderen.</CardDescription>
        </CardHeader>
        <CardContent>
          {!loading && groupedBlocks.size === 0 ? <p className="text-sm text-muted-foreground">Nog geen blokken ingesteld.</p> : null}
          <div className="space-y-4">
            {Array.from(groupedBlocks.entries())
              .sort(([a], [b]) => a.localeCompare(b))
              .map(([day, dayBlocks]) => (
                <div key={day} className="rounded-lg border border-border/70 bg-background/40 p-4">
                  <p className="mb-3 text-sm font-medium text-primary">{format(new Date(`${day}T00:00:00`), "EEEE d MMM", { locale: nl })}</p>
                  <div className="space-y-2">
                    {dayBlocks.map((block) => (
                      <div key={block.id} className="flex flex-col gap-3 rounded-md border border-border/60 p-3 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-1">
                          <p className="text-sm font-medium">
                            {format(parseISO(block.startDateTime), "HH:mm")} - {format(parseISO(block.endDateTime), "HH:mm")}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {block.isBlocked ? "Geblokkeerd" : `${block.slotDurationMinute} min per slot | capaciteit ${block.capacity}`}
                          </p>
                          {block.note ? <p className="text-xs text-muted-foreground">{block.note}</p> : null}
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge variant={block.isBlocked ? "secondary" : "default"}>{block.isBlocked ? "Geblokkeerd" : "Beschikbaar"}</Badge>
                          <Button type="button" variant="outline" size="sm" onClick={() => startEditing(block)}>
                            Bewerken
                          </Button>
                          <Button type="button" variant="destructive" size="sm" onClick={() => deleteBlock(block.id)}>
                            Verwijderen
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

      <Card>
        <CardHeader>
          <CardTitle className="luxury-heading text-2xl">Boekingen</CardTitle>
          <CardDescription>Actuele afsprakenlijst met annuleringscontrole.</CardDescription>
        </CardHeader>
        <CardContent>
          {bookings.length === 0 ? <p className="text-sm text-muted-foreground">Nog geen boekingen.</p> : null}
          <div className="space-y-2">
            {bookings.map((booking) => (
              <div key={booking.id} className="flex flex-col gap-3 rounded-md border border-border/60 p-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <p className="text-sm font-medium">
                    {format(parseISO(booking.slotDateTime), "EEE d MMM HH:mm", { locale: nl })} - {booking.customerName}
                  </p>
                  {booking.serviceName ? <p className="text-xs text-muted-foreground">{booking.serviceName}</p> : null}
                  <p className="text-xs text-muted-foreground">{booking.email}</p>
                  {booking.notes ? <p className="text-xs text-muted-foreground">{booking.notes}</p> : null}
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={booking.status === "CONFIRMED" ? "default" : "secondary"}>{bookingStatusLabel(booking.status)}</Badge>
                  {booking.status === "CONFIRMED" ? (
                    <Button size="sm" variant="outline" onClick={() => cancelBooking(booking.id)}>
                      Boeking Annuleren
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
