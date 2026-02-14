import { z } from "zod";

export const createBookingSchema = z.object({
  slotDateTime: z.string().datetime(),
  serviceName: z.string().min(2).max(80).optional(),
  customerName: z.string().min(2).max(80),
  email: z.string().email(),
  notes: z.string().max(500).optional(),
  csrfToken: z.string().min(16)
});

export const cancelBookingSchema = z.object({
  bookingId: z.string().min(1)
});
