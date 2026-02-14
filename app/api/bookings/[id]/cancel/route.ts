import { UserRole } from "@prisma/client";
import { NextRequest, NextResponse } from "next/server";
import { logger } from "@/server/logger";
import { requireApiRole } from "@/server/security/authorization";
import { assertValidCsrf } from "@/server/security/csrf";
import { bookingService } from "@/server/services/booking-service";

type Params = {
  params: { id: string } | Promise<{ id: string }>;
};

export async function PATCH(request: NextRequest, context: Params) {
  try {
    const actor = await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
    const body = (await request.json()) as { csrfToken?: string };
    const { id } = await context.params;

    assertValidCsrf(request, body.csrfToken);

    const booking = await bookingService.cancelBookingById(id);

    logger.info({
      event: "booking.canceled",
      message: "Booking canceled by staff",
      metadata: { actorId: actor.id, bookingId: id }
    });

    return NextResponse.json({ booking });
  } catch (error) {
    if (error instanceof Error && error.message === "UNAUTHORIZED") {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    return NextResponse.json({ error: error instanceof Error ? error.message : "Unable to cancel booking" }, { status: 400 });
  }
}
