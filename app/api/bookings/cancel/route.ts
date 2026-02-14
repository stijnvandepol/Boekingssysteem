import { NextRequest, NextResponse } from "next/server";
import { assertValidCsrf } from "@/server/security/csrf";
import { bookingService } from "@/server/services/booking-service";

export async function POST(request: NextRequest) {
  try {
    const body = (await request.json()) as { cancellationToken?: string; csrfToken?: string };

    assertValidCsrf(request, body.csrfToken);

    if (!body.cancellationToken) {
      return NextResponse.json({ error: "Missing cancellation token" }, { status: 400 });
    }

    const booking = await bookingService.cancelBookingByToken(body.cancellationToken);
    return NextResponse.json({ booking });
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Unable to cancel booking" }, { status: 400 });
  }
}
