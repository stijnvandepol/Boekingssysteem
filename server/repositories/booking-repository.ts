import { BookingStatus, Prisma } from "@prisma/client";
import { prisma } from "@/lib/db";

export const bookingRepository = {
  async groupedCountsForRange(start: Date, end: Date) {
    const grouped = await prisma.booking.groupBy({
      by: ["availabilityId", "slotDateTime"],
      where: {
        slotDateTime: {
          gte: start,
          lt: end
        },
        status: BookingStatus.CONFIRMED
      },
      _count: {
        _all: true
      }
    });

    const map = new Map<string, number>();
    for (const row of grouped) {
      map.set(`${row.availabilityId}:${row.slotDateTime.toISOString()}`, row._count._all);
    }

    return map;
  },

  countConfirmedBySlot(availabilityId: string, slotDateTime: Date, tx: Prisma.TransactionClient) {
    return tx.booking.count({
      where: {
        availabilityId,
        slotDateTime,
        status: BookingStatus.CONFIRMED
      }
    });
  },

  create(data: Prisma.BookingUncheckedCreateInput, tx: Prisma.TransactionClient) {
    return tx.booking.create({ data });
  },

  listAll() {
    return prisma.booking.findMany({
      orderBy: { slotDateTime: "asc" }
    });
  },

  cancelById(id: string) {
    return prisma.booking.update({
      where: { id },
      data: { status: BookingStatus.CANCELED }
    });
  },

  cancelByToken(cancellationToken: string) {
    return prisma.booking.update({
      where: { cancellationToken },
      data: { status: BookingStatus.CANCELED }
    });
  }
};
