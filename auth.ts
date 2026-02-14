import { PrismaAdapter } from "@next-auth/prisma-adapter";
import { UserRole } from "@prisma/client";
import bcrypt from "bcryptjs";
import type { NextAuthOptions } from "next-auth";
import CredentialsProvider from "next-auth/providers/credentials";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { getEnv } from "@/lib/env";
import { userRepository } from "@/server/repositories/user-repository";

const credentialsSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8)
});

export function getAuthOptions(): NextAuthOptions {
  const env = getEnv();

  return {
    adapter: PrismaAdapter(prisma),
    session: {
      strategy: "jwt"
    },
    secret: env.AUTH_SECRET,
    pages: {
      signIn: "/login"
    },
    providers: [
      CredentialsProvider({
        name: "Credentials",
        credentials: {
          email: { label: "Email", type: "email" },
          password: { label: "Password", type: "password" }
        },
        async authorize(rawCredentials) {
          const parsed = credentialsSchema.safeParse(rawCredentials);
          if (!parsed.success) {
            return null;
          }

          const user = await userRepository.findByEmail(parsed.data.email.toLowerCase());
          if (!user || !user.passwordHash) {
            return null;
          }

          const isValid = await bcrypt.compare(parsed.data.password, user.passwordHash);
          if (!isValid) {
            return null;
          }

          return {
            id: user.id,
            email: user.email,
            name: user.name,
            role: user.role
          };
        }
      })
    ],
    callbacks: {
      async jwt({ token, user }) {
        if (user) {
          token.id = user.id;
          token.role = (user.role as UserRole) ?? UserRole.CUSTOMER;
        }
        return token;
      },
      async session({ session, token }) {
        if (session.user) {
          session.user.id = token.id;
          session.user.role = token.role;
        }
        return session;
      }
    }
  };
}
