import { BookingStatus, UserRole } from "@prisma/client";
import { NextRequest, NextResponse } from "next/server";
import { getEnv } from "@/lib/env";
import { logger } from "@/server/logger";
import { requireApiRole } from "@/server/security/authorization";
import { assertValidCsrf } from "@/server/security/csrf";
import { enforceRateLimit, getClientIp } from "@/server/security/rate-limit";
import { bookingService } from "@/server/services/booking-service";
import { createBookingSchema } from "@/server/validators/booking";

export async function GET() {
  try {
    await requireApiRole([UserRole.ADMIN, UserRole.BARBER]);
    const bookings = await bookingService.listBookings();
    return NextResponse.json({ bookings });
  } catch (error) {
    if (error instanceof Error && error.message === "UNAUTHORIZED") {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    return NextResponse.json({ error: "Unable to load bookings" }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  try {
    const env = getEnv();
    const ip = getClientIp(request.headers.get("x-forwarded-for"));
    const rate = enforceRateLimit(`booking:create:${ip}`, env.RATE_LIMIT_MAX_REQUESTS, env.RATE_LIMIT_WINDOW_MS);

    if (!rate.allowed) {
      return NextResponse.json(
        {
          error: "Too many booking attempts. Please wait and retry."
        },
        {
          status: 429,
          headers: {
            "Retry-After": String(Math.ceil((rate.resetAt - Date.now()) / 1000))
          }
        }
      );
    }

    const body = (await request.json()) as Record<string, unknown>;
    const parsed = createBookingSchema.safeParse(body);

    if (!parsed.success) {
      return NextResponse.json({ error: parsed.error.flatten() }, { status: 400 });
    }

    assertValidCsrf(request, parsed.data.csrfToken);

    const booking = await bookingService.createBooking({
      slotDateTime: new Date(parsed.data.slotDateTime),
      serviceName: parsed.data.serviceName,
      customerName: parsed.data.customerName,
      email: parsed.data.email,
      notes: parsed.data.notes
    });

    logger.info({
      event: "booking.created",
      message: "Booking confirmed",
      metadata: {
        bookingId: booking.id,
        slotDateTime: booking.slotDateTime.toISOString(),
        status: BookingStatus.CONFIRMED
      }
    });

    return NextResponse.json(
      {
        bookingId: booking.id,
        slotDateTime: booking.slotDateTime.toISOString(),
        cancellationToken: booking.cancellationToken
      },
      { status: 201 }
    );
  } catch (error) {
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Booking failed"
      },
      { status: 400 }
    );
  }
}
