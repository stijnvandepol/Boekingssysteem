import crypto from "node:crypto";
import { prisma } from "@/lib/db";
import { bookingRepository } from "@/server/repositories/booking-repository";
import { availabilityRepository } from "@/server/repositories/availability-repository";
import { sanitizeOptionalText, sanitizePlainText } from "@/server/security/sanitize";

export const bookingService = {
  async createBooking(input: {
    slotDateTime: Date;
    serviceName?: string;
    customerName: string;
    email: string;
    notes?: string;
    customerId?: string;
  }) {
    return prisma.$transaction(async (tx) => {
      const lockedAvailability = await availabilityRepository.lockAvailabilityForSlot(input.slotDateTime, tx);

      if (!lockedAvailability) {
        throw new Error("No active availability for this slot.");
      }

      const slotMs = lockedAvailability.slotDurationMinute * 60_000;
      const startDiff = input.slotDateTime.getTime() - lockedAvailability.startDateTime.getTime();

      const isAligned = startDiff >= 0 && startDiff % slotMs === 0;
      const slotEndsAt = input.slotDateTime.getTime() + slotMs;

      if (!isAligned || slotEndsAt > lockedAvailability.endDateTime.getTime()) {
        throw new Error("Invalid slot for selected availability block.");
      }

      const confirmedCount = await bookingRepository.countConfirmedBySlot(
        lockedAvailability.id,
        input.slotDateTime,
        tx
      );

      if (confirmedCount >= lockedAvailability.capacity) {
        throw new Error("This slot is already fully booked.");
      }

      const booking = await bookingRepository.create(
        {
          availabilityId: lockedAvailability.id,
          slotDateTime: input.slotDateTime,
          durationMinute: lockedAvailability.slotDurationMinute,
          serviceName: sanitizeOptionalText(input.serviceName),
          customerName: sanitizePlainText(input.customerName),
          email: sanitizePlainText(input.email).toLowerCase(),
          notes: sanitizeOptionalText(input.notes),
          customerId: input.customerId,
          cancellationToken: crypto.randomBytes(24).toString("hex")
        },
        tx
      );

      return booking;
    });
  },

  async listBookings() {
    return bookingRepository.listAll();
  },

  async cancelBookingById(id: string) {
    return bookingRepository.cancelById(id);
  },

  async cancelBookingByToken(cancellationToken: string) {
    return bookingRepository.cancelByToken(cancellationToken);
  }
};
