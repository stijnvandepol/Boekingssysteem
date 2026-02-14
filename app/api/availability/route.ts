import { UserRole } from "@prisma/client";
import { addDays } from "date-fns";
import { NextRequest, NextResponse } from "next/server";
import { availabilityService } from "@/server/services/availability-service";
import { logger } from "@/server/logger";
import { availabilitySchema } from "@/server/validators/availability";
import { requireApiRole } from "@/server/security/authorization";
import { assertValidCsrf } from "@/server/security/csrf";

function parseIsoDate(value: string | null, fallback: Date): Date {
  if (!value) {
    return fallback;
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return fallback;
  }

  return date;
}

export async function GET(request: NextRequest) {
  const view = request.nextUrl.searchParams.get("view");

  if (view === "blocks") {
    try {
      await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
      const blocks = await availabilityService.listAvailabilityBlocks();
      return NextResponse.json({ blocks });
    } catch (error) {
      if (error instanceof Error && error.message === "UNAUTHORIZED") {
        return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
      }
      return NextResponse.json({ error: "Failed to fetch blocks." }, { status: 500 });
    }
  }

  const now = new Date();
  const from = parseIsoDate(request.nextUrl.searchParams.get("from"), now);
  const to = parseIsoDate(request.nextUrl.searchParams.get("to"), addDays(now, 14));

  const slots = await availabilityService.listSlots(from, to);
  const response = NextResponse.json({ slots });
  response.headers.set("Cache-Control", "public, s-maxage=30, stale-while-revalidate=60");
  return response;
}

export async function POST(request: NextRequest) {
  try {
    const actor = await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
    const body = (await request.json()) as Record<string, unknown>;

    assertValidCsrf(request, typeof body.csrfToken === "string" ? body.csrfToken : undefined);

    const parsed = availabilitySchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json({ error: parsed.error.flatten() }, { status: 400 });
    }

    const block = await availabilityService.createAvailability({
      startDateTime: new Date(parsed.data.startDateTime),
      endDateTime: new Date(parsed.data.endDateTime),
      slotDurationMinute: parsed.data.slotDurationMinute,
      capacity: parsed.data.capacity,
      isBlocked: parsed.data.isBlocked,
      note: parsed.data.note,
      createdById: actor.id
    });

    logger.info({
      event: "availability.created",
      message: "Availability block created",
      metadata: { actorId: actor.id, blockId: block.id }
    });

    return NextResponse.json({ block }, { status: 201 });
  } catch (error) {
    if (error instanceof Error && error.message === "UNAUTHORIZED") {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    return NextResponse.json({ error: error instanceof Error ? error.message : "Unable to create availability" }, { status: 400 });
  }
}
