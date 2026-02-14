import { UserRole } from "@prisma/client";
import bcrypt from "bcryptjs";
import { prisma } from "@/lib/db";

async function main(): Promise<void> {
  const email = process.env.BARBER_BOOTSTRAP_EMAIL;
  const password = process.env.BARBER_BOOTSTRAP_PASSWORD;

  if (!email || !password) {
    console.log("Skipping seed. Set BARBER_BOOTSTRAP_EMAIL and BARBER_BOOTSTRAP_PASSWORD.");
    return;
  }

  const existing = await prisma.user.findUnique({ where: { email } });
  if (existing) {
    console.log("Barber user already exists.");
    return;
  }

  const passwordHash = await bcrypt.hash(password, 12);
  await prisma.user.create({
    data: {
      email,
      passwordHash,
      role: UserRole.BARBER,
      name: "Shop Owner"
    }
  });

  console.log("Barber user created.");
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
