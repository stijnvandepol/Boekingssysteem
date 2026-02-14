import { availabilityRepository } from "@/server/repositories/availability-repository";
import { bookingRepository } from "@/server/repositories/booking-repository";
import { sanitizeOptionalText } from "@/server/security/sanitize";

export type SlotResult = {
  availabilityId: string;
  slotDateTime: string;
  remainingCapacity: number;
  totalCapacity: number;
};

type AvailabilityInput = {
  startDateTime: Date;
  endDateTime: Date;
  slotDurationMinute: number;
  capacity: number;
  isBlocked: boolean;
  note?: string;
  createdById: string;
};

export const availabilityService = {
  async listSlots(start: Date, end: Date): Promise<SlotResult[]> {
    const availabilities = await availabilityRepository.findInRange(start, end);
    const bookedCountMap = await bookingRepository.groupedCountsForRange(start, end);

    const slots: SlotResult[] = [];

    for (const availability of availabilities) {
      if (availability.isBlocked || availability.capacity < 1) {
        continue;
      }

      const slotMs = availability.slotDurationMinute * 60_000;
      let cursor = availability.startDateTime.getTime();

      while (cursor + slotMs <= availability.endDateTime.getTime()) {
        const slotDate = new Date(cursor);

        if (slotDate >= start && slotDate < end) {
          const mapKey = `${availability.id}:${slotDate.toISOString()}`;
          const booked = bookedCountMap.get(mapKey) ?? 0;
          const remaining = availability.capacity - booked;

          if (remaining > 0) {
            slots.push({
              availabilityId: availability.id,
              slotDateTime: slotDate.toISOString(),
              remainingCapacity: remaining,
              totalCapacity: availability.capacity
            });
          }
        }

        cursor += slotMs;
      }
    }

    return slots.sort((a, b) => a.slotDateTime.localeCompare(b.slotDateTime));
  },

  async createAvailability(input: AvailabilityInput) {
    const existing = await availabilityRepository.findOverlapping(input.startDateTime, input.endDateTime);
    if (existing) {
      throw new Error("This time range overlaps an existing block.");
    }

    return availabilityRepository.create({
      startDateTime: input.startDateTime,
      endDateTime: input.endDateTime,
      slotDurationMinute: input.slotDurationMinute,
      capacity: input.capacity,
      isBlocked: input.isBlocked,
      note: sanitizeOptionalText(input.note),
      createdById: input.createdById
    });
  },

  async updateAvailability(id: string, input: AvailabilityInput) {
    const existing = await availabilityRepository.findOverlapping(input.startDateTime, input.endDateTime, id);
    if (existing) {
      throw new Error("Updated time range overlaps an existing block.");
    }

    return availabilityRepository.updateById(id, {
      startDateTime: input.startDateTime,
      endDateTime: input.endDateTime,
      slotDurationMinute: input.slotDurationMinute,
      capacity: input.capacity,
      isBlocked: input.isBlocked,
      note: sanitizeOptionalText(input.note)
    });
  },

  async listAvailabilityBlocks() {
    return availabilityRepository.listAll();
  },

  async deleteAvailabilityBlock(id: string) {
    return availabilityRepository.deleteById(id);
  }
};
