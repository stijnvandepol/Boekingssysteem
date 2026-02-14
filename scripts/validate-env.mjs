const required = ["DATABASE_URL", "AUTH_SECRET", "NEXTAUTH_URL"];

for (const key of required) {
  if (!process.env[key] || process.env[key].trim().length === 0) {
    console.error(`Missing required environment variable: ${key}`);
    process.exit(1);
  }
}

if ((process.env.AUTH_SECRET ?? "").length < 32) {
  console.error("AUTH_SECRET must be at least 32 characters.");
  process.exit(1);
}

try {
  new URL(process.env.NEXTAUTH_URL);
} catch {
  console.error("NEXTAUTH_URL must be a valid URL.");
  process.exit(1);
}
