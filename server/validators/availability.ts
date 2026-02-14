import { z } from "zod";

export const availabilitySchema = z
  .object({
    startDateTime: z.string().datetime(),
    endDateTime: z.string().datetime(),
    slotDurationMinute: z.number().int().min(15).max(120),
    capacity: z.number().int().min(0).max(6),
    isBlocked: z.boolean().default(false),
    note: z.string().max(300).optional()
  })
  .superRefine((input, ctx) => {
    const start = new Date(input.startDateTime).getTime();
    const end = new Date(input.endDateTime).getTime();

    if (start >= end) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ["endDateTime"],
        message: "End time must be after start time."
      });
      return;
    }

    const validDurations = [15, 30, 45, 60, 90, 120];
    if (!validDurations.includes(input.slotDurationMinute)) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ["slotDurationMinute"],
        message: "Invalid slot duration."
      });
    }

    if (!input.isBlocked && input.capacity < 1) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ["capacity"],
        message: "Capacity must be at least 1 for bookable blocks."
      });
    }
  });
