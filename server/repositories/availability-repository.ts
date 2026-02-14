import { Prisma, PrismaClient } from "@prisma/client";
import { prisma } from "@/lib/db";

type Tx = Prisma.TransactionClient | PrismaClient;

export type LockedAvailability = {
  id: string;
  startDateTime: Date;
  endDateTime: Date;
  slotDurationMinute: number;
  capacity: number;
};

export const availabilityRepository = {
  findInRange(start: Date, end: Date) {
    return prisma.availability.findMany({
      where: {
        startDateTime: { lt: end },
        endDateTime: { gt: start }
      },
      orderBy: { startDateTime: "asc" }
    });
  },

  async findOverlapping(start: Date, end: Date, excludeId?: string, tx?: Tx) {
    const client = tx ?? prisma;
    return client.availability.findFirst({
      where: {
        id: excludeId ? { not: excludeId } : undefined,
        startDateTime: { lt: end },
        endDateTime: { gt: start }
      }
    });
  },

  create(data: Prisma.AvailabilityUncheckedCreateInput) {
    return prisma.availability.create({ data });
  },

  updateById(id: string, data: Prisma.AvailabilityUncheckedUpdateInput) {
    return prisma.availability.update({
      where: { id },
      data
    });
  },

  listAll() {
    return prisma.availability.findMany({
      orderBy: { startDateTime: "asc" }
    });
  },

  deleteById(id: string) {
    return prisma.availability.delete({ where: { id } });
  },

  async lockAvailabilityForSlot(slotDateTime: Date, tx: Prisma.TransactionClient): Promise<LockedAvailability | null> {
    const rows = await tx.$queryRaw<LockedAvailability[]>(Prisma.sql`
      SELECT
        id,
        start_datetime AS "startDateTime",
        end_datetime AS "endDateTime",
        slot_duration_minute AS "slotDurationMinute",
        capacity
      FROM availabilities
      WHERE start_datetime <= ${slotDateTime}
        AND end_datetime > ${slotDateTime}
        AND is_blocked = false
      ORDER BY start_datetime DESC
      LIMIT 1
      FOR UPDATE
    `);

    return rows[0] ?? null;
  }
};
