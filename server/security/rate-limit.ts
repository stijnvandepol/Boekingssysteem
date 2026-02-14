type RateState = {
  count: number;
  resetAt: number;
};

const store = new Map<string, RateState>();

export type RateLimitResult = {
  allowed: boolean;
  remaining: number;
  resetAt: number;
};

export function enforceRateLimit(key: string, limit: number, windowMs: number): RateLimitResult {
  const now = Date.now();
  const current = store.get(key);

  if (!current || current.resetAt <= now) {
    const resetAt = now + windowMs;
    store.set(key, { count: 1, resetAt });
    return { allowed: true, remaining: limit - 1, resetAt };
  }

  if (current.count >= limit) {
    return {
      allowed: false,
      remaining: 0,
      resetAt: current.resetAt
    };
  }

  current.count += 1;
  store.set(key, current);

  return {
    allowed: true,
    remaining: Math.max(0, limit - current.count),
    resetAt: current.resetAt
  };
}

export function getClientIp(forwardedForHeader: string | null): string {
  if (!forwardedForHeader) {
    return "unknown";
  }

  return forwardedForHeader.split(",")[0]?.trim() || "unknown";
}
