import bcrypt from "bcryptjs";
import { PrismaClient, UserRole } from "@prisma/client";

const prisma = new PrismaClient();

async function main() {
  const email = process.env.BARBER_BOOTSTRAP_EMAIL;
  const password = process.env.BARBER_BOOTSTRAP_PASSWORD;

  if (!email || !password) {
    console.log("Skipping barber bootstrap. Missing BARBER_BOOTSTRAP_EMAIL or BARBER_BOOTSTRAP_PASSWORD.");
    return;
  }

  const existing = await prisma.user.findUnique({ where: { email } });

  if (existing) {
    console.log("Bootstrap barber already exists.");
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

  console.log("Bootstrap barber created.");
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
