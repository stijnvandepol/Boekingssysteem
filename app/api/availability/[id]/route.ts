import { UserRole } from "@prisma/client";
import { NextRequest, NextResponse } from "next/server";
import { availabilityService } from "@/server/services/availability-service";
import { logger } from "@/server/logger";
import { requireApiRole } from "@/server/security/authorization";
import { assertValidCsrf } from "@/server/security/csrf";
import { availabilitySchema } from "@/server/validators/availability";

type Params = {
  params: { id: string } | Promise<{ id: string }>;
};

export async function PATCH(request: NextRequest, context: Params) {
  try {
    const actor = await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
    const { id } = await context.params;
    const body = (await request.json()) as Record<string, unknown>;

    assertValidCsrf(request, typeof body.csrfToken === "string" ? body.csrfToken : undefined);

    const parsed = availabilitySchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json({ error: parsed.error.flatten() }, { status: 400 });
    }

    const block = await availabilityService.updateAvailability(id, {
      startDateTime: new Date(parsed.data.startDateTime),
      endDateTime: new Date(parsed.data.endDateTime),
      slotDurationMinute: parsed.data.slotDurationMinute,
      capacity: parsed.data.capacity,
      isBlocked: parsed.data.isBlocked,
      note: parsed.data.note,
      createdById: actor.id
    });

    logger.info({
      event: "availability.updated",
      message: "Availability block updated",
      metadata: { actorId: actor.id, blockId: id }
    });

    return NextResponse.json({ block });
  } catch (error) {
    if (error instanceof Error && error.message === "UNAUTHORIZED") {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    return NextResponse.json({ error: error instanceof Error ? error.message : "Unable to update availability" }, { status: 400 });
  }
}

export async function DELETE(request: NextRequest, context: Params) {
  try {
    const actor = await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
    const { id } = await context.params;
    const body = (await request.json()) as { csrfToken?: string };

    assertValidCsrf(request, body.csrfToken);

    await availabilityService.deleteAvailabilityBlock(id);

    logger.info({
      event: "availability.deleted",
      message: "Availability block deleted",
      metadata: { actorId: actor.id, blockId: id }
    });

    return NextResponse.json({ ok: true });
  } catch (error) {
    if (error instanceof Error && error.message === "UNAUTHORIZED") {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    return NextResponse.json({ error: error instanceof Error ? error.message : "Unable to delete availability" }, { status: 400 });
  }
}
